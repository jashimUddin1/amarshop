<?php
require_once __DIR__ . '/common.php';
$user = pos_require_login();
$page_title = 'New bill';
pos_header($page_title, 'sale', $user);
?>
<section class="pos-page-heading">
  <div>
    <h1>New counter bill</h1>
    <p>Scan a product code or search by name. Stock is checked again when the bill is saved.</p>
  </div>
</section>

<div class="pos-grid pos-grid-2" style="grid-template-columns:minmax(0,1.05fr) minmax(360px,.95fr);align-items:start">
  <section class="pos-card">
    <div class="pos-card-header"><h2>Find products</h2><span class="pos-help">Maximum 30 matches</span></div>
    <div class="pos-card-body">
      <form id="productSearchForm" class="pos-form-row" role="search">
        <div>
          <label class="pos-label" for="productSearch">Product name or barcode</label>
          <input class="pos-input" id="productSearch" autocomplete="off" placeholder="Type or scan a product code">
        </div>
        <div style="align-self:end"><button class="pos-button" type="submit" style="width:100%">Search products</button></div>
      </form>
      <p class="pos-help" style="margin:.6rem 0 1rem">Press Enter after scanning a barcode. Only in-stock products can be added.</p>
      <div id="searchMessage" class="pos-help" role="status" aria-live="polite"></div>
      <div id="productResults" class="pos-stack" style="margin-top:.75rem"></div>
    </div>
  </section>

  <section class="pos-card">
    <div class="pos-card-header"><h2>Current bill</h2><span id="cartCount" class="pos-badge">0 items</span></div>
    <div class="pos-table-wrap">
      <table class="pos-table">
        <thead><tr><th>Item</th><th class="right">Qty</th><th class="right">Total</th><th></th></tr></thead>
        <tbody id="cartRows"><tr><td class="pos-empty" colspan="4">Search and add products to start a bill.</td></tr></tbody>
      </table>
    </div>
    <form method="post" action="save_sale.php" id="saleForm">
      <input type="hidden" name="csrf_token" value="<?= pos_escape(pos_csrf_token()); ?>">
      <input type="hidden" name="items" id="saleItems">
      <div class="pos-card-body pos-stack">
        <div class="pos-form-row">
          <div><label class="pos-label" for="customerName">Customer name (optional)</label><input class="pos-input" id="customerName" name="customer_name" maxlength="180" autocomplete="name"></div>
          <div><label class="pos-label" for="customerPhone">Phone (optional)</label><input class="pos-input" id="customerPhone" name="customer_phone" maxlength="40" inputmode="tel" autocomplete="tel"></div>
        </div>
        <div class="pos-form-row">
          <div><label class="pos-label" for="discountAmount">Bill discount (৳)</label><input class="pos-input" id="discountAmount" name="discount_amount" type="number" min="0" step="0.01" value="0"></div>
          <div><label class="pos-label" for="cashReceived">Cash received (৳)</label><input class="pos-input" id="cashReceived" name="cash_received" type="number" min="0" step="0.01" required></div>
        </div>
        <div>
          <div class="pos-summary"><span>Subtotal</span><strong id="subtotalValue">৳0.00</strong></div>
          <div class="pos-summary"><span>Discount</span><strong id="discountValue">− ৳0.00</strong></div>
          <div class="pos-summary pos-summary-total"><span>Cash due</span><strong id="totalValue">৳0.00</strong></div>
          <div class="pos-summary pos-help"><span>Change</span><strong id="changeValue">৳0.00</strong></div>
        </div>
        <button class="pos-button" id="completeSale" type="submit" disabled>Complete cash sale</button>
        <div class="pos-help">Payment method: Cash. The receipt can be printed after the sale is saved.</div>
      </div>
    </form>
  </section>
</div>

