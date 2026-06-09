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

openDetailModal = (id) => {
    fetch(`${URL_GAME}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.error) return cardhavenAlert('error', 'Error', data.error);
        })
        .catch(err => {
            console.error(err);
            cardhavenAlert('error', 'System Error', 'Gagal mengambil data dari server.');
        });
};

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
            cardhavenAlert('error', 'System Error', 'Gagal mengambil data dari server.');
        });
}
function toggleStatus(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id_game', id);
    fd.append('id_pengguna_js', getEmpId()); // Mengambil ID User

    fetch(URL_GAME, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            // Notifikasi Sukses Custom sesuai request kamu
            Swal.fire({
                icon: 'success',
                iconColor: '#0088FF',
                title: 'Berhasil!',
                text: `Status game telah di${action}.`,
                showConfirmButton: false,
                timer: 1500,
                background: '#ffffff',
                customClass: {
                    title: 'coolveticaa' 
                }
            }).then(() => {
                location.reload(); // Reload setelah notifikasi hilang
            });
        } else {
            // Jika gagal di database, kembalikan posisi toggle
            el.checked = !isActive;
            Swal.fire('Gagal', res.message, 'error');
        }
    })
    .catch(err => {
        // Jika koneksi error, kembalikan posisi toggle
        el.checked = !isActive;
        Swal.fire('Error', 'Terjadi kesalahan koneksi ke server.', 'error');
    });
}

gameForm.onsubmit = function(e) {
    e.preventDefault(); 
    const inputNama = document.getElementById('nama_game');
    const inputDev = document.getElementById('developer');
    let isValid = true;

    if (!inputNama.value.trim()) {
        showError(inputNama, "Nama game wajib diisi!");
        isValid = false;
    } else clearError(inputNama);

    if (!inputDev.value.trim()) {
        showError(inputDev, "Developer wajib diisi!");
        isValid = false;
    } else clearError(inputDev);

    if (!isValid) return;

    const formData = new FormData(gameForm);
    formData.append('id_pengguna_js', getEmpId());

    fetch(URL_GAME, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            cardhavenAlert('success', 'Success', 'Data game berhasil disimpan.', () => location.reload());
        } else {
            cardhavenAlert('error', 'Failed', res.message);
        }
    })
    .catch(err => {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Terjadi kesalahan sistem.');
    });
};

function confirmDelete(id) {
    cardhavenConfirm("Nonaktifkan Game?", "Game ini akan dinonaktifkan.", "Nonaktifkan", () => {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_game', id);
        fd.append('id_pengguna_js', getEmpId());
        
        fetch(URL_GAME, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else cardhavenAlert('error', 'Gagal', res.message);
        });
    });
}

function confirmRestore(id) {
    cardhavenConfirm("Aktifkan Game?", "Game ini akan kembali diaktifkan.", "Aktifkan", () => {
        const fd = new FormData();
        fd.append('action', 'restore'); 
        fd.append('id_game', id);
        fd.append('id_pengguna_js', getEmpId());
        
        fetch(URL_GAME, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else cardhavenAlert('error', 'Gagal', res.message);
        });
    });
}

window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; }