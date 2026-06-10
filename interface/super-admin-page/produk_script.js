const URL_PRODUK = '/cardhaven/interface/super-admin-page/controller_produk.php'; 
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

document.querySelectorAll('.modal-input').forEach(input => {
    input.addEventListener('input', function() { clearError(this); });
    input.addEventListener('change', function() { clearError(this); });
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
        if (this.value.length < 1) { box.style.display = 'none'; return; }
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
                    div.innerHTML = item.nama_game || item.nama_set;
                    div.onclick = () => {
                        input.value = item.nama_game || item.nama_set;
                        hidden.value = item.id_game || item.id_set;
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

function toggleProdFields() {
    const tipe = document.getElementById('pTipe').value;
    document.getElementById('pSetGroup').style.display = (tipe.includes('Card') || tipe.includes('Booster')) ? 'block' : 'none';
    document.getElementById('pRarityGroup').style.display = (tipe === 'Single Card') ? 'block' : 'none';
    document.getElementById('pKondisiGroup').style.display = (tipe === 'Single Card') ? 'block' : 'none';
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
            showError(input, "File terlalu besar! Maksimal 5MB.");
            input.value = ""; 
            return;
        }
        const allowedExtensions = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/svg+xml'];
        if (!allowedExtensions.includes(file.type)) {
            showError(input, "Format tidak didukung! (Hanya JPG/PNG/WEBP/SVG).");
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
        if (!document.getElementById('pIdGame').value) { showError(document.getElementById('pGameSearch'), "Select Game"); isValid = false; }
        if (!document.getElementById('pIdSet').value) { showError(document.getElementById('pSetSearch'), "Select Set"); isValid = false; }
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
        submitBtn.innerText = "SAVE PRODUCT";
    }
};

function openAddProductModal() {
    clearAllErrors('productForm');
    document.getElementById('productForm').reset();
    document.getElementById('pAction').value = 'add';
    
    const preview = document.getElementById('pPreview');
    const placeholder = document.getElementById('pPlaceholder');
    if (preview && placeholder) {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
    
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
            Swal.fire({
                icon: 'success',
                iconColor: '#0088FF',
                title: 'Success!',
                text: `Product status changed.`,
                showConfirmButton: false,
                timer: 1500,
                background: '#ffffff',
                customClass: { title: 'coolveticaa' }
            }).then(() => location.reload());
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
        document.getElementById('pAction').value = 'edit';
        document.getElementById('pID').value = id;
        document.getElementById('pNama').value = data.nama_produk;
        document.getElementById('pTipe').value = data.tipe_produk;
        document.getElementById('pIdGame').value = data.id_game;
        document.getElementById('pGameSearch').value = data.nama_game || '';
        document.getElementById('pIdSet').value = data.id_set || '';
        document.getElementById('pSetSearch').value = data.nama_set || '';
        document.getElementById('pStok').value = parseInt(data.stok, 10);
        document.getElementById('pBeli').value = data.harga_beli;
        document.getElementById('pJual').value = data.harga_jual;
        document.getElementById('pKondisi').value = data.kondisi || '';
        document.getElementById('pDeskripsi').value = data.deskripsi || ''; 
        
        const preview = document.getElementById('pPreview');
        const placeholder = document.getElementById('pPlaceholder');
        if (preview && placeholder) {
            if (data.foto_produk) {
                preview.src = '/CardHaven/' + data.foto_produk; 
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            }
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
    cardhavenConfirm("Delete Product?", "This product will be deleted.", "Delete", () => {
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
    Swal.fire({
        title: 'Loading Data...',
        allowOutsideClick: false,
        showConfirmButton: false,
        background: "transparent",
        backdrop: "rgba(13,71,161,.25)",
        customClass: { popup: "cardhaven-popup", title: "coolveticaa cardhaven-title" },
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(`${URL_PRODUK}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            Swal.close();
            if(data.error) return cardhavenAlert('error', 'Error', data.error);

            const detailHTML = `
                <div style="text-align: left; padding: 20px; border: 1.5px solid #E2E8F0; border-radius: 12px; margin-top: 10px; background: white;">
                    <div style="margin-bottom: 12px;">
                        <div style="color: #A0AEC0; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 4px;">ID / NAME</div>
                        <div style="color: #1A202C; font-weight: 700; font-size: 1.1rem;">PRD-${String(id).padStart(4, '0')} / ${data.nama_produk}</div>
                    </div>
                    <div style="border-top: 1px solid #E2E8F0; margin: 12px 0;"></div>
                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1; margin-bottom: 12px;">
                            <div style="color: #A0AEC0; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 4px;">TYPE</div>
                            <div style="color: #1A202C; font-weight: 600; font-size: 1.05rem;">${data.tipe_produk || '-'}</div>
                        </div>
                        <div style="flex: 1; margin-bottom: 12px;">
                            <div style="color: #A0AEC0; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 4px;">GAME</div>
                            <div style="color: #1A202C; font-weight: 600; font-size: 1.05rem;">${data.nama_game || '-'}</div>
                        </div>
                    </div>
                    <div style="border-top: 1px solid #E2E8F0; margin: 12px 0;"></div>
                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1; margin-bottom: 12px;">
                            <div style="color: #A0AEC0; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 4px;">STOCK</div>
                            <div style="color: #1A202C; font-weight: 600; font-size: 1.05rem;">${data.stok || '0'}</div>
                        </div>
                        <div style="flex: 1; margin-bottom: 12px;">
                            <div style="color: #A0AEC0; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 4px;">PRICE</div>
                            <div style="color: #1A202C; font-weight: 600; font-size: 1.05rem;">Rp${parseFloat(data.harga_jual).toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                    <div style="border-top: 1px solid #E2E8F0; margin: 12px 0;"></div>
                    <div>
                        <div style="color: #A0AEC0; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 8px;">CURRENT STATUS</div>
                        ${data.status == 1 
                            ? '<span style="background: #E6F4EA; color: #1E8E3E; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.5px;">ACTIVE</span>' 
                            : '<span style="background: #FCE8E6; color: #E74C3C; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.5px;">INACTIVE</span>'}
                    </div>
                </div>
            `;

            Swal.fire({
                title: "Product Detail",
                html: detailHTML,
                showConfirmButton: false, 
                showCloseButton: true,    
                background: "transparent",
                backdrop: "rgba(13,71,161,.25)",
                customClass: { popup: "cardhaven-popup", title: "coolveticaa cardhaven-title" }
            });
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'System Error', 'Failed to fetch data from server.');
        });
}

window.addEventListener('click', function(e) { 
    const md = document.getElementById('productModal');
    
    // Validasi Form Add/Edit Product
    if (md && e.target === md) {
        const nama = document.getElementById('pNama').value.trim();
        const stok = document.getElementById('pStok').value.trim();
        
        if (nama !== '' || stok !== '') {
            cardhavenConfirm(
                "Tutup Form?", 
                "Data yang sudah Anda ketik belum disimpan dan akan hilang. Yakin ingin membatalkan?", 
                "Ya, Tutup", 
                () => { md.style.display = 'none'; }
            );
        } else {
            md.style.display = 'none';
        }
    } 

    // Tutup Modal Detail Product
    const mdDetail = document.getElementById('productDetailModal'); // Sesuaikan jika ada
    if (mdDetail && e.target === mdDetail) mdDetail.style.display = 'none';
});