        </main>
    </div>
</div>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
    <div class="offcanvas-header">
        <h2 class="h5 mb-0" id="mobileNavLabel">Reseller menu</h2>
        <button class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav nav-pills flex-column gap-1">
            <?php foreach ($nav_items as $key => [$label, $icon, $href]): ?>
                <a class="nav-link <?= $current_page === $key ? 'active' : 'text-dark'; ?>" href="<?= reseller_h($href); ?>">
                    <i class="bi bi-<?= reseller_h($icon); ?> me-2"></i><?= reseller_h($label); ?>
                </a>
            <?php endforeach; ?>
            <a class="nav-link <?= $current_page === 'cart' ? 'active' : 'text-dark'; ?>" href="cart.php"><i class="bi bi-bag me-2"></i>Cart <span id="resellerMobileCartCount"><?= $cart_count ? '(' . $cart_count . ')' : ''; ?></span></a>
            <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </nav>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const sidebar = document.getElementById('resellerSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const layout = document.getElementById('resellerLayoutRow');
    const storageKey = 'amarshop_reseller_sidebar_collapsed';
    if (sidebar && toggle) {
        const collapsed = localStorage.getItem(storageKey) === 'true';
        sidebar.classList.toggle('is-collapsed', collapsed);
        layout?.classList.toggle('is-collapsed', collapsed);
        toggle.addEventListener('click', () => {
            const collapsed = sidebar.classList.toggle('is-collapsed');
            layout?.classList.toggle('is-collapsed', collapsed);
            localStorage.setItem(storageKey, String(collapsed));
        });
    }

    document.querySelectorAll('.product-image-preview').forEach(image => {
        image.addEventListener('click', () => {
            const preview = document.getElementById('resellerImagePreview');
            if (!preview) return;
            preview.querySelector('img').src = image.src;
            bootstrap.Modal.getOrCreateInstance(preview).show();
        });
    });
})();
</script>
<div class="modal fade" id="resellerImagePreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-transparent">
            <button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            <img src="" class="img-fluid rounded shadow" alt="Product preview">
        </div>
    </div>
</div>
</body>
</html>
