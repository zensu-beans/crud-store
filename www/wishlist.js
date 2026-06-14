const WISH_KEY = 'crud_store_wishlist';

/* ── State: array of { id, name, price, img } ── */
function wishGet() {
    return JSON.parse(localStorage.getItem(WISH_KEY)) || [];
}
function wishSave(list) {
    localStorage.setItem(WISH_KEY, JSON.stringify(list));
    wishUpdateBadge(list);
}

/* ── Badge sync ── */
function wishUpdateBadge(list) {
    list = list || wishGet();
    document.querySelectorAll('.wishlist-badge, #wishlist-counter').forEach(el => {
        el.textContent = list.length;
        el.style.display = list.length > 0 ? 'flex' : 'none';
    });
    /* Update drawer count label */
    const countEl = document.getElementById('wish-drawer-count');
    if (countEl) countEl.textContent = list.length;
}

/* ── Public: toggle a product in the wishlist ── */
window.wishToggle = function (product) {
    /* product: { id, name, price, img } */
    let list = wishGet();
    const idx = list.findIndex(x => x.id === product.id);
    if (idx >= 0) {
        list.splice(idx, 1);
    } else {
        list.push({ id: product.id, name: product.name, price: product.price, img: product.img || '' });
    }
    wishSave(list);
    wishRender();
    return idx < 0; /* true if now wishlisted */
};

/* ── Public: check if an item is wishlisted ── */
window.wishHas = function (id) {
    return wishGet().some(x => x.id === id);
};

/* ── Remove from wishlist ── */
window.wishRemove = function (id) {
    const list = wishGet().filter(x => x.id !== id);
    wishSave(list);
    wishRender();

    /* Reflect on product cards on products.html */
    const cardWish = document.querySelector(`.card-wish[data-id="${id}"]`);
    if (cardWish) {
        cardWish.classList.remove('active');
        cardWish.querySelector('i').className = 'bx bx-heart';
    }
};

/* ── Move wishlist item to cart ── */
window.wishMoveToCart = function (id) {
    const item = wishGet().find(x => x.id === id);
    if (!item) return;
    window.addToCart(item);
    window.wishRemove(id);

    /* Show toast if available */
    if (typeof showToast === 'function') showToast(`${item.name} moved to cart`);
};

/* ── Toggle drawer ── */
window.toggleWishlist = function (show) {
    const drawer  = document.getElementById('wishlist-drawer');
    const overlay = document.getElementById('wishlist-overlay');
    if (!drawer || !overlay) return;
    if (show) {
        wishRender();
        drawer.classList.add('open');
        overlay.classList.add('open');
    } else {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
    }
};

/* ── Render drawer ── */
window.wishRender = function wishRender() {
    const container = document.getElementById('wish-drawer-items');
    if (!container) return;
    const list = wishGet();

    /* Sync count label */
    const countEl = document.getElementById('wish-drawer-count');
    if (countEl) countEl.textContent = list.length;

    if (!list.length) {
        container.innerHTML = `
            <div class="wish-empty">
                <i class='bx bx-heart'></i>
                <div>
                    <p>Your wishlist is empty</p>
                    <small style="color:#999;font-size:11px;">Tap hearts on products to save them!</small>
                </div>
            </div>`;
        return;
    }

    container.innerHTML = list.map(item => `
        <div class="wish-item">
            <div class="wish-item-img">
                <img src="${item.img}" alt="${item.name}" onerror="this.style.display='none'">
            </div>
            <div class="wish-item-info">
                <div class="wish-item-name">${item.name}</div>
                <div class="wish-item-price">₱${Number(item.price).toLocaleString()}</div>
            </div>
            <div class="wish-item-actions">
                <button class="wish-action-btn add-cart" onclick="wishMoveToCart(${item.id})" title="Move to cart">
                    <i class='bx bx-cart-add'></i>
                </button>
                <button class="wish-action-btn remove-wish" onclick="wishRemove(${item.id})" title="Remove">
                    <i class='bx bx-trash'></i>
                </button>
            </div>
        </div>
    `).join('');
}

/* ══ INIT ══ */
document.addEventListener('DOMContentLoaded', () => {

    /* Inject drawer HTML if not already present */
    if (!document.getElementById('wishlist-drawer')) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `
            <div class="wishlist-overlay" id="wishlist-overlay"></div>
            <div class="wishlist-drawer" id="wishlist-drawer">
                <div class="wish-drawer-header">
                    <h3>My Wishlist (<span id="wish-drawer-count">0</span>)</h3>
                    <button class="wish-drawer-close" id="wish-drawer-close" aria-label="Close wishlist">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="wish-drawer-items" id="wish-drawer-items"></div>
            </div>`;
        document.body.appendChild(wrap);
    }

    /* Wire trigger */
    const trigger = document.getElementById('wishlist-trigger');
    if (trigger) {
        trigger.addEventListener('click', e => {
            e.preventDefault();
            window.toggleWishlist(true);
        });
    }

    /* Wire close */
    document.addEventListener('click', e => {
        if (e.target.closest('#wish-drawer-close') || e.target.id === 'wishlist-overlay') {
            window.toggleWishlist(false);
        }
    });

    /* Initial badge & render */
    wishUpdateBadge();

    /* Cross-tab sync */
    window.addEventListener('storage', e => {
        if (e.key === WISH_KEY) {
            wishUpdateBadge();
            wishRender();
        }
    });
});
