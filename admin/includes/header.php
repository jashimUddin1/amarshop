<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
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
      min-width: 250px;
      display: flex;
      align-items: center;
      padding: 0 1.25rem;
      color: #fff;
      background: #111827;
      text-decoration: none;
      font-size: 1.25rem;
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
        width: auto;
        min-width: 0;
        padding: 0 0.75rem;
        font-size: 1rem;
      }

      .admin-toolbar {
        padding: 0 0.5rem;
      }

      .admin-page-title {
        display: none;
      }
    }
  </style>
</head>

<body class="vh-100 overflow-hidden d-flex flex-column">

  <nav class="admin-topbar border-bottom sticky-top z-3 flex-shrink-0">
    <a class="admin-brand fw-bold" href="index.php">AmarShop Admin</a>
    <div class="admin-toolbar">
      <button class="btn d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#adminMobileMenu" aria-label="Toggle navigation">
        <i class="bi bi-list fs-3"></i>
      </button>
      <span class="admin-page-title"><?= htmlspecialchars(($current_page ?? '') === 'dashboard' ? 'Home' : ($page_title ?? 'Admin Panel')); ?></span>
      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="dropdown">
          <button class="btn btn-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
            <i class="bi bi-bell-fill text-warning"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 220px;">
            <div class="fw-semibold mb-2">Notifications</div>
            <a class="dropdown-item px-0" href="sales.php"><i class="bi bi-cart-check me-2"></i>Review recent orders</a>
          </div>
        </div>
        <a class="btn btn-link text-decoration-none fw-semibold admin-account-button" href="profile.php">
          <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
        </a>
        <div class="dropdown">
          <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Admin account options"></button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item text-danger" href="auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
          </ul>
        </div>
        <button class="btn btn-outline-secondary" id="themeToggle" type="button" aria-label="Toggle theme"><i class="bi bi-moon"></i></button>
      </div>
    </div>
  </nav>

  <div class="container-fluid flex-grow-1 overflow-hidden">
    <div class="row h-100">

      <aside class="col-lg-2 d-none d-lg-block bg-body-tertiary border-end h-100 overflow-y-auto pt-3">
        <ul class="nav nav-pills flex-column gap-1 px-1 pb-5">
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === 'dashboard' ? 'active' : 'text-body'; ?>" href="index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === 'products' ? 'active' : 'text-body'; ?>" href="products.php"><i class="bi bi-box-seam me-2"></i>Products</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === 'category' ? 'active' : 'text-body'; ?>" href="categories.php"><i class="bi bi-folder-plus me-2"></i>Categories</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === 'collection' ? 'active' : 'text-body'; ?>" href="collection.php"><i class="bi bi-tags me-2"></i>Collections</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === 'sales' ? 'active' : 'text-body'; ?>" href="sales.php"><i class="bi bi-cart-check me-2"></i>Sales</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === 'users' ? 'active' : 'text-body'; ?>" href="users.php"><i class="bi bi-people me-2"></i>Users</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === 'setting' ? 'active' : 'text-body'; ?>" href="setting.php"><i class="bi bi-gear me-2"></i>Settings</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($current_page ?? '') === 'profile' ? 'active' : 'text-body'; ?>" href="profile.php"><i class="bi bi-person-circle me-2"></i>My Profile</a>
          </li>
        </ul>
      </aside>

      <main class="col-lg-10 h-100 d-flex flex-column overflow-hidden">
        <div class="collapse d-lg-none" id="adminMobileMenu">
          <div class="nav nav-pills flex-column gap-1 p-2 border-bottom">
            <a class="nav-link" href="index.php">Dashboard</a>
            <a class="nav-link" href="products.php">Products</a>
            <a class="nav-link" href="categories.php">Categories</a>
            <a class="nav-link" href="collection.php">Collections</a>
            <a class="nav-link" href="sales.php">Sales</a>
            <a class="nav-link" href="users.php">Users</a>
            <a class="nav-link" href="setting.php">Settings</a>
            <a class="nav-link" href="profile.php">My Profile</a>
          </div>
        </div>