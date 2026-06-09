const URL_PRODUK = '/CardHaven/interface/super-admin-page/controller_produk.php'; 
// --- UTILITY: VALIDASI VISUAL ---
function showError(el, msg) {
    el.style.border = "2px solid #E74C3C"; 
    
    const err = el.closest('.modal-form-group').querySelector('.error-message');
    
    if (err) {
        err.innerText = msg;
        err.style.display = "block"; 
        err.style.color = "#E74C3C"; 
    }
}

function clearError(el) {
    el.style.border = "1.5px solid #888"; 
    
    const err = el.closest('.modal-form-group').querySelector('.error-message');
    
    if (err) {
        err.innerText = "";
    }
}

document.querySelectorAll('.modal-input').forEach(input => {
    input.addEventListener('input', function() {
        clearError(this);
    });
    input.addEventListener('change', function() {
        clearError(this);
    });
})

function clearAllErrors(formId) {
    const form = document.getElementById(formId);
    form.querySelectorAll('.modal-input').forEach(input => clearError(input));
}

// --- FUNGSI LOAD RARITY (DROPDOWN) ---
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

// --- FUNGSI SUGGESTION (GAME & SET) ---
function setupSuggest(inputId, hiddenId, boxId, param, dependId = null) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const box = document.getElementById(boxId);

    input.oninput = function() {
        if (this.value.length < 1) { box.style.display = 'none'; return; }
        let url = `${URL_PRODUK}?${param}=${this.value}`;
        if (dependId) {
            const depVal = document.getElementById(dependId).value;
            if (!depVal) { showError(input, "Pilih Game dulu!"); return; }
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

// --- TOGGLE FIELD BERDASARKAN TIPE ---
function toggleProdFields() {
    const tipe = document.getElementById('pTipe').value;
    document.getElementById('pSetGroup').style.display = (tipe.includes('Card') || tipe.includes('Booster')) ? 'block' : 'none';
    document.getElementById('pRarityGroup').style.display = (tipe === 'Single Card') ? 'block' : 'none';
    document.getElementById('pKondisiGroup').style.display = (tipe === 'Single Card') ? 'block' : 'none';
}

// --- SUBMIT FORM DENGAN VALIDASI ---
document.getElementById('productForm').onsubmit = async function(e) {
    e.preventDefault();
    clearAllErrors('productForm');
    
    let isValid = true;
    const tipe = document.getElementById('pTipe').value;

    // 1. Validasi Input Dasar
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

    // 2. Validasi Relasi (Game & Set)
    if (tipe.includes('Card') || tipe.includes('Booster')) {
        const gameID = document.getElementById('pIdGame').value;
        const gameSearch = document.getElementById('pGameSearch').value;
        const setID = document.getElementById('pIdSet').value;
        const setSearch = document.getElementById('pSetSearch').value;

        if (!gameID || !gameSearch) {
            showError(document.getElementById('pGameSearch'), "Please select a Game from the available list");
            isValid = false;
        }
        if (!setID || !setSearch) {
            showError(document.getElementById('pSetSearch'), "Please select a Set from the available list");
            isValid = false;
        }
    }

    // 3. Validasi Khusus Single Card
    if (tipe === 'Single Card') {
        if (!document.getElementById('pIdRarity').value) {
            showError(document.getElementById('pIdRarity'), "Rarity must be selected");
            isValid = false;
        }
        if (!document.getElementById('pKondisi').value) {
            showError(document.getElementById('pKondisi'), "Condition must be selected");
            isValid = false;
        }
    }

    if (!isValid) return;

    // 4. Proses Kirim (POST)
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerText = "Saving...";

    const fd = new FormData(this);
    fd.append('id_pengguna_js', getEmpId());

    try {
        const response = await fetch(URL_PRODUK, { method: 'POST', body: fd });
        const rawText = await response.text();
        let res;
        
        try {
            res = JSON.parse(rawText);
        } catch (e) {
            alert("CRASH PELADEN:\n" + rawText);
            return;
        }

        if (res.status === 'success') {
            location.reload();
        } else {
            alert("GAGAL: " + res.message);
        }
    } catch (err) {
        console.error(err);
        alert("Terjadi kesalahan koneksi.");
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = "SAVE PRODUCT";
    }
};

// --- OPEN MODAL (ADD/EDIT) ---
function openAddProductModal() {
    clearAllErrors('productForm');
    document.getElementById('productForm').reset();
    document.getElementById('pAction').value = 'add';
    
    // Baris ini dihapus karena id pLogSection sudah tidak ada di HTML
    // document.getElementById('pLogSection').style.display = 'none';
    
    toggleProdFields();
    document.getElementById('productModal').style.display = 'flex';
}

function openEditProductModal(id) {
    fetch(`${URL_PRODUK}?get_detail=${id}`)
    .then(res => res.json()).then(data => {
        clearAllErrors('productForm');
        document.getElementById('pAction').value = 'edit';
        document.getElementById('pID').value = id;
        document.getElementById('pNama').value = data.nama_produk;
        document.getElementById('pTipe').value = data.tipe_produk;
        document.getElementById('pIdGame').value = data.id_game;
        document.getElementById('pGameSearch').value = data.nama_game;
        document.getElementById('pIdSet').value = data.id_set;
        document.getElementById('pSetSearch').value = data.nama_set || '';
        document.getElementById('pStok').value = parseInt(data.stok, 10);
        document.getElementById('pBeli').value = data.harga_beli;
        document.getElementById('pJual').value = data.harga_jual;
        document.getElementById('pKondisi').value = data.kondisi;
        document.getElementById('pDeskripsi').value = data.deskripsi || ''; 
        loadRarities(data.id_game, data.id_rarity);
        toggleProdFields();

        // Blok kode yang memanggil pLogSection, pCreatedBy, dan pEditedBy telah dihapus

        const statusLabel = document.getElementById('pStatusLabel');
        const statusVal = document.getElementById('pStatusValue');
        statusVal.value = data.status;

        if (data.status == 1) {
            statusLabel.innerText = "Active";
            statusLabel.style.color = "#27AE60"; // Hijau
            statusLabel.style.fontWeight = "bold";
        } else {
            statusLabel.innerText = "Inactive";
            statusLabel.style.color = "#E74C3C"; // Merah
            statusLabel.style.fontWeight = "bold";
        }
        document.getElementById('productModal').style.display = 'flex';
    });
}

function confirmDeleteProduct(id) {
    if (confirm("Nonaktifkan produk ini?")) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_produk', id);
        fd.append('id_pengguna_js', getEmpId());

        fetch(URL_PRODUK, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else alert(res.message);
        })
        .catch(err => alert("Gagal menghapus data."));
    }
}

function confirmRestoreProduct(id) {
    if (confirm("Aktifkan kembali produk ini?")) {
        const fd = new FormData();
        fd.append('action', 'restore');
        fd.append('id_produk', id);
        fd.append('id_pengguna_js', getEmpId());

        fetch(URL_PRODUK, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else alert(res.message);
        })
        .catch(err => alert("Gagal mengaktifkan data."));
    }
}

window.addEventListener('click', function(e) {
    if (e.target === productModal) productModal.style.display = 'none';
});