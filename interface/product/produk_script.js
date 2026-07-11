const URL_PRODUK = '/cardhaven/interface/product/controller_produk.php'; 
var getEmpId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

function showError(el, msg) {
    el.style.border = "2px solid #E74C3C"; 
    const err = el.closest('.modal-form-group').querySelector('.error-message');
    if (err) { err.innerText = msg; err.style.display = "block"; err.style.color = "#E74C3C"; }
}

function clearError(el) {
    el.style.border = "1.5px solid #888"; 
    const err = el.closest('.modal-form-group').querySelector('.error-message');
    if (err) err.innerText = "";
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-input').forEach(input => {
        input.addEventListener('input', function() { clearError(this); });
        input.addEventListener('change', function() { clearError(this); });
    });

    setupSuggest('pGameSearch', 'pIdGame', 'pGameSuggest', 'search_game');
    setupSuggest('pSetSearch', 'pIdSet', 'pSetSuggest', 'search_set', 'pIdGame');
});

function clearAllErrors(formId) {
    document.getElementById(formId).querySelectorAll('.modal-input').forEach(input => clearError(input));
}

function loadRarities(gameId, selectedId = null) {
    const sel = document.getElementById('pIdRarity');
    sel.innerHTML = '<option value="">Loading...</option>';
    fetch(`${URL_PRODUK}?get_rarity_list&id_game=${gameId}`)
    .then(res => res.json()).then(data => {
        sel.innerHTML = '<option value="">-- Select Rarity --</option>';
        data.forEach(item => {
            let opt = document.createElement('option');
            opt.value = item.id_rarity;
            opt.text = `${item.nama_rarity} (${item.kode_rarity})`;
            if(selectedId && item.id_rarity == selectedId) opt.selected = true;
            sel.appendChild(opt);
        });
    });
}
function setupSuggest(inputId, hiddenId, boxId, param, dependId = null) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const box = document.getElementById(boxId);

    input.oninput = function() {
        clearError(this); // Tambahkan ini agar border merah hilang saat user mengetik kembali
        if (this.value.length < 1) { 
            box.style.display = 'none'; 
            hidden.value = '';
            return; 
        }
        let url = `${URL_PRODUK}?${param}=${this.value}`;
        if (dependId) {
            const depVal = document.getElementById(dependId).value;
            if (!depVal) { showError(input, "Please select a Game first!"); return; }
            url += `&id_game=${depVal}`;
        }

        fetch(url).then(res => res.json()).then(data => {
            box.innerHTML = '';
            if (data.length > 0) {
                box.style.display = 'block';
                data.forEach(item => {
                    let div = document.createElement('div');
                    
                    // PERBAIKAN: Tambahkan item.nama_suplier di sini
                    div.innerHTML = item.nama_game || item.nama_set || item.nama_suplier; 
                    
                    div.onclick = () => {
                        // PERBAIKAN: Tambahkan item.nama_suplier dan item.id_supplier di sini
                        input.value = item.nama_game || item.nama_set || item.nama_suplier;
                        hidden.value = item.id_game || item.id_set || item.id_supplier;
                        
                        box.style.display = 'none';
                        clearError(input);
                        if (inputId === 'pGameSearch') {
                            loadRarities(item.id_game);
                            document.getElementById('pIdSet').value = '';
                            document.getElementById('pSetSearch').value = '';
                        }
                    };
                    box.appendChild(div);
                });
            }
        });
    };
}

setupSuggest('pGameSearch', 'pIdGame', 'pGameSuggest', 'search_game');
setupSuggest('pSetSearch', 'pIdSet', 'pSetSuggest', 'search_set', 'pIdGame');
setupSuggest('pSupplierSearch', 'pIdSupplier', 'pSupplierSuggest', 'search_supplier');

function toggleProdFields() {
    const tipe = document.getElementById('pTipe').value;
    document.getElementById('pSetGroup').style.display = (tipe.includes('Card') || tipe.includes('Booster')) ? 'block' : 'none';
    document.getElementById('pRarityGroup').style.display = (tipe === 'Single Card') ? 'block' : 'none';
    document.getElementById('pKondisiGroup').style.display = (tipe === 'Single Card') ? 'block' : 'none';

    const gameLabel = document.querySelector('label[for="pGameSearch"]');
    if (gameLabel) {
        let baseText = gameLabel.innerHTML.replace('<span style="color:red;">*</span>', '').trim();
        if (isRequiredType) {
            gameLabel.innerHTML = baseText + ' <span style="color:red;">*</span>';
        } else {
            gameLabel.innerHTML = baseText;
        }
    }
}

