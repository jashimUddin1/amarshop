<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/site_settings.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST is required.']);
    exit;
}

$csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
$expected_csrf = is_string($_SESSION['site_settings_csrf'] ?? null) ? $_SESSION['site_settings_csrf'] : '';
if ($expected_csrf === '' || !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Reload Settings and try again.']);
    exit;
}

$settings = [
    'site_title' => trim((string)($_POST['site_title'] ?? '')),
    'site_tagline' => trim((string)($_POST['site_tagline'] ?? '')),
    'contact_phone' => trim((string)($_POST['contact_phone'] ?? '')),
    'contact_email' => trim((string)($_POST['contact_email'] ?? '')),
    'contact_address' => trim((string)($_POST['contact_address'] ?? '')),
    'facebook_url' => trim((string)($_POST['facebook_url'] ?? '')),
    'instagram_url' => trim((string)($_POST['instagram_url'] ?? '')),
    'whatsapp_number' => trim((string)($_POST['whatsapp_number'] ?? '')),
];

if ($settings['site_title'] === '' || strlen($settings['site_title']) > 180 ||
    strlen($settings['site_tagline']) > 500 || strlen($settings['contact_phone']) > 60 ||
    strlen($settings['contact_address']) > 500 || strlen($settings['whatsapp_number']) > 40 ||
    ($settings['contact_email'] !== '' && !filter_var($settings['contact_email'], FILTER_VALIDATE_EMAIL))) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Check the website name and contact details. Email must be valid.']);
    exit;
}
foreach (['facebook_url', 'instagram_url'] as $url_key) {
    if ($settings[$url_key] !== '' && !filter_var($settings[$url_key], FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Enter valid full URLs for social media links.']);
        exit;
    }
}

$uploaded_files = [];
$upload_dir = __DIR__ . '/../../uploads';
$file_fields = ['site_logo', 'site_favicon'];
$allowed_mime_types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/x-icon' => 'ico',
    'image/vnd.microsoft.icon' => 'ico',
];

foreach ($file_fields as $field) {
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK ||
        !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) ||
        (int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Logo and favicon files must be valid images up to 5 MB.']);
        exit;
    }
    $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!is_string($mime_type) || !isset($allowed_mime_types[$mime_type])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Use JPG, PNG, WEBP, or ICO for the logo and favicon.']);
        exit;
    }
    $settings[$field] = bin2hex(random_bytes(16)) . '.' . $allowed_mime_types[$mime_type];
    $uploaded_files[$field] = [$file['tmp_name'], $settings[$field]];
}

try {
    ensure_site_settings_table($pdo);
    if ($uploaded_files && !is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        throw new RuntimeException('Upload directory could not be created.');
    }
    foreach ($uploaded_files as [$temporary_file, $file_name]) {
        if (!move_uploaded_file($temporary_file, $upload_dir . DIRECTORY_SEPARATOR . $file_name)) {
            throw new RuntimeException('Uploaded image could not be saved.');
        }
    }

    $pdo->beginTransaction();
    $exists_stmt = $pdo->prepare('SELECT setting_key FROM site_settings WHERE setting_key = :setting_key LIMIT 1 FOR UPDATE');
    $update_stmt = $pdo->prepare('UPDATE site_settings SET setting_value = :setting_value WHERE setting_key = :setting_key');
    $insert_stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)');
    foreach ($settings as $setting_key => $setting_value) {
        $exists_stmt->execute([':setting_key' => $setting_key]);
        if ($exists_stmt->fetchColumn()) {
            $update_stmt->execute([':setting_key' => $setting_key, ':setting_value' => $setting_value]);
        } else {
            $insert_stmt->execute([':setting_key' => $setting_key, ':setting_value' => $setting_value]);
        }
    }
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Website and contact settings saved successfully.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($uploaded_files as [, $file_name]) {
        $saved_path = $upload_dir . DIRECTORY_SEPARATOR . $file_name;
        if (is_file($saved_path)) {
            unlink($saved_path);
        }
    }
    error_log('Website settings update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Website settings could not be saved. Check database and uploads permissions.']);
}
