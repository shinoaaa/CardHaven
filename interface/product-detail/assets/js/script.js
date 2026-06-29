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

                // Bind ke DOM
                document.getElementById('detailNama').innerText = prod.nama_produk;
                document.getElementById('detailStok').innerText = prod.stok;
                document.getElementById('detailGame').innerText = 'Game ' + prod.id_game;
                document.getElementById('detailType').innerText = prod.tipe_produk || 'Card';
                document.getElementById('detailKondisi').innerText = prod.kondisi || 'Near Mint';
                document.getElementById('detailDeskripsi').innerText = prod.deskripsi;
                document.getElementById('detailHarga').innerText = '$' + currentProductPrice.toLocaleString('en-US');
                
                if (prod.foto) {
                    let fotoPath = prod.foto;
                    // Jika foto mengandung path lengkap (image-profile atau assets)
                    if (fotoPath.includes('image-profile/') || fotoPath.includes('assets/')) {
                        document.getElementById('detailFoto').src = `${base}/${fotoPath}`;
                    } else {
                        // Jika hanya berupa nama file produk biasa, arahkan ke folder products
                        document.getElementById('detailFoto').src = `${base}/assets/image/products/${fotoPath}`;
                    }
                } else {
                    // Fallback jika produk tidak memiliki foto sama sekali
                    document.getElementById('detailFoto').src = `${base}/image-profile/defaultProduct.jpg`;
                }

                // Panggil Related Product setelah tahu gamenya apa
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
                allRelatedProducts = data.data;
                renderRelatedProducts();
            }
        });
}

function renderRelatedProducts() {
    const grid = document.getElementById('relatedGrid');
    grid.innerHTML = '';

    const startIndex = (currentRelatedPage - 1) * relatedLimit;
    const itemsToShow = allRelatedProducts.slice(startIndex, startIndex + relatedLimit);

    if (itemsToShow.length === 0) {
        grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #888;">No related products found.</p>';
        return;
    }

    itemsToShow.forEach(item => {
        // Tentukan path gambar terkait secara dinamis dan aman
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
            <div class="rel-icon-badge">
                <img src="${base}/assets/image/cart.svg" alt="icon">
            </div>
            <img src="${relFotoSrc}" class="rel-image" alt="Related">
            <div class="rel-title-row">
                <h4 class="rel-title">${item.nama_produk}</h4>
                <span class="rel-game">Pokemon</span>
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

// 3. Logic Quantity
function updateQty(change) {
    currentQty += change;
    if (currentQty < 1) currentQty = 1;
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
            cardhavenAlert('success', 'Berhasil', 'Produk berhasil ditambahkan ke keranjang!');
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