function previewImage(input) {
    const preview = document.getElementById('pPreview');
    const placeholder = document.getElementById('pPlaceholder');
    const errorEl = document.getElementById('error-foto');
    if(!preview || !placeholder) return;
    
    const file = input.files[0];
    errorEl.innerText = "";
    input.style.border = "1.5px solid #d1d9e6";

    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            showError(input, "File is too large! Maximum size is 5MB.");
            input.value = ""; 
            return;
        }
        const allowedExtensions = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/svg+xml'];
        if (!allowedExtensions.includes(file.type)) {
            showError(input, "Unsupported format! (Only JPG/PNG/WEBP/SVG).");
            input.value = "";
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
}

document.getElementById('productForm').onsubmit = async function(e) {
    e.preventDefault();
    clearAllErrors('productForm');
    
    let isValid = true;
    const tipe = document.getElementById('pTipe').value;
    if (!tipe) { showError(document.getElementById('pTipe'), "Product Type must be selected"); isValid = false; }

    const requiredFields = [
        { id: 'pNama', label: "Product Name" },
        { id: 'pStok', label: "Stock", isNum: true },
        { id: 'pBeli', label: "Purchase Price", isNum: true },
        { id: 'pJual', label: "Selling Price", isNum: true }
    ];

    requiredFields.forEach(f => {
        const el = document.getElementById(f.id);
        const val = el.value.trim();
        if (!val) {
            showError(el, `${f.label} must be filled in`);
            isValid = false;
        } else if (f.isNum && (isNaN(val) || parseFloat(val) < (f.id === 'pStok' ? 1 : 0))) {
            showError(el, `${f.label} must be at least ${f.id === 'pStok' ? 1 : 0}`);
            isValid = false;
        }
    });

    if (tipe.includes('Card') || tipe.includes('Booster')) {
        const gameId = document.getElementById('pIdGame').value;
        const gameSearch = document.getElementById('pGameSearch');
        
        if (!gameId || gameSearch.value.trim() === "") { 
            showError(gameSearch, "Please select a Game from the list!"); 
            isValid = false; 
        }

        const setId = document.getElementById('pIdSet').value;
        const setSearch = document.getElementById('pSetSearch');
        if (!setId || setSearch.value.trim() === "") { 
            showError(setSearch, "Please select a Set from the list!"); 
            isValid = false; 
        }
    }

    if (tipe === 'Single Card') {
        if (!document.getElementById('pIdRarity').value) { showError(document.getElementById('pIdRarity'), "Select Rarity"); isValid = false; }
        if (!document.getElementById('pKondisi').value) { showError(document.getElementById('pKondisi'), "Select Condition"); isValid = false; }
    }

    if (!isValid) return;

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerText = "Saving...";

    const fd = new FormData(this);
    fd.append('id_pengguna_js', getEmpId());

    try {
        const response = await fetch(URL_PRODUK, { method: 'POST', body: fd });
        const res = JSON.parse(await response.text());

        if (res.status === 'success') {
            cardhavenAlert('success', 'Success', 'Product data saved successfully.', () => {
                document.getElementById('productModal').style.display = 'none'; 
                setTimeout(() => { location.reload(); }, 300);
            });
        } else {
            cardhavenAlert('error', 'Failed', res.message);
        }
    } catch (err) {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Connection error occurred. Server failed to process request.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = "Save Product";
    }
};

function loadProductPage(page) {
    const container = document.getElementById('container-produk');
    container.style.opacity = '0.5';

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('pp', page);

    fetch(`${window.location.pathname}?${urlParams.toString()}`)
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            container.innerHTML = doc.getElementById('container-produk').innerHTML;
            container.style.opacity = '1';
            window.history.pushState({}, '', `${window.location.pathname}?${urlParams.toString()}`);
        });
}

function openAddProductModal() {
    clearAllErrors('productForm');
    document.getElementById('productForm').reset();
    document.getElementById('pAction').value = 'add';
    

    document.getElementById('pPreview').style.display = 'none';
    document.getElementById('pPlaceholder').style.display = 'block';
    // Kunci status default
    const statDisp = document.getElementById('productStatusDisplay');
    statDisp.value = 'ACTIVE (Default)';
    statDisp.style.color = '#27AE60';
    
    toggleProdFields();
    document.getElementById('productModal').style.display = 'flex';
}

