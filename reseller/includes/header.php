<?php
$nav_items = [
    'home' => ['Home', 'house-door', 'home.php'],
    'dashboard' => ['Dashboard', 'speedometer2', 'dashboard.php'],
    'sells' => ['Sells', 'cart-check', 'sells.php'],
    'commission' => ['Commission', 'cash-coin', 'commission.php'],
    'profile' => ['Profile', 'person', 'profile.php'],
];
$cart_count = reseller_cart_count();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= reseller_h($page_title); ?> · AmarShop Reseller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { background: #f4f7f5; }
        .reseller-topbar { min-height: 68px; z-index: 1030; }
        .reseller-layout { min-height: 0; }
        .reseller-sidebar { flex: 0 0 16.666667%; max-width: 16.666667%; transition: flex-basis .2s ease, max-width .2s ease; }
        .reseller-sidebar-inner { height: 100%; overflow-y: auto; }
        .reseller-main { flex: 0 0 83.333333%; max-width: 83.333333%; min-width: 0; height: 100%; overflow-y: auto; }
        .reseller-sidebar.is-collapsed { flex-basis: 76px; max-width: 76px; }
        .reseller-sidebar.is-collapsed .nav-label, .reseller-sidebar.is-collapsed .sidebar-heading { display: none; }
        .reseller-sidebar.is-collapsed .nav-link { justify-content: center; padding-left: .5rem; padding-right: .5rem; }
        .reseller-sidebar.is-collapsed .nav-link i { margin-right: 0 !important; }
        .reseller-layout.is-collapsed .reseller-main { flex-basis: calc(100% - 76px); max-width: calc(100% - 76px); }
        .reseller-nav-link { min-height: 44px; }
        .reseller-nav-link.active, .offcanvas .nav-link.active { background: #198754; color: #fff !important; }
        .reseller-cart-badge { min-width: 20px; }
        .reseller-product-card { border: 0; border-radius: 12px; overflow: hidden; transition: transform .18s ease, box-shadow .18s ease; }
        .reseller-product-card:hover { transform: translateY(-3px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.12) !important; }
        .reseller-product-image { height: 230px; object-fit: cover; background: #f0f2f1; cursor: zoom-in; }
        .reseller-product-image-placeholder { height: 230px; background: #f0f2f1; }
        .reseller-product-options { background: rgba(255,255,255,.97); }
        .reseller-size-button.active { color: #fff; background: #198754; border-color: #198754; }
        @media (max-width: 991.98px) {
            .reseller-sidebar { display: none !important; }
            .reseller-main { flex: 0 0 100%; max-width: 100%; }
        }
        @media (max-width: 575.98px) {
            .reseller-topbar { min-height: 60px; }
            .reseller-product-image, .reseller-product-image-placeholder { height: 175px; }
        }
    </style>
</head>
<body class="d-flex flex-column overflow-hidden">
<nav class="navbar bg-white border-bottom shadow-sm sticky-top reseller-topbar px-2 px-md-3">
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Open menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <a class="navbar-brand fw-bold text-success mb-0 d-flex align-items-center" href="home.php">
            <i class="bi bi-shop me-2"></i><span class="d-none d-sm-inline">AmarShop</span><span class="d-sm-none">Shop</span>
        </a>
        <button class="btn btn-sm btn-outline-secondary d-none d-lg-inline-flex" id="sidebarToggle" type="button" aria-label="Minimize or expand menu" aria-controls="resellerSidebar">
            <i class="bi bi-layout-sidebar"></i>
        </button>
    </div>
    <?php if ($current_page === 'home'): ?>
        <form class="d-none d-md-flex flex-grow-1 mx-3" action="home.php" method="get" role="search">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input class="form-control bg-light border-start-0" type="search" name="search" value="<?= reseller_h($_GET['search'] ?? ''); ?>" placeholder="Search products" aria-label="Search products">
            </div>
        </form>
    <?php endif; ?>
    <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto">
        <a class="btn btn-outline-success position-relative" href="cart.php" aria-label="Cart">
            <i class="bi bi-bag fs-5"></i>
            <span class="badge rounded-pill text-bg-danger reseller-cart-badge <?= $cart_count ? '' : 'd-none'; ?>" id="resellerCartCount"><?= $cart_count; ?></span>
        </a>
        <span class="small text-muted d-none d-xl-inline">Welcome, <?= reseller_h($reseller['name']); ?></span>
        <a class="btn btn-outline-danger btn-sm" href="logout.php" aria-label="Logout"><i class="bi bi-box-arrow-right me-md-1"></i><span class="d-none d-md-inline">Logout</span></a>
    </div>
</nav>
<div class="container-fluid flex-grow-1 overflow-hidden">
    <div class="row flex-nowrap h-100 reseller-layout" id="resellerLayoutRow">
        <aside class="col-lg-2 bg-white border-end p-0 reseller-sidebar d-none d-lg-block" id="resellerSidebar">
            <div class="reseller-sidebar-inner d-flex flex-column p-3">
                <div class="sidebar-heading fw-semibold text-muted small text-uppercase mb-3">Reseller menu</div>
                <nav class="nav nav-pills flex-column gap-1">
                    <?php foreach ($nav_items as $key => [$label, $icon, $href]): ?>
                        <a class="nav-link reseller-nav-link d-flex align-items-center <?= $current_page === $key ? 'active' : 'text-dark'; ?>" href="<?= reseller_h($href); ?>" title="<?= reseller_h($label); ?>">
                            <i class="bi bi-<?= reseller_h($icon); ?> me-3"></i><span class="nav-label"><?= reseller_h($label); ?></span>
                        </a>
                    <?php endforeach; ?>
                    <a class="nav-link reseller-nav-link d-flex align-items-center <?= $current_page === 'cart' ? 'active' : 'text-dark'; ?>" href="cart.php" title="Cart">
                        <i class="bi bi-bag me-3"></i><span class="nav-label">Cart <span id="resellerSidebarCartCount"><?= $cart_count ? '(' . $cart_count . ')' : ''; ?></span></span>
                    </a>
                </nav>
                <div class="mt-auto small text-muted pt-4 sidebar-heading">Reseller account</div>
            </div>
        </aside>
        <main class="col-lg-10 p-3 p-lg-4 reseller-main" id="resellerMainContent">
            <?php if ($notice): ?>
                <div class="alert alert-<?= reseller_h($notice['type']); ?> alert-dismissible fade show" role="alert">
                    <?= reseller_h($notice['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
