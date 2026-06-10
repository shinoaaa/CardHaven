const setModal = document.getElementById('setModal');
const setForm  = document.getElementById('setForm');
const SET_API_URL_PATH = '/CardHaven/interface/super-admin-page/controller_set.php'; 

let setGamesLoaded = false;
function loadGameOptionsForSet(selectedId) {
    if (setGamesLoaded && selectedId) {
        document.getElementById('setGameId').value = selectedId;
        return;
    }

    fetch(`${SET_API_URL_PATH}?get_games=1`)
        .then(res => res.json())
        .then(res => {
            const select = document.getElementById('setGameId');
            select.innerHTML = '<option value="">-- Pilih Game --</option>';
            res.data.forEach(g => {
                select.appendChild(new Option(g.nama_game, g.id_game));
            });
            setGamesLoaded = true;
            if (selectedId) select.value = selectedId;
        });
}

function openAddSetModal() {
    clearAllErrors('setForm');
    document.getElementById('setModalTitle').innerHTML = 'ADD <span class="blue-text">SET</span>';
    document.getElementById('setDisplayID').innerText = '';
    document.getElementById('setFormAction').value = 'add';
    setForm.reset();
    document.getElementById('setTanggal').value = ''; 
    loadGameOptionsForSet(null);
    setModal.style.display = 'flex';
}

function openEditSetModal(id) {
    fetch(`${SET_API_URL_PATH}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(!data || data.error) return cardhavenAlert('error', 'Error', 'Gagal mengambil data detail');

            clearAllErrors('setForm');
            document.getElementById('setModalTitle').innerHTML = '<span class="blue-text">EDIT</span> SET';
            document.getElementById('setDisplayID').innerText = 'SET-' + String(id).padStart(3, '0');
            document.getElementById('setFormAction').value = 'edit';
            document.getElementById('setIdInput').value   = id;
            document.getElementById('setNama').value      = data.nama_set;
            document.getElementById('setKode').value      = data.kode_set;
            
            if(data.tanggal_rilis) {
                document.getElementById('setTanggal').value = data.tanggal_rilis;
            }

            loadGameOptionsForSet(data.id_game);
            setModal.style.display = 'flex';
        })
        .catch(err => console.error(err));
}
function toggleSetStatus(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id_set', id);
    fd.append('id_pengguna_js', getEmpId());

    fetch(SET_API_URL_PATH, { method: 'POST', body: fd })
    .then(res => {
        if (!res.ok) throw new Error("Network response was not ok");
        return res.json();
    })
    .then(res => {
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                iconColor: '#0088FF',
                title: 'Berhasil!',
                text: `Set telah di${action}.`,
                showConfirmButton: false,
                timer: 1500,
                background: '#ffffff',
                customClass: { title: 'coolveticaa' }
            }).then(() => {
                location.reload();
            });
        } else {
            el.checked = !isActive;
            Swal.fire('Gagal', res.message, 'error');
        }
    })
    .catch(err => {
        el.checked = !isActive;
        console.error(err);
        Swal.fire('Error', 'Terjadi kesalahan koneksi ke server.', 'error');
    });
}
setForm.onsubmit = function(e) {
    e.preventDefault();
    let isValid = true;
    const game = document.getElementById('setGameId');
    const nama = document.getElementById('setNama');
    const kode = document.getElementById('setKode');

    if (!game.value) { showError(game, "Pilih game dari list"); isValid = false; } else clearError(game);
    if (!nama.value.trim()) { showError(nama, "Nama set wajib diisi!"); isValid = false; } else clearError(nama);
    if (!kode.value.trim()) { showError(kode, "Kode set wajib diisi!"); isValid = false; } else clearError(kode);

    if (!isValid) return;

    const formData = new FormData(setForm);
    formData.append('id_pengguna_js', getEmpId());

    fetch(SET_API_URL_PATH, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                cardhavenAlert('success', 'Success', 'Data set berhasil disimpan.', () => location.reload());
            } else {
                cardhavenAlert('error', 'Failed', res.message);
            }
        })
        .catch(err => cardhavenAlert('error', 'System Error', 'Terjadi kesalahan sistem.'));
};

function confirmDeleteSet(id) {
    cardhavenConfirm("Nonaktifkan Set?", "Set ini akan dinonaktifkan.", "Nonaktifkan", () => {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id_set', id);
        formData.append('id_pengguna_js', getEmpId());

        fetch(SET_API_URL_PATH, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') location.reload();
                else cardhavenAlert('error', 'Gagal', res.message);
            });
    });
}

function confirmRestoreSet(id) {
    cardhavenConfirm("Aktifkan Set?", "Set ini akan kembali diaktifkan.", "Aktifkan", () => {
        const formData = new FormData();
        formData.append('action', 'restore');
        formData.append('id_set', id);
        formData.append('id_pengguna_js', getEmpId());

        fetch(SET_API_URL_PATH, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') location.reload();
                else cardhavenAlert('error', 'Gagal', res.message);
            });
    });
}

window.addEventListener('click', function(e) { if (e.target === setModal) setModal.style.display = 'none'; });