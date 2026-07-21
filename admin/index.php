<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Admin Panel</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      overflow-x: hidden;
    }

    .sidebar {
      min-height: calc(100vh - 56px);
      border-right: 1px solid var(--bs-border-color);
    }

    .sidebar .nav-link {
      color: var(--bs-body-color);
      padding: .8rem 1rem;
      border-radius: .5rem;
      margin: .2rem .5rem;
    }

    .sidebar .nav-link:hover {
      background: var(--bs-secondary-bg);
    }

    .sidebar .nav-link.active {
      background: var(--bs-primary);
      color: #fff;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg bg-body border-bottom sticky-top px-3">

    <button class="btn d-lg-none me-2"
      data-bs-toggle="offcanvas"
      data-bs-target="#sidebar">
      <i class="bi bi-list fs-3"></i>
    </button>

    <a class="navbar-brand fw-bold" href="index.php">
      AmarShop Admin
    </a>

    <div class="ms-auto d-flex align-items-center gap-3">

      <span class="fw-semibold">
        Jasim
      </span>

      <button class="btn btn-outline-secondary"
        id="themeToggle">

        <i class="bi bi-moon"></i>

      </button>

    </div>

  </nav>

  <div class="container-fluid">

    <div class="row">

      <!-- Desktop Sidebar -->

      <aside class="col-lg-2 d-none d-lg-block sidebar bg-body-tertiary">

        <ul class="nav flex-column py-3">

          <li class="nav-item">
            <a class="nav-link active" href="index.php">
              <i class="bi bi-speedometer2 me-2"></i>
              Dashboard
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="Products/index.php">
              <i class="bi bi-box-seam me-2"></i>
              Products
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="Sales/index.php">
              <i class="bi bi-cart-check me-2"></i>
              Sales
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="Users/index.php">
              <i class="bi bi-people me-2"></i>
              Users
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="site-setting/index.php">
              <i class="bi bi-gear me-2"></i>
              Site Settings
            </a>
          </li>

        </ul>

      </aside>

      <!-- Mobile Sidebar -->

      <div class="offcanvas offcanvas-start"
        tabindex="-1"
        id="sidebar">

        <div class="offcanvas-header">

          <h5>Menu</h5>

          <button class="btn-close"
            data-bs-dismiss="offcanvas"></button>

        </div>

        <div class="offcanvas-body">

          <ul class="nav flex-column">

            <li><a class="nav-link" href="index.php">Dashboard</a></li>
            <li><a class="nav-link" href="Products/index.php">Products</a></li>
            <li><a class="nav-link" href="Sales/index.php">Sales</a></li>
            <li><a class="nav-link" href="Users/index.php">Users</a></li>
            <li><a class="nav-link" href="site-setting/index.php">Site Settings</a></li>

          </ul>

        </div>

      </div>

      <!-- Content -->

      <main class="col-lg-10 p-4">

        <h2>Dashboard</h2>

      </main>

    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const html = document.documentElement;
    const btn = document.getElementById("themeToggle");

    function applyTheme(theme) {
      html.setAttribute("data-bs-theme", theme);
      btn.innerHTML = theme === "dark"
        ? '<i class="bi bi-sun"></i>'
        : '<i class="bi bi-moon"></i>';
    }

    const savedTheme = localStorage.getItem("amarshop_admin_theme");
    if (savedTheme) {
      applyTheme(savedTheme);
    }

    btn.addEventListener("click", () => {
      const currentTheme = html.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
      applyTheme(currentTheme);
      localStorage.setItem("amarshop_admin_theme", currentTheme);
    });
  </script>



</body>

</html>