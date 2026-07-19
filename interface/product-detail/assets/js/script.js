// Dapatkan ID Produk dari URL (?id_produk=...) atau (?id=...)
const urlParams = new URLSearchParams(window.location.search);
const productId = urlParams.get('id_produk') || urlParams.get('id');

// Mendapatkan ID Pengguna (User Session/Localstorage)
const userId = CardHavenAuth.id() || null;
console.log(productId);

// State Variables
let currentProductPrice = 0;
let currentQty = 1;
let currentIdGame = null;
let currentProductStock = 0;

// State untuk Related Product
let allRelatedProducts = [];
let currentRelatedPage = 1;
const relatedLimit = 4;

// Penyesuaian Base URL XAMPP secara dinamis dan aman
const base = typeof BASE_URL !== 'undefined' ? BASE_URL : '/CardHaven';

document.addEventListener('DOMContentLoaded', () => {
    if (!productId) {
        cardhavenAlert('error', 'Error', 'Product ID is missing from URL!', () => {
            window.location.href = `${base}/home`;
        });
        return;
    }
    fetchProductDetail();
});

// 1. Fetch Detail Product
function fetchProductDetail() {
    fetch(`${base}/interface/product-detail/controller/ProductDetailController.php?action=get_detail&id_produk=${productId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const prod = data.data;
                currentProductPrice = parseFloat(prod.harga_jual);
                currentIdGame = prod.id_game;
                
                // SIMPAN STOK KE VARIABEL GLOBAL (Pastikan di-parse ke Integer)
                currentProductStock = parseInt(prod.stok) || 0;

                // Jika stoknya 0, set currentQty jadi 0, jika tidak minimal 1
                currentQty = currentProductStock > 0 ? 1 : 0;
                document.getElementById('qtyValue').innerText = currentQty;

                // Bind ke DOM
                document.getElementById('detailNama').innerText = prod.nama_produk;
                document.getElementById('detailStok').innerText = prod.stok;

                // Tampilan habis: badge SOLD OUT + nonaktifkan tombol beli
                const soldOut = currentProductStock <= 0;
                const btnCart = document.querySelector('.btn-add-cart');
                const btnCheckout = document.querySelector('.btn-checkout');
                [btnCart, btnCheckout].forEach(btn => {
                    if (!btn) return;
                    btn.disabled = soldOut;
                    btn.style.opacity = soldOut ? '0.5' : '';
                    btn.style.cursor = soldOut ? 'not-allowed' : '';
                });
                if (btnCart) btnCart.innerText = soldOut ? 'Out of Stock' : 'Add To Cart';
                const imgBox = document.querySelector('.pd-image-box');
                const oldBadge = document.getElementById('pd-soldout-badge');
                if (oldBadge) oldBadge.remove();
                if (soldOut && imgBox) {
                    imgBox.style.position = 'relative';
                    const badge = document.createElement('div');
                    badge.id = 'pd-soldout-badge';
                    badge.style.cssText = 'position:absolute; top:1rem; left:1rem; background:#dc2626; color:#fff; font-weight:800; letter-spacing:1px; padding:0.4rem 1rem; border-radius:0.4rem; z-index:2;';
                    badge.innerText = 'SOLD OUT';
                    imgBox.appendChild(badge);
                }

                document.getElementById('detailGame').innerText = prod.nama_game || 'General';
                document.getElementById('detailType').innerText = prod.tipe_produk || 'Card';
                document.getElementById('detailKondisi').innerText = prod.kondisi || 'Near Mint';
                document.getElementById('detailDeskripsi').innerText = prod.deskripsi;
                document.getElementById('detailHarga').innerText = 'Rp.' + currentProductPrice.toLocaleString('en-US');
                
                if (prod.foto) {
                    let fotoPath = prod.foto;
                    // Data lama: path tersimpan dgn prefix folder lama → arahkan ke lokasi baru
                    if (fotoPath.startsWith('image-profile/')) fotoPath = `assets/image/${fotoPath}`;
                    if (fotoPath.includes('assets/')) {
                        document.getElementById('detailFoto').src = `${base}/${fotoPath}`;
                    } else {
                        document.getElementById('detailFoto').src = `${base}/assets/image/products/${fotoPath}`;
                    }
                } else {
                    document.getElementById('detailFoto').src = `${base}/assets/image/image-profile/defaultProduct.jpg`;
                }

                fetchRelatedProducts();
            } else {
                cardhavenAlert('error', 'Not Found', 'Product not found.');
            }
        })
        .catch(err => console.error(err));
}

// 2. Fetch & Render Related Products
function fetchRelatedProducts() {
    fetch(`${base}/interface/product-detail/controller/ProductDetailController.php?action=get_related&id_game=${currentIdGame}&id_produk=${productId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // DOUBLE PROTECTION: Filter out produk ini sendiri dari array (Pastikan tipe datanya sama dengan == )
                allRelatedProducts = data.data.filter(item => item.id_produk != productId);
                
                renderRelatedProducts();
            }
        });
}

