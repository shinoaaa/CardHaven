// ==========================================
// KONFIGURASI UMUM
// ==========================================
const base = typeof BASE_URL !== 'undefined' ? BASE_URL : '/CardHaven';
const CART_CONTROLLER = `${base}/interface/cart/controller_keranjang.php`;

const getUserId = () => CardHavenAuth.id() || null;
const userId = getUserId();

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

// ==========================================
// STATE FILTER & PAGINATION
// ==========================================
let state = {
    search: '',     // <--- STATE BARU UNTUK SEARCH
    games: [],
    types: [],
    rarities: [],
    minPrice: null,
    maxPrice: null,
    sortBy: 'default',
    page: 1,
    limit: 6
};

let totalPages = 1;

let pendingGameName = null;

// ==========================================
// INITIALIZE
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    parseUrlParams();
    fetchFiltersDB(); // Pastikan urutannya: Parse URL -> Ambil DB -> Render Katalog
});

// Fungsi ini yang bikin error kalau nggak ada!
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// 1. Membaca Parameter URL dari Browser (Kebal Error)
function parseUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // TANGKAP KEYWORD PENCARIAN DARI NAVBAR
    if (urlParams.has('search') && urlParams.get('search').trim() !== '') {
        state.search = urlParams.get('search').trim();
        
        // Tampilkan teks "Related product to..." di Topbar
        const indicator = document.getElementById('search-indicator');
        if (indicator) {
            indicator.innerHTML = `Related product to "<span style="color:#F97316;">${escapeHtml(state.search)}</span>"`;
            indicator.style.display = 'block';
        }
    }

    // A. Dari klik Card Game di homepage 
    if (urlParams.has('id') && urlParams.get('id').trim() !== '') {
        state.games.push(urlParams.get('id').trim());
    }

    // B. Dari Form Explore Products Homepage
    if (urlParams.has('game_name') && urlParams.get('game_name').trim() !== '') {
        pendingGameName = urlParams.get('game_name').trim().toLowerCase(); 
    }

    // C. Tangkap Tipe Produk
    if (urlParams.has('product_type') && urlParams.get('product_type').trim() !== '') {
        const typeParam = urlParams.get('product_type').trim().toLowerCase();
        const validTypes = ['Single card', 'Booster pack', 'Booster box', 'Sleeve', 'Playmat', 'Toploader'];
        const matchedType = validTypes.find(t => t.toLowerCase() === typeParam);
        
        if (matchedType) state.types.push(matchedType);
        else state.types.push(urlParams.get('product_type').trim()); 
    }

    // D. Tangkap Harga
    if (urlParams.has('min_price') && urlParams.get('min_price') !== '') {
        state.minPrice = urlParams.get('min_price');
        document.getElementById('inputMinPrice').value = state.minPrice;
    }
    if (urlParams.has('max_price') && urlParams.get('max_price') !== '') {
        state.maxPrice = urlParams.get('max_price');
        document.getElementById('inputMaxPrice').value = state.maxPrice;
    }
}

// 2. Fetch Master Filter (Game & Rarity) dari DB
function fetchFiltersDB() {
    fetch(`${base}/interface/catalogue/controller/CatalogueController.php?action=get_filters`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                
                // --- PENCOCOKAN NAMA GAME KE ID GAME (Jika dari form Explore) ---
                if (pendingGameName) {
                    const matchedGame = data.games.find(g => 
                        g.nama_game.toLowerCase() === pendingGameName || 
                        g.nama_game.toLowerCase().includes(pendingGameName) ||
                        pendingGameName.includes(g.nama_game.toLowerCase())
                    );
                    // Kalau ketemu ID-nya, masukkan ke array filter
                    if (matchedGame && !state.games.includes(matchedGame.id_game.toString())) {
                        state.games.push(matchedGame.id_game.toString());
                    }
                }

                // Render Sidebar
                renderFilterList('listGames', data.games, 'id_game', 'nama_game', 'game');
                renderFilterList('listRarities', data.rarities, 'id_rarity', 'nama_rarity', 'rarity');
                
                checkRarityVisibility();

                // Nyalakan warna 'Active' pada Tipe Produk statis
                document.querySelectorAll('#listTypes .filter-item').forEach(el => {
                    if (state.types.includes(el.innerText)) el.classList.add('active');
                });
                
                // BARU SEKARANG KITA LOAD KATALOGNYA (Agar tidak balapan datanya)
                fetchCatalogue(); 
            }
        })
        .catch(err => console.error("Filter Fetch Error:", err));
}

function renderFilterList(containerId, dataArray, idKey, nameKey, filterType) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    dataArray.forEach(item => {
        const div = document.createElement('div');
        div.className = 'filter-item';
        
        const valStr = item[idKey].toString();
        const isActive = (filterType === 'game' && state.games.includes(valStr)) || 
                         (filterType === 'rarity' && state.rarities.includes(valStr));
        if (isActive) div.classList.add('active');

        div.innerText = item[nameKey];
        div.onclick = () => toggleFilter(filterType, valStr, div);
        container.appendChild(div);
    });
}

