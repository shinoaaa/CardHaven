// Dapatkan ID Produk dari URL (?id_produk=...) atau (?id=...)
const urlParams = new URLSearchParams(window.location.search);
const productId = urlParams.get('id_produk') || urlParams.get('id');

// Mendapatkan ID Pengguna (User Session/Localstorage)
const userId = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
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
                document.getElementById('detailGame').innerText = prod.nama_game || 'General';
                document.getElementById('detailType').innerText = prod.tipe_produk || 'Card';
                document.getElementById('detailKondisi').innerText = prod.kondisi || 'Near Mint';
                document.getElementById('detailDeskripsi').innerText = prod.deskripsi;
                document.getElementById('detailHarga').innerText = 'Rp.' + currentProductPrice.toLocaleString('en-US');
                
                if (prod.foto) {
                    let fotoPath = prod.foto;
                    if (fotoPath.includes('image-profile/') || fotoPath.includes('assets/')) {
                        document.getElementById('detailFoto').src = `${base}/${fotoPath}`;
                    } else {
                        document.getElementById('detailFoto').src = `${base}/assets/image/products/${fotoPath}`;
                    }
                } else {
                    document.getElementById('detailFoto').src = `${base}/image-profile/defaultProduct.jpg`;
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
        // Cek Tombol Prev (Jika di page 1)
        if (currentRelatedPage <= 1) {
            btnPrev.style.opacity = '0.3';
            btnPrev.style.cursor = 'default';
        } else {
            btnPrev.style.opacity = '1';
            btnPrev.style.cursor = 'pointer';
        }

        // Cek Tombol Next (Jika di page terakhir atau datanya kosong)
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
        let relFotoPath = item.foto || 'image-profile/defaultProduct.jpg';
        let relFotoSrc = '';
        
        if (relFotoPath.includes('image-profile/') || relFotoPath.includes('assets/')) {
            relFotoSrc = `${base}/${relFotoPath}`;
        } else {
            relFotoSrc = `${base}/assets/image/products/${relFotoPath}`;
        }

        const card = document.createElement('div');
        card.className = 'rel-card';
        card.innerHTML = `
            <img src="${relFotoSrc}" class="rel-image" alt="Related">
            <div class="rel-title-row">
                <h4 class="rel-title">${item.nama_produk}</h4>
                <span class="rel-game">${item.nama_game || 'General'}</span>
            </div>
            <p class="rel-price">Price: Rp${parseFloat(item.harga_jual).toLocaleString('id-ID')}</p>
            <button class="btn-check-detail" onclick="window.goToDetail(${item.id_produk})">Check Detail</button>
        `;
        grid.appendChild(card);
    });
}

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
        cardhavenAlert('warning', 'Out of Stock', 'Maaf, stok produk ini sedang kosong.');
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
function addToCart() {
    if (!userId) {
        cardhavenAlert('error', 'Authentication Required', 'Please login to add items to your cart.');
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
            cardhavenAlert('success', 'Success', 'Successfully add product to cart!');
        } else {
            cardhavenAlert('error', 'Error', res.msg || 'Gagal menambahkan produk.');
        }
    })
    .catch(err => {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Terjadi kesalahan sistem.');
    });
}

// 5. Global GoToDetail (Dideklarasikan di window sesuai prompt)
window.goToDetail = function(idProduk) {
    window.location.href = `${base}/home/productdetail?id_produk=${idProduk}`;
}

// 6. Checkout Product Placeholder
function checkoutProduct() {
    if (!userId) {
        cardhavenAlert('error', 'Authentication Required', 'Please login to proceed.');
        return;
    }
    cardhavenAlert('info', 'Checkout Process', 'Melanjutkan ke checkout dengan ' + currentQty + ' item. (Fitur WIP)');
}