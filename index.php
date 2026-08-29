<?php //index.php main page - main frontend
require_once('auth/config.php');
/**
 * @param \PDO $pdo
 * @param string $collection_name
 */

function get_products_by_collection($pdo, $collection_name, $limit = 12)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE collection_name = :collection ORDER BY id DESC LIMIT :limit");

        $stmt->bindValue(':collection', $collection_name, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

$new_arrivals  = get_products_by_collection($pdo, 'new_arrivals');
$top_deals     = get_products_by_collection($pdo, 'top_deals');
$high_discount = get_products_by_collection($pdo, 'high_discount');
$top_sales     = get_products_by_collection($pdo, 'top_sales');
$top_rated     = get_products_by_collection($pdo, 'top_rated');

$is_logged_in = isset($_SESSION['user_id']) ? true : false;


// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amarshop - Website</title>

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

       /* overflow-x: hidden থাকলে sticky-top কাজ করে না */
body {
    font-family: Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow-x: hidden; /* html থেকে স্রেফ body তে রাখা হলো */
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

        /* Hero Slider */
        .sliderCls {
            max-width: 1000px !important;
            min-height: 480px;
            margin: 0 auto;
        }

        /* Category Card */
        .category-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s ease-in-out;
            background: #fff;
        }

        .category-card:hover {
            transform: translateY(-5px);
            border-color: var(--brand);
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.1);
        }

        .category-img-container {
            position: relative;
            height: 140px;
            overflow: hidden;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }

        .category-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .category-text {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            transition: 0.3s;
        }

        .category-card:hover .category-text {
            color: var(--brand);
        }


        /* Universal Product Cards */
        .product-card{
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #fff;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover{
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border-color: #dee2e6;
        }

        .product-img-container{
            position: relative;
            height: 200px;
            overflow: hidden;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .product-img-container img{
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .product-card:hover img{
            transform: scale(1.05);
        }

        /* add to card modal */
        .mwt {
            min-width: 50%;
        }

        .cart_popup {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        /* Footer */
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

        /* Responsive Breakpoints */
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
            .category-img-container {
                height: 100px;
            }

            .category-text {
                font-size: 0.85rem;
            }

            .product-img-container{
                height: 160px;
            }

            .product-title{
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>

<div class="sticky-top z-3">
    <!-- Topbar -->
    <!-- Marquee -->
    <!-- Header -->

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
                    <a href="#" class="d-flex align-items-center text-decoration-none">
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
                    <!-- Cart Icon Button -->
                    <a href="#cartOffcanvas" class="icon-btn" data-bs-toggle="offcanvas" role="button" aria-controls="cartOffcanvas" title="Cart">
                        <i class="bi bi-cart3"></i>
                        <!-- id="cartBadge" যুক্ত করা হয়েছে -->
                        <span class="badge bg-success rounded-pill" id="cartBadge">0</span>
                    </a>

                    <!-- Navbar User Icon -->
                    <?php if (!$is_logged_in): ?>
                        <!-- ১. লগইন না থাকলে: আউটলাইন আইকন & ডাইরেক্ট লগইন পেজের লিংক -->
                        <a href="auth/login.php" class="icon-btn" title="Login / Register">
                            <i class="bi bi-person"></i>
                            <span class="badge bg-danger rounded-pill">?</span>
                        </a>
                    <?php else: ?>
                        <!-- ২. লগইন থাকলে: ফিল করা আইকন & Offcanvas ট্র্রিগার বাটন -->
                        <?php
                        $user_name = trim($_SESSION['user_name'] ?? '');

                        if (!empty($user_name)) {
                            // ১. নাম থেকে প্রথম শব্দটি বের করা
                            $first_word = explode(' ', $user_name)[0];

                            // ২. প্রথম শব্দ ৩ অক্ষরের বেশি কি না চেক করা (multibyte safe string length)
                            if (mb_strlen($first_word) > 3) {
                                $display_name = mb_substr($first_word, 0, 3) . '.';
                            } else {
                                $display_name = $first_word;
                            }
                        } else {
                            $display_name = 'User';
                        }
                        ?>
                        <button class="icon-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#userProfileDrawer" aria-controls="userProfileDrawer" title="My Account">
                            <i class="bi bi-person"></i>
                            <span class="badge bg-success">
                                <?= htmlspecialchars($display_name); ?>
                            </span>
                        </button>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </header>

</div>

    <!-- Navigation Menu menu ar kaj pore dhorbo tai comment kora-->
    <!-- <nav class="navbar navbar-expand-lg bg-white border-bottom py-2">
        <div class="container-fluid px-2 px-md-3">
            <div class="custom-header-row justify-content-between align-items-center w-100">
                <button class="navbar-toggler border-0 p-0 d-lg-none icon-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <div class="collapse navbar-collapse d-none d-lg-block">
                    <ul class="navbar-nav gap-3 font-weight-medium">
                        <li class="nav-item dropdown">
                            <a class="nav-link text-dark dropdown-toggle p-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Home</a>
                            <ul class="dropdown-menu shadow-sm border-0 mt-2">
                                <li><a class="dropdown-item" href="#">Home Style 1</a></li>
                                <li><a class="dropdown-item" href="#">Home Style 2</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link text-dark dropdown-toggle p-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Shop</a>
                            <ul class="dropdown-menu shadow-sm border-0 mt-2">
                                <li><a class="dropdown-item" href="#">Shop Grid</a></li>
                                <li><a class="dropdown-item" href="#">Shop List</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark p-0" href="#">About US</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link text-dark dropdown-toggle p-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Blog</a>
                            <ul class="dropdown-menu shadow-sm border-0 mt-2">
                                <li><a class="dropdown-item" href="#">Blog Grid</a></li>
                                <li><a class="dropdown-item" href="#">Blog Details</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark p-0" href="#">Categories</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link text-dark dropdown-toggle p-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Pages</a>
                            <ul class="dropdown-menu shadow-sm border-0 mt-2">
                                <li><a class="dropdown-item" href="#">FAQ</a></li>
                                <li><a class="dropdown-item" href="#">Privacy Policy</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark p-0" href="#">Contact</a>
                        </li>
                    </ul>
                </div>

                <div class="d-flex align-items-center text-danger fw-semibold sm-text-end" style="font-size: 0.88rem; gap: 6px;">
                    <i class="bi bi-lightbulb fs-5 text-pink" style="color: #e91e63;"></i>
                    <span>Special up to 60% Off all item</span>
                </div>
            </div>
        </div>
    </nav> -->

    <!-- Offcanvas Mobile Drawer -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel" style="width: 280px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold text-success" id="mobileMenuLabel">AmarShop Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav gap-3">
                <li class="nav-item dropdown">
                    <a class="nav-link text-dark dropdown-toggle d-flex justify-content-between align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Home
                    </a>
                    <ul class="dropdown-menu border-0 bg-light mt-1 ps-2">
                        <li><a class="dropdown-item" href="#">Home Style 1</a></li>
                        <li><a class="dropdown-item" href="#">Home Style 2</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-dark dropdown-toggle d-flex justify-content-between align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Shop
                    </a>
                    <ul class="dropdown-menu border-0 bg-light mt-1 ps-2">
                        <li><a class="dropdown-item" href="#">Shop Grid</a></li>
                        <li><a class="dropdown-item" href="#">Shop List</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark" href="#">About US</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-dark dropdown-toggle d-flex justify-content-between align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Blog
                    </a>
                    <ul class="dropdown-menu border-0 bg-light mt-1 ps-2">
                        <li><a class="dropdown-item" href="#">Blog Grid</a></li>
                        <li><a class="dropdown-item" href="#">Blog Details</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark" href="#">Categories</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-dark dropdown-toggle d-flex justify-content-between align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Pages
                    </a>
                    <ul class="dropdown-menu border-0 bg-light mt-1 ps-2">
                        <li><a class="dropdown-item" href="#">FAQ</a></li>
                        <li><a class="dropdown-item" href="#">Privacy Policy</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark" href="#">Contact</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Session Alert Messages (Success, Warning, Danger) -->
    <?php if (isset($_SESSION['status']) && isset($_SESSION['message'])): ?>
        <?php
        $alert_type = 'danger';
        if ($_SESSION['status'] === 'success') {
            $alert_type = 'success';
        } elseif ($_SESSION['status'] === 'warning') {
            $alert_type = 'warning';
        }
        ?>
        <div class="container my-3">
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-0" role="alert">
                <div class="d-flex align-items-center">
                    <div>
                        <?= htmlspecialchars($_SESSION['message']); ?>
                    </div>
                </div>
                <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php
        // মেসেজ দেখানোর পর Session মুছে ফেলা হচ্ছে
        unset($_SESSION['status']);
        unset($_SESSION['message']);
        ?>
    <?php endif; ?>

    <!-- Hero Slider -->
    <div id="heroCarousel" class="carousel slide carousel-fade shadow-sm my-3" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        </div>

        <div class="carousel-inner" style="background-color: #d1d5db;">
            <!-- Slide 1 -->
            <div class="carousel-item active" data-bs-interval="5000">
                <div class="container-fluid px-2 px-md-5">
                    <div class="row align-items-center min-vh-50 py-5 py-md-0 sliderCls">
                        <div class="col-md-6 order-2 order-md-1 px-4 text-center text-md-start">
                            <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2">
                                <span class="bg-success text-white px-2 py-1 rounded fs-7 fw-semibold me-2">New</span>
                                <span class="text-muted fw-bold tracking-wide text-uppercase" style="font-size: 0.85rem;">New Collection</span>
                            </div>
                            <h1 class="display-4 fw-bold text-dark lh-sm mb-3">The Great Fashion <br class="d-none d-lg-block">Collection 2026</h1>
                            <p class="text-secondary mb-4">Up To 40% Off Final Sale Items. <br>Caught in the Moment!</p>
                            <a href="#" class="btn btn-success btn-lg px-4 py-2.5 fs-6 shadow-sm border-0 d-inline-flex align-items-center gap-2" style="background-color: var(--brand); border-radius: 4px;">
                                Show Collection <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="col-md-6 order-1 order-md-2 text-center mb-4 mb-md-0">
                            <img src="assets/uploads/model3rbg.png" class="img-fluid" alt="Fashion Collection" style="max-height: 420px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="carousel-item" data-bs-interval="5000">
                <div class="container-fluid px-2 px-md-5">
                    <div class="row align-items-center min-vh-50 py-5 py-md-0 sliderCls">
                        <div class="col-md-6 order-1 order-md-1 text-center mb-4 mb-md-0">
                            <img src="assets/uploads/model1rbg.png" class="img-fluid" alt="Trending Gadgets" style="max-height: 420px; object-fit: contain;">
                        </div>
                        <div class="col-md-6 order-2 order-md-2 px-4 text-center text-md-start">
                            <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2">
                                <span class="bg-success text-white px-2 py-1 rounded fs-7 fw-semibold me-2">Hot</span>
                                <span class="text-muted fw-bold tracking-wide text-uppercase" style="font-size: 0.85rem;">Trending Now</span>
                            </div>
                            <h1 class="display-4 fw-bold text-dark lh-sm mb-3">Exclusive Premium <br class="d-none d-lg-block">Gadgets Pack</h1>
                            <p class="text-secondary mb-4">Up To 50% Off On Modern Tech Accessories. <br>Grab It Before It's Gone!</p>
                            <a href="#" class="btn btn-success btn-lg px-4 py-2.5 fs-6 shadow-sm border-0 d-inline-flex align-items-center gap-2" style="background-color: var(--brand); border-radius: 4px;">
                                Shop Collection
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev d-none d-md-flex" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="btn btn-success rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 45px; height: 45px; background-color: var(--brand); border: none;">
                <i class="bi bi-chevron-left fs-5"></i>
            </span>
        </button>
        <button class="carousel-control-next d-none d-md-flex" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="btn btn-success rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 45px; height: 45px; background-color: var(--brand); border: none;">
                <i class="bi bi-chevron-right fs-5"></i>
            </span>
        </button>
    </div>

    <!-- Product Category Grid -->
    <!-- <div class="container-fluid px-2 px-md-5 my-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Top Categories</h2>
            <p class="text-muted">Explore our wide range of products across different categories</p>
            <div class="d-inline-block bg-success" style="width: 70px; height: 3px;"></div>
        </div>

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 g-md-4">
            <div class="col">
                <a href="#" class="text-decoration-none">
                    <div class="category-card text-center h-100 p-2">
                        <div class="category-img-container">
                            <img src="https://via.placeholder.com/150x150/f8f9fa/374151?text=Men" class="img-fluid" alt="Men's Fashion">
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <p class="category-text mb-0">Men's Fashion</p>
                            <small class="text-muted d-none d-sm-inline">240+ Items</small>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="#" class="text-decoration-none">
                    <div class="category-card text-center h-100 p-2">
                        <div class="category-img-container">
                            <img src="https://via.placeholder.com/150x150/f8f9fa/374151?text=Women" class="img-fluid" alt="Women's Fashion">
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <p class="category-text mb-0">Women's Fashion</p>
                            <small class="text-muted d-none d-sm-inline">310+ Items</small>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="#" class="text-decoration-none">
                    <div class="category-card text-center h-100 p-2">
                        <div class="category-img-container">
                            <img src="https://via.placeholder.com/150x150/f8f9fa/374151?text=Gadgets" class="img-fluid" alt="Electronics">
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <p class="category-text mb-0">Electronics</p>
                            <small class="text-muted d-none d-sm-inline">190+ Items</small>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="#" class="text-decoration-none">
                    <div class="category-card text-center h-100 p-2">
                        <div class="category-img-container">
                            <img src="https://via.placeholder.com/150x150/f8f9fa/374151?text=Mobile" class="img-fluid" alt="Mobile & Accessories">
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <p class="category-text mb-0">Smartphones</p>
                            <small class="text-muted d-none d-sm-inline">150+ Items</small>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="#" class="text-decoration-none">
                    <div class="category-card text-center h-100 p-2">
                        <div class="category-img-container">
                            <img src="https://via.placeholder.com/150x150/f8f9fa/374151?text=Home" class="img-fluid" alt="Home & Life">
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <p class="category-text mb-0">Home Decor</p>
                            <small class="text-muted d-none d-sm-inline">210+ Items</small>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="#" class="text-decoration-none">
                    <div class="category-card text-center h-100 p-2">
                        <div class="category-img-container">
                            <img src="https://via.placeholder.com/150x150/f8f9fa/374151?text=Beauty" class="img-fluid" alt="Beauty & Care">
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <p class="category-text mb-0">Beauty & Care</p>
                            <small class="text-muted d-none d-sm-inline">120+ Items</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div> -->

    <?php
    // ১. আপনার ডাটাবেজ থেকে ফেচ করা ডাটাগুলোর কালেকশন লিস্ট
    $all_collections = [
        [
            'title'         => 'New Arrivals',
            'slug'          => 'new_arrivals',
            'products'      => $new_arrivals ?? [],
            'badge'         => 'New',
            'badge_bg'      => 'bg-danger',
            'percentage_bg' => 'bg-dark' // % ব্যাজের ব্যাকগ্রাউন্ড কালার
        ],
        [
            'title'         => 'Top Deals',
            'slug'          => 'top_deals',
            'products'      => $top_deals ?? [],
            'badge'         => 'Hot Deal',
            'badge_bg'      => 'bg-warning text-dark',
            'percentage_bg' => 'bg-danger'
        ],
        [
            'title'         => 'High Discount',
            'slug'          => 'high_discount',
            'products'      => $high_discount ?? [],
            'badge'         => 'Offer',
            'badge_bg'      => 'bg-primary',
            'percentage_bg' => 'bg-danger'
        ],
        [
            'title'         => 'Top Sales',
            'slug'          => 'top_sales',
            'products'      => $top_sales ?? [],
            'badge'         => 'Best Seller',
            'badge_bg'      => 'bg-success',
            'percentage_bg' => 'bg-danger'
        ],
        [
            'title'         => 'Top Rated',
            'slug'          => 'top_rated',
            'products'      => $top_rated ?? [],
            'badge'         => 'Top Rated',
            'badge_bg'      => 'bg-warning text-dark',
            'percentage_bg' => 'bg-info text-dark'
        ],
        [
            'title'         => 'test',
            'slug'          => 'top_test',
            'products'      => $top_test ?? [],
            'badge'         => 'Top test',
            'badge_bg'      => 'bg-warning text-dark', 
            'percentage_bg' => 'bg-info text-success'
        ]
    ];
    ?>

    <?php foreach ($all_collections as $collection): ?>
        <!-- Collection Section Start -->
        <div class="container-fluid px-2 px-md-5 my-5">

            <!-- Header: Title & View All Link -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark mb-0"><?= htmlspecialchars($collection['title']); ?></h2>
                <a href="collection.php?name=<?= urlencode($collection['slug']); ?>" class="btn btn-success text-decoration-none">
                    View All
                </a>
            </div>

            <!-- Products Grid -->
            <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 py-2 row-cols-lg-4 g-3 g-md-4">
                <?php if (!empty($collection['products'])): ?>
                    <?php foreach ($collection['products'] as $product): ?>
                        <?php
                        // ১. প্রাইস ও ডিসকাউন্ট হিসেব
                        $sell_price   = (float)($product['sell_price'] ?? 0);
                        $actual_price = (float)($product['actual_price'] ?? $sell_price);

                        $has_discount = ($sell_price > $actual_price && $sell_price > 0);
                        $discount_percent = 0;
                        if ($has_discount) {
                            $discount_percent = round((($sell_price - $actual_price) / $sell_price) * 100);
                        }

                        // ২. রিভিউ ও রেটিং তথ্য
                        $rating        = (float)($product['rating'] ?? 0);
                        $total_reviews = (int)($product['total_reviews'] ?? 0);
                        ?>

                        <div class="col">
                            <div class="product-card position-relative border rounded p-2 bg-white shadow-sm h-100">

                                <!-- Product Image & Dual Badges (Left & Right) -->
                                <div class="product-img-container text-center mb-2 position-relative">

                                    <!-- বাম পাশে: কালেকশন ব্যাজ (সবসময় দেখাবে) -->
                                    <span class="product-badge <?= $collection['badge_bg']; ?> text-white position-absolute top-0 start-0 px-2 py-1 rounded small fw-bold">
                                        <?= htmlspecialchars($collection['badge']); ?>
                                    </span>

                                    <!-- ডান পাশে: % ডিসকাউন্ট ব্যাজ (ডাইনামিক কালার) -->
                                    <?php if ($discount_percent > 0): ?>
                                        <span class="product-badge <?= $collection['percentage_bg'] ?? 'bg-danger'; ?> text-white position-absolute top-0 end-0 px-2 py-1 rounded small fw-bold">
                                            <?= $discount_percent; ?>%
                                        </span>
                                    <?php endif; ?>

                                    <img src="<?= base_url('uploads/' . ($product['main_img'] ?: 'placeholder.jpg')); ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($product['name']); ?>">
                                </div>

                                <!-- Product Details -->
                                <div class="product-card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between">
                                            <p class="product-category text-muted small mb-1 d-none d-md-block text-capitalize">
                                                <?= htmlspecialchars($product['category_name'] ?? 'General'); ?>
                                            </p>
                                            <p class="product-category text-muted small mb-1 d-none d-md-block text-capitalize">
                                                <?= htmlspecialchars($product['product_code'] ?? ''); ?>
                                            </p>
                                        </div>
                                        <a href="product-details.php?id=<?= $product['id']; ?>" class="product-title text-decoration-none text-dark fw-bold d-block text-truncate">
                                            <?= htmlspecialchars($product['name']); ?>
                                        </a>

                                        <!-- ডাইনামিক রিভিউ অ্যান্ড রেটিং -->
                                        <?php if ($total_reviews > 0): ?>
                                            <div class="product-rating my-1 d-flex align-items-center gap-1">
                                                <i class="bi bi-star-fill text-warning small"></i>
                                                <span class="fw-bold small text-dark"><?= number_format($rating, 1); ?></span>
                                                <small class="text-muted" style="font-size: 0.75rem;">(<?= $total_reviews; ?>)</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-2">
                                        <!-- ডাইনামিক প্রাইস -->
                                        <div class="price-wrapper mb-2 d-flex align-items-center gap-2">
                                            <span class="current-price fw-bold text-success fs-6">
                                                ৳<?= number_format($actual_price, 2); ?>
                                            </span>
                                            <?php if ($has_discount): ?>
                                                <span class="old-price text-muted text-decoration-line-through small">
                                                    ৳<?= number_format($sell_price, 2); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex justify-content-between gap-2 mb-2 mx-2">
                                            <a href="product-details.php?id=<?= $product['id']; ?>" class="btn btn-outline-secondary w-50 btn-sm">
                                                <span class="d-none d-md-inline"><i class="bi bi-eye me-1"></i></span> View
                                            </a>

                                            <button type="button" class="btn btn-outline-success w-50 btn-sm open-options-btn">
                                                <span class="d-none d-md-inline"><i class="bi bi-cart-plus me-1"></i></span> Add <span class="d-none d-md-inline">to Cart</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Options Pop-up Overlay -->
                                <div class="card-options-overlay position-absolute bottom-0 end-0 mwt bg-white rounded p-3 shadow d-none flex-column justify-content-between cart_popup" style="z-index: 10;">

                                    <div class="d-flex justify-content-between align-items-center pb-1 border-bottom">
                                        <span class="fw-bold text-dark small">Select Options</span>
                                        <button type="button" class="btn-close close-options-btn" style="font-size: 0.7rem;"></button>
                                    </div>

                                    <!-- Dynamic Size Selection -->
                                    <?php if (!empty($product['size'])): ?>
                                        <div class="my-2">
                                            <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Size:</label>
                                            <div class="d-flex gap-1 justify-content-start flex-wrap">
                                                <?php
                                                $sizes = array_map('trim', explode(',', $product['size']));
                                                foreach ($sizes as $index => $size):
                                                    if (!empty($size)):
                                                ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success size-btn px-2 py-0 fw-bold <?= $index === 0 ? 'active' : ''; ?>">
                                                            <?= htmlspecialchars($size); ?>
                                                        </button>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Quantity Selection -->
                                    <div class="mb-2">
                                        <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Quantity (Max 6):</label>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-light border px-2 py-0 minus-btn fw-bold">-</button>
                                            <input type="text" class="form-control form-control-sm text-center p-0 qty-input fw-bold" value="1" readonly style="width: 35px; height: 28px; font-size: 0.8rem;">
                                            <button type="button" class="btn btn-sm btn-light border px-2 py-0 plus-btn fw-bold">+</button>
                                        </div>
                                    </div>

                                    <!-- Final Submit Button -->
                                    <button type="button"
                                        class="btn btn-success btn-sm w-100 fw-semibold final-add-cart-btn"
                                        data-product-id="<?= $product['id']; ?>"
                                        data-product-code="<?= htmlspecialchars($product['product_code'] ?? ''); ?>">
                                        Add to Cart
                                    </button>

                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted">No products found in <?= htmlspecialchars($collection['title']); ?>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Collection Section End -->
    <?php endforeach; ?>

    <!-- Footer Section -->
    <footer class="footer mt-5">
        <div class="container-fluid px-2 px-md-5">
            <div class="row footer-col">
                <div class="col-md-6 col-lg-4 mb-4 mb-lg-0 pr-lg-5">
                    <div class="footer-logo">
                        <img src="assets/img/logo1.png" alt="AmarShop Logo" class="rounded bg-white p-1">
                    </div>
                    <p class="mb-3">AmarShop is your one-stop online shop for the best quality products at affordable prices. Experience fast delivery and great customer support.</p>
                    <div class="footer-social-icons">
                        <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" title="Twitter"><i class="bi bi-twitter"></i></a>
                        <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" title="Youtube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2 mb-4 mb-lg-0">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>About Us</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>Contact Us</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>Shop</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>My Account</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>Order History</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>Blog</a></li>
                    </ul>
                </div>

                <div class="col-6 col-md-3 col-lg-2 mb-4 mb-lg-0">
                    <h5 class="footer-title">Customer Care</h5>
                    <ul class="list-unstyled">
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>FAQ</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>How to Order</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>Privacy Policy</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>Terms & Conditions</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>Return & Refund</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right me-1"></i>Track Order</a></li>
                    </ul>
                </div>

                <div class="col-md-6 col-lg-4">
                    <h5 class="footer-title">Contact & Payment</h5>
                    <ul class="list-unstyled text-light mb-3">
                        <li class="d-flex align-items-center mb-2">
                            <i class="bi bi-geo-alt-fill me-2 text-success"></i>
                            <span>123 Shopping Street, Dhaka, Bangladesh</span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="bi bi-telephone-fill me-2 text-success"></i>
                            <a href="tel:+8801700000000">+8801700-000000</a>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope-fill me-2 text-success"></i>
                            <a href="mailto:support@amarshop.com">support@amarshop.com</a>
                        </li>
                    </ul>
                    <div class="payment-icons pt-2">
                        <img src="https://via.placeholder.com/50x25/ffffff/000000?text=Bkash" alt="Bkash">
                        <img src="https://via.placeholder.com/50x25/ffffff/000000?text=Nagad" alt="Nagad">
                        <img src="https://via.placeholder.com/50x25/ffffff/000000?text=Rocket" alt="Rocket">
                        <img src="https://via.placeholder.com/50x25/ffffff/000000?text=Visa" alt="Visa">
                        <img src="https://via.placeholder.com/50x25/ffffff/000000?text=Master" alt="MasterCard">
                        <img src="https://via.placeholder.com/70x25/ffffff/000000?text=COD" alt="Cash on Delivery">
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom mt-4">
            <div class="container-fluid px-2 px-md-5">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                        <p class="mb-0">&copy; 2026 AmarShop. All Rights Reserved.</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <p class="mb-0">Designed & Developed by <a href="#" class="text-success fw-semibold">Developer Jasim</a></p>
                    </div>
                </div>
            </div>
        </div>
    </footer>


    <!-- Right Side Cart Offcanvas Drawer -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel" style="width: 380px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold text-dark" id="cartOffcanvasLabel">
                <i class="bi bi-bag-check text-success me-2"></i>Your Shopping Cart
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column justify-content-between p-3">
            <!-- ডিলিট মেসেজ দেখানোর জন্য অ্যালার্ট নোটিফিকেশন কন্টেইনার -->
            <div id="cartAlertMessage"></div>

            <!-- Cart Items List Container -->
            <div id="cartItemsContainer" class="flex-grow-1 overflow-auto pe-1">
                <!-- Dynamic Cart Items will be loaded here via AJAX -->
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-cart-x fs-1 opacity-50"></i>
                    <p class="mt-2 mb-0">Your cart is empty</p>
                </div>
            </div>

            <!-- Footer / Checkout Section -->
            <div class="border-top pt-3 mt-2">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-secondary">Subtotal:</span>
                    <span class="fw-bold fs-5 text-success" id="cartSubtotal">৳0.00</span>
                </div>
                <div class="d-grid gap-2">
                    <a href="cart.php" class="btn btn-outline-success fw-semibold">View Full Cart</a>
                    <a href="checkout.php" class="btn btn-success fw-semibold">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    </div>


    <?php if ($is_logged_in): ?>
        <!-- Profile Offcanvas Drawer -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="userProfileDrawer" aria-labelledby="userProfileDrawerLabel" style="width: 330px;">

            <!-- Drawer Header -->
            <div class="offcanvas-header bg-light border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; font-size: 1.2rem; font-weight: bold;">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div>
                        <h6 class="offcanvas-title fw-bold mb-0" id="userProfileDrawerLabel">
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'My Account'); ?>
                        </h6>
                        <small class="text-muted"><?= htmlspecialchars($_SESSION['user_email'] ?? 'Welcome back!'); ?></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>

            <!-- Drawer Menu Items -->
            <div class="offcanvas-body p-0">
                <div class="list-group list-group-flush">

                    <a href="user/profile.php" class="list-group-item list-group-item-action py-3 px-4 border-0 d-flex align-items-center">
                        <i class="bi bi-person-vcard text-primary me-3 fs-5"></i>
                        <span class="fw-semibold text-dark">My Profile</span>
                    </a>

                    <a href="user/my_orders.php" class="list-group-item list-group-item-action py-3 px-4 border-0 d-flex align-items-center">
                        <i class="bi bi-box-seam text-warning me-3 fs-5"></i>
                        <span class="fw-semibold text-dark">My Orders</span>
                    </a>

                    <a href="user/addresses.php" class="list-group-item list-group-item-action py-3 px-4 border-0 d-flex align-items-center">
                        <i class="bi bi-geo-alt text-danger me-3 fs-5"></i>
                        <span class="fw-semibold text-dark">Saved Addresses</span>
                    </a>

                    <a href="user/transactions.php" class="list-group-item list-group-item-action py-3 px-4 border-0 d-flex align-items-center">
                        <i class="bi bi-receipt text-info me-3 fs-5"></i>
                        <span class="fw-semibold text-dark">Transaction History</span>
                    </a>

                    <hr class="my-2">

                    <a href="auth/logout.php" class="list-group-item list-group-item-action py-3 px-4 border-0 d-flex align-items-center text-danger">
                        <i class="bi bi-box-arrow-right me-3 fs-5"></i>
                        <span class="fw-bold">Logout</span>
                    </a>

                </div>
            </div>
        </div>
    <?php endif; ?>


    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const isLoggedIn = <?= $is_logged_in; ?>;

        // ১. ডাটাবেজ থেকে ইউনিক আইটেম কাউন্ট আপডেট
        function updateCartBadge() {
            fetch('core/get_cart_count.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const badge = document.getElementById('cartBadge');
                        if (badge) badge.innerText = data.cart_count;
                    }
                })
                .catch(err => console.error('Error updating badge:', err));
        }

        // ২. Offcanvas কার্ট ড্রয়ারে বিস্তারিত ডাটা লোড (নতুন লেআউটসহ)
        function loadCartDetails() {
            fetch('core/get_cart_data.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('cartItemsContainer');
                    const subtotalEl = document.getElementById('cartSubtotal');

                    updateCartBadge();
                    if (subtotalEl) subtotalEl.innerText = '৳' + data.subtotal;

                    if (!data.items || data.items.length === 0) {
                        container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-cart-x fs-1 opacity-50"></i>
                        <p class="mt-2 mb-0">Your cart is empty</p>
                    </div>`;
                        return;
                    }

                    let html = '';
                    data.items.forEach(item => {
                        const itemTotal = (item.price * item.quantity).toFixed(2);

                        // ২ নম্বর রোর জন্য সাইজ ড্রপডাউন
                        let sizeSelectHtml = '';
                        if (item.available_sizes) {
                            const sizes = item.available_sizes.split(',').map(s => s.trim());
                            sizeSelectHtml = `<select class="form-select form-select-sm py-0 ps-2 pe-4" style="font-size: 0.75rem; width: auto;" onchange="updateCartSize(${item.cart_id}, this.value)">`;
                            sizes.forEach(sz => {
                                const selected = (sz === item.selected_size) ? 'selected' : '';
                                sizeSelectHtml += `<option value="${sz}" ${selected}>Size: ${sz}</option>`;
                            });
                            sizeSelectHtml += `</select>`;
                        }

                        html += `
                    <div class="d-flex align-items-center justify-content-between border-bottom py-3">
                        <!-- Left: Image -->
                        <img src="uploads/${item.main_img}" class="rounded border" style="width: 65px; height: 65px; object-fit: contain;">
                        
                        <!-- Middle: Details Stack (3 Rows Layout) -->
                        <div class="flex-grow-1 px-3">
                            <!-- Row 1: Product Name -->
                            <h6 class="mb-1 text-truncate fw-semibold" style="max-width: 170px; font-size: 0.85rem;" title="${item.name}">${item.name}</h6>
                            
                            <!-- Row 2: Size & Quantity Controls -->
                            <div class="d-flex align-items-center gap-2 my-1">
                                ${sizeSelectHtml}
                                <div class="input-group input-group-sm" style="width: 75px;">
                                    <button class="btn btn-outline-secondary px-2 py-0" type="button" onclick="updateCartQuantity(${item.cart_id}, 'decrease')">-</button>
                                    <input type="text" class="form-control text-center p-0 fw-bold" value="${item.quantity}" readonly style="font-size: 0.75rem;">
                                    <button class="btn btn-outline-secondary px-2 py-0" type="button" onclick="updateCartQuantity(${item.cart_id}, 'increase')">+</button>
                                </div>
                            </div>
                            
                            <!-- Row 3: Total (Price * Quantity) -->
                            <div class="mt-1" style="font-size: 0.8rem;">
                                <span class="fw-bold text-success">Total: ৳${itemTotal}</span>
                                <small class="text-muted ms-1">(৳${item.price} × ${item.quantity})</small>
                            </div>
                        </div>

                        <!-- Right: Delete Icon Button -->
                        <div>
                            <button type="button" class="btn btn-sm text-danger p-1" onclick="removeCartItem(${item.cart_id})" title="Remove Item">
                                <i class="bi bi-trash fs-5"></i>
                            </button>
                        </div>
                    </div>`;
                    });

                    container.innerHTML = html;
                })
                .catch(err => console.error('Error loading cart details:', err));
        }

        // ৩. Quantity Update
        function updateCartQuantity(cartId, action) {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('action', action);

            fetch('core/update_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') loadCartDetails();
                });
        }

        // ৪. Size Update
        function updateCartSize(cartId, newSize) {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('action', 'update_size');
            formData.append('size', newSize);

            fetch('update_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') loadCartDetails();
                });
        }


        // ১. অ্যালার্ট মেসেজ দেখানোর কাস্টম ফংশন
        function showCartAlert(message, type = 'success') {
            const alertBox = document.getElementById('cartAlertMessage');
            if (alertBox) {
                alertBox.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show py-2 px-3 mb-3 text-center" style="font-size: 0.85rem;" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> ${message}
                <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

                // ৩ সেকেন্ড পর অটোমেটিক মেসেজ গায়েব হয়ে যাবে
                setTimeout(() => {
                    const currentAlert = alertBox.querySelector('.alert');
                    if (currentAlert) {
                        currentAlert.classList.remove('show');
                        setTimeout(() => alertBox.innerHTML = '', 150);
                    }
                }, 3000);
            }
        }

        // ২. আপডেটেড ডিলিট ফংশন
        function removeCartItem(cartId) {
            if (confirm("Are you sure you want to remove this item?")) {
                const formData = new FormData();
                formData.append('cart_id', cartId);

                fetch('core/remove_cart_item.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // কার্ট লিস্ট ও ব্যাজ রিফ্রেশ করা
                            loadCartDetails();
                            updateCartBadge();

                            // Offcanvas-এর একদম উপরে মেসেজ শো করা
                            showCartAlert("Item successfully removed from cart!", "danger");
                        } else {
                            showCartAlert(data.message || "Failed to remove item", "warning");
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showCartAlert("Something went wrong!", "danger");
                    });
            }
        }
        // DOM Ready Events
        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();

            const cartOffcanvasEl = document.getElementById('cartOffcanvas');
            if (cartOffcanvasEl) {
                cartOffcanvasEl.addEventListener('show.bs.offcanvas', loadCartDetails);
            }

            // Open Popup & Auth Check
            document.querySelectorAll('.open-options-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();

                    if (!isLoggedIn) {
                        window.location.href = 'auth/login.php?status=warning&message=' + encodeURIComponent('Login first then add to cart');
                        return;
                    }

                    const overlay = this.closest('.position-relative').querySelector('.card-options-overlay');
                    document.querySelectorAll('.card-options-overlay').forEach(el => {
                        el.classList.add('d-none');
                        el.classList.remove('d-flex');
                    });

                    overlay.classList.remove('d-none');
                    overlay.classList.add('d-flex');
                });
            });

            // Close Popup
            document.querySelectorAll('.close-options-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const overlay = this.closest('.card-options-overlay');
                    overlay.classList.add('d-none');
                    overlay.classList.remove('d-flex');
                });
            });

            // Card Overlay Size & Quantity Handlers
            document.querySelectorAll('.card-options-overlay').forEach(overlay => {
                const sizeBtns = overlay.querySelectorAll('.size-btn');
                sizeBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        sizeBtns.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                    });
                });

                const minusBtn = overlay.querySelector('.minus-btn');
                const plusBtn = overlay.querySelector('.plus-btn');
                const qtyInput = overlay.querySelector('.qty-input');

                if (minusBtn && plusBtn && qtyInput) {
                    minusBtn.addEventListener('click', () => {
                        let val = parseInt(qtyInput.value);
                        if (val > 1) qtyInput.value = val - 1;
                    });

                    plusBtn.addEventListener('click', () => {
                        let val = parseInt(qtyInput.value);
                        if (val < 6) qtyInput.value = val + 1;
                    });
                }
            });

            // Add To Cart Final Click
            document.querySelectorAll('.final-add-cart-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const overlay = this.closest('.card-options-overlay');
                    const productId = this.getAttribute('data-product-id');
                    const productCode = this.getAttribute('data-product-code') || '';
                    const selectedSizeBtn = overlay.querySelector('.size-btn.active');
                    const size = selectedSizeBtn ? selectedSizeBtn.innerText.trim() : '';
                    const quantity = overlay.querySelector('.qty-input').value;

                    const formData = new FormData();
                    formData.append('product_id', productId);
                    formData.append('product_code', productCode);
                    formData.append('size', size);
                    formData.append('quantity', quantity);

                    fetch('core/add_to_cart.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.status === 'success') {
                                updateCartBadge();
                                overlay.classList.add('d-none');
                                overlay.classList.remove('d-flex');
                            }
                        });
                });
            });

            // Outside Click handler
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.card-options-overlay') && !e.target.closest('.open-options-btn')) {
                    document.querySelectorAll('.card-options-overlay').forEach(el => {
                        el.classList.add('d-none');
                        el.classList.remove('d-flex');
                    });
                }
            });
        });
    </script>
</body>

</html>