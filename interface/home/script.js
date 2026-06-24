// 1. Tambahkan path controller keranjang di bagian atas
const CART_CONTROLLER = '/cardhaven/interface/cart/controller_keranjang.php';

// 2. Fungsi untuk mengambil ID Pengguna (Pastikan ini ada)
var getUserId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');


document.addEventListener("DOMContentLoaded", function() {
    
    // Pisahkan state halaman secara absolut (Masing-masing jalan sendiri)
    let currentEventPage = 1;
    let currentGameBarPage = 1;
    let currentGameCardPage = 1;
    let currentProductPage = 1;
    let currentPromoPage = 1; // <-- STATE BARU KHUSUS PROMO
    
    let totalEventPages = 0; 
    let totalGameBarPages = 0;   
    let totalGameCardPages = 0;  
    let totalProductPages = 0;
    let totalPromoPages = 0; // <-- TOTAL PAGE PROMO

    // Fungsi format rupiah
    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    function loadData() {
        // Update URL kirim semua parameter halaman termasuk halaman_promo
        const urlController = `/cardhaven/interface/home/controller/getData.php?halaman_event=${currentEventPage}&halaman_game_bar=${currentGameBarPage}&halaman_game_card=${currentGameCardPage}&halaman_product=${currentProductPage}&halaman_promo=${currentPromoPage}`;

        fetch(urlController)
            .then(response => {
                if (!response.ok) throw new Error("Gagal terhubung ke server");
                return response.json();
            })
            .then(data => {
                // Set state aktif dari response
                currentEventPage = data.halaman_event_aktif;
                currentGameBarPage = data.halaman_game_bar_aktif;
                currentGameCardPage = data.halaman_game_card_aktif;
                currentProductPage = data.halaman_product_aktif;
                currentPromoPage = data.halaman_promo_aktif; // <-- UPDATE STATE PROMO
                
                // Set total data
                totalEventPages = data.total_event; 
                totalGameBarPages = Math.ceil(data.total_game_bar / 4); 
                totalGameCardPages = Math.ceil(data.total_game_card / 4); 
                totalProductPages = Math.ceil(data.total_product / 4); 
                totalPromoPages = Math.ceil(data.total_promo / 4); // <-- UPDATE TOTAL PROMO DIBAGI 4

                updateTampilan(data);
            })
            .catch(error => console.error("Waduh, gagal fetch data:", error));
    }

    function updateTampilan(data) {
        // --- 1. RENDER EVENT (PREORDER) ---
        if (data.event) {
            document.getElementById('ui-event-title').textContent = data.event.nama_event;
            document.getElementById('ui-event-product').textContent = data.event.nama_produk;
            document.getElementById('ui-event-date').textContent = data.event.tanggal_sampai;
            document.getElementById('ui-event-desc').textContent = data.event.deskripsi;
            if(!data.event.foto){
                document.getElementById('ui-event-image').src = `/cardhaven/image-profile/defaultProduct.jpg`;
            } else {
                document.getElementById('ui-event-image').src = `/cardhaven/${data.event.foto}`;
            }
        }

        // --- 2. RENDER EVENT (PROMO) - RENDER 4 ITEM ---
        const promoContainer = document.querySelector('.promo-content'); // Pastikan HTML kamu pakai class ini untuk bungkus card promo
        if (promoContainer) {
            promoContainer.innerHTML = '';
            if (data.list_promo && data.list_promo.length > 0) {
                data.list_promo.forEach(promo => {
                    const bannerSrc = promo.foto_banner ? `/cardhaven/${promo.foto_banner}` : '/cardhaven/image-profile/defaultEvent.jpg';
                    const gameName = promo.nama_game ? promo.nama_game : 'All Games';
                    
                    const promoHTML = `
                    <div class="promo-card" style="background-image: url('${bannerSrc}');">
                        <div style="z-index: 999; display: flex; justify-content: center; align-items: center; flex-direction: column; row-gap: 0.75rem;">
                            <p style="color: #e4e4e4;"><span style="color: #90b3ff;">${gameName}</span>'s Event</p>
                            <h2 class="coolveticaa" style="text-align: center; width: 30rem;">${promo.nama_event}</h2>
                            <button style="background: var(--bg-gradient); color: white; padding: 0.5rem 2.5rem; border-radius: 9999px;">
                                Join Now
                            </button>
                        </div>
                        <div style="background-color: black; width: 100%; height: 100%; position: absolute; top: 0; left: 0; opacity: 50%;"></div>
                    </div>`;
                    promoContainer.innerHTML += promoHTML;
                });
            } else {
                promoContainer.innerHTML = '<span style="color: white; text-align: center; display: block; width: 100%;">Belum ada event promo saat ini</span>';
            }
        }

        // --- 3. RENDER GAME BAR ---
        const gameBarContainer = document.getElementById('ui-game-list');
        if (gameBarContainer) {
            gameBarContainer.innerHTML = '';
            if (data.list_game_bar && data.list_game_bar.length > 0) {
                data.list_game_bar.forEach(game => {
                    const aNav = document.createElement('a');
                    aNav.href = `list.php?id=${game.id_game}`;
                    aNav.textContent = game.nama_game;
                    aNav.style.color = 'white';
                    gameBarContainer.appendChild(aNav);
                });
            } else {
                gameBarContainer.innerHTML = '<span>Tidak ada game</span>';
            }
        }

        // --- 4. RENDER GAME CARD ---
        const gameCardContainer = document.getElementById('ui-game-card-list');
        if (gameCardContainer) {
            gameCardContainer.innerHTML = '';
            if (data.list_game_card && data.list_game_card.length > 0) {
                data.list_game_card.forEach(game => {
                    const bannerSrc = game.foto_banner ? `/cardhaven/${game.foto_banner}` : '/cardhaven/image-profile/defaultBanner.jpg';
                    const cardHTML = `
                    <a href="list.php?id=${game.id_game}">
                        <div class="game-card" style="background-image: url('${bannerSrc}');">
                            <h1 style="font-size: 1.25rem; z-index: 999; position: relative; color: white;">${game.nama_game}</h1>
                            <div style="background-color: black; width: 100%; height: 100%; position: absolute; top: 0; left: 0; opacity: 50%;"></div>
                        </div>
                    </a>`;
                    gameCardContainer.innerHTML += cardHTML;
                });
            } else {
                gameCardContainer.innerHTML = '<span style="color: white;">Tidak ada game tersedia</span>';
            }
        }

        // --- 5. RENDER PRODUK ---
        const productContainer = document.querySelector('.product-list');
        if (productContainer) {
            productContainer.innerHTML = '';
            if (data.list_product && data.list_product.length > 0) {
                data.list_product.forEach(prod => {
                    const safeDesc = prod.deskripsi ? prod.deskripsi : '';
                    const limitDesc = safeDesc.length > 80 ? safeDesc.substring(0, 80) + '...' : safeDesc;
                    const gameName = prod.nama_game ? prod.nama_game : '-';
                    const fotoSrc = prod.foto ? `/cardhaven/${prod.foto}` : '/cardhaven/image-profile/defaultProduct.jpg';

                    const cardHTML = `
                    <div class="product-card">
                        <div style="width: 47%; display: flex; align-items: center; justify-content: center;">
                            <div style="width: 100%; height: 85%; border-radius: 0.5rem; overflow: hidden;">
                                <img src="${fotoSrc}" style="height: 100%; object-fit: contain;">
                            </div>
                        </div>
                        <div style="width: 50%; display: flex; flex-direction: column; justify-content: center;">
                            <h2 class="coolveticaa" style="font-size: 1.75rem; color: var(--primary-color);">${prod.nama_produk}</h2>
                            <div style="display: flex;">
                                <div style="display: flex; gap: 1.5rem; margin-top: 1.5rem;">
                                    <div>
                                        <p class="product-header">Stock Remain:</p>
                                        <p class="product-paragraf">${prod.stok}</p>
                                    </div>
                                    <div>
                                        <p class="product-header">Game:</p>
                                        <p class="product-paragraf">${gameName}</p>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 1rem;">
                                <p class="product-header">Description:</p>
                                <p class="product-paragraf" style="width: 17rem; height: 5rem; text-align: justify; font-size: 0.75rem; overflow:hidden;">
                                    ${limitDesc}
                                </p>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; color: var(--primary-color); margin-top: 1.25rem;">
                                <h2>Price: <span id="display-price-${prod.id_produk}">${formatRupiah(prod.harga_jual)}</span></h2>
                                <div style="display: flex; align-items: center; gap: 10px; border: 1px solid #ccc; border-radius: 20px; padding: 2px 10px;">
                                    <span onclick="updateHomeQty(${prod.id_produk}, -1, ${prod.harga_jual})" style="cursor:pointer; font-weight:bold; padding: 0 5px;">-</span>
                                    <span id="qty-val-${prod.id_produk}" style="font-weight:bold; min-width: 20px; text-align:center;">1</span>
                                    <span onclick="updateHomeQty(${prod.id_produk}, 1, ${prod.harga_jual})" style="cursor:pointer; font-weight:bold; padding: 0 5px;">+</span>
                                </div>
                            </div>
                            <button style="width: 100%; padding: 0.5rem 0; font-size: 1rem; margin: 1.5rem 0rem 0.75rem 0rem; color: var(--primary-color); border: 1px solid var(--primary-color); background: transparent; border-radius: 9999px;">Check Detail</button>
                            <button class="btn-primary" 
                                    onclick="addToCart(${prod.id_produk}, ${prod.harga_jual})" 
                                    style="width: 100%; padding: 0.5rem 0; font-size: 1rem;">
                                Add To Cart
                            </button>
                        </div>
                    </div>`;
                    productContainer.innerHTML += cardHTML;
                });
            }
        }

        // --- 6. INFO HALAMAN & DISABLE TOMBOL ---
        // Helper buat ganti state tombol
        const setBtnState = (btn, isDisabled) => {
            if(btn) {
                btn.style.opacity = isDisabled ? '0.5' : '1';
                btn.style.cursor = isDisabled ? 'default' : 'pointer';
            }
        };

        // Event Preorder
        setBtnState(document.getElementById('btn-prev-event'), currentEventPage <= 1);
        setBtnState(document.getElementById('btn-next-event'), currentEventPage >= totalEventPages);

        // Promo
        const pageInfoPromo = document.getElementById('promo-page-info');
        if (pageInfoPromo) pageInfoPromo.innerHTML = `<span>${currentPromoPage}</span> <span>of</span> <span>${totalPromoPages > 0 ? totalPromoPages : 1}</span>`;
        setBtnState(document.getElementById('btn-prev-promo'), currentPromoPage <= 1);
        setBtnState(document.getElementById('btn-next-promo'), currentPromoPage >= totalPromoPages);

        // Game Bar
        setBtnState(document.getElementById('btn-prev-game'), currentGameBarPage <= 1);
        setBtnState(document.getElementById('btn-next-game'), currentGameBarPage >= totalGameBarPages);

        // Game Card
        setBtnState(document.getElementById('btn-prev-game-card'), currentGameCardPage <= 1);
        setBtnState(document.getElementById('btn-next-game-card'), currentGameCardPage >= totalGameCardPages);

        // Produk
        const pageInfoProduk = document.getElementById('ui-product-page-info');
        if (pageInfoProduk) pageInfoProduk.innerHTML = `<span>${currentProductPage}</span> <span>of</span> <span>${totalProductPages > 0 ? totalProductPages : 1}</span>`;
        setBtnState(document.getElementById('btn-prev-product'), currentProductPage <= 1);
        setBtnState(document.getElementById('btn-next-product'), currentProductPage >= totalProductPages);
    }

    // ==========================================
    // EVENT LISTENER KLIK BUTTON (TIDAK SALING TUMPANG TINDIH)
    // ==========================================
    
    // Event Preorder
    document.getElementById('btn-prev-event')?.addEventListener('click', () => { if (currentEventPage > 1) { currentEventPage--; loadData(); } });
    document.getElementById('btn-next-event')?.addEventListener('click', () => { if (currentEventPage < totalEventPages) { currentEventPage++; loadData(); } });

    // Promo
    document.getElementById('btn-prev-promo')?.addEventListener('click', () => { if (currentPromoPage > 1) { currentPromoPage--; loadData(); } });
    document.getElementById('btn-next-promo')?.addEventListener('click', () => { if (currentPromoPage < totalPromoPages) { currentPromoPage++; loadData(); } });

    // Game Bar
    document.getElementById('btn-prev-game')?.addEventListener('click', () => { if (currentGameBarPage > 1) { currentGameBarPage--; loadData(); } });
    document.getElementById('btn-next-game')?.addEventListener('click', () => { if (currentGameBarPage < totalGameBarPages) { currentGameBarPage++; loadData(); } });

    // Game Card
    document.getElementById('btn-prev-game-card')?.addEventListener('click', () => { if (currentGameCardPage > 1) { currentGameCardPage--; loadData(); } });
    document.getElementById('btn-next-game-card')?.addEventListener('click', () => { if (currentGameCardPage < totalGameCardPages) { currentGameCardPage++; loadData(); } });

    // Produk
    document.getElementById('btn-prev-product')?.addEventListener('click', () => { if (currentProductPage > 1) { currentProductPage--; loadData(); } });
    document.getElementById('btn-next-product')?.addEventListener('click', () => { if (currentProductPage < totalProductPages) { currentProductPage++; loadData(); } });

    // Initial Load
    loadData();
});
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { 
        style: 'currency', 
        currency: 'IDR', 
        minimumFractionDigits: 0 
    }).format(angka);
}


