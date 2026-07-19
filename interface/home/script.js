// 1. Tambahkan path controller keranjang di bagian atas (Menggunakan var agar aman dari crash redeclare)
var CART_CONTROLLER = '/CardHaven/interface/cart/controller_keranjang.php';

// 2. Fungsi untuk mengambil ID Pengguna dari PHP session (window.CH_AUTH)
var getUserId = () => CardHavenAuth.id() || null;
let eventButton; 

function formatTanggal(dateInput) {
    if (!dateInput) return '-';
    
    let targetDate;
    
    // Cek kalau datanya berupa objek dari PHP DateTime
    if (typeof dateInput === 'object' && dateInput.date) {
        targetDate = new Date(dateInput.date);
    } else {
        // Kalau datanya string biasa (misal: "2026-06-25")
        targetDate = new Date(dateInput);
    }

    // Proteksi kalau date-nya invalid / ngaco
    if (isNaN(targetDate.getTime())) return '-';

    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(targetDate);
}

document.addEventListener("DOMContentLoaded", function() {

    // Sembunyikan spinner bawaan browser pada input quantity supaya tampilannya tetap sama seperti span sebelumnya
    if (!document.getElementById('qty-input-style')) {
        const qtyStyle = document.createElement('style');
        qtyStyle.id = 'qty-input-style';
        qtyStyle.textContent = `
            .qty-input-home::-webkit-outer-spin-button,
            .qty-input-home::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .qty-input-home {
                -moz-appearance: textfield;
            }
        `;
        document.head.appendChild(qtyStyle);
    }

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

    // ==========================================
    // ISI DROPDOWN GAME EXPLORE (FULL DATA)
    // ==========================================
    const gameSelect = document.getElementById('homeGameName');
    if (gameSelect) {
        // Nembak ke controller katalog buat ngambil FULL list game
        fetch('/CardHaven/interface/catalogue/controller/CatalogueController.php?action=get_filters')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.games) {
                    gameSelect.innerHTML = '<option value="">All Games</option>';
                    data.games.forEach(game => {
                        const opt = document.createElement('option');
                        opt.value = game.nama_game; 
                        opt.textContent = game.nama_game;
                        gameSelect.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Gagal meload filter game:', err));
    }

    function loadData() {
        // Update URL kirim semua parameter halaman termasuk halaman_promo (Ganti ke /CardHaven/)
        const urlController = `/CardHaven/interface/home/controller/getData.php?halaman_event=${currentEventPage}&halaman_game_bar=${currentGameBarPage}&halaman_game_card=${currentGameCardPage}&halaman_product=${currentProductPage}&halaman_promo=${currentPromoPage}`;

        fetch(urlController)
            .then(response => {
                if (!response.ok) throw new Error("Failed to connect to the server");
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
            .catch(error => console.error("Failed to fetch data", error));
    }

    function updateTampilan(data) {
        // --- 1. RENDER EVENT (PREORDER) ---
        const heroDesc  = document.querySelector('.hero-section .hero-desc');
        const heroImg   = document.querySelector('.hero-section .hero-img');
        const heroEmpty = document.getElementById('heroEmpty');
        if (data.event) {
            // Ada event → tampilkan konten normal, sembunyikan empty state.
            if (heroEmpty) heroEmpty.style.display = 'none';
            if (heroDesc)  heroDesc.style.display  = '';
            if (heroImg)   heroImg.style.display   = '';
            document.getElementById('ui-event-title').textContent = data.event.nama_event;
            document.getElementById('ui-event-product').textContent = data.event.nama_produk;
            document.getElementById('ui-event-date').textContent = data.event.tanggal_sampai;
            document.getElementById('ui-event-desc').textContent = data.event.deskripsi;
            document.getElementById('startDate').textContent = formatTanggal(data.event.tanggal_mulai);
            document.getElementById('endDate').textContent = formatTanggal(data.event.tanggal_berakhir);
            
            if (!data.event.foto) {
                document.getElementById('ui-event-image').src = `/CardHaven/assets/image/image-profile/defaultProduct.jpg`;
            } else {
                // Jika isinya hanya nama file (misal 'mewtwo_ex_special_art.webp'), arahkan ke folder products
                let eventPath = data.event.foto;
                if (!eventPath.includes('/')) {
                    eventPath = `assets/image/products/${eventPath}`;
                } else if (eventPath.startsWith('image-profile/')) {
                    // Data lama: foto tersimpan dgn prefix folder lama
                    eventPath = `assets/image/${eventPath}`;
                }
                document.getElementById('ui-event-image').src = `/CardHaven/${eventPath}`;
            }
            
            const eventTitle = document.getElementById('btn-title');

            // SAKTI NYA DI SINI: Pakai .onclick supaya listener lama di-overwrite otomatis
            eventTitle.onclick = () => {
                openPreOrderEvent(data.event.id_event);
            };

            // Atur teks dan status tombol berdasarkan status_event
            if (data.event.status_event == 1) {
                eventTitle.textContent = "Check detail";
                eventTitle.disabled = false; 
            } else if (data.event.status_event == 2) {
                eventTitle.textContent = "Upcoming event check detail";
                eventTitle.disabled = false;
            } else {
                // Event complete: tombol TETAP aktif supaya modal (detail/history) bisa dibuka.
                // Tombol beli di dalam modal sudah dinonaktifkan (preorder-transaction/script.js).
                eventTitle.textContent = "Event was complete";
                eventTitle.disabled = false;
                // onclick tetap openPreOrderEvent(...) yang di-set di atas — sengaja tidak di-null-kan.
            }
        } else {
            // Tidak ada preorder event → tampilkan empty state (maskot + caption).
            if (heroEmpty) heroEmpty.style.display = 'flex';
            if (heroDesc)  heroDesc.style.display  = 'none';
            if (heroImg)   heroImg.style.display   = 'none';
        }

        // --- 2. RENDER EVENT (PROMO) - RENDER 4 ITEM ---
        const promoContainer = document.querySelector('.promo-content');
        if (promoContainer) {
            promoContainer.innerHTML = '';
            if (data.list_promo && data.list_promo.length > 0) {
                data.list_promo.forEach(promo => {
                    // Jika ada banner, cek apakah itu path lengkap atau cuma nama file
                    let promoPath = promo.foto_banner;
                    if (promoPath && !promoPath.includes('/')) {
                        promoPath = `assets/image/products/${promoPath}`;
                    } else if (promoPath && promoPath.startsWith('image-profile/')) {
                        // Data lama: banner tersimpan dgn prefix folder lama
                        promoPath = `assets/image/${promoPath}`;
                    }
                    const bannerSrc = promoPath ? `/CardHaven/${promoPath}` : '/CardHaven/assets/image/image-profile/defaultEvent.jpg';
                    const gameName = promo.nama_game ? promo.nama_game : 'All Games';

                    if (promo.status_event == 1) {
                        eventButton = "Check detail";
                    } else if (promo.status_event == 2) {
                        eventButton = "Upcoming event check detail";
                    } else {
                        eventButton = "Event was complete";
                    }
                    
                    // --- PARSING & FORMAT TANGGAL DI SINI ---
                    const tglMulai = formatTanggal(promo.tanggal_mulai);
                    const tglBerakhir = formatTanggal(promo.tanggal_berakhir);
                    
                    const promoHTML = `
                    <div class="promo-card" style="background-image: url('${bannerSrc}');">
                        <div style="z-index: 999; display: flex; justify-content: center; align-items: center; flex-direction: column; row-gap: 0.75rem;">
                            <p style="color: #e4e4e4;"><span style="color: #90b3ff;">${gameName}</span>'s Event</p>
                            <h2 class="coolveticaa" style="text-align: center; width: 30rem;">${promo.nama_event}</h2>
                            <div class="date-event" style="display: flex; gap: 0.5rem; color: #fff;">
                                <p>${tglMulai}</p>
                                <span>to</span>
                                <p>${tglBerakhir}</p>
                            </div>
                            <button style="cursor:pointer; background: var(--bg-gradient); color: white; padding: 0.75rem 2.5rem; border-radius: 9999px; margin-top:1rem;" onclick="openPromoEvent(${promo.id_event})">
                                ${eventButton}
                            </button>
                        </div>
                        <div style="background-color: black; width: 100%; height: 100%; position: absolute; top: 0; left: 0; opacity: 50%;"></div>
                    </div>`;
                    
                    promoContainer.innerHTML += promoHTML;
                });
            }
            else {
                // Empty state promo: maskot + caption (front-end English).
                promoContainer.innerHTML = `
                    <div style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; padding: 2rem 1rem;">
                        <img src="/cardhaven/assets/image/empty-state.png" alt="No promo" style="width: 160px; height: auto; image-rendering: pixelated;">
                        <p style="color: #7e7e7e; font-size: 1.1rem; font-weight: 600;">Promo is currently unavailable</p>
                    </div>`;
            }
        }

        // --- RENDER GAME CARD ---
        const gameCardContainer = document.getElementById('ui-game-card-list');
        if (gameCardContainer) {
            gameCardContainer.innerHTML = '';
            if (data.list_game_card && data.list_game_card.length > 0) {
                data.list_game_card.forEach(game => {
                    let gamePath = game.foto_banner;
                    if (gamePath && !gamePath.includes('/')) {
                        gamePath = `assets/image/products/${gamePath}`;
                    } else if (gamePath && gamePath.startsWith('image-profile/')) {
                        // Data lama: banner tersimpan dgn prefix folder lama
                        gamePath = `assets/image/${gamePath}`;
                    }
                    const bannerSrc = gamePath ? `/CardHaven/${gamePath}` : '/CardHaven/assets/image/image-profile/defaultBanner.jpg';
                    
                    // Mengganti <a href> dengan onclick pada div utama
                    const cardHTML = `
                    <div onclick="openGameCatalogue(${game.id_game})" style="cursor: pointer;">
                        <div class="game-card" style="background-image: url('${bannerSrc}');">
                            <h1 style="font-size: 1.25rem; z-index: 999; position: relative; color: white;">${game.nama_game}</h1>
                            <div style="background-color: black; width: 100%; height: 100%; position: absolute; top: 0; left: 0; opacity: 50%;"></div>
                        </div>
                    </div>`;
                    gameCardContainer.innerHTML += cardHTML;
                });
            } else {
                gameCardContainer.innerHTML = '<span style="color: white;">No games are available</span>';
            }
        }

        // --- RENDER GAME BAR ---
        const gameBarContainer = document.getElementById('ui-game-list');
        if (gameBarContainer) {
            gameBarContainer.innerHTML = '';
            if (data.list_game_bar && data.list_game_bar.length > 0) {
                data.list_game_bar.forEach(game => {
                    // Mengganti elemen <a> href menjadi elemen <span> atau <a> dengan onclick
                    const aNav = document.createElement('a');
                    aNav.textContent = game.nama_game;
                    aNav.style.color = 'white';
                    aNav.style.cursor = 'pointer'; // Biar tetep keliatan bisa diklik
                    aNav.onclick = () => openGameCatalogue(game.id_game);
                    
                    gameBarContainer.appendChild(aNav);
                });
            } else {
                gameBarContainer.innerHTML = '<span>There are no games here</span>';
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
                    const gameName = prod.nama_game ? prod.nama_game : 'General';
                    
                    // Jika data foto hanya berupa nama file, arahkan ke folder produk
                    let prodPath = prod.foto;
                    if (prodPath && !prodPath.includes('/')) {
                        prodPath = `assets/image/products/${prodPath}`;
                    } else if (prodPath && prodPath.startsWith('image-profile/')) {
                        // Data lama: foto tersimpan dgn prefix folder lama
                        prodPath = `assets/image/${prodPath}`;
                    }
                    const fotoSrc = prodPath ? `/CardHaven/${prodPath}` : '/CardHaven/assets/image/image-profile/defaultProduct.jpg';

                    const soldOut = (parseInt(prod.stok) || 0) <= 0;

                    const cardHTML = `
                    <div class="product-card">
                        <div style="width: 47%; display: flex; align-items: center; justify-content: center;">
                            <div style="position: relative; width: 15rem; height: 20rem; border-radius: 0.5rem; overflow: hidden;">
                                <img src="${fotoSrc}" style="height: 100%; object-fit: contain; ${soldOut ? 'filter: grayscale(1) brightness(0.7);' : ''}">
                                ${soldOut ? `
                                <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.35);">
                                    <span style="background:#dc2626; color:#fff; font-weight:800; letter-spacing:1px; padding:0.4rem 1rem; border-radius:0.4rem; transform:rotate(-12deg); font-size:1.1rem;">OUT OF STOCK</span>
                                </div>` : ''}
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
                                <h2 style="font-size:1rem">Price: <span id="display-price-${prod.id_produk}">${formatRupiah(prod.harga_jual)}</span></h2>
                                <div style="display: flex; align-items: center; gap: 10px; border: 1px solid #ccc; border-radius: 20px; padding: 2px 10px; ${soldOut ? 'opacity:0.4; pointer-events:none;' : ''}">
                                    <span id="minus-${prod.id_produk}" onclick="updateHomeQty(${prod.id_produk}, -1, ${prod.harga_jual})" style="cursor:pointer; font-weight:bold; padding: 0 5px;">-</span>
                                    <input type="number" id="qty-val-${prod.id_produk}" class="qty-input-home" data-stok="${prod.stok}" value="1" min="1" max="${prod.stok}" oninput="handleHomeQtyInput(${prod.id_produk}, ${prod.harga_jual})" onblur="handleHomeQtyBlur(${prod.id_produk}, ${prod.harga_jual})" style="font-weight:bold; width: 32px; text-align:center; border:none; outline:none; background:transparent; padding:0; -moz-appearance:textfield;">
                                    <span id="plus-${prod.id_produk}" onclick="updateHomeQty(${prod.id_produk}, 1, ${prod.harga_jual})" style="cursor:pointer; font-weight:bold; padding: 0 5px;">+</span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.65rem; margin:0.5rem 0">
                                <button class="detail-product" onclick="goToDetail(${prod.id_produk})" style="width: 100%; padding: 0.5rem 0; font-size: 1rem; color: var(--primary-color); border: 1px solid var(--primary-color); background: transparent; border-radius: 9999px;">Check Detail</button>
                                <button class="detail-product"
                                    onclick="addToCart(${prod.id_produk}, ${prod.harga_jual})"
                                    ${soldOut ? 'disabled' : ''}
                                    style="width: 100%; padding: 0.5rem 0; font-size: 1rem; ${soldOut ? 'opacity:0.5; cursor:default;' : ''}; border: 1px solid var(--primary-color); border-radius: 999px; background: transparent; color: var(--primary-color);">
                                    ${soldOut ? 'Out of Stock' : 'Add To Cart'}
                                </button>
                            </div>
                            <button class="btn-primary"
                                    onclick="buyNow(${prod.id_produk}, ${prod.harga_jual})"
                                    ${soldOut ? 'disabled' : ''}
                                    style="width: 100%; padding: 0.75rem 0; font-size: 1rem; background: var(--bg-gradient); ${soldOut ? 'opacity:0.5; cursor:default;' : ''}">
                                Checkout Product
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

        // --- 7. ISI DROPDOWN GAME EXPLORE FILTER ---
        const gameSelect = document.getElementById('homeGameName');
        if (gameSelect && data.list_game_bar) {
            // Bersihkan dulu biar kalau user pindah page, gamenya ga numpuk ganda
            gameSelect.innerHTML = '<option value="">All Games</option>';
            
            // Loop data game dan jadikan opsi <option>
            data.list_game_bar.forEach(game => {
                const opt = document.createElement('option');
                // Value pakai nama game agar ditangkap parameter `?game_name=` di Katalog
                opt.value = game.nama_game; 
                opt.textContent = game.nama_game;
                gameSelect.appendChild(opt);
            });
        }

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
        cardhavenAlert('success', 'Success', `${qty} the item has been added to your cart!`);
        window.location.href = "/CardHaven/interface/login-page/";
        return;
    }

    // Siapkan data untuk dikirim
    const fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('id_produk', idProduk);
    fd.append('harga_produk', harga);

    // Kirim data ke controller_keranjang.php
    fetch(CART_CONTROLLER, {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            // Toast notification, ngk perlu klik OK
            cardhavenToast('success', 'Product added to cart!');
        } else {
            alert("Failed: " + res.message);
        }
    })
    .catch(err => console.error("Error add to cart:", err));
};

// Fungsi bantu: sinkronkan tampilan harga + status tombol +/- berdasarkan qty saat ini
function syncHomeQtyUI(id, hargaSatuan, currentQty) {
    const qtyEl    = document.getElementById(`qty-val-${id}`);
    const priceEl  = document.getElementById(`display-price-${id}`);
    const plusBtn  = document.getElementById(`plus-${id}`);
    const minusBtn = document.getElementById(`minus-${id}`);
    const stok     = parseInt(qtyEl.dataset.stok) || 1;

    qtyEl.value         = currentQty;
    priceEl.textContent = formatRupiah(currentQty * hargaSatuan);

    // Tombol "+" disable kalau sudah mentok stok
    if (currentQty >= stok) {
        plusBtn.style.opacity = '0.3';
        plusBtn.style.cursor  = 'default';
        plusBtn.onclick       = null;
    } else {
        plusBtn.style.opacity = '1';
        plusBtn.style.cursor  = 'pointer';
        plusBtn.onclick       = () => updateHomeQty(id, 1, hargaSatuan);
    }

    // Tombol "-" disable kalau sudah di angka minimal
    if (currentQty <= 1) {
        minusBtn.style.opacity = '0.3';
        minusBtn.style.cursor  = 'default';
        minusBtn.onclick       = null;
    } else {
        minusBtn.style.opacity = '1';
        minusBtn.style.cursor  = 'pointer';
        minusBtn.onclick       = () => updateHomeQty(id, -1, hargaSatuan);
    }
}

window.updateHomeQty = function(id, change, hargaSatuan) {
    const qtyEl = document.getElementById(`qty-val-${id}`);
    const stok  = parseInt(qtyEl.dataset.stok) || 1;

    let currentQty = parseInt(qtyEl.value) || 1;
    currentQty += change;

    if (currentQty < 1)    currentQty = 1;
    if (currentQty > stok) currentQty = stok;

    syncHomeQtyUI(id, hargaSatuan, currentQty);
};

// Dipanggil setiap kali user mengetik langsung di kolom quantity
window.handleHomeQtyInput = function(id, hargaSatuan) {
    const qtyEl = document.getElementById(`qty-val-${id}`);
    const stok  = parseInt(qtyEl.dataset.stok) || 1;

    // Biarkan field kosong sementara saat user masih mengetik, tanpa dipaksa jadi 1 dulu
    if (qtyEl.value === '') return;

    let typedQty = parseInt(qtyEl.value);
    if (isNaN(typedQty)) return;

    // Tetap batasi maksimal ke jumlah stok sambil mengetik, biar tidak kebablasan
    if (typedQty > stok) typedQty = stok;
    if (typedQty < 1)    typedQty = 1;

    if (String(typedQty) !== qtyEl.value) qtyEl.value = typedQty;

    const priceEl = document.getElementById(`display-price-${id}`);
    priceEl.textContent = formatRupiah(typedQty * hargaSatuan);
};

// Dipanggil saat user selesai mengetik (keluar dari kolom), untuk rapikan nilai akhir
window.handleHomeQtyBlur = function(id, hargaSatuan) {
    const qtyEl = document.getElementById(`qty-val-${id}`);
    const stok  = parseInt(qtyEl.dataset.stok) || 1;

    let finalQty = parseInt(qtyEl.value);
    if (isNaN(finalQty) || finalQty < 1) finalQty = 1;
    if (finalQty > stok) finalQty = stok;

    syncHomeQtyUI(id, hargaSatuan, finalQty);
};
// Update fungsi addToCart untuk mengambil nilai Qty terbaru
window.addToCart = function(idProduk, hargaSatuan) {
    const userId = getUserId();
    // Ambil jumlah barang dari elemen quantity
    const qtyEl = document.getElementById(`qty-val-${idProduk}`);
    const qty   = parseInt(qtyEl.value) || 1;
    const stok  = parseInt(qtyEl.dataset.stok) || 0;

    if (stok <= 0) {
        cardhavenAlert('error', 'Out of Stock', 'This product is currently out of stock.');
        return;
    }

    if (!userId || userId === "0") {
        cardhavenAlert('error', 'Failed', `Failed to add product to cart, please login first!`);
        return;
    }

    const fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('id_produk', idProduk);
    fd.append('harga_produk', hargaSatuan); // Kirim harga satuan
    fd.append('jumlah', qty);               // Kirim jumlah yang dipilih

    fetch('/CardHaven/interface/cart/controller_keranjang.php', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            cardhavenToast('success', `${qty} Product added to cart!`);
            // Reset qty ke 1 setelah berhasil
            document.getElementById(`qty-val-${idProduk}`).value = 1;
            document.getElementById(`display-price-${idProduk}`).textContent = formatRupiah(hargaSatuan);
        } else {
            cardhavenToast('error', res.message || 'Failed to add product to cart');
        }
    });
};

// Checkout langsung (buy now): set item ini sebagai satu-satunya item terpilih
// di keranjang, lalu pindah ke halaman checkout.
window.buyNow = function(idProduk, hargaSatuan) {
    const userId = getUserId();
    const qtyEl  = document.getElementById(`qty-val-${idProduk}`);
    const qty    = parseInt(qtyEl.value) || 1;
    const stok   = parseInt(qtyEl.dataset.stok) || 0;

    if (!userId || userId === "0") {
        window.location.replace('login')
        return;
    }
    if (stok <= 0) {
        cardhavenAlert('error', 'Out of Stock', 'This product is currently out of stock.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'buy_now');
    fd.append('id_produk', idProduk);
    fd.append('harga_produk', hargaSatuan);
    fd.append('jumlah', qty);

    fetch('/CardHaven/interface/cart/controller_keranjang.php', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            window.location.href = res.redirect || '/CardHaven/checkout';
        } else {
            cardhavenAlert('error', 'Failed', res.message || 'Failed to proceed to checkout.');
        }
    })
    .catch(err => {
        console.error('buyNow error:', err);
        cardhavenAlert('error', 'System Error', 'A system error occurred.');
    });
};

window.goToDetail = function(idProduk) {
    window.location.href = `/CardHaven/home/productdetail?id_produk=${idProduk}`;
}

window.openGameCatalogue = function(idGame) {
    window.location.href = `/CardHaven/home/list?id=${idGame}`; 
};

const btnConfirmExplore = document.getElementById('btnConfirmExplore');
if (btnConfirmExplore) {
    btnConfirmExplore.addEventListener('click', () => {
        const minPrice = document.getElementById('homeMinPrice').value;
        const maxPrice = document.getElementById('homeMaxPrice').value;
        const productType = document.getElementById('homeProductType').value;
        const gameName = document.getElementById('homeGameName').value;

        // Kumpulkan URL Parameter
        const params = new URLSearchParams();
        if (minPrice) params.append('min_price', minPrice);
        if (maxPrice) params.append('max_price', maxPrice);
        if (productType) params.append('product_type', productType);
        if (gameName) params.append('game_name', gameName);

        // Jika ada parameter yang dipilih, arahkan ke halaman katalog
        // (Sesuaikan dengan path katalog asli-mu. Contoh: /CardHaven/home/list.php)
        window.location.href = `/CardHaven/home/list?${params.toString()}`;
    });
}