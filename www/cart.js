const CART_KEY = 'crud_store_cart';

/* ── State ── */
window.CART = JSON.parse(localStorage.getItem(CART_KEY)) || [];

/* ── Persist & sync badge ── */
function cartSave() {
    localStorage.setItem(CART_KEY, JSON.stringify(window.CART));
    cartUpdateBadge();
}

function cartUpdateBadge() {
    const total = window.CART.reduce((sum, item) => sum + item.quantity, 0);
    document.querySelectorAll('.cart-badge').forEach(el => {
        el.textContent = total;
        el.style.display = total > 0 ? 'flex' : 'none';
    });
}

/* ── Public: add a product object to cart ── */
window.addToCart = function (p) {
    const existing = window.CART.find(x => x.id === p.id);
    if (existing) {
        existing.quantity++;
    } else {
        window.CART.push({
            id:       p.id,
            name:     p.name,
            price:    p.price,
            img:      p.img || '',
            category: p.category || '',
            quantity: 1,
        });
    }
    cartSave();
    cartRender();
};

/* ── Toggle drawer ── */
window.toggleCart = function (show) {
    const overlay = document.getElementById('cart-overlay');
    const drawer  = document.getElementById('cart-drawer');
    if (!overlay || !drawer) return;
    if (show) {
        cartRender();
        overlay.classList.add('show');
        drawer.classList.add('show');
    } else {
        overlay.classList.remove('show');
        drawer.classList.remove('show');
    }
};

/* ── Render drawer items ── */
function cartRender() {
    const container = document.getElementById('cart-items-container');
    const totalEl   = document.getElementById('cart-total-price');
    if (!container) return;

    if (!window.CART.length) {
        container.innerHTML = `
            <div class="cart-empty">
                <i class='bx bx-cart'></i>
                <p>Your cart is empty</p>
                <small>Add some products to get started!</small>
            </div>`;
        if (totalEl) totalEl.textContent = '₱0.00';
        return;
    }

    container.innerHTML = window.CART.map(item => `
        <div class="cart-item">
            <div class="cart-item-img">
                <img src="${item.img}" alt="${item.name}" onerror="this.style.display='none'">
            </div>
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">₱${(item.price * item.quantity).toLocaleString()}</div>
                <div class="cart-item-qty">
                    <button class="qty-btn" onclick="cartChangeQty(${item.id}, -1)"><i class='bx bx-minus'></i></button>
                    <span>${item.quantity}</span>
                    <button class="qty-btn" onclick="cartChangeQty(${item.id}, 1)"><i class='bx bx-plus'></i></button>
                </div>
            </div>
            <button class="cart-item-remove" onclick="cartRemove(${item.id})" aria-label="Remove">
                <i class='bx bx-x'></i>
            </button>
        </div>
    `).join('');

    const total = window.CART.reduce((sum, item) => sum + item.price * item.quantity, 0);
    if (totalEl) totalEl.textContent = '₱' + total.toLocaleString();
}

/* ── Quantity change ── */
window.cartChangeQty = function (id, delta) {
    const item = window.CART.find(x => x.id === id);
    if (!item) return;
    item.quantity += delta;
    if (item.quantity <= 0) {
        window.CART = window.CART.filter(x => x.id !== id);
    }
    cartSave();
    cartRender();
};

/* ── Remove item ── */
window.cartRemove = function (id) {
    window.CART = window.CART.filter(x => x.id !== id);
    cartSave();
    cartRender();
};

/* ══ INIT ══ */
document.addEventListener('DOMContentLoaded', () => {

    /* Inject drawer HTML once */
    if (!document.getElementById('cart-drawer')) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `
            <div class="cart-overlay" id="cart-overlay"></div>
            <div class="cart-drawer" id="cart-drawer">
                <div class="cart-header">
                    <h3>Shopping Cart</h3>
                    <button class="cart-close" id="cart-close" aria-label="Close cart">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="cart-body" id="cart-items-container"></div>
                <div class="cart-footer">
                    <div class="cart-total">
                        <span>Total</span>
                        <span id="cart-total-price">₱0.00</span>
                    </div>
                    <button class="btn-checkout" onclick="alert('Checkout coming soon!')">
                        <i class='bx bx-credit-card'></i> Proceed to Checkout
                    </button>
                </div>
            </div>`;
        document.body.appendChild(wrap);
    }

    /* Wire up trigger */
    const trigger = document.getElementById('cart-trigger');
    if (trigger) {
        trigger.addEventListener('click', e => {
            e.preventDefault();
            window.toggleCart(true);
        });
    }

    /* Wire close button & overlay */
    document.addEventListener('click', e => {
        if (e.target.closest('#cart-close') || e.target.id === 'cart-overlay') {
            window.toggleCart(false);
        }
    });

    /* Initial badge sync */
    cartUpdateBadge();

    /* Listen for cross-tab changes */
    window.addEventListener('storage', e => {
        if (e.key === CART_KEY) {
            window.CART = JSON.parse(e.newValue) || [];
            cartUpdateBadge();
        }
    });
});