<style>
  .pos-product-result { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.85rem; border:1px solid var(--line); border-radius:.65rem; }
  .pos-product-result:hover { border-color:#a9cdb5; background:#fbfdfb; }
  .pos-product-result-main { min-width:0; }
  .pos-product-result-name { font-weight:750; }
  .pos-product-result-code { color:var(--muted); font-size:.82rem; margin-top:.15rem; }
  .pos-product-result-meta { color:var(--muted); font-size:.82rem; margin-top:.3rem; }
  .pos-quantity { width:5rem; min-height:36px; text-align:center; }
  @media(max-width:850px) { .pos-grid[style*="grid-template-columns"] { grid-template-columns:1fr !important; } }
</style>

<script>
(() => {
  const searchForm = document.getElementById('productSearchForm');
  const searchInput = document.getElementById('productSearch');
  const results = document.getElementById('productResults');
  const searchMessage = document.getElementById('searchMessage');
  const cartRows = document.getElementById('cartRows');
  const itemsInput = document.getElementById('saleItems');
  const completeButton = document.getElementById('completeSale');
  const cart = new Map();
  let searchRequest = 0;
  const money = value => `৳${Number(value || 0).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2})}`;

  const addToCart = product => {
    if (product.stock < 1) return false;
    const current = cart.get(product.id);
    if (current && current.quantity >= product.stock) {
      searchMessage.textContent = 'You cannot add more than the available stock.';
      return false;
    }
    cart.set(product.id, {...product, quantity: current ? current.quantity + 1 : 1});
    renderCart();
    return true;
  };

  const renderResults = products => {
    results.replaceChildren();
    if (!products.length) {
      searchMessage.textContent = 'No matching in-stock product found.';
      return;
    }
    searchMessage.textContent = `${products.length} product${products.length === 1 ? '' : 's'} found.`;
    products.forEach(product => {
      const card = document.createElement('article');
      card.className = 'pos-product-result';
      const details = document.createElement('div');
      details.className = 'pos-product-result-main';
      const name = document.createElement('div');
      name.className = 'pos-product-result-name';
      name.textContent = product.name;
      const code = document.createElement('div');
      code.className = 'pos-product-result-code';
      code.textContent = `${product.product_code}${product.size ? ` · ${product.size}` : ''}`;
      const meta = document.createElement('div');
      meta.className = 'pos-product-result-meta';
      meta.textContent = `${money(product.price)} · ${product.stock} in stock`;
      details.append(name, code, meta);
      const addButton = document.createElement('button');
      addButton.type = 'button';
      addButton.className = 'pos-button pos-button-secondary';
      addButton.textContent = product.stock > 0 ? 'Add' : 'Out of stock';
      addButton.disabled = product.stock < 1;
      addButton.addEventListener('click', () => addToCart(product));
      card.append(details, addButton);
      results.append(card);
    });
  };

  const renderCart = () => {
    cartRows.replaceChildren();
    let subtotal = 0;
    let itemCount = 0;
    cart.forEach(item => {
      itemCount += item.quantity;
      subtotal += item.price * item.quantity;
      const row = document.createElement('tr');
      const nameCell = document.createElement('td');
      const name = document.createElement('strong');
      name.textContent = item.name;
      const code = document.createElement('div');
      code.className = 'pos-help';
      code.textContent = `${item.product_code}${item.size ? ` · ${item.size}` : ''}`;
      nameCell.append(name, code);

      const quantityCell = document.createElement('td');
      quantityCell.className = 'right';
      const quantity = document.createElement('input');
      quantity.type = 'number';
      quantity.className = 'pos-input pos-quantity';
      quantity.min = '1';
      quantity.max = String(item.stock);
      quantity.step = '1';
      quantity.value = String(item.quantity);
      quantity.setAttribute('aria-label', `Quantity for ${item.name}`);
      quantity.addEventListener('change', () => {
        const nextQuantity = Number.parseInt(quantity.value, 10);
        if (!Number.isInteger(nextQuantity) || nextQuantity < 1 || nextQuantity > item.stock) {
          quantity.value = String(item.quantity);
          searchMessage.textContent = 'Quantity must not exceed the available stock.';
          return;
        }
        item.quantity = nextQuantity;
        renderCart();
      });
      quantityCell.append(quantity);

      const totalCell = document.createElement('td');
      totalCell.className = 'right';
      totalCell.textContent = money(item.price * item.quantity);
      const removeCell = document.createElement('td');
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'pos-button pos-button-danger';
      remove.textContent = '×';
      remove.setAttribute('aria-label', `Remove ${item.name}`);
      remove.addEventListener('click', () => {
        cart.delete(item.id);
        renderCart();
      });
      removeCell.append(remove);
      row.append(nameCell, quantityCell, totalCell, removeCell);
      cartRows.append(row);
    });
    if (!cart.size) {
      const row = document.createElement('tr');
      const empty = document.createElement('td');
      empty.className = 'pos-empty';
      empty.colSpan = 4;
      empty.textContent = 'Search and add products to start a bill.';
      row.append(empty);
      cartRows.append(row);
    }

    document.getElementById('cartCount').textContent = `${itemCount} item${itemCount === 1 ? '' : 's'}`;
    document.getElementById('subtotalValue').textContent = money(subtotal);
    updateTotals();
  };

  const updateTotals = () => {
    let subtotal = 0;
    cart.forEach(item => { subtotal += item.price * item.quantity; });
    const discount = Number.parseFloat(document.getElementById('discountAmount').value || '0');
    const cashReceived = Number.parseFloat(document.getElementById('cashReceived').value || '0');
    const safeDiscount = Number.isFinite(discount) ? Math.max(0, Math.min(discount, subtotal)) : 0;
    const total = Math.max(0, subtotal - safeDiscount);
    document.getElementById('discountValue').textContent = `− ${money(safeDiscount)}`;
    document.getElementById('totalValue').textContent = money(total);
    document.getElementById('changeValue').textContent = money(Math.max(0, cashReceived - total));
    completeButton.disabled = cart.size === 0;
  };

  searchForm.addEventListener('submit', async event => {
    event.preventDefault();
    const query = searchInput.value.trim();
    if (!query) {
      searchMessage.textContent = 'Enter a product name or scan a code first.';
      searchInput.focus();
      return;
    }
    const requestId = ++searchRequest;
    searchMessage.textContent = 'Searching products…';
    results.replaceChildren();
    try {
      const response = await fetch(`api_products.php?q=${encodeURIComponent(query)}`, {headers:{Accept:'application/json'}});
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Product search failed.');
      if (requestId === searchRequest) {
        const exactCodeProduct = data.products.find(product =>
          product.product_code.trim().toLocaleLowerCase() === query.toLocaleLowerCase()
        );
        if (exactCodeProduct && exactCodeProduct.stock > 0) {
          renderResults(data.products);
          if (addToCart(exactCodeProduct)) {
            searchInput.value = '';
            searchMessage.textContent = `${exactCodeProduct.name} added to the bill. Scan or search another product.`;
            searchInput.focus();
          }
        } else {
          renderResults(data.products);
        }
      }
    } catch (error) {
      if (requestId !== searchRequest) return;
      searchMessage.textContent = error instanceof Error ? error.message : 'Products could not be loaded.';
    }
  });

  document.getElementById('discountAmount').addEventListener('input', updateTotals);
  document.getElementById('cashReceived').addEventListener('input', updateTotals);
  document.getElementById('saleForm').addEventListener('submit', event => {
    if (!cart.size) {
      event.preventDefault();
      searchMessage.textContent = 'Add at least one product before completing the bill.';
      return;
    }
    itemsInput.value = JSON.stringify(Array.from(cart.values(), item => ({
      product_id: item.id,
      quantity: item.quantity
    })));
    if (!document.getElementById('saleForm').reportValidity()) event.preventDefault();
  });
  searchInput.focus();
})();
</script>
<?php pos_footer(); ?>
