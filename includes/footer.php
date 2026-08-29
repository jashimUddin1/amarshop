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

    <!-- Cart Offcanvas Drawer -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel" style="width: 380px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold text-dark" id="cartOffcanvasLabel">
                <i class="bi bi-bag-check text-success me-2"></i>Your Shopping Cart
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column justify-content-between p-3">
            <div id="cartAlertMessage"></div>
            <div id="cartItemsContainer" class="flex-grow-1 overflow-auto pe-1">
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-cart-x fs-1 opacity-50"></i>
                    <p class="mt-2 mb-0">Your cart is empty</p>
                </div>
            </div>

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

    <!-- User Profile Offcanvas Drawer -->
    <?php if (isset($is_logged_in) && $is_logged_in): ?>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="userProfileDrawer" aria-labelledby="userProfileDrawerLabel" style="width: 330px;">
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

    <!-- Global Scripts -->
    <script>
        /* global FormData */
        const isLoggedIn = <?= json_encode($is_logged_in ?? false); ?>;

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
                            <img src="uploads/${item.main_img}" class="rounded border" style="width: 65px; height: 65px; object-fit: contain;">
                            <div class="flex-grow-1 px-3">
                                <h6 class="mb-1 text-truncate fw-semibold" style="max-width: 170px; font-size: 0.85rem;" title="${item.name}">${item.name}</h6>
                                <div class="d-flex align-items-center gap-2 my-1">
                                    ${sizeSelectHtml}
                                    <div class="input-group input-group-sm" style="width: 75px;">
                                        <button class="btn btn-outline-secondary px-2 py-0" type="button" onclick="updateCartQuantity(${item.cart_id}, 'decrease')">-</button>
                                        <input type="text" class="form-control text-center p-0 fw-bold" value="${item.quantity}" readonly style="font-size: 0.75rem;">
                                        <button class="btn btn-outline-secondary px-2 py-0" type="button" onclick="updateCartQuantity(${item.cart_id}, 'increase')">+</button>
                                    </div>
                                </div>
                                <div class="mt-1" style="font-size: 0.8rem;">
                                    <span class="fw-bold text-success">Total: ৳${itemTotal}</span>
                                    <small class="text-muted ms-1">(৳${item.price} × ${item.quantity})</small>
                                </div>
                            </div>
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

        function updateCartQuantity(cartId, action) {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('action', action);

            fetch('core/update_cart.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => { if (res.status === 'success') loadCartDetails(); });
        }

        function updateCartSize(cartId, newSize) {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('action', 'update_size');
            formData.append('size', newSize);

            fetch('update_cart.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => { if (res.status === 'success') loadCartDetails(); });
        }

        function showCartAlert(message, type = 'success') {
            const alertBox = document.getElementById('cartAlertMessage');
            if (alertBox) {
                alertBox.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show py-2 px-3 mb-3 text-center" style="font-size: 0.85rem;" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> ${message}
                    <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
                setTimeout(() => {
                    const currentAlert = alertBox.querySelector('.alert');
                    if (currentAlert) {
                        currentAlert.classList.remove('show');
                        setTimeout(() => alertBox.innerHTML = '', 150);
                    }
                }, 3000);
            }
        }

        function removeCartItem(cartId) {
            if (confirm("Are you sure you want to remove this item?")) {
                const formData = new FormData();
                formData.append('cart_id', cartId);

                fetch('core/remove_cart_item.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            loadCartDetails();
                            updateCartBadge();
                            showCartAlert("Item successfully removed from cart!", "danger");
                        } else {
                            showCartAlert(data.message || "Failed to remove item", "warning");
                        }
                    })
                    .catch(err => showCartAlert("Something went wrong!", "danger"));
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();

            const cartOffcanvasEl = document.getElementById('cartOffcanvas');
            if (cartOffcanvasEl) {
                cartOffcanvasEl.addEventListener('show.bs.offcanvas', loadCartDetails);
            }

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

            document.querySelectorAll('.close-options-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const overlay = this.closest('.card-options-overlay');
                    overlay.classList.add('d-none');
                    overlay.classList.remove('d-flex');
                });
            });

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

                    fetch('core/add_to_cart.php', { method: 'POST', body: formData })
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