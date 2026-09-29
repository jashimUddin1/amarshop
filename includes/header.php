<?php
// Session & Auth check (প্রয়োজন অনুযায়ী dynamic path ব্যবহার করুন)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']) ? true : false;
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
        .topbar { background: #0d6efd; color: #fff; font-size: 0.85rem; }
        .marquee-wrap { background: #212529; color: #fff; overflow: hidden; white-space: nowrap; font-size: 0.85rem; }
        .marquee { display: inline-block; padding-left: 100%; animation: marquee 18s linear infinite; margin: 7px 0px; }
        .marquee span { display: inline-block; padding-right: 2rem; }
        @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-100%); } }

        /* Header */
        .header { background: #fff; padding: 10px 0; border-bottom: 1px solid #e9ecef; }
        .custom-header-row { display: flex; align-items: center; flex-wrap: nowrap; gap: 10px; width: min(100%, 1400px); margin: 0 auto; padding: 0 12px; }
        .logo-area { display: flex; align-items: center; flex-shrink: 0; }
        .logo-area img { width: 110px; height: 42px; object-fit: contain; border-radius: 6px; }
        .search-area { flex: 1 1 auto; min-width: 0; margin: 0 8px; }
        .search-area form { width: 100%; }
        .input-group-custom { display: flex; align-items: center; width: 100%; gap: 6px; }
        .input-group-custom .form-select-custom,
        .input-group-custom .form-control-custom,
        .input-group-custom .btn-custom { height: 40px; border: 1px solid var(--border); border-radius: 6px; }
        .input-group-custom .form-select-custom { width: 110px; font-size: 0.78rem; padding: 0 10px; background-color: #f8f9fa; flex-shrink: 0; }
        .input-group-custom .form-control-custom { flex: 1 1 auto; min-width: 0; font-size: 0.8rem; padding: 0 12px; }
        .input-group-custom .btn-custom { font-size: 0.8rem; padding: 0 14px; flex-shrink: 0; background: var(--brand); border-color: var(--brand); color: #fff; }
        .input-group-custom .btn-custom:hover { background: var(--brand-dark); border-color: var(--brand-dark); }
        .icons-area { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .icon-btn { position: relative; width: 38px; height: 38px; border: 1px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #333; font-size: 15px; transition: 0.3s ease; flex-shrink: 0; background: #fff; }
        .icon-btn:hover { background: var(--brand); color: #fff; border-color: var(--brand); }
        .icon-btn .badge { position: absolute; top: -4px; right: -4px; font-size: 9px; padding: 3px 5px; }

        /* Hero Slider */
        .sliderCls { max-width: 1000px !important; min-height: 480px; margin: 0 auto; }

        /* Universal Product Cards */
        .product-card { border: 1px solid #e9ecef; border-radius: 10px; transition: all 0.3s ease; background: #fff; overflow: hidden; height: 100%; display: flex; flex-direction: column; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05); border-color: #dee2e6; }
        .product-img-container { position: relative; height: 200px; overflow: hidden; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; padding: 10px; }
        .product-img-container img { max-height: 100%; max-width: 100%; object-fit: contain; transition: transform 0.5s ease; }
        .product-card:hover img { transform: scale(1.05); }

        /* Add to cart modal */
        .mwt { min-width: 50%; }
        .cart_popup { margin-right: 5px; margin-bottom: 5px; }

        /* Footer Styles */
        .footer { background-color: #212529; color: #ced4da; font-size: 0.9rem; padding-top: 50px; }
        .footer a { color: #ced4da; text-decoration: none; transition: 0.3s; }
        .footer a:hover { color: #fff; padding-left: 5px; }
        .footer-title { color: #fff; font-weight: 600; font-size: 1.1rem; margin-bottom: 20px; position: relative; padding-bottom: 10px; }
        .footer-title::after { content: ''; position: absolute; left: 0; bottom: 0; width: 50px; height: 2px; background-color: var(--brand); }
        .footer-logo img { width: 130px; height: auto; margin-bottom: 15px; }
        .footer-social-icons a { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background-color: rgba(255, 255, 255, 0.1); border-radius: 50%; margin-right: 8px; color: #fff; transition: 0.3s; }
        .footer-social-icons a:hover { background-color: var(--brand); transform: translateY(-3px); padding-left: 0; }
        .payment-icons img { height: 25px; margin-right: 10px; margin-bottom: 10px; filter: grayscale(100%); transition: 0.3s; }
        .payment-icons img:hover { filter: grayscale(0%); }
        .footer-bottom { background-color: #1a1e21; padding: 15px 0; font-size: 0.85rem; border-top: 1px solid rgba(255, 255, 255, 0.05); }

        /* Media Queries */
        @media (max-width: 768px) {
            .logo-area img { width: 50px; }
            .custom-header-row { padding: 0 5px; gap: 3px; }
            .search-area { margin: 0 4px; }
            .input-group-custom { gap: 3px; }
            .input-group-custom .form-select-custom { width: 60px; font-size: 8px; }
            .input-group-custom .btn-custom { padding: 0 10px; }
            .icon-btn { width: 36px; height: 36px; }
            .footer-col { margin-bottom: 30px; }
        }
        @media (min-width: 768px) {
            .logo-area img { width: 130px; height: 45px; }
            .input-group-custom .form-select-custom { width: 140px; font-size: 0.82rem; }
            .input-group-custom .form-control-custom { font-size: 0.82rem; }
            .input-group-custom .btn-custom { font-size: 0.85rem; padding: 0 18px; }
            .icon-btn { width: 42px; height: 42px; font-size: 18px; }
            .header { padding: 12px 0; }
        }
        @media (max-width: 575.98px) {
            .product-img-container { height: 160px; }
            .product-title { font-size: 0.85rem; }
        }
    </style>
</head>

<body>

<div class="sticky-top z-3">
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

    <!-- Header Section -->
    <header class="header bg-white shadow-sm">
        <div class="container-fluid px-2 px-md-3">
            <div class="custom-header-row justify-content-between">
                <!-- Logo -->
                <div class="logo-area">
                    <a href="index.php" class="d-flex align-items-center text-decoration-none">
                        <img src="assets/img/logo1.png" class="me-1 me-md-2 rounded" alt="Logo">
                    </a>
                </div>

                <!-- Search -->
                <div class="search-area">
                    <form>
                        <div class="input-group-custom">
                            <select class="form-select-custom">
                                <option selected>Category</option>
                                <option>Shirt</option>
                                <option>Katua</option>
                                <option>T-Shirt</option>
                                <option>Pant</option>
                            </select>
                            <input type="text" class="form-control-custom" placeholder="Search...">
                            <button class="btn btn-success btn-custom" type="submit">
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
</div>

<!-- Navigation Menu Bar (NEWLY ADDED) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success border-top border-light border-opacity-10 py-1">
        <div class="container px-2 px-md-3">
            <!-- Mobile Offcanvas Toggle Button -->
            <button class="navbar-toggler border-0 shadow-none py-1 px-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                <i class="bi bi-list fs-3 text-white"></i>
                <span class="fs-6 ms-1 text-white align-middle">Menu</span>
            </button>

            <!-- All Categories Dropdown Button (Mobile & Desktop) -->
            <div class="dropdown me-auto me-lg-3">
                <button class="btn btn-dark btn-sm dropdown-toggle fw-bold d-flex align-items-center gap-1 py-1.5 px-3" type="button" id="categoryMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #145ce6; border: none;">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i> All Categories
                </button>
                <ul class="dropdown-menu shadow-lg border-0 mt-2" aria-labelledby="categoryMenuButton">
                    <li><a class="dropdown-item py-2" href="#"><i class="bi bi-tag me-2 text-success"></i> Shirts</a></li>
                    <li><a class="dropdown-item py-2" href="#"><i class="bi bi-tag me-2 text-success"></i> Katua</a></li>
                    <li><a class="dropdown-item py-2" href="#"><i class="bi bi-tag me-2 text-success"></i> T-Shirts</a></li>
                    <li><a class="dropdown-item py-2" href="#"><i class="bi bi-tag me-2 text-success"></i> Pants</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 fw-bold text-success" href="#">View All Categories <i class="bi bi-arrow-right"></i></a></li>
                </ul>
            </div>

            <!-- Desktop Navigation Links -->
            <div class="collapse navbar-collapse" id="desktopNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 fw-semibold">
                    <li class="nav-item">
                        <a class="nav-link active text-white px-3" href="index.php"><i class="bi bi-house-door me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 px-3 hover-white" href="#">Shop</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white-50 px-3" href="#" id="collectionsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Collections
                        </a>
                        <ul class="dropdown-menu border-0 shadow mt-1">
                            <li><a class="dropdown-item" href="#">New Arrivals</a></li>
                            <li><a class="dropdown-item" href="#">Top Deals</a></li>
                            <li><a class="dropdown-item" href="#">Trending Sales</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 px-3" href="#">Offers <span class="badge bg-warning text-dark ms-1">HOT</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 px-3" href="#">Contact Us</a>
                    </li>
                </ul>

                <!-- Right Side Quick Info / Hotline -->
                <div class="d-none d-xl-flex align-items-center text-white gap-2 small">
                    <i class="bi bi-headset fs-5"></i>
                    <div>
                        <span class="d-block text-white-50 lh-1" style="font-size: 0.75rem;">Support</span>
                        <span class="fw-bold">+880 1700-000000</span>
                    </div>
                </div>
            </div>
        </div>
    </nav>
 <!-- sticky-top end -->

<!-- Mobile Drawer Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold text-success" id="mobileMenuLabel">AmarShop Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="navbar-nav gap-3">
            <li class="nav-item"><a class="nav-link text-dark" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="#">Shop</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="#">Categories</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="#">Contact</a></li>
        </ul>
    </div>
</div>

<!-- Session Messages -->
<?php if (isset($_SESSION['status']) && isset($_SESSION['message'])): ?>
    <?php
    $alert_type = ($_SESSION['status'] === 'success') ? 'success' : (($_SESSION['status'] === 'warning') ? 'warning' : 'danger');
    ?>
    <div class="container my-3">
        <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-0" role="alert">
            <div><?= htmlspecialchars($_SESSION['message']); ?></div>
            <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php
    unset($_SESSION['status']);
    unset($_SESSION['message']);
    ?>
<?php endif; ?>