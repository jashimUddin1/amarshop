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