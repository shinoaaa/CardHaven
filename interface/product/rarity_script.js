const modalRarity = document.getElementById('rarityModal');
const rarityForm = document.getElementById('rarityForm');
const API_URL = '/CardHaven/interface/product/controller_rarity.php'; // Pastikan path sesuai dengan foldermu
var getEmpId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

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

function loadRarityPage(page) {
    const container = document.getElementById('container-rarity');
    container.style.opacity = '0.5';

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('pr', page);

    fetch(`${window.location.pathname}?${urlParams.toString()}`)
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            container.innerHTML = doc.getElementById('container-rarity').innerHTML;
            container.style.opacity = '1';
            window.history.pushState({}, '', `${window.location.pathname}?${urlParams.toString()}`);
        });
}

function openDetailRarity(id) {
    fetch(`${API_URL}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) return cardhavenAlert('error', 'Error', data.error);

            document.getElementById('rarityDetailDisplayID').innerText = 'RAR-' + String(id).padStart(3, '0');
            document.getElementById('detailRarityGame').innerText = data.nama_game ?? '-';
            document.getElementById('detailRarityNama').innerText = data.nama_rarity ?? '-';
            document.getElementById('detailRarityKode').innerText = data.kode_rarity || '-';
            document.getElementById('detailRarityStatus').innerHTML = data.aktif == 1
                ? '<span style="color: #27AE60; font-weight: bold;">Active</span>'
                : '<span style="color: #E74C3C; font-weight: bold;">Inactive</span>';

            document.getElementById('rarityDetailModal').style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'System Error', 'Failed to fetch data from server.');
        });
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
            const selectGame = document.getElementById('inputGameRarity');
            selectGame.value = data.id_game;
            if (!selectGame.value) {
                const opt = document.createElement('option');
                opt.value = data.id_game;
                opt.text = data.nama_game ?? 'Unknown Game';
                selectGame.appendChild(opt);
                selectGame.value = data.id_game;
            }
            document.getElementById('inputNamaRarity').value = data.nama_rarity;
            document.getElementById('inputKodeRarity').value = data.kode_rarity;
            if(document.getElementById('inputAktifRarity')) document.getElementById('inputAktifRarity').value = data.aktif;
            
            modalRarity.style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'System Error', 'Data transmission failed.');
        });
}

rarityForm.onsubmit = async function(e) {
    e.preventDefault();
    let isValid = true;
    const game = document.getElementById('inputGameRarity');
    const nama = document.getElementById('inputNamaRarity');
    const kode = document.getElementById('inputKodeRarity');
    const idRarity = document.getElementById('inputIdRarity').value; 

    if (!game.value) { showError(game, "Please select a game!"); isValid = false; } else clearError(game);
    if (!nama.value.trim()) { showError(nama, "Rarity name is required!"); isValid = false; } else clearError(nama);
    if (!kode.value.trim()) { showError(kode, "Rarity code is required!"); isValid = false; } else clearError(kode);
    if (!isValid) return;
    
    const submitBtn = rarityForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerText = "Checking...";

    try {
        const duplicate = await isDuplicate(game.value, nama.value.trim(), kode.value.trim(), idRarity);
        if (duplicate) {
            showError(nama, "Rarity name or code already exists in this game!");
            submitBtn.disabled = false;
            submitBtn.innerText = "Save Rarity";
            return; 
        }

        const formData = new FormData(rarityForm);
        formData.append('id_pengguna_js', getEmpId());

        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const result = JSON.parse(await res.text());

        if (result.status === 'success') {
            cardhavenAlert('success', 'Success', 'Rarity data saved successfully.', () => {
                modalRarity.style.display = 'none'; 
                setTimeout(() => { location.reload(); }, 300);
            });
        } else {
            cardhavenAlert('error', 'Failed', result.message);
            submitBtn.disabled = false;
            submitBtn.innerText = "Save Rarity";
        }
    } catch (err) {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Connection error occurred.');
        submitBtn.disabled = false;
        submitBtn.innerText = "Save Rarity";
    }
};

function toggleRarityStatus(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id_rarity', id);
    fd.append('id_pengguna_js', getEmpId()); 

    fetch(API_URL, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            Swal.fire({ icon: 'success', iconColor: '#0088FF', title: 'Success!', text: `Rarity has been ${action}.`, showConfirmButton: false, timer: 1500, customClass: { title: 'coolveticaa' } }).then(() => location.reload());
        } else {
            el.checked = !isActive;
            Swal.fire('Failed', res.message, 'error');
        }
    })
    .catch(err => {
        el.checked = !isActive;
        Swal.fire('Error', 'Connection error occurred.', 'error');
    });
}

function confirmDeleteRarity(id) {
    cardhavenConfirm("Delete Rarity?", "This rarity will be permanently deleted.", "Yes, Delete", () => {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_rarity', id);
        fd.append('id_pengguna_js', getEmpId());
        
        fetch(API_URL, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') location.reload();
                else cardhavenAlert('error', 'Failed', res.message);
            });
    });
}

window.addEventListener('click', (e) => { 
    if (e.target == modalRarity) {
        const nama = document.getElementById('inputNamaRarity').value.trim();
        if (nama !== '') {
            cardhavenConfirm("Close Form?", "Unsaved data will be lost. Are you sure you want to cancel?", "Yes, Close", () => { modalRarity.style.display = 'none'; });
        } else {
            modalRarity.style.display = 'none';
        }
    }
    if (e.target == document.getElementById('rarityDetailModal')) document.getElementById('rarityDetailModal').style.display = "none"; 
});