function toggleProductStatus(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id_produk', id);
    fd.append('id_pengguna_js', getEmpId()); 

    fetch(URL_PRODUK, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            Swal.fire({ icon: 'success', iconColor: '#0088FF', title: 'Success!', text: `Product status changed.`, showConfirmButton: false, timer: 1500, customClass: { title: 'coolveticaa' } }).then(() => location.reload());
        } else {
            el.checked = !isActive;
            Swal.fire('Failed', res.message, 'error');
        }
    })
    .catch(err => {
        el.checked = !isActive;
        console.error(err);
        Swal.fire('Error', 'Connection error occurred.', 'error');
    });
}

function openEditProductModal(id) {
    fetch(`${URL_PRODUK}?get_detail=${id}`)
    .then(res => res.json()).then(data => {
        if(data.error) return cardhavenAlert('error', 'Error', data.error);
        clearAllErrors('productForm');
        document.getElementById('pTitle').innerHTML = '<span class="blue-text">EDIT</span> PRODUCT';
        document.getElementById('pAction').value = 'edit';
        document.getElementById('pID').value = id;
        document.getElementById('pNama').value = data.nama_produk;
        document.getElementById('pTipe').value = data.tipe_produk;
        document.getElementById('pIdGame').value = data.id_game;
        document.getElementById('pGameSearch').value = data.nama_game || '';
        document.getElementById('pIdSupplier').value = data.id_supplier || '';
        document.getElementById('pSupplierSearch').value = data.nama_suplier || '';
        document.getElementById('pIdSet').value = data.id_set || '';
        document.getElementById('pSetSearch').value = data.nama_set || '';
        document.getElementById('pStok').value = parseInt(data.stok, 10);
        document.getElementById('pBeli').value = data.harga_beli;
        document.getElementById('pJual').value = data.harga_jual;
        document.getElementById('pKondisi').value = data.kondisi || '';
        document.getElementById('pDeskripsi').value = data.deskripsi || ''; 
        
        // Tampilkan status terkini (Read-Only)
        const statDisp = document.getElementById('productStatusDisplay');
        statDisp.value = data.status == 1 ? 'ACTIVE' : 'INACTIVE';
        statDisp.style.color = data.status == 1 ? '#27AE60' : '#E74C3C';

        const preview = document.getElementById('pPreview');
        const placeholder = document.getElementById('pPlaceholder');
        if (data.foto) {
            let prodPath = data.foto;
            if (prodPath && !prodPath.includes('/')) {
                prodPath = `/CardHaven/assets/image/products/${prodPath}`;
            }else{
                prodPath = `/CardHaven/${prodPath}`;
            }
            preview.src = prodPath;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'block';
        }
        
        loadRarities(data.id_game, data.id_rarity);
        toggleProdFields();
        document.getElementById('productModal').style.display = 'flex';
    }).catch(err => {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Failed to retrieve product details.');
    });
}

function confirmDeleteProduct(id) {
    cardhavenConfirm("Delete Product?", "This product will be permanently deleted. Are you sure?", "Yes, Delete", () => {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_produk', id);
        fd.append('id_pengguna_js', getEmpId());

        fetch(URL_PRODUK, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else cardhavenAlert('error', 'Failed', res.message);
        });
    });
}

