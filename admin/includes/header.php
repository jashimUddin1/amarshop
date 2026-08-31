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
</head>

<body class="vh-100 overflow-hidden d-flex flex-column">

  <nav class="navbar navbar-expand-lg bg-body border-bottom sticky-top px-3 z-3 flex-shrink-0">
    <button class="btn d-lg-none me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
      <i class="bi bi-list fs-3"></i>
    </button>
    <a class="navbar-brand fw-bold" href="index.php">AmarShop Admin</a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="fw-semibold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
      <button class="btn btn-outline-secondary" id="themeToggle"><i class="bi bi-moon"></i></button>
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
        </ul>
      </aside>

      <main class="col-lg-10 h-100 d-flex flex-column overflow-hidden">