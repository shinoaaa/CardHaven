const setModal = document.getElementById('setModal');
const setForm  = document.getElementById('setForm');
const SET_API  = '/CardHaven/interface/product/controller_set.php';
var getEmpId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

let setGamesLoaded = false;

function loadGameOptionsForSet(selectedId) {
    if (setGamesLoaded && selectedId) {
        document.getElementById('setGameId').value = selectedId;
        return;
    }

    fetch(`${SET_API}?get_games=1`)
        .then(async res => JSON.parse(await res.text()))
        .then(res => {
            const select = document.getElementById('setGameId');
            select.innerHTML = '<option value="">-- Select Game --</option>';
            res.data.forEach(g => { select.appendChild(new Option(g.nama_game, g.id_game)); });
            setGamesLoaded = true;
            if (selectedId) select.value = selectedId;
        })
        .catch(err => {
            console.error('loadGameOptionsForSet error:', err);
            cardhavenAlert('error', 'System Error', 'Failed to load game list.');
        });
}

function openAddSetModal() {
    clearAllErrors('setForm');
    document.getElementById('setModalTitle').innerHTML = 'ADD <span class="blue-text">SET</span>';
    document.getElementById('setDisplayID').innerText  = '';
    document.getElementById('setFormAction').value     = 'add';
    setForm.reset();
    document.getElementById('setTanggal').value = '';
    loadGameOptionsForSet(null);
    setModal.style.display = 'flex';
}

function openEditSetModal(id) {
    fetch(`${SET_API}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (!data || data.error) return cardhavenAlert('error', 'Error', data.error || 'Failed to fetch set data.');

            clearAllErrors('setForm');
            document.getElementById('setModalTitle').innerHTML = '<span class="blue-text">EDIT</span> SET';
            document.getElementById('setDisplayID').innerText  = 'SET-' + String(id).padStart(3, '0');
            document.getElementById('setFormAction').value     = 'edit';
            document.getElementById('setIdInput').value        = id;
            document.getElementById('setNama').value           = data.nama_set  || '';
            document.getElementById('setKode').value           = data.kode_set  || '';

            if (data.tanggal_rilis) document.getElementById('setTanggal').value = data.tanggal_rilis;

            loadGameOptionsForSet(data.id_game);
            setModal.style.display = 'flex';
        })
        .catch(err => {
            console.error('openEditSetModal error:', err);
            cardhavenAlert('error', 'System Error', 'Failed to connect to server.');
        });
}

setForm.onsubmit = async function(e) {
    e.preventDefault();
    let isValid = true;

    const game = document.getElementById('setGameId');
    const nama = document.getElementById('setNama');
    const kode = document.getElementById('setKode');

    if (!game.value)        { showError(game, 'Please select a game!');    isValid = false; } else clearError(game);
    if (!nama.value.trim()) { showError(nama, 'Set name is required!');    isValid = false; } else clearError(nama);
    if (!kode.value.trim()) { showError(kode, 'Set code is required!');    isValid = false; } else clearError(kode);

    if (!isValid) return;

    const submitBtn = setForm.querySelector('button[type="submit"]');
    submitBtn.disabled  = true;
    submitBtn.innerText = 'Saving...';

    try {
        const formData = new FormData(setForm);
        formData.append('id_pengguna_js', getEmpId());

        const res    = await fetch(SET_API, { method: 'POST', body: formData });
        const result = JSON.parse(await res.text());

        if (result.status === 'success') {
            cardhavenAlert('success', 'Success', 'Set data saved successfully.', () => {
                setModal.style.display = 'none'; 
                setTimeout(() => { location.reload(); }, 300);
            });
        } else {
            cardhavenAlert('error', 'Failed', result.message);
            submitBtn.disabled  = false;
            submitBtn.innerText = 'Save Set';
        }
    } catch (err) {
        console.error('setForm submit error:', err);
        cardhavenAlert('error', 'System Error', 'Connection error. Please try again.');
        submitBtn.disabled  = false;
        submitBtn.innerText = 'Save Set';
    }
};

function toggleSetStatus(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    const label  = isActive ? 'activated' : 'deactivated';

    const fd = new FormData();
    fd.append('action', action);
    fd.append('id_set', id);
    fd.append('id_pengguna_js', getEmpId());

    fetch(SET_API, { method: 'POST', body: fd })
        .then(async res => JSON.parse(await res.text()))
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', iconColor: '#0088FF', title: 'Success!', text: `Set status has been ${label}.`, showConfirmButton: false, timer: 1500, customClass: { title: 'coolveticaa' } }).then(() => location.reload());
            } else {
                el.checked = !isActive;
                cardhavenAlert('error', 'Failed', res.message);
            }
        })
        .catch(err => {
            console.error('toggleSetStatus error:', err);
            el.checked = !isActive;
            cardhavenAlert('error', 'System Error', 'Connection error.');
        });
}

function confirmDeleteSet(id) {
    cardhavenConfirm('Delete Set?', 'This set will be permanently deleted. Are you sure?', 'Yes, Delete', () => {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_set', id);
        fd.append('id_pengguna_js', getEmpId());

        fetch(SET_API, { method: 'POST', body: fd })
            .then(async res => JSON.parse(await res.text()))
            .then(res => {
                if (res.status === 'success') location.reload();
                else cardhavenAlert('error', 'Failed', res.message);
            });
    });
}

function openDetailSetModal(id) {
    fetch(`${SET_API}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (!data || data.error) return cardhavenAlert('error', 'Error', data.error || 'Failed to fetch set data.');

            document.getElementById('setDetailDisplayID').innerText  = 'SET-' + String(id).padStart(3, '0');
            document.getElementById('detailSetNama').innerText        = data.nama_set    || '-';
            document.getElementById('detailSetKode').innerText        = data.kode_set    || '-';
            document.getElementById('detailSetGame').innerText        = data.nama_game   || '-';
            document.getElementById('detailSetTanggal').innerText     = data.tanggal_rilis
                ? new Date(data.tanggal_rilis).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '-';

            const statusEl = document.getElementById('detailSetStatus');
            if (data.aktif == 1) {
                statusEl.innerText   = 'Active';
                statusEl.style.color = '#27AE60';
                statusEl.style.fontWeight = '700';
            } else {
                statusEl.innerText   = 'Inactive';
                statusEl.style.color = '#E74C3C';
                statusEl.style.fontWeight = '700';
            }

            document.getElementById('setDetailModal').style.display = 'flex';
        })
        .catch(err => {
            console.error('openDetailSetModal error:', err);
            cardhavenAlert('error', 'System Error', 'Failed to connect to server.');
        });
}

window.addEventListener('click', function(e) {
    if (e.target === setModal) {
        const nama = document.getElementById('setNama').value.trim();
        if (nama !== '') {
            cardhavenConfirm("Close Form?", "Unsaved data will be lost. Are you sure you want to cancel?", "Yes, Close", () => { setModal.style.display = 'none'; });
        } else {
            setModal.style.display = 'none';
        }
    }
    if (e.target === document.getElementById('setDetailModal')) document.getElementById('setDetailModal').style.display = 'none';
});