function renderRelatedProducts() {
    const grid = document.getElementById('relatedGrid');
    grid.innerHTML = '';

    // Hitung total halaman
    const totalPages = Math.ceil(allRelatedProducts.length / relatedLimit);

    // --- LOGIKA OPACITY TOMBOL PAGINATION ---
    const btnPrev = document.getElementById('btnPrevRelated');
    const btnNext = document.getElementById('btnNextRelated');

    if (btnPrev && btnNext) {
        if (currentRelatedPage <= 1) {
            btnPrev.style.opacity = '0.3';
            btnPrev.style.cursor = 'default';
        } else {
            btnPrev.style.opacity = '1';
            btnPrev.style.cursor = 'pointer';
        }

        if (currentRelatedPage >= totalPages || totalPages === 0) {
            btnNext.style.opacity = '0.3';
            btnNext.style.cursor = 'default';
        } else {
            btnNext.style.opacity = '1';
            btnNext.style.cursor = 'pointer';
        }
    }
    // ----------------------------------------

    const startIndex = (currentRelatedPage - 1) * relatedLimit;
    const itemsToShow = allRelatedProducts.slice(startIndex, startIndex + relatedLimit);

    if (itemsToShow.length === 0) {
        grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #888;">No related products found.</p>';
        return;
    }

    itemsToShow.forEach(item => {
        let relFotoPath = item.foto || 'assets/image/image-profile/defaultProduct.jpg';
        let relFotoSrc = '';

        // Data lama: path tersimpan dgn prefix folder lama → arahkan ke lokasi baru
        if (relFotoPath.startsWith('image-profile/')) relFotoPath = `assets/image/${relFotoPath}`;
        if (relFotoPath.includes('assets/')) {
            relFotoSrc = `${base}/${relFotoPath}`;
        } else {
            relFotoSrc = `${base}/assets/image/products/${relFotoPath}`;
        }

        // --- PERBAIKAN LOGIKA STOK DI SINI ---
        // Ambil stok asli dari database. Kalau datanya ga ada/undefined, paksa jadi 0 (jangan 1).
        let stokTersedia = parseInt(item.stok);
        if (isNaN(stokTersedia)) {
            console.warn(`Stok tidak ditemukan untuk produk ID ${item.id_produk}. Pastikan SP SQL sudah narik kolom 'stok'.`);
            stokTersedia = 0; 
        }
        let soldOut = stokTersedia <= 0;
        // ------------------------------------

        const card = document.createElement('div');
        card.className = 'rel-card';
        card.innerHTML = `
            <img src="${relFotoSrc}" class="rel-image" alt="Related" style="margin-bottom: 10px;">
            <div class="rel-title-row" style="margin-bottom: 5px;">
                <h4 class="rel-title" style="margin: 0; font-size: 13px;">${item.nama_produk}</h4>
                <span class="rel-game" style="font-size: 10px;">${item.nama_game || 'General'}</span>
            </div>
            <p class="rel-price" style="margin: 0 0 10px 0; font-size: 12px; font-weight: bold;">Price: Rp${parseFloat(item.harga_jual).toLocaleString('id-ID')}</p>
            
            <!-- Hidden Span Untuk Dibaca oleh fungsi window.buyNow dan addRelatedToCart -->
            <span id="qty-val-${item.id_produk}" data-stok="${stokTersedia}" style="display:none;">1</span>

            <!-- Tombol Check Detail & Add To Cart Berdampingan -->
            <div style="display: flex; gap: 0.4rem; margin-bottom: 0.4rem;">
                <button onclick="window.goToDetail(${item.id_produk})" 
                        style="flex: 1; padding: 0.4rem 0; font-size: 0.75rem; font-weight:600; color: var(--primary-color, #173C99); border: 1px solid var(--primary-color, #173C99); background: transparent; border-radius: 9999px; cursor: pointer;">
                    Detail
                </button>
                <button onclick="addRelatedToCart(${item.id_produk}, ${item.harga_jual})"
                        ${soldOut ? 'disabled' : ''}
                        style="flex: 1; padding: 0.4rem 0; font-size: 0.75rem; font-weight:600; ${soldOut ? 'opacity:0.5; cursor:default;' : 'cursor:pointer;'}; border: 1px solid var(--primary-color, #173C99); border-radius: 9999px; background: transparent; color: var(--primary-color, #173C99);">
                    ${soldOut ? 'Out of stock' : '+ Cart'}
                </button>
            </div>
            
            <!-- Tombol Checkout Product Bawahnya -->
            <button onclick="window.buyNow(${item.id_produk}, ${item.harga_jual})"
                    ${soldOut ? 'disabled' : ''}
                    style="width: 100%; padding: 0.5rem 0; font-size: 0.8rem; font-weight:600; background: var(--bg-gradient, linear-gradient(90deg, #173C99, #0D47A1)); border: none; border-radius: 9999px; color: #fff; ${soldOut ? 'opacity:0.5; cursor:default;' : 'cursor:pointer;'}">
                Checkout
            </button>
        `;
        grid.appendChild(card);
    });
}

