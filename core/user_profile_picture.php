<?php

function ensure_user_profile_pictures_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_profile_pictures (
            user_id BIGINT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function get_user_profile_picture(PDO $pdo, int $user_id): ?string
{
    ensure_user_profile_pictures_table($pdo);
    $stmt = $pdo->prepare('SELECT image_path FROM user_profile_pictures WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $image_path = $stmt->fetchColumn();

    if (!is_string($image_path) || preg_match('~^profiles/[a-f0-9]{32}\.(?:jpg|png|webp)$~', $image_path) !== 1) {
        return null;
    }

    return $image_path;
}

function store_user_profile_picture(PDO $pdo, int $user_id, array $upload): void
{
    ensure_user_profile_pictures_table($pdo);
    $upload_error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if (!is_int($upload_error) || $upload_error !== UPLOAD_ERR_OK) {
        $message = match ($upload_error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image is too large. Choose an image under 3 MB.',
            UPLOAD_ERR_NO_FILE => 'Choose a profile picture to upload.',
            default => 'The profile picture could not be uploaded. Please try again.',
        };
        throw new InvalidArgumentException($message);
    }

    $temporary_path = $upload['tmp_name'] ?? '';
    $file_size = $upload['size'] ?? 0;
    if (!is_string($temporary_path) || !is_uploaded_file($temporary_path) ||
        !is_int($file_size) || $file_size <= 0 || $file_size > 3 * 1024 * 1024) {
        throw new InvalidArgumentException('Choose a valid image under 3 MB.');
    }

    $file_info = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $file_info->file($temporary_path);
    $image_info = getimagesize($temporary_path);
    $image_types = [
        'image/jpeg' => ['extension' => 'jpg', 'type' => IMAGETYPE_JPEG],
        'image/png' => ['extension' => 'png', 'type' => IMAGETYPE_PNG],
        'image/webp' => ['extension' => 'webp', 'type' => IMAGETYPE_WEBP],
    ];
    if (!is_string($mime_type) || !isset($image_types[$mime_type]) ||
        !is_array($image_info) || ($image_info['mime'] ?? '') !== $mime_type ||
        ($image_info[2] ?? null) !== $image_types[$mime_type]['type'] ||
        $image_info[0] > 6000 || $image_info[1] > 6000) {
        throw new InvalidArgumentException('Use a valid JPG, PNG, or WEBP image no larger than 6000 × 6000 pixels.');
    }

    $upload_directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
    if (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, true) && !is_dir($upload_directory)) {
        throw new RuntimeException('Profile picture storage is unavailable. Please contact support.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $image_types[$mime_type]['extension'];
    $relative_path = 'profiles/' . $filename;
    $target_path = $upload_directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($temporary_path, $target_path)) {
        throw new RuntimeException('Profile picture could not be saved. Please try again.');
    }

    try {
        $stmt = $pdo->prepare('SELECT image_path FROM user_profile_pictures WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $user_id]);
        $previous_path = $stmt->fetchColumn();
        $save_stmt = $pdo->prepare("
            INSERT INTO user_profile_pictures (user_id, image_path)
            VALUES (:user_id, :image_path)
            ON DUPLICATE KEY UPDATE image_path = VALUES(image_path)
        ");
        $save_stmt->execute([':user_id' => $user_id, ':image_path' => $relative_path]);
    } catch (Throwable $e) {
        if (is_file($target_path)) {
            unlink($target_path);
        }
        throw $e;
    }

    if (is_string($previous_path)) {
        remove_managed_user_profile_picture_file($previous_path);
    }
}

function clear_user_profile_picture(PDO $pdo, int $user_id): void
{
    ensure_user_profile_pictures_table($pdo);
    $stmt = $pdo->prepare('SELECT image_path FROM user_profile_pictures WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $image_path = $stmt->fetchColumn();
    $delete_stmt = $pdo->prepare('DELETE FROM user_profile_pictures WHERE user_id = :user_id');
    $delete_stmt->execute([':user_id' => $user_id]);

    if (is_string($image_path)) {
        remove_managed_user_profile_picture_file($image_path);
    }
}

function remove_managed_user_profile_picture_file(string $image_path): void
{
    if (preg_match('~^profiles/[a-f0-9]{32}\.(?:jpg|png|webp)$~', $image_path) !== 1) {
        return;
    }

    $file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR
        . 'profiles' . DIRECTORY_SEPARATOR . basename($image_path);
    if (is_file($file_path) && !unlink($file_path)) {
        error_log('Could not remove old user profile picture: ' . $image_path);
    }
}
