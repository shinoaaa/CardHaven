const modalRarity = document.getElementById('rarityModal');
const rarityForm = document.getElementById('rarityForm');
const API_URL = '/CardHaven/interface/super-admin-page/controller_rarity.php';

async function isDuplicate(idGame, nama, kode, excludeId) {
    const resp = await fetch(`${API_URL}?check_duplicate=1&id_game=${idGame}&nama_rarity=${encodeURIComponent(nama)}&kode_rarity=${encodeURIComponent(kode)}&exclude_id=${excludeId}`);
    const data = await resp.json();
    return data.exists;
}

function openModalRarity() {
    document.getElementById('modalTitleRarity').innerHTML = 'ADD <span class="blue-text">RARITY</span>';
    document.getElementById('displayIDRarity').innerText = '';
    document.getElementById('formActionRarity').value = 'add';
    rarityForm.reset();
    clearAllErrors('rarityForm');
    document.getElementById('inputIdRarity').value = "0";
    modalRarity.style.display = 'flex';
}

function openEditRarity(id) {
    fetch(`${API_URL}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (data.error) return cardhavenAlert('error', 'Error', data.error);

            clearAllErrors('rarityForm');
            document.getElementById('modalTitleRarity').innerHTML = '<span class="blue-text">EDIT</span> RARITY';
            document.getElementById('displayIDRarity').innerText = 'RAR-' + String(id).padStart(3, '0');
            document.getElementById('formActionRarity').value = 'edit';
            document.getElementById('inputIdRarity').value = id;
            document.getElementById('inputGameRarity').value = data.id_game;
            document.getElementById('inputNamaRarity').value = data.nama_rarity;
            document.getElementById('inputKodeRarity').value = data.kode_rarity;
            
            modalRarity.style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'System Error', 'Transmisi data gagal.');
        });
}

rarityForm.onsubmit = async function(e) {
    e.preventDefault();
    let isValid = true;
    const game = document.getElementById('inputGameRarity');
    const nama = document.getElementById('inputNamaRarity');
    const kode = document.getElementById('inputKodeRarity');
    const idRarity = document.getElementById('inputIdRarity').value; 

    if (!game.value) { showError(game, "Pilih game dari list"); isValid = false; } else clearError(game);
    if (!nama.value.trim()) { showError(nama, "Nama rarity wajib diisi!"); isValid = false; } else clearError(nama);
    if (!kode.value.trim()) { showError(kode, "Kode rarity wajib diisi!"); isValid = false; } else clearError(kode);
    if (!isValid) return;
    
    const submitBtn = rarityForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerText = "Checking...";

    try {
        const duplicate = await isDuplicate(game.value, nama.value.trim(), kode.value.trim(), idRarity);
        if (duplicate) {
            showError(nama, "Nama atau Kode Rarity sudah ada di game ini!");
            submitBtn.disabled = false;
            submitBtn.innerText = "SAVE";
            return; 
        }

        const formData = new FormData(rarityForm);
        formData.append('id_pengguna_js', getEmpId());

        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const result = JSON.parse(await res.text());

        if (result.status === 'success') {
            cardhavenAlert('success', 'Success', 'Data rarity berhasil disimpan.', () => location.reload());
        } else {
            cardhavenAlert('error', 'Failed', result.message);
            submitBtn.disabled = false;
            submitBtn.innerText = "SAVE";
        }
    } catch (err) {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Terjadi kesalahan koneksi.');
        submitBtn.disabled = false;
        submitBtn.innerText = "SAVE";
    }
};

function confirmDeleteRarity(id) {
    cardhavenConfirm("Nonaktifkan Rarity?", "Rarity ini akan dinonaktifkan.", "Nonaktifkan", () => {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_rarity', id);
        fd.append('id_pengguna_js', getEmpId());
        
        fetch(API_URL, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') location.reload();
                else cardhavenAlert('error', 'Gagal', res.message);
            });
    });
}

function confirmRestoreRarity(id) {
    cardhavenConfirm("Aktifkan Rarity?", "Rarity ini akan kembali diaktifkan.", "Aktifkan", () => {
        const fd = new FormData();
        fd.append('action', 'restore');
        fd.append('id_rarity', id);
        fd.append('id_pengguna_js', getEmpId()); 
        
        fetch(API_URL,  { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else cardhavenAlert('error', 'Gagal', res.message);
        });
    });
}

window.addEventListener('click', (e) => { if (e.target == modalRarity) modalRarity.style.display = "none"; });