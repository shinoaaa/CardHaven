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

function openDetailRarity(id) {
    Swal.fire({
        title: 'Loading Data...',
        allowOutsideClick: false,
        showConfirmButton: false,
        background: "transparent",
        backdrop: "rgba(13,71,161,.25)",
        customClass: { popup: "cardhaven-popup", title: "coolveticaa cardhaven-title" },
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(`${API_URL}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.error) return cardhavenAlert('error', 'Error', data.error);

            const detailHTML = `
                <div style="text-align: left; margin-top: 1rem; padding: 1rem; background: white; border-radius: 12px; border: 1px solid #E2E8F0;">
                    <div style="margin-bottom: 10px;">
                        <small style="color: #A0AEC0; font-weight: bold;">ID / NAME</small>
                        <div style="color: #2D3748; font-weight: bold; font-size: 1.1rem;">RAR-${String(id).padStart(3, '0')} / ${data.nama_rarity}</div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <small style="color: #A0AEC0; font-weight: bold;">RARITY CODE</small>
                        <div style="color: #2D3748;">${data.kode_rarity || '-'}</div>
                    </div>
                    <hr style="border: 0; border-top: 1px solid #E2E8F0; margin: 15px 0;">
                    <div style="margin-bottom: 10px;">
                        <small style="color: #A0AEC0; font-weight: bold;">CURRENT STATUS</small>
                        <div style="margin-top: 4px;">
                            ${data.aktif == 1 
                                ? '<span style="background: #E6F4EA; color: #1E8E3E; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">ACTIVE</span>' 
                                : '<span style="background: #FCE8E6; color: #D93025; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">INACTIVE</span>'}
                        </div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: "Rarity Detail",
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
            submitBtn.innerText = "SAVE";
            return; 
        }

        const formData = new FormData(rarityForm);
        formData.append('id_pengguna_js', getEmpId());

        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const result = JSON.parse(await res.text());

        if (result.status === 'success') {
            cardhavenAlert('success', 'Success', 'Rarity data saved successfully.', () => {
                modalRarity.style.display = 'none'; // Auto-close modal
                setTimeout(() => { location.reload(); }, 300);
            });
        } else {
            cardhavenAlert('error', 'Failed', result.message);
            submitBtn.disabled = false;
            submitBtn.innerText = "SAVE";
        }
    } catch (err) {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Connection error occurred.');
        submitBtn.disabled = false;
        submitBtn.innerText = "SAVE";
    }
};

function toggleRarityStatus(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    const label = isActive ? 'activated' : 'deactivated';
    
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id_rarity', id);
    fd.append('id_pengguna_js', getEmpId()); 

    fetch(API_URL, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                iconColor: '#0088FF',
                title: 'Success!',
                text: `Rarity has been ${label}.`,
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
        Swal.fire('Error', 'Connection error occurred.', 'error');
    });
}

function confirmDeleteRarity(id) {
    cardhavenConfirm("Delete Rarity?", "This rarity will be deleted.", "Delete", () => {
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

function confirmRestoreRarity(id) {
    cardhavenConfirm("Restore Rarity?", "This rarity will be restored.", "Restore", () => {
        const fd = new FormData();
        fd.append('action', 'restore');
        fd.append('id_rarity', id);
        fd.append('id_pengguna_js', getEmpId()); 
        
        fetch(API_URL,  { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else cardhavenAlert('error', 'Failed', res.message);
        });
    });
}

window.addEventListener('click', (e) => { if (e.target == modalRarity) modalRarity.style.display = "none"; });