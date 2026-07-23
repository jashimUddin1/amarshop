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
        .product-card,
        .top-deals-card,
        .high-discount-card,
        .top-sales-card,
        .top-review-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #fff;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover,
        .top-deals-card:hover,
        .high-discount-card:hover,
        .top-sales-card:hover,
        .top-review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border-color: #dee2e6;
        }

        .product-img-container,
        .top-deals-img-container,
        .high-discount-img-container,
        .top-sales-img-container,
        .top-review-img-container {
            position: relative;
            height: 200px;
            overflow: hidden;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .product-img-container img,
        .top-deals-img-container img,
        .high-discount-img-container img,
        .top-sales-img-container img,
        .top-review-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .product-card:hover img,
        .top-deals-card:hover img,
        .high-discount-card:hover img,
        .top-sales-card:hover img,
        .top-review-card:hover img {
            transform: scale(1.05);
        }

        /* Badges */
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--brand);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 1;
        }

        .top-deals-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #dc3545;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 1;
        }

        .high-discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #f57c00;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            z-index: 1;
        }

        .top-sales-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #28a745;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 1;
        }

        .top-review-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #f5c71a;
            color: #333;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 1;
        }

        /* Card Content Body */
        .product-card-body,
        .top-deals-card-body,
        .high-discount-card-body,
        .top-sales-card-body,
        .top-review-card-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-category,
        .top-deals-category,
        .high-discount-category,
        .top-sales-category,
        .top-review-category {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .product-title,
        .top-deals-title,
        .high-discount-title,
        .top-sales-title,
        .top-review-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            margin-bottom: 10px;
            display: -webkit-box;
            line-clamp: 2;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.3s;
        }

        .product-title:hover,
        .top-deals-title:hover,
        .high-discount-title:hover,
        .top-sales-title:hover,
        .top-review-title:hover {
            color: var(--brand);
        }

        /* Pricing & Buttons */
        .price-wrapper,
        .top-deals-price-wrapper,
        .high-discount-price-wrapper,
        .top-sales-price-wrapper,
        .top-review-price-wrapper {
            margin-bottom: 15px;
        }

        .current-price,
        .top-deals-current-price,
        .high-discount-current-price,
        .top-sales-current-price,
        .top-review-current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand);
            margin-right: 5px;
        }

        .old-price,
        .top-deals-old-price,
        .high-discount-old-price,
        .top-sales-old-price {
            font-size: 0.9rem;
            color: #adb5bd;
            text-decoration: line-through;
        }

        .btn-add-cart,
        .btn-top-deals-add-cart,
        .btn-high-discount-add-cart,
        .btn-top-sales-add-cart,
        .btn-top-review-add-cart {
            width: 100%;
            background-color: transparent;
            color: var(--brand);
            border: 1px solid var(--brand);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .btn-add-cart:hover,
        .btn-top-deals-add-cart:hover,
        .btn-high-discount-add-cart:hover,
        .btn-top-sales-add-cart:hover,
        .btn-top-review-add-cart:hover {
            background-color: var(--brand);
            color: #fff;
        }

        /* Ratings */
        .rating-wrapper {
            margin-bottom: 10px;
            color: #f5c71a;
            font-size: 0.9rem;
        }

        .review-count {
            color: var(--muted);
            font-size: 0.8rem;
            margin-left: 5px;
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

            .product-img-container,
            .top-deals-img-container,
            .high-discount-img-container,
            .top-sales-img-container,
            .top-review-img-container {
                height: 160px;
            }

            .product-title,
            .top-deals-title,
            .high-discount-title,
            .top-sales-title,
            .top-review-title {
                font-size: 0.85rem;
            }

            .current-price,
            .top-deals-current-price,
            .high-discount-current-price,
            .top-sales-current-price,
            .top-review-current-price {
                font-size: 1rem;
            }
        }

        /* Quick View Button Style */
        .btn-quick-view {
            width: 100%;
            background-color: #f8f9fa;
            color: var(--text);
            border: 1px solid var(--border);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .btn-quick-view:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #000;
        }
    </style>
</head>

<body>

    <!-- Topbar & Announcement -->
    <div>
        <div class="topbar py-1">
            <div class="container d-flex justify-content-between align-items-center">
                <small>Welcome to Amarshop</small>
                <small>Free delivery on orders over $50</small>
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
                                <option>Electronics</option>
                                <option>Fashion</option>
                                <option>Mobile</option>
                                <option>Books</option>
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
                    <a href="#" class="icon-btn" title="Cart">
                        <i class="bi bi-cart3"></i>
                        <span class="badge bg-danger rounded-pill">3</span>
                    </a>
                    <a href="#" class="icon-btn" title="Profile">
                        <i class="bi bi-person"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom py-2">
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
    </nav>

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
                                Shop Collection <i class="bi bi-arrow-right"></i>
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
    <div class="container-fluid px-2 px-md-5 my-5">
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
    </div>

    <!-- New Arrivals Section -->
    <div class="container-fluid px-2 px-md-5 my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">New Arrivals</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <div class="col">
                <div class="product-card">
                    <div class="product-img-container">
                        <span class="product-badge">New</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Product+1" class="img-fluid" alt="Men's Casual Shirt">
                    </div>
                    <div class="product-card-body">
                        <div>
                            <p class="product-category mb-1">Men's Fashion</p>
                            <a href="#" class="product-title">Men's Premium Casual Shirt - Dark Green</a>
                        </div>
                        <div>
                            <div class="price-wrapper mb-2">
                                <span class="current-price">$35.00</span>
                                <span class="old-price">$45.00</span>
                            </div>
                            <div class="d-flex">
            <a href="product-details.html" class="btn btn-quick-view" title="Quick View">
    <i class="bi bi-eye me-1"></i> View
</a>&nbsp;
                            <button class="btn btn-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="product-card">
                    <div class="product-img-container">
                        <span class="product-badge">New</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Product+2" class="img-fluid" alt="Women's Bag">
                    </div>
                    <div class="product-card-body">
                        <div>
                            <p class="product-category mb-1">Accessories</p>
                            <a href="#" class="product-title">Elegant Women's Leather Handbag - Black</a>
                        </div>
                        <div>
                            <div class="price-wrapper mb-2">
                                <span class="current-price">$60.00</span>
                            </div>
                            <button class="btn btn-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="product-card">
                    <div class="product-img-container">
                        <span class="product-badge">New</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Product+3" class="img-fluid" alt="Wireless Earbuds">
                    </div>
                    <div class="product-card-body">
                        <div>
                            <p class="product-category mb-1">Electronics</p>
                            <a href="#" class="product-title">True Wireless Earbuds - Bluetooth 5.3</a>
                        </div>
                        <div>
                            <div class="price-wrapper mb-2">
                                <span class="current-price">$28.00</span>
                                <span class="old-price">$32.00</span>
                            </div>
                            <button class="btn btn-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="product-card">
                    <div class="product-img-container">
                        <span class="product-badge">New</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Product+4" class="img-fluid" alt="Running Shoes">
                    </div>
                    <div class="product-card-body">
                        <div>
                            <p class="product-category mb-1">Men's Fashion</p>
                            <a href="#" class="product-title">Sport Running Shoes - Lightweight</a>
                        </div>
                        <div>
                            <div class="price-wrapper mb-2">
                                <span class="current-price">$45.00</span>
                            </div>
                            <button class="btn btn-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Deals Section -->
    <div class="container-fluid px-2 px-md-5 my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">Top Deals</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <div class="col">
                <div class="top-deals-card">
                    <div class="top-deals-img-container">
                        <span class="top-deals-badge">Hot Deal</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Deal+1" class="img-fluid" alt="Men's Running Shoes">
                    </div>
                    <div class="top-deals-card-body">
                        <div>
                            <p class="top-deals-category mb-1">Footwear</p>
                            <a href="#" class="top-deals-title">Men's Lightweight Running Shoes - Blue</a>
                        </div>
                        <div>
                            <div class="top-deals-price-wrapper mb-2">
                                <span class="top-deals-current-price">$40.00</span>
                                <span class="top-deals-old-price">$60.00</span>
                            </div>
                            <button class="btn-top-deals-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-deals-card">
                    <div class="top-deals-img-container">
                        <span class="top-deals-badge">Hot Deal</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Deal+2" class="img-fluid" alt="Smartphone">
                    </div>
                    <div class="top-deals-card-body">
                        <div>
                            <p class="top-deals-category mb-1">Electronics</p>
                            <a href="#" class="top-deals-title">Latest Smartphone - 128GB, 8GB RAM</a>
                        </div>
                        <div>
                            <div class="top-deals-price-wrapper mb-2">
                                <span class="top-deals-current-price">$300.00</span>
                                <span class="top-deals-old-price">$350.00</span>
                            </div>
                            <button class="btn-top-deals-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-deals-card">
                    <div class="top-deals-img-container">
                        <span class="top-deals-badge">Hot Deal</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Deal+3" class="img-fluid" alt="Men's Casual T-Shirt">
                    </div>
                    <div class="top-deals-card-body">
                        <div>
                            <p class="top-deals-category mb-1">Apparel</p>
                            <a href="#" class="top-deals-title">Men's Premium Casual T-Shirt - Black</a>
                        </div>
                        <div>
                            <div class="top-deals-price-wrapper mb-2">
                                <span class="top-deals-current-price">$18.00</span>
                                <span class="top-deals-old-price">$25.00</span>
                            </div>
                            <button class="btn-top-deals-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-deals-card">
                    <div class="top-deals-img-container">
                        <span class="top-deals-badge">Hot Deal</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Deal+4" class="img-fluid" alt="Coffee Maker">
                    </div>
                    <div class="top-deals-card-body">
                        <div>
                            <p class="top-deals-category mb-1">Home Appliances</p>
                            <a href="#" class="top-deals-title">Automatic Coffee Maker - Programmable</a>
                        </div>
                        <div>
                            <div class="top-deals-price-wrapper mb-2">
                                <span class="top-deals-current-price">$75.00</span>
                                <span class="top-deals-old-price">$100.00</span>
                            </div>
                            <button class="btn-top-deals-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- High Discount Section -->
    <div class="container-fluid px-2 px-md-5 my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">High Discount Products</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <div class="col">
                <div class="high-discount-card">
                    <div class="high-discount-img-container">
                        <span class="high-discount-badge">50% OFF</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Discounted+1" class="img-fluid" alt="Headphones">
                    </div>
                    <div class="high-discount-card-body">
                        <div>
                            <p class="high-discount-category mb-1">Electronics</p>
                            <a href="#" class="high-discount-title">Wireless Over-Ear Headphones - Noise Cancelling</a>
                        </div>
                        <div>
                            <div class="high-discount-price-wrapper mb-2">
                                <span class="high-discount-current-price">$50.00</span>
                                <span class="high-discount-old-price">$100.00</span>
                            </div>
                            <button class="btn-high-discount-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="high-discount-card">
                    <div class="high-discount-img-container">
                        <span class="high-discount-badge">40% OFF</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Discounted+2" class="img-fluid" alt="Digital Watch">
                    </div>
                    <div class="high-discount-card-body">
                        <div>
                            <p class="high-discount-category mb-1">Accessories</p>
                            <a href="#" class="high-discount-title">Digital Watch - Water Resistant, Fitness Tracker</a>
                        </div>
                        <div>
                            <div class="high-discount-price-wrapper mb-2">
                                <span class="high-discount-current-price">$30.00</span>
                                <span class="high-discount-old-price">$50.00</span>
                            </div>
                            <button class="btn-high-discount-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="high-discount-card">
                    <div class="high-discount-img-container">
                        <span class="high-discount-badge">60% OFF</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Discounted+3" class="img-fluid" alt="Women's Dress">
                    </div>
                    <div class="high-discount-card-body">
                        <div>
                            <p class="high-discount-category mb-1">Women's Fashion</p>
                            <a href="#" class="high-discount-title">Women's Summer Dress - Cotton, Stylish</a>
                        </div>
                        <div>
                            <div class="high-discount-price-wrapper mb-2">
                                <span class="high-discount-current-price">$20.00</span>
                                <span class="high-discount-old-price">$50.00</span>
                            </div>
                            <button class="btn-high-discount-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="high-discount-card">
                    <div class="high-discount-img-container">
                        <span class="high-discount-badge">30% OFF</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Discounted+4" class="img-fluid" alt="Laptop Bag">
                    </div>
                    <div class="high-discount-card-body">
                        <div>
                            <p class="high-discount-category mb-1">Accessories</p>
                            <a href="#" class="high-discount-title">Premium Laptop Bag - Water Repellent, Durable</a>
                        </div>
                        <div>
                            <div class="high-discount-price-wrapper mb-2">
                                <span class="high-discount-current-price">$35.00</span>
                                <span class="high-discount-old-price">$50.00</span>
                            </div>
                            <button class="btn-high-discount-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Sales Section -->
    <div class="container-fluid px-2 px-md-5 my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">Top Sales</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <div class="col">
                <div class="top-sales-card">
                    <div class="top-sales-img-container">
                        <span class="top-sales-badge">Best Seller</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Sales+1" class="img-fluid" alt="Men's Casual Shirt">
                    </div>
                    <div class="top-sales-card-body">
                        <div>
                            <p class="top-sales-category mb-1">Men's Fashion</p>
                            <a href="#" class="top-sales-title">Men's Premium Casual Shirt - Dark Green</a>
                        </div>
                        <div>
                            <div class="top-sales-price-wrapper mb-2">
                                <span class="top-sales-current-price">$35.00</span>
                                <span class="top-sales-old-price">$45.00</span>
                            </div>
                            <button class="btn-top-sales-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-sales-card">
                    <div class="top-sales-img-container">
                        <span class="top-sales-badge">Best Seller</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Sales+2" class="img-fluid" alt="Women's Bag">
                    </div>
                    <div class="top-sales-card-body">
                        <div>
                            <p class="top-sales-category mb-1">Accessories</p>
                            <a href="#" class="top-sales-title">Elegant Women's Leather Handbag - Black</a>
                        </div>
                        <div>
                            <div class="top-sales-price-wrapper mb-2">
                                <span class="top-sales-current-price">$60.00</span>
                            </div>
                            <button class="btn-top-sales-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-sales-card">
                    <div class="top-sales-img-container">
                        <span class="top-sales-badge">Best Seller</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Sales+3" class="img-fluid" alt="Wireless Earbuds">
                    </div>
                    <div class="top-sales-card-body">
                        <div>
                            <p class="top-sales-category mb-1">Electronics</p>
                            <a href="#" class="top-sales-title">True Wireless Earbuds - Bluetooth 5.3</a>
                        </div>
                        <div>
                            <div class="top-sales-price-wrapper mb-2">
                                <span class="top-sales-current-price">$28.00</span>
                                <span class="top-sales-old-price">$32.00</span>
                            </div>
                            <button class="btn-top-sales-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-sales-card">
                    <div class="top-sales-img-container">
                        <span class="top-sales-badge">Best Seller</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Sales+4" class="img-fluid" alt="Running Shoes">
                    </div>
                    <div class="top-sales-card-body">
                        <div>
                            <p class="top-sales-category mb-1">Men's Fashion</p>
                            <a href="#" class="top-sales-title">Sport Running Shoes - Lightweight</a>
                        </div>
                        <div>
                            <div class="top-sales-price-wrapper mb-2">
                                <span class="top-sales-current-price">$45.00</span>
                            </div>
                            <button class="btn-top-sales-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Review Products Section -->
    <div class="container-fluid px-2 px-md-5 my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">Top Rated Products</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <div class="col">
                <div class="top-review-card">
                    <div class="top-review-img-container">
                        <span class="top-review-badge">Top Rated</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Rated+1" class="img-fluid" alt="Wireless Headphones">
                    </div>
                    <div class="top-review-card-body">
                        <div>
                            <p class="top-review-category mb-1">Electronics</p>
                            <a href="#" class="top-review-title">Wireless Over-Ear Headphones - Noise Cancelling</a>
                            <div class="rating-wrapper">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <span class="review-count">(120 Reviews)</span>
                            </div>
                        </div>
                        <div>
                            <div class="top-review-price-wrapper mb-2">
                                <span class="top-review-current-price">$80.00</span>
                            </div>
                            <button class="btn-top-review-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-review-card">
                    <div class="top-review-img-container">
                        <span class="top-review-badge">Top Rated</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Rated+2" class="img-fluid" alt="Digital Watch">
                    </div>
                    <div class="top-review-card-body">
                        <div>
                            <p class="top-review-category mb-1">Accessories</p>
                            <a href="#" class="top-review-title">Digital Watch - Water Resistant, Fitness Tracker</a>
                            <div class="rating-wrapper">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-half"></i>
                                <span class="review-count">(85 Reviews)</span>
                            </div>
                        </div>
                        <div>
                            <div class="top-review-price-wrapper mb-2">
                                <span class="top-review-current-price">$35.00</span>
                            </div>
                            <button class="btn-top-review-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-review-card">
                    <div class="top-review-img-container">
                        <span class="top-review-badge">Top Rated</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Rated+3" class="img-fluid" alt="Women's Sneakers">
                    </div>
                    <div class="top-review-card-body">
                        <div>
                            <p class="top-review-category mb-1">Women's Fashion</p>
                            <a href="#" class="top-review-title">Women's Stylish Sneakers - Casual Wear</a>
                            <div class="rating-wrapper">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <span class="review-count">(150 Reviews)</span>
                            </div>
                        </div>
                        <div>
                            <div class="top-review-price-wrapper mb-2">
                                <span class="top-review-current-price">$55.00</span>
                            </div>
                            <button class="btn-top-review-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="top-review-card">
                    <div class="top-review-img-container">
                        <span class="top-review-badge">Top Rated</span>
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Top+Rated+4" class="img-fluid" alt="Laptop Bag">
                    </div>
                    <div class="top-review-card-body">
                        <div>
                            <p class="top-review-category mb-1">Accessories</p>
                            <a href="#" class="top-review-title">Premium Laptop Bag - Water Repellent, Durable</a>
                            <div class="rating-wrapper">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <span class="review-count">(110 Reviews)</span>
                            </div>
                        </div>
                        <div>
                            <div class="top-review-price-wrapper mb-2">
                                <span class="top-review-current-price">$45.00</span>
                            </div>
                            <button class="btn-top-review-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <p class="mb-0">Designed & Developed by <a href="#" class="text-success fw-semibold">Your Name</a></p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>