function openDetailProductModal(id) {
    fetch(`${URL_PRODUK}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.error) return cardhavenAlert('error', 'Error', data.error);

            document.getElementById('detProdID').innerText = 'PRD-' + String(id).padStart(4, '0');
            document.getElementById('detProdNama').innerText = data.nama_produk || '-';
            document.getElementById('detProdTipe').innerText = data.tipe_produk || '-';
            document.getElementById('detProdGame').innerText = data.nama_game || '-';
            document.getElementById('detProdSupplier').innerText = data.nama_suplier || '-';
            document.getElementById('detProdStok').innerText = (data.stok || '0') + ' pcs';
            document.getElementById('detProdHarga').innerText = 'Rp ' + parseFloat(data.harga_jual).toLocaleString('id-ID');
            document.getElementById('detProdDeskripsi').innerText = data.deskripsi || 'No description available.';
            
            const statusEl = document.getElementById('detProdStatus');
            statusEl.innerHTML = data.status == 1 
                ? '<span style="color: #27AE60; font-weight: bold;"><i class="fas fa-check-circle"></i> Active</span>' 
                : '<span style="color: #E74C3C; font-weight: bold;"><i class="fas fa-times-circle"></i> Inactive</span>';

            const imgEl = document.getElementById('detProdImg');
            const placeholderEl = document.getElementById('detProdImgPlaceholder');
            if (imgEl && placeholderEl) {
                if (data.foto) {
                    let prodPath = data.foto;
                    if (prodPath && !prodPath.includes('/')) {
                        prodPath = `/CardHaven/assets/image/products/${prodPath}`;
                    }else{
                        prodPath = `/CardHaven/${prodPath}`;
                    }
                    imgEl.src = prodPath;
                    imgEl.style.display = 'block';
                    placeholderEl.style.display = 'none';
                } else {
                    imgEl.style.display = 'none';
                    placeholderEl.style.display = 'block';
                }
            }

            const tipe = data.tipe_produk;
            const rowSet = document.getElementById('detRowSet');
            const rowRarity = document.getElementById('detRowRarity');
            const rowKondisi = document.getElementById('detRowKondisi');
            const supplierEl = document.getElementById('detProdSupplier');
            if(supplierEl) {
                supplierEl.innerText = data.nama_suplier || '-';
            }

            if(rowSet) rowSet.style.display = 'none';
            if(rowRarity) rowRarity.style.display = 'none';
            if(rowKondisi) rowKondisi.style.display = 'none';

            if (rowSet && (tipe.includes('Card') || tipe.includes('Booster'))) {
                rowSet.style.display = 'table-row';
                document.getElementById('detProdSet').innerText = data.nama_set || '-';
            }

            if (tipe === 'Single Card') {
                if(rowRarity) rowRarity.style.display = 'table-row';
                if(rowKondisi) rowKondisi.style.display = 'table-row';
                
                if (data.nama_rarity) {
                    document.getElementById('detProdRarity').innerText = `${data.nama_rarity} (${data.kode_rarity})`;
                } else {
                    document.getElementById('detProdRarity').innerText = '-';
                }
                
                const mapKondisi = { 'M': 'Mint', 'NM': 'Near Mint', 'LP': 'Lightly Played', 'MP': 'Moderately Played', 'HP': 'Heavily Played', 'DMG': 'Damaged' };
                document.getElementById('detProdKondisi').innerText = mapKondisi[data.kondisi] || data.kondisi || '-';
            }

            document.getElementById('productDetailModal').style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'System Error', 'Detail Error: ' + err.message);
        });
}

window.addEventListener('click', function(e) { 
    const md = document.getElementById('productModal');
    if (md && e.target === md) {
        const nama = document.getElementById('pNama').value.trim();
        if (nama !== '') {
            md.style.display = 'none'; // Sembunyikan form seketika
            let isConfirmed = false;
            
            const actionText = document.getElementById('pAction').value === 'edit' ? 'Edit' : 'Add';
            // Gunakan fungsi bawaan sistem Anda
            cardhavenConfirm(
                `Cancel ${actionText} Product?`, 
                "The data you have entered will be lost.",
                "Yes, Exit", 
                () => {
                    isConfirmed = true;
                    document.getElementById('productForm').reset();
                    clearAllErrors('productForm');
                }
            );

            // Pantau penutupan pop-up. Jika batal keluar, munculkan form lagi
            const checkSwal = setInterval(() => {
                if (!Swal.isVisible()) {
                    clearInterval(checkSwal);
                    if (!isConfirmed) md.style.display = 'flex';
                }
            }, 15);
        } else {
            md.style.display = 'none';
        }
    } 
    const mdDetail = document.getElementById('productDetailModal'); 
    if (mdDetail && e.target === mdDetail) mdDetail.style.display = 'none';
});

document.addEventListener('DOMContentLoaded', () => {
    const scrollBtn = document.getElementById('scrollBottomBtn');
    
    // Deteksi kontainer scroll. Jika .main-content tidak memiliki scroll, gunakan window.
    const scrollContainer = document.querySelector('.main-content') || window;
    const isWindow = scrollContainer === window;

    if (scrollBtn) {
        // Eksekusi scroll ke paling bawah
        scrollBtn.addEventListener('click', () => {
            if (isWindow) {
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            } else {
                scrollContainer.scrollTo({ top: scrollContainer.scrollHeight, behavior: 'smooth' });
            }
        });

        // Logika evaluasi posisi scroll
        const checkScrollPosition = () => {
            const scrollTop = isWindow ? window.scrollY : scrollContainer.scrollTop;
            const clientHeight = isWindow ? window.innerHeight : scrollContainer.clientHeight;
            const scrollHeight = isWindow ? document.body.scrollHeight : scrollContainer.scrollHeight;

            // Sembunyikan tombol jika sudah mencapai jarak 50px dari batas bawah
            if (scrollTop + clientHeight >= scrollHeight - 50) {
                scrollBtn.classList.add('hidden');
            } else {
                scrollBtn.classList.remove('hidden');
            }
        };

        // Pasang event listener dan jalankan sekali saat halaman dimuat
        (isWindow ? window : scrollContainer).addEventListener('scroll', checkScrollPosition);
        checkScrollPosition();
    }
});