// 3. Logika Klik Toggle Filter
window.toggleFilter = function(type, value, element) {
    let targetArray;
    if (type === 'game') targetArray = state.games;
    if (type === 'type') targetArray = state.types;
    if (type === 'rarity') targetArray = state.rarities;

    const index = targetArray.indexOf(value);
    
    if (index > -1) {
        targetArray.splice(index, 1);
        element.classList.remove('active');
    } else {
        targetArray.push(value);
        element.classList.add('active');
    }

    state.page = 1; 
    if (type === 'type') checkRarityVisibility();
    fetchCatalogue();
};

function checkRarityVisibility() {
    const wrapper = document.getElementById('wrapperRarity');
    if (state.types.includes('Single card')) {
        wrapper.style.display = 'block';
    } else {
        wrapper.style.display = 'none';
        state.rarities = []; 
        document.querySelectorAll('#listRarities .filter-item').forEach(el => el.classList.remove('active'));
    }
}

window.applyPriceFilter = function() {
    const minVal = document.getElementById('inputMinPrice').value;
    const maxVal = document.getElementById('inputMaxPrice').value;
    state.minPrice = minVal !== '' ? minVal : null;
    state.maxPrice = maxVal !== '' ? maxVal : null;
    state.page = 1;
    fetchCatalogue();
};

window.applySort = function() {
    state.sortBy = document.getElementById('sortSelect').value;
    state.page = 1;
    fetchCatalogue();
};

window.resetFilters = function() {
    state = { games: [], types: [], rarities: [], minPrice: null, maxPrice: null, sortBy: 'default', page: 1, limit: 6 };
    document.querySelectorAll('.filter-item.active').forEach(el => el.classList.remove('active'));
    document.getElementById('inputMinPrice').value = '';
    document.getElementById('inputMaxPrice').value = '';
    document.getElementById('sortSelect').value = 'default';
    
    // Bersihkan URL tanpa reload
    window.history.pushState({}, document.title, window.location.pathname);
    
    checkRarityVisibility();
    fetchCatalogue();
};

