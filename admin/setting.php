<?php
require_once 'auth/config.php';

$page_title = "Site Settings";
$current_page = "setting";

// Fetch Current Settings
$settings = [];
try {
  $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
  }
} catch (PDOException $e) {
  // Table missing fallback logic
}

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0">
  <h2 class="h3 mb-0">Website Settings</h2>
  <span class="text-muted small">Manage main index content & controls</span>
</div>

<div id="alert-container" class="flex-shrink-0">
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

<div class="card border-0 shadow-sm flex-grow-1 overflow-y-auto p-4 mb-3">
  <form id="siteSettingsForm" enctype="multipart/form-data" onsubmit="handleAjaxSubmit(event, 'core/setting_update_core.php', '#siteSettingsForm')">
    
    <!-- General Information -->
    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-globe me-2"></i>General Information</h5>
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

    <hr class="my-4">

    <!-- Contact Details -->
    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-telephone me-2"></i>Contact Information</h5>
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

    <hr class="my-4">

    <!-- Social Media Links -->
    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-share me-2"></i>Social Media Links</h5>
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

    <hr class="my-4">

    <!-- Main Index Branding & Media -->
    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-image me-2"></i>Branding & Media</h5>
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

    <div class="d-flex justify-content-end gap-2 mt-4">
      <button type="submit" class="btn btn-primary btn-lg px-4 submit-btn">
        <i class="bi bi-check-circle me-1"></i> Save All Changes
      </button>
    </div>

  </form>
</div>

<?php include_once 'includes/footer.php'; ?>