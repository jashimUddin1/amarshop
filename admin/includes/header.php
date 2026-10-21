<?php
require_once __DIR__ . '/../../core/user_profile_picture.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$admin_profile_picture = !empty($_SESSION['user_id'])
  ? get_user_profile_picture($pdo, (int)$_SESSION['user_id'])
  : null;

$admin_nav_links = [
  ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'speedometer2', 'href' => 'index.php'],
  ['key' => 'orders', 'label' => 'Orders', 'icon' => 'cart-check', 'href' => 'sales.php'],
  ['key' => 'order-requests', 'label' => 'Order Requests', 'icon' => 'inbox', 'href' => 'order_requests.php'],
  ['key' => 'products', 'label' => 'Products', 'icon' => 'box-seam', 'href' => 'products.php'],
  ['key' => 'category', 'label' => 'Categories', 'icon' => 'folder-plus', 'href' => 'categories.php'],
  ['key' => 'collection', 'label' => 'Collections', 'icon' => 'tags', 'href' => 'collection.php'],
  ['key' => 'users', 'label' => 'Users', 'icon' => 'people', 'href' => 'users.php'],
  ['key' => 'setting', 'label' => 'Settings', 'icon' => 'gear', 'href' => 'setting.php'],
  ['key' => 'profile', 'label' => 'My Profile', 'icon' => 'person-circle', 'href' => 'profile.php'],
];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $page_title ?? 'Admin Panel'; ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .admin-topbar {
      min-height: 80px;
      display: flex;
      background: var(--bs-body-bg);
    }

    .admin-brand {
      width: 16.666667%;
      flex: 0 0 16.666667%;
      display: flex;
      align-items: center;
      padding: 0 1.25rem;
      color: var(--bs-body-color);
      background: var(--bs-tertiary-bg);
      text-decoration: none;
      font-size: 1.25rem;
      border-right: 1px solid var(--bs-border-color);
    }

    .admin-toolbar {
      flex: 1;
      min-width: 0;
      display: flex;
      align-items: center;
      padding: 0 1.25rem;
    }

    .admin-page-title {
      color: var(--bs-body-color);
      font-size: 1.25rem;
      font-weight: 600;
    }

    .admin-account-button {
      color: var(--bs-body-color);
    }

    .admin-account-button:hover,
    .admin-account-button:focus {
      color: var(--bs-primary);
    }

    @media (max-width: 991.98px) {
      .admin-brand {
        display: none;
      }

      .admin-toolbar {
        padding: 0 0.5rem;
      }

      .admin-page-title {
        display: none;
      }
    }

    @media (max-width: 575.98px) {
      .admin-toolbar > .d-flex {
        gap: 0.5rem !important;
      }

      .admin-brand {
        font-size: 0.9rem;
        padding: 0 0.5rem;
      }
    }
  </style>
</head>

<body class="vh-100 overflow-hidden d-flex flex-column">

  <nav class="admin-topbar border-bottom sticky-top z-3 flex-shrink-0">
    <button class="btn d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileMenu" aria-controls="adminMobileMenu" aria-label="Open navigation menu">
      <i class="bi bi-list fs-2"></i>
    </button>
    <a class="admin-brand fw-bold" href="index.php">AmarShop Admin</a>
    <div class="admin-toolbar">
      <span class="admin-page-title"><?= htmlspecialchars(($current_page ?? '') === 'dashboard' ? 'Home' : ($page_title ?? 'Admin Panel')); ?></span>
      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="dropdown d-none d-sm-block">
          <button class="btn btn-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
            <i class="bi bi-bell-fill text-warning"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 220px;">
            <div class="fw-semibold mb-2">Notifications</div>
            <a class="dropdown-item px-0" href="sales.php"><i class="bi bi-cart-check me-2"></i>Review recent orders</a>
          </div>
        </div>
        <a class="btn btn-link text-decoration-none fw-semibold admin-account-button" href="profile.php">
          <?php if ($admin_profile_picture): ?>
            <img src="../uploads/<?= htmlspecialchars($admin_profile_picture, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded-circle object-fit-cover me-1" style="width:28px;height:28px;">
          <?php else: ?>
            <i class="bi bi-person-circle me-1"></i>
          <?php endif; ?>
          <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
        </a>
        <div class="dropdown d-none d-sm-block">
          <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Admin account options"></button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item text-danger" href="auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
          </ul>
        </div>
        <button class="btn btn-outline-secondary" id="themeToggle" type="button" aria-label="Toggle theme"><i class="bi bi-moon"></i></button>
      </div>
    </div>
  </nav>

  <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="adminMobileMenu" aria-labelledby="adminMobileMenuLabel">
    <div class="offcanvas-header border-bottom">
      <h2 class="offcanvas-title h5 fw-bold" id="adminMobileMenuLabel">AmarShop Admin</h2>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close navigation menu"></button>
    </div>
    <div class="offcanvas-body p-2 bg-body-tertiary">
      <ul class="nav nav-pills flex-column gap-1">
        <?php foreach ($admin_nav_links as $link): ?>
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === $link['key'] ? 'active' : 'text-body'; ?>" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>">
              <i class="bi bi-<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </li>
        <?php endforeach; ?>
        <li class="nav-item mt-2 border-top pt-2">
          <a class="nav-link text-danger" href="auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a>
        </li>
      </ul>
    </div>
  </div>

  <div class="container-fluid flex-grow-1 overflow-hidden">
    <div class="row h-100">

      <aside class="col-lg-2 d-none d-lg-block bg-body-tertiary border-end h-100 overflow-y-auto pt-3">
        <ul class="nav nav-pills flex-column gap-1 px-1 pb-5">
          <?php foreach ($admin_nav_links as $link): ?>
            <li class="nav-item">
              <a class="nav-link <?= ($current_page ?? '') === $link['key'] ? 'active' : 'text-body'; ?>" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

      <main class="col-lg-10 h-100 d-flex flex-column overflow-hidden">