// =====================================================================
// FUNGSI UNTUK TOMBOL-TOMBOL DI RELATED PRODUCT
// =====================================================================

// Tambah Ke Keranjang Khusus Related Product
window.addRelatedToCart = function(id_produk, harga) {
    if (!userId || userId === "0") {
        cardhavenAlert('error', 'Authentication Required', 'Please login to add items to your cart.');
        return;
    }

    // Ambil data stok dari span hidden
    const qtyEl = document.getElementById(`qty-val-${id_produk}`);
    const stok = parseInt(qtyEl.dataset.stok) || 0;

    console.log(stok);
    
    // PENCEGAHAN STOK HABIS
    if (stok <= 0) {
        cardhavenAlert('error', 'Out of Stock', 'Barang ini sedang habis.');
        return;
    }

    const fd = new FormData();
    fd.append('id_pengguna', userId);
    fd.append('id_produk', id_produk);
    fd.append('harga', harga);
    fd.append('qty', 1);

    fetch(`${base}/interface/product-detail/controller/ProductDetailController.php?action=add_to_cart`, {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            cardhavenToast('success', 'Successfully added related product to cart!');
        } else {
            cardhavenAlert('error', 'Error', res.msg || 'Gagal menambahkan produk.');
        }
    })
    .catch(err => {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Terjadi kesalahan sistem.');
    });
};