// 3. Fungsi utama Add To Cart
window.addToCart = function(idProduk, harga) {
    const userId = getUserId();

    // Cek Login
    if (!userId || userId === "0") {
        alert("Silahkan login terlebih dahulu!");
        window.location.href = "/cardhaven/interface/login-page/";
        return;
    }

    // Siapkan data untuk dikirim
    const fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('id_produk', idProduk);
    fd.append('harga_produk', harga);
    fd.append('id_pengguna_js', userId); // Menggunakan ID dari storage agar sinkron

    // Kirim data ke controller_keranjang.php
    fetch(CART_CONTROLLER, {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            // Jika pakai SweetAlert (cardhavenAlert)
            cardhavenAlert('success', 'Berhasil', 'Produk ditambahkan ke keranjang!');
        } else {
            alert("Gagal: " + res.message);
        }
    })
    .catch(err => console.error("Error add to cart:", err));
};

window.updateHomeQty = function(id, change, hargaSatuan) {
    const qtyEl = document.getElementById(`qty-val-${id}`);
    const priceEl = document.getElementById(`display-price-${id}`);
    
    let currentQty = parseInt(qtyEl.textContent);
    currentQty += change;
    
    if (currentQty < 1) currentQty = 1; // Minimal 1
    
    // Update teks quantity
    qtyEl.textContent = currentQty;
    
    // Update teks harga (Harga Satuan * Quantity Baru)
    priceEl.textContent = formatRupiah(currentQty * hargaSatuan);
};

// Update fungsi addToCart untuk mengambil nilai Qty terbaru
window.addToCart = function(idProduk, hargaSatuan) {
    const userId = getUserId();
    // Ambil jumlah barang dari elemen quantity
    const qty = parseInt(document.getElementById(`qty-val-${idProduk}`).textContent);

    if (!userId || userId === "0") {
        alert("Silahkan login terlebih dahulu");
        return;
    }

    const fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('id_produk', idProduk);
    fd.append('harga_produk', hargaSatuan); // Kirim harga satuan
    fd.append('jumlah', qty);               // Kirim jumlah yang dipilih
    fd.append('id_pengguna_js', userId);

    fetch('/cardhaven/interface/cart/controller_keranjang.php', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            cardhavenAlert('success', 'Berhasil', `${qty} item ditambahkan ke keranjang!`);
            // Reset qty ke 1 setelah berhasil
            document.getElementById(`qty-val-${idProduk}`).textContent = 1;
            document.getElementById(`display-price-${idProduk}`).textContent = formatRupiah(hargaSatuan);
        }
    });
};