<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amarshop - Website</title>
    <!-- Bootstrap 5 CSS CDN  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!--for Bootstrap Icons  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body>
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

        @media (max-width: 768px) {
            .logo-area img{
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

        .sliderCls {
            max-width: 1300px !important;
            min-height: 480px;
            margin: auto;
            margin-left: 250px;
        }

        @media(max-width:768px) {
            .sliderCls {
                margin-left: 0px;
            }
        }
    </style>

    <div>
        <!-- Topbar -->
        <div class="topbar py-1">
            <div class="container d-flex justify-content-between align-items-center">
                <small>Welcome to Amarshop</small>
                <small>Free delivery on orders over $50</small>
            </div>
        </div>

        <!-- Marquee -->
        <div class="marquee-wrap py-1">
            <div class="marquee">
                <span>✨ New Arrivals Every Week</span>
                <span>🔥 Flash Sale - Up to 50% Off</span>
                <span>🚚 Fast Delivery Across the City</span>
                <span>💳 Secure Online Payment</span>
            </div>
        </div>
    </div>

    <!-- Header (Mobile & Desktop Same Same Row Layout) -->
    <header class="header bg-white shadow-sm">
        <div class="container-fluid px-2 px-md-3">
            <div class="custom-header-row justify-content-between">

                <!-- 1. Logo + Name -->
                <div class="logo-area">
                    <a href="#" class="d-flex align-items-center text-decoration-none">
                        <img src="assets/img/logo1.png" class="me-1 me-md-2 rounded" alt="Logo">
                        <!-- <div class="logo-text">
                            <h4 class="fw-bold text-success">AmarShop</h4>
                            <small class="text-muted d-none d-sm-block">Online Shopping</small>
                        </div> -->
                    </a>
                </div>

                <!-- 2. Category Dropdown + Search Box + 🔍 Button -->
                <div class="search-area">
                    <form>
                        <div class="input-group-custom">
                            <!-- Category Select -->
                            <select class="form-select-custom">
                                <option selected>Category</option>
                                <option>Electronics</option>
                                <option>Fashion</option>
                                <option>Mobile</option>
                                <option>Books</option>
                            </select>
                            <!-- Search Input -->
                            <input type="text" class="form-control-custom" placeholder="Search...">
                            <!-- Search Button (icon only on mobile, text on desktop) -->
                            <button class="btn btn-success btn-custom" type="submit">
                                <i class="bi bi-search d-inline d-md-none"></i>
                                <span class="d-none d-md-inline">Search</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. Icons (Cart & User) -->
                <div class="icons-area">
                    <!-- Cart Icon -->
                    <a href="#" class="icon-btn" title="Cart">
                        <i class="bi bi-cart3"></i>
                        <span class="badge bg-danger rounded-pill">3</span>
                    </a>
                    <!-- Profile Icon -->
                    <a href="#" class="icon-btn" title="Profile">
                        <i class="bi bi-person"></i>
                    </a>
                </div>

            </div>
        </div>
    </header>


    <!-- ================= Navbar / Menu Section Start ================= -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom py-2">
        <div class="container-fluid px-2 px-md-3">
            <div class="custom-header-row justify-content-between align-items-center w-100">

                <!-- মোবাইল স্ক্রিনের জন্য হ্যামবার্গার বাটন (বাম পাশে) -->
                <button class="navbar-toggler border-0 p-0 d-lg-none icon-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <!-- ডেক্সটপ মেনু (বড় স্ক্রিনে দেখাবে, মোবাইলে হাইড থাকবে) -->
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

                <!-- ডান পাশের স্পেশাল অফার টেক্সট (মোবাইলেও সুন্দর দেখানোর জন্য টেক্সট ছোট করা হয়েছে) -->
                <div class="d-flex align-items-center text-danger fw-semibold sm-text-end" style="font-size: 0.88rem; gap: 6px;">
                    <i class="bi bi-lightbulb fs-5 text-pink" style="color: #e91e63;"></i>
                    <span>Special up to 60% Off all item</span>
                </div>

            </div>
        </div>
    </nav>

    <!-- মোবাইল অফক্যানভাস ড্রয়ার (মোবাইলে ক্লিক করলে বাম দিক থেকে আসবে) -->
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
    <!-- ================= Navbar / Menu Section End ================= -->


    <!-- ================= Slider Section Start ================= -->
    <div id="heroCarousel" class="carousel slide carousel-fade shadow-sm my-3" data-bs-ride="carousel">

        <!-- স্লাইডারের ইন্ডিকেটর ফোটাগুলো (ঐচ্ছিক) -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        </div>

        <div class="carousel-inner" style="background-color: #d1d5db;">

            <!-- স্লাইড ১ -->
            <div class="carousel-item active" data-bs-interval="5000">
                <div class="container-fluid px-2 px-md-5">
                    <div class="row align-items-center min-vh-50 py-5 py-md-0 sliderCls" style="min-height: 480px;">
                        <!-- বাম পাশের টেক্সট কন্টেন্ট -->
                        <div
                            iv class="col-md-6 order-2 order-md-1 px-4 text-center text-md-start">
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
                        <!-- ডান পাশের ইমেজ (প্লেসহোল্ডার দিয়ে রাখা হলো, আপনি আপনার ইমেজ বসিয়ে নিবেন) -->
                        <div class="col-md-6 order-1 order-md-2 text-center mb-4 mb-md-0">
                            <img src="assets/uploads/model3rbg.png" class="img-fluid" alt="Fashion Collection" style="max-height: 420px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- স্লাইড ২ -->
            <div class="carousel-item" data-bs-interval="5000">
                <div class="container-fluid px-2 px-md-5">
                    <div class="row align-items-center min-vh-50 py-5 py-md-0 " style="min-height: 480px;">

                        <!-- পরিবর্তন: order-md-1 এবং order-1 ছবিকে প্রথম ক্রমে রাখবে -->
                        <div class="col-md-6 order-1 order-md-1 text-center mb-4 mb-md-0">
                            <img src="assets/uploads/model1rbg.png" class="img-fluid" alt="Trending Gadgets" style="max-height: 420px; object-fit: contain;">
                        </div>

                        <!-- পরিবর্তন: order-md-2 এবং order-2 টেক্সটকে দ্বিতীয় ক্রমে রাখবে -->
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

        <!-- স্লাইডার কন্ট্রোল নেভিগেশন বাটন (বাম ও ডান) -->
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
    <!-- ================= Slider Section End ================= -->



    <!-- ================= Product Category Grid Start ================= -->
    <style>
        /* এই সেকশনের জন্য বিশেষ কিছু স্টাইল */
        .category-card {
            border: 1px solid #e9ecef;
            /* হালকা বর্ডার */
            border-radius: 12px;
            /* একটু গোলকার কোণা */
            transition: all 0.3s ease-in-out;
            /* অ্যানিমেশন এফেক্ট */
            background: #fff;
            /* সাদা ব্যাকগ্রাউন্ড */
        }

        /* কার্ডের ওপর মাউস নিলে যা হবে */
        .category-card:hover {
            transform: translateY(-5px);
            /* একটু ওপরের দিকে উঠবে */
            border-color: var(--brand);
            /* বর্ডার কালার গ্রিন হবে */
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.1);
            /* হালকা শ্যাডো আসবে */
        }

        .category-img-container {
            position: relative;
            height: 140px;
            /* ইমেজের ফিক্সড হাইট */
            overflow: hidden;
            border-radius: 12px 12px 0 0;
            /* ওপরের কোণাগুলো গোল */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
            /* ইমেজের চারপাশে একটু গ্যাপ */
        }

        .category-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            /* ইমেজ পুরো এরিয়া জুড়ে থাকবে */
        }

        .category-text {
            font-size: 1rem;
            font-weight: 600;
            /* একটু মোটা ফন্ট */
            color: #333;
            text-decoration: none;
            transition: 0.3s;
        }

        .category-card:hover .category-text {
            color: var(--brand);
            /* হোভার করলে লেখার কালার গ্রিন হবে */
        }

        /* মোবাইল স্ক্রিনের জন্য কিছু পরিবর্তন */
        @media (max-width: 575.98px) {
            .category-img-container {
                height: 100px;
                /* মোবাইলে ইমেজের হাইট কম */
            }

            .category-text {
                font-size: 0.85rem;
                /* মোবাইলে লেখা একটু ছোট */
            }
        }
    </style>

    <div class="container-fluid px-2 px-md-5 my-5">
        <!-- সেকশন টাইটেল (সেন্টার এলাইন) -->
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Top Categories</h2>
            <p class="text-muted">Explore our wide range of products across different categories</p>
            <div class="d-inline-block bg-success" style="width: 70px; height: 3px;"></div>
        </div>

        <!-- ক্যাটাগরি গ্রিড (৬টি কলামের গ্রিড) -->
        <!-- 'row-cols-2' মোবাইলে ২টি, 'row-cols-sm-3' ছোট স্ক্রিনে ৩টি, 'row-cols-lg-6' বড় স্ক্রিনে ৬টি কার্ড দেখাবে -->
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 g-md-4">

            <!-- ক্যাটাগরি ১: Men's Fashion -->
            <div class="col">
                <a href="#" class="text-decoration-none">
                    <div class="category-card text-center h-100 p-2">
                        <div class="category-img-container">
                            <!-- ছবির জন্য প্লেসহোল্ডার, আপনি আপনার ইমেজ বসিয়ে নিবেন -->
                            <img src="https://via.placeholder.com/150x150/f8f9fa/374151?text=Men" class="img-fluid" alt="Men's Fashion">
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <p class="category-text mb-0">Men's Fashion</p>
                            <small class="text-muted d-none d-sm-inline">240+ Items</small>
                        </div>
                    </div>
                </a>
            </div>

            <!-- ক্যাটাগরি ২: Women's Fashion -->
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

            <!-- ক্যাটাগরি ৩: Electronics -->
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

            <!-- ক্যাটাগরি ৪: Mobile & Accessories -->
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

            <!-- ক্যাটাগরি ৫: Home & Life -->
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

            <!-- ক্যাটাগরি ৬: Beauty & Care -->
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
    <!-- ================= Product Category Grid End ================= -->



    <!-- ================= New Arrivals Section Start ================= -->
    <style>
        /* New Arrivals সেকশনের জন্য বিশেষ কিছু স্টাইল */
        .product-card {
            border: 1px solid #e9ecef;
            /* हल्का बर्डर */
            border-radius: 10px;
            /* गोलाकार कोने */
            transition: all 0.3s ease;
            /* एनीमेशन */
            background: #fff;
            overflow: hidden;
            /* कार्ड के बाहर कुछ न जाए */
            height: 100%;
            /* सभी कार्ड एक ही ऊंचाई के हों */
            display: flex;
            flex-direction: column;
            /* कन्टेंट को ऊपर से नीचे सजाएं */
        }

        /* কার্ডের ওপর মাউস নিলে যা হবে */
        .product-card:hover {
            transform: translateY(-5px);
            /* थोड़ा ऊपर उठेगा */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            /* हल्का शैडो */
            border-color: #dee2e6;
        }

        /* প্রোডাক্টের ইমেজের এরিয়া */
        .product-img-container {
            position: relative;
            height: 200px;
            /* इमेज के लिए फिक्सड ऊंचाई */
            overflow: hidden;
            background-color: #f8f9fa;
            /* इमेज के पीछे हल्का ग्रे बैकग्राउंड */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .product-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            /* इमेज सुंदर दिखे, कटे नहीं */
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-img-container img {
            transform: scale(1.05);
            /* होवर करने पर इमेज थोड़ी बड़ी होगी */
        }

        /* "New" বা অন্য কোনো ব্যাজ (Badge) */
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--brand);
            /* ग्रीन कलर */
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 1;
        }

        /* কার্ডের নিচের তথ্যের অংশ */
        .product-card-body {
            padding: 15px;
            flex-grow: 1;
            /* कन्टेंट खाली जगह ले ले */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* ऊपर के टेक्स्ट और नीचे के बटन/प्राइस को अलग करें */
        }

        .product-category {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .product-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            margin-bottom: 10px;
            display: -webkit-box;
            /* दो लाइन के बाद टेक्स्ट को '...' करें */
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.3s;
        }

        .product-title:hover {
            color: var(--brand);
        }

        /* দামের অংশ */
        .price-wrapper {
            margin-bottom: 15px;
        }

        .current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand);
            margin-right: 5px;
        }

        .old-price {
            font-size: 0.9rem;
            color: #adb5bd;
            text-decoration: line-through;
            /* कटे हुए दाम */
        }

        /* কালেকশন বাটন */
        .btn-add-cart {
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

        .btn-add-cart:hover {
            background-color: var(--brand);
            color: #fff;
        }

        /* মোবাইল স্ক্রিনের জন্য অ্যাডজাস্টমেন্ট */
        @media (max-width: 575.98px) {
            .product-img-container {
                height: 160px;
                /* मोबाइल में इमेज की ऊंचाई कम */
            }

            .product-title {
                font-size: 0.85rem;
            }

            .current-price {
                font-size: 1rem;
            }
        }
    </style>

    <div class="container-fluid px-2 px-md-5 my-5">
        <!-- সেকশন টাইটেল এবং "View All" বাটন -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">New Arrivals</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <!-- প্রোডাক্ট গ্রিড (৪টি কলাম) -->
        <!-- 'row-cols-2' মোবাইলে ২টি, 'row-cols-sm-2' ছোট স্ক্রিনে ২টি, 'row-cols-md-3' ট্যাবলেটে ৩টি, 'row-cols-lg-4' বড় স্ক্রিনে ৪টি -->
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">

            <!-- প্রোডাক্ট ১ -->
            <div class="col">
                <div class="product-card">
                    <!-- ইমেজ এরিয়া -->
                    <div class="product-img-container">
                        <span class="product-badge">New</span>
                        <!-- ছবির জন্য প্লেসহোল্ডার, আপনি আপনার ইমেজ বসিয়ে নিবেন -->
                        <img src="https://via.placeholder.com/200x200/f8f9fa/374151?text=Product+1" class="img-fluid" alt="Men's Casual Shirt">
                    </div>
                    <!-- তথ্যের অংশ -->
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
                            <button class="btn btn-add-cart">
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- প্রোডাক্ট ২ -->
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

            <!-- প্রোডাক্ট ৩ -->
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

            <!-- প্রোডাক্ট ৪ -->
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
    <!-- ================= New Arrivals Section End ================= -->


    <!-- ================= Top Deals Section Start ================= -->
    <style>
        /* Top Deals সেকশনের জন্য বিশেষ কিছু স্টাইল */
        .top-deals-card {
            border: 1px solid #e9ecef;
            /* हल्का बर्डर */
            border-radius: 10px;
            /* गोलाकार कोने */
            transition: all 0.3s ease;
            /* एनीमेशन */
            background: #fff;
            overflow: hidden;
            /* कार्ड के बाहर कुछ न जाए */
            height: 100%;
            /* सभी कार्ड एक ही ऊंचाई के हों */
            display: flex;
            flex-direction: column;
            /* कन्टेंट को ऊपर से नीचे सजाएं */
        }

        /* কার্ডের ওপর মাউস নিলে যা হবে */
        .top-deals-card:hover {
            transform: translateY(-5px);
            /* थोड़ा ऊपर उठेगा */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            /* हल्का शैडो */
            border-color: #dee2e6;
        }

        /* প্রোডাক্টের ইমেজের এরিয়া */
        .top-deals-img-container {
            position: relative;
            height: 200px;
            /* इमेज के लिए फिक्सड ऊंचाई */
            overflow: hidden;
            background-color: #f8f9fa;
            /* इमेज के पीछे हल्का ग्रे बैकग्राउंड */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .top-deals-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            /* इमेज सुंदर दिखे, कटे नहीं */
            transition: transform 0.5s ease;
        }

        .top-deals-card:hover .top-deals-img-container img {
            transform: scale(1.05);
            /* होवर करने पर इमेज थोड़ी बड़ी होगी */
        }

        /* "Hot Deal" বা অন্য কোনো ব্যাজ (Badge) */
        .top-deals-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #dc3545;
            /* লাল কালার - হট ডিল */
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 1;
        }

        /* কার্ডের নিচের তথ্যের অংশ */
        .top-deals-card-body {
            padding: 15px;
            flex-grow: 1;
            /* कन्टেন্ট खाली जगह ले ले */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* ऊपर के टेक्स्ट और नीचे के बटन/प्राइस को अलग करें */
        }

        .top-deals-category {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .top-deals-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.3s;
        }

        .top-deals-title:hover {
            color: var(--brand);
        }

        /* দামের অংশ */
        .top-deals-price-wrapper {
            margin-bottom: 15px;
        }

        .top-deals-current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand);
            margin-right: 5px;
        }

        .top-deals-old-price {
            font-size: 0.9rem;
            color: #adb5bd;
            text-decoration: line-through;
            /* कटे हुए दाम */
        }

        /* কালেকশন বাটন */
        .btn-top-deals-add-cart {
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

        .btn-top-deals-add-cart:hover {
            background-color: var(--brand);
            color: #fff;
        }

        /* মোবাইল স্ক্রিনের জন্য অ্যাডজাস্টমেন্ট */
        @media (max-width: 575.98px) {
            .top-deals-img-container {
                height: 160px;
                /* मोबाइल में इमेज की ऊंचाई कम */
            }

            .top-deals-title {
                font-size: 0.85rem;
            }

            .top-deals-current-price {
                font-size: 1rem;
            }
        }
    </style>

    <div class="container-fluid px-2 px-md-5 my-5">
        <!-- সেকশন টাইটেল এবং "View All" বাটন -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">Top Deals</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <!-- প্রোডাক্ট গ্রিড (৪টি কলাম) -->
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">

            <!-- প্রোডাক্ট ১ -->
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

            <!-- প্রোডাক্ট ২ -->
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

            <!-- প্রোডাক্ট ৩ -->
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

            <!-- প্রোডাক্ট ৪ -->
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
    <!-- ================= Top Deals Section End ================= -->

    <!-- ================= High Discount Section Start ================= -->
    <style>
        /* High Discount সেকশনের জন্য বিশেষ কিছু স্টাইল */
        .high-discount-card {
            border: 1px solid #e9ecef;
            /* हल्का बर्डर */
            border-radius: 10px;
            /* गोलाकार कोने */
            transition: all 0.3s ease;
            /* एनीमेशन */
            background: #fff;
            overflow: hidden;
            /* कार्ड के बाहर कुछ न जाए */
            height: 100%;
            /* सभी कार्ड एक ही ऊंचाई के हों */
            display: flex;
            flex-direction: column;
            /* कन्टेंट को ऊपर से नीचे सजाएं */
        }

        /* কার্ডের ওপর মাউস নিলে যা হবে */
        .high-discount-card:hover {
            transform: translateY(-5px);
            /* थोड़ा ऊपर उठेगा */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            /* हल्का शैडो */
            border-color: #dee2e6;
        }

        /* প্রোডাক্টের ইমেজের এরিয়া */
        .high-discount-img-container {
            position: relative;
            height: 200px;
            /* इमेज के लिए फिक्सड ऊंचाई */
            overflow: hidden;
            background-color: #f8f9fa;
            /* इमेज के पीछे हल्का ग्रे बैकग्राउंड */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .high-discount-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            /* इमेज सुंदर दिखे, कटे नहीं */
            transition: transform 0.5s ease;
        }

        .high-discount-card:hover .high-discount-img-container img {
            transform: scale(1.05);
            /* होवर करने पर इमेज थोड़ी बड़ी होगी */
        }

        /* "Discount" ব্যাজ (Badge) - যেমন: 50% OFF */
        .high-discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            /* ডান পাশে */
            background-color: #f57c00;
            /* কমলা কালার - বিশেষ ছাড় */
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            z-index: 1;
        }

        /* কার্ডের নিচের তথ্যের অংশ */
        .high-discount-card-body {
            padding: 15px;
            flex-grow: 1;
            /* कन्टेंट खाली जगह ले ले */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* ऊपर के टेक्स्ट और नीचे के बटन/प्राइस को अलग करें */
        }

        .high-discount-category {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .high-discount-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            margin-bottom: 10px;
            display: -webkit-box;
            /* दो लाइन के बाद टेक्स्ट को '...' करें */
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.3s;
        }

        .high-discount-title:hover {
            color: var(--brand);
        }

        /* দামের অংশ */
        .high-discount-price-wrapper {
            margin-bottom: 15px;
        }

        .high-discount-current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand);
            margin-right: 5px;
        }

        .high-discount-old-price {
            font-size: 0.9rem;
            color: #adb5bd;
            text-decoration: line-through;
            /* कटे हुए दाम */
        }

        /* কালেকশন বাটন */
        .btn-high-discount-add-cart {
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

        .btn-high-discount-add-cart:hover {
            background-color: var(--brand);
            color: #fff;
        }

        /* মোবাইল স্ক্রিনের জন্য অ্যাডजাস্টমেন্ট */
        @media (max-width: 575.98px) {
            .high-discount-img-container {
                height: 160px;
                /* मोबाइल में इमेज की ऊंचाई कम */
            }

            .high-discount-title {
                font-size: 0.85rem;
            }

            .high-discount-current-price {
                font-size: 1rem;
            }
        }
    </style>

    <div class="container-fluid px-2 px-md-5 my-5">
        <!-- সেকশন টাইটেল এবং "View All" বাটন -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">High Discount Products</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <!-- প্রোডাক্ট গ্রিড (৪টি কলাম) -->
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">

            <!-- প্রোডাক্ট ১ -->
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

            <!-- প্রোডাক্ট ২ -->
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

            <!-- প্রোডাক্ট ৩ -->
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

            <!-- প্রোডাক্ট ৪ -->
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
    <!-- ================= High Discount Section End ================= -->


    <!-- ================= Top Sales Section Start ================= -->
    <style>
        /* Top Sales সেকশনের জন্য বিশেষ কিছু স্টাইল */
        .top-sales-card {
            border: 1px solid #e9ecef;
            /* হালকা বর্ডার */
            border-radius: 10px;
            /* গোলাকার কোণা */
            transition: all 0.3s ease;
            /* অ্যানিমেশন এফেক্ট */
            background: #fff;
            overflow: hidden;
            /* কার্ডের বাইরে কিছু না যায় */
            height: 100%;
            /* সব কার্ড একই উচ্চতার হবে */
            display: flex;
            flex-direction: column;
            /* কন্টেন্ট ওপর থেকে নিচে সাজানো */
        }

        /* কার্ডের ওপর মাউস নিলে যা হবে */
        .top-sales-card:hover {
            transform: translateY(-5px);
            /* একটু ওপরের দিকে উঠবে */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            /* হালকা শ্যাডো */
            border-color: #dee2e6;
        }

        /* প্রোডাক্টের ইমেজের এরিয়া */
        .top-sales-img-container {
            position: relative;
            height: 200px;
            /* ইমেজের জন্য ফিক্সড উচ্চতা */
            overflow: hidden;
            background-color: #f8f9fa;
            /* ইমেজের পেছনে হালকা ধূসর ব্যাকগ্রাউন্ড */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .top-sales-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            /* ইমেজ সুন্দর দেখাবে, কাটবে না */
            transition: transform 0.5s ease;
        }

        .top-sales-card:hover .top-sales-img-container img {
            transform: scale(1.05);
            /* হোভার করলে ইমেজ একটু বড় হবে */
        }

        /* "Best Seller" বা অন্য কোনো ব্যাজ (Badge) */
        .top-sales-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #28a745;
            /* সবুজ কালার - বেস্ট সেলার */
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 1;
        }

        /* কার্ডের নিচের তথ্যের অংশ */
        .top-sales-card-body {
            padding: 15px;
            flex-grow: 1;
            /* কন্টেন্ট খালি জায়গা নিয়ে নেবে */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* ওপরের টেক্সট এবং নিচের বাটন/প্রাইসকে আলাদা করবে */
        }

        .top-sales-category {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .top-sales-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            margin-bottom: 10px;
            display: -webkit-box;
            /* দুই লাইনের পর টেক্সটকে '...' করবে */
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.3s;
        }

        .top-sales-title:hover {
            color: var(--brand);
        }

        /* দামের অংশ */
        .top-sales-price-wrapper {
            margin-bottom: 15px;
        }

        .top-sales-current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand);
            margin-right: 5px;
        }

        .top-sales-old-price {
            font-size: 0.9rem;
            color: #adb5bd;
            text-decoration: line-through;
            /* কাটা দাম */
        }

        /* কালেকশন বাটন */
        .btn-top-sales-add-cart {
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

        .btn-top-sales-add-cart:hover {
            background-color: var(--brand);
            color: #fff;
        }

        /* মোবাইল স্ক্রিনের জন্য অ্যাডজাস্টমেন্ট */
        @media (max-width: 575.98px) {
            .top-sales-img-container {
                height: 160px;
                /* মোবাইলে ইমেজের উচ্চতা কম */
            }

            .top-sales-title {
                font-size: 0.85rem;
            }

            .top-sales-current-price {
                font-size: 1rem;
            }
        }
    </style>

    <div class="container-fluid px-2 px-md-5 my-5">
        <!-- সেকশন টাইটেল এবং "View All" বাটন -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">Top Sales</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <!-- প্রোডাক্ট গ্রিড (৪টি কলাম) -->
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">

            <!-- প্রোডাক্ট ১ -->
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

            <!-- প্রোডাক্ট ২ -->
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

            <!-- প্রোডাক্ট ৩ -->
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

            <!-- প্রোডাক্ট ৪ -->
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
    <!-- ================= Top Sales Section End ================= -->


    <!-- ================= Top Review Products Section Start ================= -->
    <style>
        /* Top Review Products সেকশনের জন্য বিশেষ কিছু স্টাইল */
        .top-review-card {
            border: 1px solid #e9ecef;
            /* হালকা বর্ডার */
            border-radius: 10px;
            /* গোলাকার কোণা */
            transition: all 0.3s ease;
            /* অ্যানিমেশন এফেক্ট */
            background: #fff;
            overflow: hidden;
            /* কার্ডের বাইরে কিছু না যায় */
            height: 100%;
            /* সব কার্ড একই উচ্চতার হবে */
            display: flex;
            flex-direction: column;
            /* কন্টেন্ট ওপর থেকে নিচে সাজানো */
        }

        /* কার্ডের ওপর মাউস নিলে যা হবে */
        .top-review-card:hover {
            transform: translateY(-5px);
            /* একটু ওপরের দিকে উঠবে */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            /* হালকা শ্যাডো */
            border-color: #dee2e6;
        }

        /* প্রোডাক্টের ইমেজের এরিয়া */
        .top-review-img-container {
            position: relative;
            height: 200px;
            /* ইমেজের জন্য ফিক্সড উচ্চতা */
            overflow: hidden;
            background-color: #f8f9fa;
            /* ইমেজের পেছনে হালকা ধূসর ব্যাকগ্রাউন্ড */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .top-review-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            /* ইমেজ সুন্দর দেখাবে, কাটবে না */
            transition: transform 0.5s ease;
        }

        .top-review-card:hover .top-review-img-container img {
            transform: scale(1.05);
            /* হোভার করলে ইমেজ একটু বড় হবে */
        }

        /* "Top Rated" বা অন্য কোনো ব্যাজ (Badge) */
        .top-review-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            /* ডান পাশে */
            background-color: #f5c71a;
            /* সোনালী কালার - টপ রেটেড */
            color: #333;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 1;
        }

        /* কার্ডের নিচের তথ্যের অংশ */
        .top-review-card-body {
            padding: 15px;
            flex-grow: 1;
            /* কন্টেন্ট খালি জায়গা নিয়ে নেবে */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* ওপরের টেক্সট এবং নিচের বাটন/প্রাইসকে আলাদা করবে */
        }

        .top-review-category {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .top-review-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            margin-bottom: 5px;
            display: -webkit-box;
            /* দুই লাইনের পর টেক্সটকে '...' করবে */
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.3s;
        }

        .top-review-title:hover {
            color: var(--brand);
        }

        /* রেটিং এবং রিভিউর অংশ */
        .rating-wrapper {
            margin-bottom: 10px;
            color: #f5c71a;
            /* সোনালী কালার - স্টার */
            font-size: 0.9rem;
        }

        .review-count {
            color: var(--muted);
            font-size: 0.8rem;
            margin-left: 5px;
        }

        /* দামের অংশ */
        .top-review-price-wrapper {
            margin-bottom: 15px;
        }

        .top-review-current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand);
            margin-right: 5px;
        }

        /* কালেকশন বাটন */
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

        .btn-top-review-add-cart:hover {
            background-color: var(--brand);
            color: #fff;
        }

        /* মোবাইল স্ক্রিনের জন্য অ্যাডজাস্টমেন্ট */
        @media (max-width: 575.98px) {
            .top-review-img-container {
                height: 160px;
                /* মোবাইলে ইমেজের উচ্চতা কম */
            }

            .top-review-title {
                font-size: 0.85rem;
            }

            .top-review-current-price {
                font-size: 1rem;
            }
        }
    </style>

    <div class="container-fluid px-2 px-md-5 my-5">
        <!-- সেকশন টাইটেল এবং "View All" বাটন -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0">Top Rated Products</h2>
            <a href="#" class="text-success text-decoration-none fw-semibold">
                View All <i class="bi bi-arrow-right fs-7 ms-1"></i>
            </a>
        </div>

        <!-- প্রোডাক্ট গ্রিড (৪টি কলাম) -->
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">

            <!-- প্রোডাক্ট ১ -->
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

            <!-- প্রোডাক্ট ২ -->
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

            <!-- প্রোডাক্ট ৩ -->
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

            <!-- প্রোডাক্ট ৪ -->
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
    <!-- ================= Top Review Products Section End ================= -->



    <!-- ================= Footer Section Start ================= -->
    <style>
        /* ফুটারের জন্য কাস্টম স্টাইল */
        .footer {
            background-color: #212529;
            /* ডার্ক ব্যাকগ্রাউন্ড */
            color: #ced4da;
            /* হালকা টেক্সট কালার */
            font-size: 0.9rem;
            padding-top: 50px;
        }

        .footer a {
            color: #ced4da;
            /* লিংকের ডিফল্ট কালার */
            text-decoration: none;
            transition: 0.3s;
        }

        .footer a:hover {
            color: #fff;
            /* হোভার করলে সাদা হবে */
            padding-left: 5px;
            /* একটু ডানদিকে সরবে */
        }

        .footer-title {
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        /* টাইটেলের নিচে ছোট গ্রিন দাগ */
        .footer-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--brand);
            /* গ্রিন কালার */
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
            /* হোভার করলে গ্রিন হবে */
            transform: translateY(-3px);
            padding-left: 0;
            /* ডিফল্ট লিংকের এফেক্ট সরানোর জন্য */
        }

        /* পেমেন্ট মেথড আইকন */
        .payment-icons img {
            height: 25px;
            margin-right: 10px;
            margin-bottom: 10px;
            filter: grayscale(100%);
            /* ডিফল্ট ধূসর */
            transition: 0.3s;
        }

        .payment-icons img:hover {
            filter: grayscale(0%);
            /* হোভার করলে কালার হবে */
        }

        .footer-bottom {
            background-color: #1a1e21;
            /* আরও ডার্ক */
            padding: 15px 0;
            font-size: 0.85rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        @media (max-width: 767.98px) {
            .footer-col {
                margin-bottom: 30px;
                /* মোবাইলে কলামের মাঝে গ্যাপ */
            }
        }
    </style>

    <footer class="footer mt-5">
        <div class="container-fluid px-2 px-md-5">
            <div class="row footer-col">

                <!-- কলাম ১: লোগো এবং পরিচিতি -->
                <div class="col-md-6 col-lg-4 mb-4 mb-lg-0 pr-lg-5">
                    <div class="footer-logo">
                        <!-- ছবিতে ব্যবহৃত লোগোর প্লেসহোল্ডার -->
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

                <!-- কলাম ২: কুইক লিংক -->
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

                <!-- কলাম ৩: কাস্টমার সার্ভিস -->
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

                <!-- কলাম ৪: যোগাযোগ এবং পেমেন্ট -->
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
                        <!-- পেমেন্ট মেথড আইকনের প্লেসহোল্ডার -->
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

        <!-- ফুটার বটম: কপিরাইট এবং ক্রেডিট -->
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
    <!-- ================= Footer Section End ================= -->

    <!-- সঠিক Bootstrap 5 JS Bundle CDN লিংক -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>