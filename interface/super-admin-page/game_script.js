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
                title: 'Berhasil!',
                text: `Status game telah di${action}.`,
                showConfirmButton: false,
                timer: 1500,
                background: '#ffffff',
                customClass: { title: 'coolveticaa' }
            }).then(() => location.reload());
        } else {
            el.checked = !isActive;
            Swal.fire('Gagal', res.message, 'error');
        }
    })
    .catch(err => {
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
            cardhavenAlert('success', 'Success', 'Data game berhasil disimpan.', () => {
                modal.style.display = 'none';
                setTimeout(() => { location.reload(); }, 300);
            });
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
    cardhavenConfirm("Hapus Game?", "Game ini akan Hapus.", "Hapus", () => {
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

window.addEventListener('click', function(e) {
    // 1. Validasi saat menutup Form Add / Edit Game
    if (e.target == modal) {
        const inputNama = document.getElementById('nama_game').value.trim();
        const inputDev = document.getElementById('developer').value.trim();

        // Jika ada salah satu input yang sudah diketik
        if (inputNama !== '' || inputDev !== '') {
            cardhavenConfirm(
                "Tutup Form?", 
                "Data yang sudah Anda ketik belum disimpan dan akan hilang. Yakin ingin membatalkan?", 
                "Ya, Tutup", 
                () => {
                    modal.style.display = "none"; // Tutup form jika user klik "Ya, Tutup"
                }
            );
        } else {
            // Jika inputan masih kosong, langsung tutup saja
            modal.style.display = "none";
        }
    }
    
    // 2. Untuk Modal Detail (hanya untuk baca), boleh langsung tutup
    if (e.target == document.getElementById('gameDetailModal')) {
        document.getElementById('gameDetailModal').style.display = "none";
    }
});