<?php
require_once 'auth/config.php';
require_once __DIR__ . '/core/site_settings.php';
require_admin();
ensure_site_settings_table($pdo);

$page_title = "Site Settings";
$current_page = "setting";
$smtp_settings_message = '';
$smtp_settings_message_type = '';
$smtp_csrf = $_SESSION['smtp_settings_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['smtp_settings_csrf'] = $smtp_csrf;
$site_settings_csrf = $_SESSION['site_settings_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['site_settings_csrf'] = $site_settings_csrf;
$can_manage_smtp = in_array($_SESSION['user_role'] ?? '', ['CEO', 'MD'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_smtp_settings') {
  $csrf = is_string($_POST['smtp_csrf'] ?? null) ? $_POST['smtp_csrf'] : '';
  if (!$can_manage_smtp) {
    http_response_code(403);
    exit('Only the CEO or MD can manage SMTP settings.');
  }
  if (!hash_equals($smtp_csrf, $csrf)) {
    http_response_code(400);
    exit('Invalid request token.');
  }

  $smtp_host = trim((string)($_POST['smtp_host'] ?? ''));
  $smtp_port = filter_var($_POST['smtp_port'] ?? null, FILTER_VALIDATE_INT);
  $smtp_encryption = is_string($_POST['smtp_encryption'] ?? null) ? $_POST['smtp_encryption'] : '';
  $smtp_user = trim((string)($_POST['smtp_user'] ?? ''));
  $smtp_pass = is_string($_POST['smtp_pass'] ?? null) ? $_POST['smtp_pass'] : '';
  $smtp_from = trim((string)($_POST['smtp_from'] ?? ''));
  $smtp_name = trim((string)($_POST['smtp_name'] ?? ''));
  $clear_password = isset($_POST['clear_smtp_pass']) && $_POST['clear_smtp_pass'] === '1';
  $valid_host = filter_var($smtp_host, FILTER_VALIDATE_IP)
    || filter_var($smtp_host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

  if (!$valid_host || $smtp_port === false || $smtp_port < 1 || $smtp_port > 65535 ||
      !in_array($smtp_encryption, ['tls', 'ssl', 'none'], true) ||
      !filter_var($smtp_user, FILTER_VALIDATE_EMAIL) ||
      !filter_var($smtp_from, FILTER_VALIDATE_EMAIL) ||
      $smtp_name === '' || strlen($smtp_name) > 120 || strlen($smtp_pass) > 2048) {
    $smtp_settings_message = 'Please enter valid SMTP host, port, encryption, email addresses, and sender name.';
    $smtp_settings_message_type = 'danger';
  } else {
    try {
      require_once __DIR__ . '/../core/email_verification.php';
      ensure_auth_settings_table($pdo);
      $existing_stmt = $pdo->query('SELECT smtp_pass FROM `auth` WHERE id = 1');
      $existing_password = $existing_stmt->fetchColumn();
      $encrypted_password = $smtp_pass !== ''
        ? encrypt_smtp_password($smtp_pass)
        : ($clear_password ? null : $existing_password);

      $update_stmt = $pdo->prepare("
        UPDATE `auth`
        SET smtp_host = :smtp_host, smtp_port = :smtp_port,
            smtp_encryption = :smtp_encryption, smtp_user = :smtp_user,
            smtp_pass = :smtp_pass, smtp_from = :smtp_from,
            smtp_name = :smtp_name, updated_by_admin_id = :admin_id
        WHERE id = 1
      ");
      $update_stmt->execute([
        ':smtp_host' => $smtp_host,
        ':smtp_port' => $smtp_port,
        ':smtp_encryption' => $smtp_encryption,
        ':smtp_user' => $smtp_user,
        ':smtp_pass' => $encrypted_password,
        ':smtp_from' => $smtp_from,
        ':smtp_name' => $smtp_name,
        ':admin_id' => (int)$_SESSION['admin_user_id'],
      ]);
      $_SESSION['status'] = 'SMTP email settings saved. The password is encrypted in the database.';
      $_SESSION['status_type'] = 'success';
      header('Location: setting.php');
      exit;
    } catch (Throwable $e) {
      error_log('SMTP settings update failed: ' . $e->getMessage());
      $smtp_settings_message = 'SMTP settings could not be saved. Check database access and private key directory permissions.';
      $smtp_settings_message_type = 'danger';
    }
  }
}

$smtp_settings = [
  'smtp_host' => 'smtp.gmail.com',
  'smtp_port' => 587,
  'smtp_encryption' => 'tls',
  'smtp_user' => '',
  'smtp_from' => '',
  'smtp_name' => 'AmarShop',
];
$smtp_password_configured = false;
if ($can_manage_smtp) {
  try {
    require_once __DIR__ . '/../core/email_verification.php';
    ensure_auth_settings_table($pdo);
    $smtp_stmt = $pdo->query('SELECT smtp_host, smtp_port, smtp_encryption, smtp_user, smtp_pass, smtp_from, smtp_name FROM `auth` WHERE id = 1');
    $stored_smtp_settings = $smtp_stmt->fetch(PDO::FETCH_ASSOC);
    if ($stored_smtp_settings) {
      $smtp_password_configured = !empty($stored_smtp_settings['smtp_pass']);
      unset($stored_smtp_settings['smtp_pass']);
      $smtp_settings = array_merge($smtp_settings, $stored_smtp_settings);
    }
  } catch (Throwable $e) {
    error_log('SMTP settings could not be loaded: ' . $e->getMessage());
    $smtp_settings_message = 'SMTP settings could not be loaded. Check the auth table and database permissions.';
    $smtp_settings_message_type = 'danger';
  }
}

// Fetch Current Settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $settings[$row['setting_key']] = $row['setting_value'];
}

include_once 'includes/header.php';
?>
<style>
  .settings-section-toggle .bi-chevron-down {
    transition: transform 0.15s ease;
  }
  .settings-section-toggle[aria-expanded="false"] .bi-chevron-down {
    transform: rotate(-90deg);
  }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0">
  <button class="btn btn-link settings-section-toggle d-flex align-items-center gap-2 p-0 text-start text-decoration-none text-body" type="button" data-bs-toggle="collapse" data-bs-target="#websiteSettingsSection" aria-expanded="true" aria-controls="websiteSettingsSection">
    <span class="h3 mb-0">Website Settings</span><i class="bi bi-chevron-down" aria-hidden="true"></i>
  </button>
  <span class="text-muted small">Manage main index content & controls</span>
</div>

<div id="alert-container" class="flex-shrink-0">
  <?php if ($smtp_settings_message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($smtp_settings_message_type, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($smtp_settings_message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['status'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['status_type'] ?? 'info'); ?> alert-dismissible fade show auto-banish-alert" role="alert">
      <?= htmlspecialchars($_SESSION['status']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php
    unset($_SESSION['status']);
    unset($_SESSION['status_type']);
    ?>
  <?php endif; ?>
</div>

<div class="collapse show flex-grow-1 overflow-y-auto" id="websiteSettingsSection">
<?php if ($can_manage_smtp): ?>
  <div class="card border-0 shadow-sm flex-shrink-0 p-4 mb-3">
    <button class="btn btn-link settings-section-toggle d-flex align-items-center justify-content-between w-100 p-0 text-start text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#smtpSettingsSection" aria-expanded="true" aria-controls="smtpSettingsSection">
      <span class="h5 fw-bold mb-0 text-primary"><i class="bi bi-envelope-gear me-2"></i>Email / SMTP Settings</span>
      <i class="bi bi-chevron-down ms-2" aria-hidden="true"></i>
    </button>
    <div class="collapse show" id="smtpSettingsSection">
      <p class="text-muted small mt-2 mb-3">শুধু CEO ও MD এই সেটিংস পরিবর্তন করতে পারবেন। SMTP password encrypted অবস্থায় database-এর <code>auth.smtp_pass</code> কলামে থাকে; এখানে সংরক্ষিত password আর দেখানো হয় না। Live server-এ এই পেজ HTTPS দিয়ে ব্যবহার করুন।</p>
      <form method="post" action="setting.php" class="row g-3">
      <input type="hidden" name="action" value="save_smtp_settings">
      <input type="hidden" name="smtp_csrf" value="<?= htmlspecialchars($smtp_csrf, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="col-md-6">
        <label class="form-label fw-semibold">SMTP Host</label>
        <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars((string)$smtp_settings['smtp_host'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="mail.example.com" required>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">SMTP Port</label>
        <input type="number" name="smtp_port" class="form-control" min="1" max="65535" value="<?= (int)$smtp_settings['smtp_port']; ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">Encryption</label>
        <select name="smtp_encryption" class="form-select" required>
          <option value="tls" <?= $smtp_settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>STARTTLS (usually 587)</option>
          <option value="ssl" <?= $smtp_settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL (usually 465)</option>
          <option value="none" <?= $smtp_settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">SMTP Username / Email</label>
        <input type="email" name="smtp_user" class="form-control" value="<?= htmlspecialchars((string)$smtp_settings['smtp_user'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">SMTP Password / App Password</label>
        <input type="password" name="smtp_pass" class="form-control" autocomplete="new-password" placeholder="<?= $smtp_password_configured ? 'Saved securely — leave blank to keep current password' : 'Enter SMTP password or app password'; ?>">
        <?php if ($smtp_password_configured): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="clear_smtp_pass" value="1" id="clearSmtpPass">
            <label class="form-check-label small" for="clearSmtpPass">Remove saved SMTP password</label>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">From Email</label>
        <input type="email" name="smtp_from" class="form-control" value="<?= htmlspecialchars((string)$smtp_settings['smtp_from'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Sender Name</label>
        <input type="text" name="smtp_name" class="form-control" maxlength="120" value="<?= htmlspecialchars((string)$smtp_settings['smtp_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>
      <div class="col-12 text-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-shield-lock me-1"></i>Save Email Settings</button>
      </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm flex-grow-1 overflow-y-auto p-4 mb-3">
  <form id="siteSettingsForm" enctype="multipart/form-data" onsubmit="handleAjaxSubmit(event, 'core/setting_update_core.php', '#siteSettingsForm')">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($site_settings_csrf, ENT_QUOTES, 'UTF-8'); ?>">
    
    <h5 class="mb-3">
      <button class="btn btn-link settings-section-toggle d-flex align-items-center justify-content-between w-100 p-0 text-start text-decoration-none fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#generalInformationSection" aria-expanded="true" aria-controls="generalInformationSection">
        <span><i class="bi bi-globe me-2"></i>General Information</span><i class="bi bi-chevron-down ms-2" aria-hidden="true"></i>
      </button>
    </h5>
    <div class="collapse show" id="generalInformationSection">
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Website Name</label>
        <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($settings['site_title'] ?? 'AmarShop'); ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Header Notice / Tagline</label>
        <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($settings['site_tagline'] ?? 'Welcome to AmarShop - Best Online Store!'); ?>">
      </div>
    </div>
    </div>

    <hr class="my-4">

    <h5 class="mb-3">
      <button class="btn btn-link settings-section-toggle d-flex align-items-center justify-content-between w-100 p-0 text-start text-decoration-none fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#contactInformationSection" aria-expanded="true" aria-controls="contactInformationSection">
        <span><i class="bi bi-telephone me-2"></i>Contact Information</span><i class="bi bi-chevron-down ms-2" aria-hidden="true"></i>
      </button>
    </h5>
    <div class="collapse show" id="contactInformationSection">
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Support Phone</label>
        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone'] ?? '+880 1700-000000'); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Support Email</label>
        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email'] ?? 'info@amarshop.com'); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Store Address</label>
        <input type="text" name="contact_address" class="form-control" value="<?= htmlspecialchars($settings['contact_address'] ?? 'Dhaka, Bangladesh'); ?>">
      </div>
    </div>
    </div>

    <hr class="my-4">

    <h5 class="mb-3">
      <button class="btn btn-link settings-section-toggle d-flex align-items-center justify-content-between w-100 p-0 text-start text-decoration-none fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#socialMediaSection" aria-expanded="true" aria-controls="socialMediaSection">
        <span><i class="bi bi-share me-2"></i>Social Media Links</span><i class="bi bi-chevron-down ms-2" aria-hidden="true"></i>
      </button>
    </h5>
    <div class="collapse show" id="socialMediaSection">
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Facebook Page URL</label>
        <input type="url" name="facebook_url" class="form-control" value="<?= htmlspecialchars($settings['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/yourpage">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Instagram URL</label>
        <input type="url" name="instagram_url" class="form-control" value="<?= htmlspecialchars($settings['instagram_url'] ?? ''); ?>" placeholder="https://instagram.com/yourhandle">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">WhatsApp Number</label>
        <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>" placeholder="8801700000000">
      </div>
    </div>
    </div>

    <hr class="my-4">

    <h5 class="mb-3">
      <button class="btn btn-link settings-section-toggle d-flex align-items-center justify-content-between w-100 p-0 text-start text-decoration-none fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#brandingMediaSection" aria-expanded="true" aria-controls="brandingMediaSection">
        <span><i class="bi bi-image me-2"></i>Branding &amp; Media</span><i class="bi bi-chevron-down ms-2" aria-hidden="true"></i>
      </button>
    </h5>
    <div class="collapse show" id="brandingMediaSection">
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Website Logo</label>
        <div class="d-flex align-items-center gap-3">
          <?php if (!empty($settings['site_logo'])): ?>
            <img src="../uploads/<?= htmlspecialchars($settings['site_logo']); ?>" alt="Logo" class="img-thumbnail" style="height: 50px;">
          <?php endif; ?>
          <input type="file" name="site_logo" class="form-control" accept="image/*">
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Favicon Icon</label>
        <div class="d-flex align-items-center gap-3">
          <?php if (!empty($settings['site_favicon'])): ?>
            <img src="../uploads/<?= htmlspecialchars($settings['site_favicon']); ?>" alt="Favicon" class="img-thumbnail" style="height: 35px;">
          <?php endif; ?>
          <input type="file" name="site_favicon" class="form-control" accept="image/*">
        </div>
      </div>
    </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
      <button type="submit" class="btn btn-primary btn-lg px-4 submit-btn">
        <i class="bi bi-check-circle me-1"></i> Save All Changes
      </button>
    </div>

  </form>
</div>
  </div>

<?php include_once 'includes/footer.php'; ?>