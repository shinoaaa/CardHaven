const modal = document.getElementById('gameModal');
const gameForm = document.getElementById('gameForm');
const URL_GAME = '/cardhaven/interface/product/controller_game.php';
// var getEmpId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

document.querySelectorAll('#gameForm .modal-input').forEach(input => {
    input.addEventListener('input', function() { clearError(this); });
});

function loadGamePage(page) {
    const container = document.getElementById('container-game'); // Pastikan div di game_card.php punya ID ini
    container.style.opacity = '0.5';

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('pg', page);

    fetch(`${window.location.pathname}?${urlParams.toString()}`)
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            container.innerHTML = doc.getElementById('container-game').innerHTML;
            container.style.opacity = '1';
            window.history.pushState({}, '', `${window.location.pathname}?${urlParams.toString()}`);
        });
}
function previewBannerImage(input) {
    const preview = document.getElementById('gPreview');
    const placeholder = document.getElementById('gPlaceholder');
    const errorEl = document.getElementById('error-foto-game');
    
    const file = input.files[0];
    errorEl.innerText = "";

    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            showError(input, "File is too large! (Max 5MB)");
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
function resetGameForm() {
    const form = document.getElementById('gameForm');
    if (form) form.reset(); // Reset input teks

    // Reset Input File (Penting!)
    const fileInput = document.getElementById('gFoto');
    if (fileInput) fileInput.value = ""; 

    // Reset Preview Gambar
    const preview = document.getElementById('gPreview');
    const placeholder = document.getElementById('gPlaceholder');
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }
    if (placeholder) {
        placeholder.style.display = 'block';
    }

    // Bersihkan error
    clearAllErrors('gameForm');
}
function openAddModal() {
    resetGameForm();
    document.getElementById('modalTitle').innerHTML = 'ADD <span class="blue-text">GAME</span>';
    document.getElementById('displayID').innerText = '';
    document.getElementById('formAction').value = 'add';
    gameForm.reset();

    document.getElementById('gPreview').style.display = 'none';
    document.getElementById('gPlaceholder').style.display = 'block';
    modal.style.display = 'flex';
}

function openDetailModal(id) {
    resetGameForm()
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
            const detImg = document.getElementById('detailGameBanner');
            const detNoImg = document.getElementById('detailGameNoBanner');
            if (data.foto_banner) {
                detImg.src = '/CardHaven/' + data.foto_banner;
                detImg.style.display = 'block';
                detNoImg.style.display = 'none';
            } else {
                detImg.style.display = 'none';
                detNoImg.style.display = 'block';
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
            
            const statusDisplay = document.getElementById('gameStatusDisplay');
            if (statusDisplay) {
                statusDisplay.value = (data.aktif == 1) ? 'Active' : 'Inactive';
                statusDisplay.style.color = (data.aktif == 1) ? '#27AE60' : '#E74C3C';
            }
            const preview = document.getElementById('gPreview');
            const placeholder = document.getElementById('gPlaceholder');
            if (data.foto_banner) {
                preview.src = '/CardHaven/' + data.foto_banner;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            }
            modal.style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'System Error', 'Failed to fetch data from server.');
        });
}

function toggleStatus(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    const label  = isActive ? 'activated' : 'deactivated';

    cardhavenConfirm(
        `${isActive ? 'Activate' : 'Deactivate'} Game?`,
        `Are you sure you want to ${isActive ? 'activate' : 'deactivate'} this game?`,
        isActive ? 'Activate' : 'Deactivate',
        () => {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id_game', id);
            fd.append('id_pengguna_js', getEmpId());

            fetch(URL_GAME, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    cardhavenAlert('success', 'Success', `Game status has been ${label}.`, () => location.reload());
                } else {
                    el.checked = !isActive;
                    cardhavenAlert('error', 'Failed', res.message);
                }
            })
            .catch(err => {
                el.checked = !isActive;
                cardhavenAlert('error', 'Error', 'Connection error occurred.');
            });
        },
        () => { el.checked = !isActive; }
    );
}

gameForm.onsubmit = function(e) {
    e.preventDefault(); 
    const inputNama = document.getElementById('nama_game');
    const inputDev = document.getElementById('developer');
    let isValid = true;

    if (!inputNama.value.trim()) { showError(inputNama, "Game name is required."); isValid = false; } else clearError(inputNama);
    if (!inputDev.value.trim()) { showError(inputDev, "Developer name is required."); isValid = false; } else clearError(inputDev);

    if (!isValid) return;

    const btnSubmit    = gameForm.querySelector('button[type="submit"]');
    const originalText = btnSubmit.innerText;
    btnSubmit.innerText = 'Processing...';
    btnSubmit.disabled  = true;

    const formData = new FormData(gameForm);
    formData.append('id_pengguna_js', getEmpId());

    fetch(URL_GAME, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        btnSubmit.innerText = originalText;
        btnSubmit.disabled  = false;
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
        btnSubmit.innerText = originalText;
        btnSubmit.disabled  = false;
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
            if (res.status === 'success') {
                cardhavenAlert('success', 'Success', 'Game has been deleted.', () => location.reload());
            } else {
                cardhavenAlert('error', 'Failed', res.message);
            }
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'Error', 'Connection error occurred.');
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
            if (res.status === 'success') {
                cardhavenAlert('success', 'Success', 'Game has been activated.', () => location.reload());
            } else {
                cardhavenAlert('error', 'Failed', res.message);
            }
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'Error', 'Connection error occurred.');
        });
    });
}

window.addEventListener('click', function(e) {
    if (e.target == modal) {
        const inputNama = document.getElementById('nama_game').value.trim();
        const inputDev  = document.getElementById('developer').value.trim();
        const inputFoto = document.getElementById('gFoto').value; 

        // If any field is filled or a file is selected
        if (inputNama !== '' || inputDev !== '' || inputFoto !== '') {
            modal.style.display = "none"; // Hide temporarily
            let isConfirmed = false;
            
            const actionText = document.getElementById('formAction').value === 'edit' ? 'Edit' : 'Add';
            
            cardhavenConfirm(
                `Cancel ${actionText} Game?`, 
                "Any unsaved changes will be lost. Are you sure you want to exit?", 
                "Yes, Exit", 
                () => {
                    isConfirmed = true;
                    resetGameForm(); // Full reset on exit
                }
            );

            // Monitor if user cancels the confirmation, then show modal again
            const checkSwal = setInterval(() => {
                if (!Swal.isVisible()) {
                    clearInterval(checkSwal);
                    if (!isConfirmed) modal.style.display = "flex";
                }
            }, 15);
        } else {
            modal.style.display = "none";
            resetGameForm();
        }
    }
    // Detail modal close
    if (e.target == document.getElementById('gameDetailModal')) {
        document.getElementById('gameDetailModal').style.display = "none";
    }
});
