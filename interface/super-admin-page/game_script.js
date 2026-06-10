const modal = document.getElementById('gameModal');
const gameForm = document.getElementById('gameForm');
const URL_GAME = '/cardhaven/interface/super-admin-page/controller_game.php';
var getEmpId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

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
    fetch(`${URL_GAME}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) return cardhavenAlert('error', 'Error', data.error);

            document.getElementById('gameDetailDisplayID').innerText = 'GAM-' + String(id).padStart(4, '0');
            document.getElementById('detailGameNama').innerText = data.nama_game || '-';
            document.getElementById('detailGameDev').innerText = data.developer || '-';

            const statusEl = document.getElementById('detailGameStatus');
            if (data.aktif == 1) {
                statusEl.innerHTML = '<span style="color: #27AE60; font-weight: bold;">Active</span>';
            } else {
                statusEl.innerHTML = '<span style="color: #E74C3C; font-weight: bold;">Inactive</span>';
            }

            document.getElementById('gameDetailModal').style.display = 'flex';
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
                text: `Game status has been ${label}.`,
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
        showError(inputDev, "Developer name is required!");
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
                modal.style.display = 'none';
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
    cardhavenConfirm("Delete Game?", "This game will be permanently deleted. Are you sure?", "Yes, Delete", () => {
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
    cardhavenConfirm("Activate Game?", "This game will be activated again. Are you sure?", "Yes, Activate", () => {
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

window.addEventListener('click', function(e) {
    if (e.target == modal) {
        const inputNama = document.getElementById('nama_game').value.trim();
        const inputDev = document.getElementById('developer').value.trim();

        if (inputNama !== '' || inputDev !== '') {
            cardhavenConfirm(
                "Close Form?", 
                "Unsaved data will be lost. Are you sure you want to cancel?", 
                "Yes, Close", 
                () => {
                    modal.style.display = "none";
                }
            );
        } else {
            modal.style.display = "none";
        }
    }
    
    if (e.target == document.getElementById('gameDetailModal')) {
        document.getElementById('gameDetailModal').style.display = "none";
    }
});