// ==========================================
// FETCH & RENDER CATALOGUE
// ==========================================
function fetchCatalogue() {
    const fd = new URLSearchParams();
    fd.append('action', 'get_products');
    
    // TAMBAHKAN PARAMETER SEARCH KE CONTROLLER PHP
    if (state.search) fd.append('search_query', state.search);
    
    if (state.games.length) fd.append('games', state.games.join(','));
    if (state.types.length) fd.append('types', state.types.join(','));
    if (state.rarities.length) fd.append('rarities', state.rarities.join(','));
    if (state.minPrice) fd.append('min_price', state.minPrice);
    if (state.maxPrice) fd.append('max_price', state.maxPrice);
    fd.append('sort_by', state.sortBy);
    fd.append('page', state.page);
    fd.append('limit', state.limit);

    fetch(`${base}/interface/catalogue/controller/CatalogueController.php?${fd.toString()}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                totalPages = Math.ceil((data.total_rows || 1) / state.limit);
                if (totalPages === 0) totalPages = 1; 
                renderCatalogue(data.data);
                updatePaginationUI();
            } else {
                console.error("SQL/Backend Error:", data.msg);
            }
        })
        .catch(err => console.error("Fetch Data Error:", err));
}


function renderCatalogue(products) {
    const grid = document.getElementById('catalogueGrid');
    grid.innerHTML = '';

    if (products.length === 0) {
        grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #888; font-size: 1.2rem; margin-top:50px;">Oops, no products found based on your filters.</p>';
        return;
    }

    products.forEach(item => {
        let fotoPath = item.foto || 'assets/image/image-profile/defaultProduct.jpg';
        // Data lama: path tersimpan dgn prefix folder lama → arahkan ke lokasi baru
        if (fotoPath.startsWith('image-profile/')) fotoPath = `assets/image/${fotoPath}`;
        let fotoSrc = fotoPath.includes('assets/') ? `${base}/${fotoPath}` : `${base}/assets/image/products/${fotoPath}`;

        let stokTersedia = parseInt(item.stok);
        if (isNaN(stokTersedia)) stokTersedia = 0; 
        let soldOut = stokTersedia <= 0;

        const card = document.createElement('div');
        card.className = 'cat-card';
        card.innerHTML = `
            <img src="${fotoSrc}" class="cat-image" alt="Product" style="margin-bottom: 10px; ${soldOut ? 'filter: grayscale(1) brightness(0.7);' : ''}">
            <div class="cat-title-row" style="margin-bottom: 5px;">
                <h4 class="cat-title" style="margin: 0; font-size: 13px;">${item.nama_produk}</h4>
                <span class="cat-game" style="font-size: 10px;">${item.nama_game || 'General'}</span>
            </div>
            <p class="cat-price" style="margin: 0 0 10px 0; font-size: 12px; font-weight: bold;">Price: ${formatRupiah(item.harga_jual)}</p>
            
            <span id="qty-val-${item.id_produk}" data-stok="${stokTersedia}" style="display:none;">1</span>

            <div style="display: flex; gap: 0.4rem; margin-bottom: 0.4rem;">
                <button class="btn-detail" onclick="window.goToDetail(${item.id_produk})" 
                        style="flex: 1; padding: 0.4rem 0; font-size: 0.75rem; font-weight:600; color: #173C99; border: 1px solid #173C99; background: transparent; border-radius: 9999px; cursor: pointer;">
                    Check Detail
                </button>
                <button class="btn-cart" onclick="window.addCatToCart(${item.id_produk}, ${item.harga_jual})" 
                        ${soldOut ? 'disabled' : ''} 
                        style="flex: 1; padding: 0.4rem 0; font-size: 0.75rem; font-weight:600; color: #173C99; border: 1px solid #173C99; background: transparent; border-radius: 9999px; ${soldOut ? 'opacity:0.5; cursor:default;' : 'cursor: pointer;'}">
                    ${soldOut ? 'Out of Stock' : 'Add To Cart'}
                </button>
            </div>

            <button class="btn-checkout-card" onclick="window.buyNowCat(${item.id_produk}, ${item.harga_jual})"
                    ${soldOut ? 'disabled' : ''} 
                    style="width: 100%; padding: 0.5rem 0; font-size: 0.8rem; font-weight:600; background: linear-gradient(90deg, #173C99, #0D47A1); border: none; border-radius: 9999px; color: #fff; ${soldOut ? 'opacity:0.5; cursor:default;' : 'cursor: pointer;'}">
                Checkout Product
            </button>
        `;
        grid.appendChild(card);
    });
}

// ==========================================
// PAGINATION & TRANSAKSI
// ==========================================



window.changePage = function(direction) {
    const newPage = state.page + direction;
    if (newPage >= 1 && newPage <= totalPages) {
        state.page = newPage;
        fetchCatalogue();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

function updatePaginationUI() {
    document.getElementById('catPageInfo').innerText = `${state.page} of ${totalPages}`;
    const btnPrev = document.getElementById('btnCatPrev');
    const btnNext = document.getElementById('btnCatNext');

    if (btnPrev) {
        btnPrev.style.opacity = state.page <= 1 ? '0.3' : '1';
        btnPrev.style.cursor = state.page <= 1 ? 'default' : 'pointer';
    }
    if (btnNext) {
        btnNext.style.opacity = state.page >= totalPages ? '0.3' : '1';
        btnNext.style.cursor = state.page >= totalPages ? 'default' : 'pointer';
    }
}

window.goToDetail = function(idProduk) {
    window.location.href = `${base}/home/productdetail?id_produk=${idProduk}`;
};

window.addCatToCart = function(idProduk, hargaSatuan) {
    const qtyEl = document.getElementById(`qty-val-${idProduk}`);
    const qty   = parseInt(qtyEl.textContent) || 1;
    const stok  = parseInt(qtyEl.dataset.stok) || 0;

    if (stok <= 0) { cardhavenAlert('error', 'Out of Stock', 'Barang ini sedang habis.'); return; }
    if (!userId || userId === "0") { cardhavenAlert('error', 'Login Required', 'Please login to add items to your cart.'); return; }

    const fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('id_produk', idProduk);
    fd.append('harga_produk', hargaSatuan); 
    fd.append('jumlah', qty);               

    fetch(CART_CONTROLLER, { method: 'POST', body: fd })
    .then(res => res.json()).then(res => {
        if (res.success || res.status === 'success') cardhavenToast('success', 'Product added to cart!');
        else cardhavenAlert('error', 'Failed', res.message || res.msg || 'Gagal menambahkan produk.');
    });
};

window.buyNowCat = function(idProduk, hargaSatuan) {
    const qtyEl  = document.getElementById(`qty-val-${idProduk}`);
    const qty    = parseInt(qtyEl.textContent) || 1;
    const stok   = parseInt(qtyEl.dataset.stok) || 0;

    if (stok <= 0) { cardhavenAlert('error', 'Out of Stock', 'Barang ini sedang habis.'); return; }
    if (!userId || userId === "0") { cardhavenAlert('error', 'Login Required', 'Please login first to checkout!'); return; }

    const fd = new FormData();
    fd.append('action', 'buy_now');
    fd.append('id_produk', idProduk);
    fd.append('harga_produk', hargaSatuan);
    fd.append('jumlah', qty);

    fetch(CART_CONTROLLER, { method: 'POST', body: fd })
    .then(res => res.json()).then(res => {
        if (res.success) window.location.href = res.redirect || `${base}/checkout`;
        else cardhavenAlert('error', 'Failed', res.message || 'Failed to proceed to checkout.');
    });
};