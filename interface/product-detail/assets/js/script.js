// Dapatkan ID Produk dari URL (?id=...)
const urlParams = new URLSearchParams(window.location.search);
const productId = urlParams.get('id_produk');

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

document.addEventListener('DOMContentLoaded', () => {
    if (!productId) {
        cardhavenAlert('error', 'Error', 'Product ID is missing from URL!', () => {
            window.location.href = '/cardhaven/home';
        });
        return;
    }
    fetchProductDetail();
});

// 1. Fetch Detail Product
function fetchProductDetail() {
    fetch(`/cardhaven/interface/product-detail/controller/ProductDetailController.php?action=get_detail&id_produk=${productId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const prod = data.data;
                currentProductPrice = parseFloat(prod.harga_jual);
                currentIdGame = prod.id_game;

                // Bind to DOM
                document.getElementById('detailNama').innerText = prod.nama_produk;
                document.getElementById('detailStok').innerText = prod.stok;
                document.getElementById('detailGame').innerText = 'Game ' + prod.id_game; // Sesuaikan kalau ada text nama gamenya
                document.getElementById('detailType').innerText = prod.tipe_produk || 'Card';
                document.getElementById('detailKondisi').innerText = prod.kondisi || 'Near Mint';
                document.getElementById('detailDeskripsi').innerText = prod.deskripsi;
                document.getElementById('detailHarga').innerText = '$' + currentProductPrice.toLocaleString('en-US'); // Figma reference uses $
                
                if (prod.foto) {
                    document.getElementById('detailFoto').src = `/cardhaven/${prod.foto}`;
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
    fetch(`/cardhaven/interface/product-detail/controller/ProductDetailController.php?action=get_related&id_game=${currentIdGame}&id_produk=${productId}`)
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
        const card = document.createElement('div');
        card.className = 'rel-card';
        card.innerHTML = `
            <div class="rel-icon-badge">
                <img src="/cardhaven/assets/icon/cart.svg" alt="icon">
            </div>
            <img src="/cardhaven/${item.foto || 'placeholder.png'}" class="rel-image" alt="Related">
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
    // Cek batas stok jika diperlukan: if (currentQty > maxStok) ...
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

    fetch('/cardhaven/interface/product-detail/controller/ProductDetailController.php?action=add_to_cart', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            cardhavenAlert('success', 'Success', 'Product successfully added to your cart!');
        } else {
            cardhavenAlert('error', 'Error', res.msg || 'Failed to add product.');
        }
    })
    .catch(err => {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Something went wrong.');
    });
}

// 5. Global GoToDetail (Dideklarasikan di window sesuai prompt)
window.goToDetail = function(idProduk) {
    window.location.href = `/cardhaven/productdetail?id=${idProduk}`;
}

// 6. Checkout Product Placeholder
function checkoutProduct() {
    if (!userId) {
        cardhavenAlert('error', 'Authentication Required', 'Please login to proceed.');
        return;
    }
    // Karena idenya belum ada, lempar ke alert dulu
    cardhavenAlert('info', 'Checkout Process', 'Proceeding to checkout with ' + currentQty + ' items. (Feature WIP)');
    // Nanti bisa window.location.href = `/cardhaven/checkout?id_produk=${productId}&qty=${currentQty}`;
}