const URL_PRODUK = '/CardHaven/interface/super-admin-page/controller_produk.php'; 
// --- UTILITY: VALIDASI VISUAL ---
function showError(el, msg) {
    el.style.border = "2px solid #E74C3C"; // Border jadi merah
    
    const err = el.closest('.modal-form-group').querySelector('.error-message');
    
    if (err) {
        err.innerText = msg;
        err.style.display = "block"; // Pastikan muncul
        err.style.color = "#E74C3C"; // Pastikan warna teks merah
    }
}


function clearError(el) {
    el.style.border = "1.5px solid #888"; // Border balik normal
    
    // TAMBAHKAN TITIK (.) sebelum nama class
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
document.getElementById('productForm').onsubmit = function(e) {
    e.preventDefault();
    let isValid = true;

    const fields = [
        { id: 'pNama', msg: "Nama produk wajib diisi" },
        { id: 'pGameSearch', msg: "Pilih game dari list" },
        { id: 'pStok', msg: "Stok minimal 0" },
        { id: 'pBeli', msg: "Harga beli wajib diisi" },
        { id: 'pJual', msg: "Harga jual wajib diisi" }
    ];
    fields.forEach(f => {
        const el = document.getElementById(f.id);
        if (!el.value) {
            showError(el, f.msg);
            isValid = false;
        } else {
            clearError(el);
        }
    });

    const tipe = document.getElementById('pTipe').value;
    if (tipe === 'Single Card') {
        if (!document.getElementById('pIdRarity').value) {
            showError(document.getElementById('pIdRarity'), "Pilih rarity");
            isValid = false;
        }
        if (!document.getElementById('pKondisi').value) {
            showError(document.getElementById('pKondisi'), "Pilih kondisi");
            isValid = false;
        }
    }
    if (tipe.includes('Card') || tipe.includes('Booster')) {
        if (!document.getElementById('pIdSet').value) {
            showError(document.getElementById('pSetSearch'), "Pilih set dari list");
            isValid = false;
        }
    }

    if (isValid) {
        const fd = new FormData(this);
        fd.append('id_pengguna_js', localStorage.getItem('id_pengguna'));
        fetch(URL_PRODUK, { method: 'POST', body: fd })
        .then(res => res.json()).then(res => {
            if (res.status === 'success') location.reload();
            else alert(res.message);
        });
    }
};

// --- OPEN MODAL (ADD/EDIT) ---
function openAddProductModal() {
    clearAllErrors('productForm');
    document.getElementById('productForm').reset();
    document.getElementById('pAction').value = 'add';
    document.getElementById('pLogSection').style.display = 'none';
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
        document.getElementById('pStok').value = data.stok;
        document.getElementById('pBeli').value = data.harga_beli;
        document.getElementById('pJual').value = data.harga_jual;
        document.getElementById('pKondisi').value = data.kondisi;
        loadRarities(data.id_game, data.id_rarity);
        toggleProdFields();
        document.getElementById('pLogSection').style.display = 'block';
        document.getElementById('productModal').style.display = 'flex';
    });
}

window.addEventListener('click', function(e) {
    if (e.target === productModal) productModal.style.display = 'none';
});