// Checkout Khusus Related Product
window.buyNow = function(idProduk, hargaSatuan) {
    if (!userId || userId === "0") {
        cardhavenAlert('error', 'Failed', 'Please login first to checkout!');
        return;
    }

    // Ambil data stok dari span hidden
    const qtyEl  = document.getElementById(`qty-val-${idProduk}`);
    const qty    = parseInt(qtyEl.textContent) || 1;
    const stok   = parseInt(qtyEl.dataset.stok) || 0;

    // PENCEGAHAN STOK HABIS
    if (stok <= 0) {
        cardhavenAlert('error', 'Out of Stock', 'This product is currently out of stock.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'buy_now');
    fd.append('id_produk', idProduk);
    fd.append('harga_produk', hargaSatuan);
    fd.append('jumlah', qty);

    fetch(`${base}/interface/cart/controller_keranjang.php`, {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            window.location.href = res.redirect || `${base}/checkout`;
        } else {
            cardhavenAlert('error', 'Failed', res.message || 'Failed to proceed to checkout.');
        }
    })
    .catch(err => {
        console.error('buyNow error:', err);
        cardhavenAlert('error', 'System Error', 'A system error occurred.');
    });
};

function nextRelatedPage() {
    if ((currentRelatedPage * relatedLimit) < allRelatedProducts.length) {
        currentRelatedPage++;
        renderRelatedProducts();
    }
}
function prevRelatedPage() {
    if (currentRelatedPage > 1) {
        currentRelatedPage--;
        renderRelatedProducts();
    }
}

// 3. Logic Quantity dengan Limit Stok
function updateQty(change) {
    // Jika stok sedang kosong, tidak bisa nambah atau kurang
    if (currentProductStock === 0) {
        cardhavenAlert('warning', 'Out of Stock', 'This product is currently out of stock.');
        return;
    }

    let newQty = currentQty + change;

    // Cek batas bawah (minimal 1)
    if (newQty < 1) {
        newQty = 1;
    }
    
    // Cek batas atas (maksimal = stok)
    if (newQty > currentProductStock) {
        newQty = currentProductStock;
        cardhavenAlert('info', 'Stock Limit', `You can only add ${currentProductStock} product.`);
    }

    currentQty = newQty;
    document.getElementById('qtyValue').innerText = currentQty;
}

// 4. Add to Cart (Memanggil API yang akan eksekusi sp_ManageCart)
// 4. Add to Cart (Produk Utama)
function addToCart() {
    if (!userId || userId === "0") {
        cardhavenAlert('error', 'Authentication Required', 'Please login to add items to your cart.');
        return;
    }

    // PENCEGAHAN: Jika stok habis, hentikan fungsi!
    if (currentProductStock <= 0) {
        cardhavenAlert('error', 'Out of Stock', 'Barang ini sedang habis dan tidak bisa dimasukkan ke keranjang.');
        return;
    }

    const formData = new FormData();
    formData.append('id_pengguna', userId);
    formData.append('id_produk', productId);
    formData.append('harga', currentProductPrice);
    formData.append('qty', currentQty);

    fetch(`${base}/interface/product-detail/controller/ProductDetailController.php?action=add_to_cart`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            cardhavenToast('success', 'Successfully added product to cart!');
        } else {
            cardhavenAlert('error', 'Error', res.msg || 'Gagal menambahkan produk.');
        }
    })
    .catch(err => {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Terjadi kesalahan sistem.');
    });
}

// 6. Checkout Product (Produk Utama)
function checkoutProduct() {
    if (!userId || userId === "0") {
        cardhavenAlert('error', 'Failed', 'Please login first to checkout!');
        return;
    }
    
    // PENCEGAHAN: Jika stok habis, hentikan fungsi!
    if (currentProductStock <= 0) {
        cardhavenAlert('error', 'Out of Stock', 'Barang ini sedang habis dan tidak bisa di-checkout.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'buy_now');
    fd.append('id_produk', productId);
    fd.append('harga_produk', currentProductPrice);
    fd.append('jumlah', currentQty);

    fetch(`${base}/interface/cart/controller_keranjang.php`, {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            window.location.href = res.redirect || `${base}/checkout`;
        } else {
            cardhavenAlert('error', 'Failed', res.message || 'Failed to proceed to checkout.');
        }
    })
    .catch(err => {
        console.error('checkoutProduct error:', err);
        cardhavenAlert('error', 'System Error', 'A system error occurred.');
    });
}

// 5. Global GoToDetail (Dideklarasikan di window sesuai prompt)
window.goToDetail = function(idProduk) {
    window.location.href = `${base}/home/productdetail?id_produk=${idProduk}`;
}