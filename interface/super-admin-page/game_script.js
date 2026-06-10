const modal = document.getElementById('gameModal');
const gameForm = document.getElementById('gameForm');
const URL_GAME = '/cardhaven/interface/super-admin-page/controller_game.php';
const getEmpId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

document.querySelectorAll('#gameForm .modal-input').forEach(input => {
    input.addEventListener('input', function() { clearError(this); });
});

function openAddModal() {
    clearAllErrors('gameForm');
    document.getElementById('modalTitle').innerHTML = 'ADD <span class="blue-text">GAME</span>';
    document.getElementById('displayID').innerText = '';
    document.getElementById('formAction').value = 'add';
    gameForm.reset();
    modal.style.display = 'flex';
}

function openDetailModal(id) {
    Swal.fire({
        title: 'Loading Data...',
        allowOutsideClick: false,
        showConfirmButton: false,
        background: "transparent",
        backdrop: "rgba(13,71,161,.25)",
        customClass: { popup: "cardhaven-popup", title: "coolveticaa cardhaven-title" },
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(`${URL_GAME}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.error) return cardhavenAlert('error', 'Error', data.error);

            const detailHTML = `
                <div style="text-align: left; margin-top: 1rem; padding: 1rem; background: white; border-radius: 12px; border: 1px solid #E2E8F0;">
                    <div style="margin-bottom: 10px;">
                        <small style="color: #A0AEC0; font-weight: bold;">ID / NAME</small>
                        <div style="color: #2D3748; font-weight: bold; font-size: 1.1rem;">GAM-${id} / ${data.nama_game}</div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <small style="color: #A0AEC0; font-weight: bold;">DEVELOPER</small>
                        <div style="color: #2D3748;">${data.developer || '-'}</div>
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
                title: "Game Detail",
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

function openEditModal(id) {
    fetch(`${URL_GAME}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.error) return cardhavenAlert('error', 'Error', data.error);

            clearAllErrors('gameForm');
            document.getElementById('modalTitle').innerHTML = '<span class="blue-text">EDIT</span> GAME';
            document.getElementById('displayID').innerText = 'GAM-' + id;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formID').value = id;
            document.getElementById('nama_game').value = data.nama_game;
            document.getElementById('developer').value = data.developer;

            modal.style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'System Error', 'Failed to fetch data from server.');
        });
}

function toggleStatus(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    const label = isActive ? 'activated' : 'deactivated';
    
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id_game', id);
    fd.append('id_pengguna_js', getEmpId()); 

    fetch(URL_GAME, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                iconColor: '#0088FF',
                title: 'Success!',
                text: `Game has been ${label}.`,
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

gameForm.onsubmit = function(e) {
    e.preventDefault(); 
    const inputNama = document.getElementById('nama_game');
    const inputDev = document.getElementById('developer');
    let isValid = true;

    if (!inputNama.value.trim()) {
        showError(inputNama, "Game name is required!");
        isValid = false;
    } else clearError(inputNama);

    if (!inputDev.value.trim()) {
        showError(inputDev, "Developer is required!");
        isValid = false;
    } else clearError(inputDev);

    if (!isValid) return;

    const formData = new FormData(gameForm);
    formData.append('id_pengguna_js', getEmpId());

    fetch(URL_GAME, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            cardhavenAlert('success', 'Success', 'Game data saved successfully.', () => {
                modal.style.display = 'none'; // Auto-close modal
                setTimeout(() => { location.reload(); }, 300);
            });
        } else {
            cardhavenAlert('error', 'Failed', res.message);
        }
    })
    .catch(err => {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'A system error occurred.');
    });
};

function confirmDelete(id) {
    cardhavenConfirm("Delete Game?", "This game will be deleted.", "Delete", () => {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_game', id);
        fd.append('id_pengguna_js', getEmpId());
        
        fetch(URL_GAME, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else cardhavenAlert('error', 'Failed', res.message);
        });
    });
}

function confirmRestore(id) {
    cardhavenConfirm("Restore Game?", "This game will be restored.", "Restore", () => {
        const fd = new FormData();
        fd.append('action', 'restore'); 
        fd.append('id_game', id);
        fd.append('id_pengguna_js', getEmpId());
        
        fetch(URL_GAME, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else cardhavenAlert('error', 'Failed', res.message);
        });
    });
}

window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; }