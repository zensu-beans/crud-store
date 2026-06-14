document.addEventListener('DOMContentLoaded', () => {

    /* ── Navbar scroll shadow ── */
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 10);
        });
    }

    /* ── Search overlay inject ── */
    if (!document.getElementById('search-overlay')) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `
            <div class="search-overlay" id="search-overlay">
                <div class="search-overlay-inner">
                    <div class="search-overlay-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" id="search-overlay-input"
                               placeholder="Search products, categories…"
                               autocomplete="off">
                        <button class="search-overlay-close" id="search-overlay-close" aria-label="Close search">
                            <i class='bx bx-x'></i>
                        </button>
                    </div>
                    <div class="search-overlay-hint">
                        Press <kbd>Enter</kbd> to search · <kbd>Esc</kbd> to close
                    </div>
                </div>
            </div>`;
        document.body.appendChild(wrap);
    }

    const searchOverlay = document.getElementById('search-overlay');
    const searchInput   = document.getElementById('search-overlay-input');
    const searchClose   = document.getElementById('search-overlay-close');
    const searchTrigger = document.getElementById('search-trigger');

    function openSearch() {
        searchOverlay.classList.add('open');
        setTimeout(() => searchInput && searchInput.focus(), 80);
    }

    function closeSearch() {
        searchOverlay.classList.remove('open');
        if (searchInput) searchInput.value = '';
    }

    // On products.html the search icon focuses the inline box — skip overlay there
    const isProductsPage = !!document.getElementById('search-input');
    if (searchTrigger && !isProductsPage) {
        searchTrigger.addEventListener('click', e => {
            e.preventDefault();
            openSearch();
        });
    }

    if (searchClose) {
        searchClose.addEventListener('click', closeSearch);
    }

    if (searchOverlay) {
        searchOverlay.addEventListener('click', e => {
            if (e.target === searchOverlay) closeSearch();
        });
    }

    /* Submit: go to products page with ?q= */
    if (searchInput) {
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                const q = searchInput.value.trim();
                if (q) {
                    window.location.href = `products.html?q=${encodeURIComponent(q)}`;
                }
            }
            if (e.key === 'Escape') closeSearch();
        });
    }

    /* ── 5-click CRUD ── */
    const logo  = document.getElementById('logo-trigger');
    const hint  = document.getElementById('logo-hint');
    if (logo) {
        let clicks = 0, timer = null, hintTimer = null;
        const msgs = { 3: '✦ Keep going…', 4: '✦ One more…' };

        function showHint(msg) {
            if (!hint) return;
            hint.textContent = msg;
            hint.classList.add('show');
            clearTimeout(hintTimer);
            hintTimer = setTimeout(() => hint.classList.remove('show'), 1200);
        }

        logo.addEventListener('click', () => {
            clicks++;
            if (msgs[clicks]) showHint(msgs[clicks]);
            if (clicks >= 5) {
                if (hint) hint.classList.remove('show');
                clearTimeout(timer);
                window.location.href = 'index.php';
                return;
            }
            clearTimeout(timer);
            timer = setTimeout(() => {
                clicks = 0;
                if (hint) hint.classList.remove('show');
            }, 3000);
        });
    }
});
