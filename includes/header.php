<?php
// Session & Auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']) ? true : false;

$current_page = basename($_SERVER['PHP_SELF']);

// Database থেকে Categories dynamic vabe fetch kora
$nav_categories = [];
$nav_collections = [];
if (isset($pdo)) {
    try {
        $cat_stmt = $pdo->query("SELECT name FROM categories ORDER BY id ASC LIMIT 6");
        $nav_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Database থেকে Collections dynamic vabe fetch kora
        $col_stmt = $pdo->query("SELECT title, slug FROM collections ORDER BY id ASC");
        $nav_collections = $col_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $nav_categories = [];
        $nav_collections = [];
    }
}

// Function to build query string preserving existing parameters
function build_query_url($param_name, $param_value)
{
    $params = $_GET;
    if ($param_value === '') {
        unset($params[$param_name]); // faka hole parameter remove kore dibe
    } else {
        $params[$param_name] = $param_value;
    }
    return basename($_SERVER['PHP_SELF']) . (!empty($params) ? '?' . http_build_query($params) : '');
}

$selected_category = $_GET['category'] ?? '';

$selected_collection = $_GET['collection'] ?? '';

// Active collection-er title ber korar jonno loop chalano ba default text set kora
$collection_btn_title = 'All Collections';
if (!empty($selected_collection) && !empty($nav_collections)) {
    foreach ($nav_collections as $col) {
        if ($col['slug'] === $selected_collection) {
            $collection_btn_title = $col['title'];
            break;
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - Amarshop' : 'Amarshop - Online Shop'; ?></title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Swiper CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        :root {
            --brand: #198754;
            --brand-dark: #157347;
            --text: #1f2937;
            --muted: #6c757d;
            --border: #dee2e6;
            --bg: #f8f9fa;
        }

        html {
            scroll-behavior: smooth;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* Topbar & Marquee */
        .topbar {
            background: #0d6efd;
            color: #fff;
            font-size: 0.85rem;
        }

        .marquee-wrap {
            background: #212529;
            color: #fff;
            overflow: hidden;
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .marquee {
            display: inline-block;
            padding-left: 100%;
            animation: marquee 18s linear infinite;
            margin: 7px 0px;
        }

        .marquee span {
            display: inline-block;
            padding-right: 2rem;
        }

        @keyframes marquee {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-100%);
            }
        }

        /* Header */
        .header {
            background: #fff;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .custom-header-row {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 10px;
            width: min(100%, 1400px);
            margin: 0 auto;
            padding: 0 12px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .logo-area img {
            width: 110px;
            height: 42px;
            object-fit: contain;
            border-radius: 6px;
        }

        .search-area {
            flex: 1 1 auto;
            min-width: 0;
            margin: 0 8px;
        }

        .search-area form {
            width: 100%;
        }

        .input-group-custom {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 6px;
        }

        .input-group-custom .form-select-custom,
        .input-group-custom .form-control-custom,
        .input-group-custom .btn-custom {
            height: 40px;
            border: 1px solid var(--border);
            border-radius: 6px;
        }

        .input-group-custom .form-select-custom {
            width: 110px;
            font-size: 0.78rem;
            padding: 0 10px;
            background-color: #f8f9fa;
            flex-shrink: 0;
        }

        .input-group-custom .form-control-custom {
            flex: 1 1 auto;
            min-width: 0;
            font-size: 0.8rem;
            padding: 0 12px;
        }

        .input-group-custom .btn-custom {
            font-size: 0.8rem;
            padding: 0 14px;
            flex-shrink: 0;
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .input-group-custom .btn-custom:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }

        .icons-area {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .icon-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #333;
            font-size: 15px;
            transition: 0.3s ease;
            flex-shrink: 0;
            background: #fff;
        }

        .icon-btn:hover {
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
        }

        .icon-btn .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            font-size: 9px;
            padding: 3px 5px;
        }

        /* Dynamic Sticky Shadow Fix */
        .sticky-wrapper {
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        [id^="col-"] {
            scroll-margin-top: 140px;
            /* হেডার এবং নেভবার মিলিয়ে যতটুকু উচ্চতা হয় */
        }

        /* Hero Slider */
        .sliderCls {
            max-width: 1000px !important;
            min-height: 480px;
            margin: 0 auto;
        }

        /* Universal Product Cards */
        .product-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #fff;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border-color: #dee2e6;
        }

        .product-img-container {
            position: relative;
            height: 200px;
            overflow: hidden;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .product-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .product-card:hover img {
            transform: scale(1.05);
        }

        /* Add to cart modal */
        .mwt {
            min-width: 50%;
        }

        .cart_popup {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        /* Footer Styles */
        .footer {
            background-color: #212529;
            color: #ced4da;
            font-size: 0.9rem;
            padding-top: 50px;
        }

        .footer a {
            color: #ced4da;
            text-decoration: none;
            transition: 0.3s;
        }

        .footer a:hover {
            color: #fff;
            padding-left: 5px;
        }

        .footer-title {
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--brand);
        }

        .footer-logo img {
            width: 130px;
            height: auto;
            margin-bottom: 15px;
        }

        .footer-social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin-right: 8px;
            color: #fff;
            transition: 0.3s;
        }

        .footer-social-icons a:hover {
            background-color: var(--brand);
            transform: translateY(-3px);
            padding-left: 0;
        }

        .payment-icons img {
            height: 25px;
            margin-right: 10px;
            margin-bottom: 10px;
            filter: grayscale(100%);
            transition: 0.3s;
        }

        .payment-icons img:hover {
            filter: grayscale(0%);
        }

        .footer-bottom {
            background-color: #1a1e21;
            padding: 15px 0;
            font-size: 0.85rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
 

        /* Media Queries */
        @media (max-width: 768px) {
            .logo-area img {
                width: 50px;
            }

            .custom-header-row {
                padding: 0 5px;
                gap: 3px;
            }

            .search-area {
                margin: 0 4px;
            }

            .input-group-custom {
                gap: 3px;
            }

            .input-group-custom .form-select-custom {
                width: 60px;
                font-size: 8px;
            }

            .input-group-custom .btn-custom {
                padding: 0 10px;
            }

            .icon-btn {
                width: 36px;
                height: 36px;
            }

            .footer-col {
                margin-bottom: 30px;
            }
        }

        @media (min-width: 768px) {
            .logo-area img {
                width: 130px;
                height: 45px;
            }

            .input-group-custom .form-select-custom {
                width: 140px;
                font-size: 0.82rem;
            }

            .input-group-custom .form-control-custom {
                font-size: 0.82rem;
            }

            .input-group-custom .btn-custom {
                font-size: 0.85rem;
                padding: 0 18px;
            }

            .icon-btn {
                width: 42px;
                height: 42px;
                font-size: 18px;
            }

            .header {
                padding: 12px 0;
            }
        }

        @media (max-width: 575.98px) {
            .product-img-container {
                height: 160px;
            }

            .product-title {
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>

    <!-- Topbar & Announcement -->
    <div>
        <div class="topbar py-1">
            <div class="container d-flex justify-content-between align-items-center">
                <small>Welcome to Amarshop</small>
                <small>Free delivery on orders over ৳2000</small>
            </div>
        </div>

        <div class="marquee-wrap py-1">
            <div class="marquee">
                <span>✨ New Arrivals Every Week</span>
                <span>🔥 Flash Sale - Up to 50% Off</span>
                <span>🚚 Fast Delivery Across the City</span>
                <span>💳 Secure Online Payment</span>
            </div>
        </div>
    </div>

    <!-- STICKY WRAPPER -->
    <div class="sticky-wrapper shadow-sm">
        <!-- Header Section -->
        <header class="header bg-white">
            <div class="container-fluid px-2 px-md-3">
                <div class="custom-header-row justify-content-between">
                    <!-- Logo -->
                    <div class="logo-area">
                        <a href="index.php" class="d-flex align-items-center text-decoration-none">
                            <img src="assets/img/logo1.png" class="me-1 me-md-2 rounded" alt="Logo">
                        </a>
                    </div>


                    <!-- Search Bar -->
                    <div class="search-area">
                        <form id="searchForm" onsubmit="return false;">
                            <div class="input-group-custom">
                                <input type="text" name="search" id="liveSearchInput" class="form-control-custom" placeholder="Search products...">
                                <button class="btn btn-success btn-custom" type="button">
                                    <i class="bi bi-search d-inline d-md-none"></i>
                                    <span class="d-none d-md-inline">Search</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Action Icons -->
                    <div class="icons-area">
                        <a href="#cartOffcanvas" class="icon-btn" data-bs-toggle="offcanvas" role="button" aria-controls="cartOffcanvas" title="Cart">
                            <i class="bi bi-cart3"></i>
                            <span class="badge bg-success rounded-pill" id="cartBadge">0</span>
                        </a>

                        <?php if (!$is_logged_in): ?>
                            <a href="auth/login.php" class="icon-btn" title="Login / Register">
                                <i class="bi bi-person"></i>
                                <span class="badge bg-danger rounded-pill">?</span>
                            </a>
                        <?php else: ?>
                            <?php
                            $user_name = trim($_SESSION['user_name'] ?? '');
                            if (!empty($user_name)) {
                                $first_word = explode(' ', $user_name)[0];
                                $display_name = (mb_strlen($first_word) > 3) ? mb_substr($first_word, 0, 3) . '.' : $first_word;
                            } else {
                                $display_name = 'User';
                            }
                            ?>
                            <button class="icon-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#userProfileDrawer" aria-controls="userProfileDrawer" title="My Account">
                                <i class="bi bi-person"></i>
                                <span class="badge bg-success"><?= htmlspecialchars($display_name); ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Navigation Menu Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-success border-top border-light border-opacity-10 py-1">
            <div class="container d-flex justify-content-between align-items-center">
                <!-- Mobile Offcanvas Toggle Button -->
                <button class="navbar-toggler border-0 shadow-none py-1 px-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                    <i class="bi bi-list fs-3 text-white"></i>
                </button>

                <!-- Mobile View Support Info (Shudhu Mobile Version-e dekha jabe) -->
                <div class="d-flex d-lg-none align-items-center text-white gap-2 small">
                    <i class="bi bi-headset fs-5"></i>
                    <div>
                        <span class="d-block text-white-50 lh-1" style="font-size: 0.68rem;">10 AM to 10 PM</span>
                        <span class="fw-bold" style="font-size: 0.8rem;">+880 1700-000000</span>
                    </div>
                </div>

                <!-- Dynamic Category Dropdown Button (Desktop View) -->
                <div class="dropdown me-auto me-lg-3 d-none d-lg-block">
                    <button class="btn btn-dark btn-sm dropdown-toggle fw-bold d-flex align-items-center gap-1 py-1.5 px-3" type="button" id="categoryMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #145ce6; border: none;">
                        <?= !empty($selected_category) ? htmlspecialchars($selected_category) : 'All Categories'; ?>
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0 mt-2" aria-labelledby="categoryMenuButton">
                        <!-- Default All Categories option (eta select korle category parameter faka hoye jabe) -->


                        <?php if (!empty($nav_categories)): ?>
                            <!-- "All Categories" option -->
                            <li>
                                <a class="dropdown-item py-2 fw-semibold <?= empty($_GET['category']) ? 'active bg-success text-white' : ''; ?>" href="<?= build_query_url('category', ''); ?>">
                                    All Categories
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <?php foreach ($nav_categories as $cat): ?>
                                <?php $is_active = (($_GET['category'] ?? '') === $cat['name']); ?>
                                <li>
                                    <a class="dropdown-item py-2 <?= $is_active ? 'active bg-success text-white' : ''; ?>" href="<?= build_query_url('category', $cat['name']); ?>">
                                        <i class="bi bi-tag me-2 <?= $is_active ? 'text-white' : 'text-success'; ?>"></i> <?= htmlspecialchars($cat['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><span class="dropdown-item text-muted">No categories found</span></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="collapse navbar-collapse" id="desktopNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 fw-semibold">

                        <!-- Dynamic Collections Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white px-3" href="#" id="collectionsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= htmlspecialchars($collection_btn_title); ?>
                            </a>
                            <ul class="dropdown-menu border-0 shadow mt-1">
                                <?php if (!empty($nav_collections)): ?>
                                    <?php foreach ($nav_collections as $col): ?>
                                        <?php $is_col_active = (($_GET['collection'] ?? '') === $col['slug']); ?>
                                        <li>
                                            <a class="dropdown-item py-2 <?= $is_col_active ? 'active bg-success text-white' : ''; ?>" href="<?= build_query_url('collection', $col['slug']); ?>#col-<?= htmlspecialchars($col['slug']); ?>">
                                                <?= htmlspecialchars($col['title']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><span class="dropdown-item text-muted">No collections found</span></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    </ul>

                    <!-- Desktop View Support Info -->
                    <div class="d-none d-lg-flex align-items-center text-white gap-2 small">
                        <i class="bi bi-headset fs-5"></i>
                        <div>
                            <span class="d-block text-white-50 lh-1" style="font-size: 0.75rem;">10 AM to 10 PM</span>
                            <span class="fw-bold">+880 1700-000000</span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
    <!-- Sticky Wrapper End -->

    <!-- Mobile Drawer Offcanvas -->
    <div class="offcanvas offcanvas-start d-flex flex-column justify-content-between" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel" style="width: 280px;">
        <div>
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title fw-bold text-success" id="mobileMenuLabel">AmarShop Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>

            <!-- Dynamic Category Dropdown Button (Mobile Offcanvas inside) -->
            <div class="dropdown p-3 border-bottom">
                <button class="btn btn-dark btn-sm dropdown-toggle fw-bold w-100 d-flex justify-content-between align-items-center py-2 px-3" type="button" id="mobileCategoryMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #145ce6; border: none;">
                    All Categories
                </button>
                <ul class="dropdown-menu w-100 shadow-lg border-0 mt-1" aria-labelledby="mobileCategoryMenuButton">
                    <?php if (!empty($nav_categories)): ?>
                        <?php foreach ($nav_categories as $cat): ?>
                            <li>
                                <a class="dropdown-item py-2" href="category.php?name=<?= urlencode($cat['name']); ?>">
                                    <i class="bi bi-tag me-2 text-success"></i> <?= htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><span class="dropdown-item text-muted">No categories found</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="offcanvas-body">
                <ul class="navbar-nav gap-2">
                    <li class="nav-item"><a class="nav-link text-dark fw-bold" href="index.php"><i class="bi bi-house-door me-2"></i>Home</a></li>

                    <small class="text-muted fw-bold text-uppercase mt-2 d-block" style="font-size: 0.75rem;">Collections</small>
                    <?php if (!empty($nav_collections)): ?>
                        <?php foreach ($nav_collections as $col): ?>
                            <li class="nav-item">
                                <a class="nav-link text-dark py-1 ps-2" href="collection.php?name=<?= urlencode($col['slug']); ?>">
                                    <i class="bi bi-grid me-2 text-success"></i><?= htmlspecialchars($col['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Offcanvas Bottom Hotline Support -->
        <div class="p-3 bg-success text-white d-flex align-items-center gap-2">
            <i class="bi bi-headset fs-4"></i>
            <div>
                <span class="d-block text-white-50 lh-1" style="font-size: 0.72rem;">10 AM to 10 PM</span>
                <span class="fw-bold" style="font-size: 0.9rem;">+880 1700-000000</span>
            </div>
        </div>
    </div>