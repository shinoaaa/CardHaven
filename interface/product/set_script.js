const setModal = document.getElementById('setModal');
const setForm  = document.getElementById('setForm');
const SET_API  = '/CardHaven/interface/product/controller_set.php';
// getEmpId() didefinisikan di produk_script.js (ambil id dari PHP session via CardHavenAuth).

// Game dipilih lewat search-suggest (sama seperti Master Produk), bukan dropdown penuh.
document.addEventListener('DOMContentLoaded', function() {
    setupSuggest('setGameSearch', 'setGameId', 'setGameSuggest', 'search_game', null, SET_API);
});

function setSetGame(id, nama) {
    document.getElementById('setGameId').value     = id || '';
    document.getElementById('setGameSearch').value = nama || '';
}

function loadSetPage(page) {
    const container = document.getElementById('container-set'); 
    container.style.opacity = '0.5';

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('ps', page);

    fetch(`${window.location.pathname}?${urlParams.toString()}`)
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            container.innerHTML = doc.getElementById('container-set').innerHTML;
            container.style.opacity = '1';
            window.history.pushState({}, '', `${window.location.pathname}?${urlParams.toString()}`);
        });
}

function openAddSetModal() {
    clearAllErrors('setForm');
    document.getElementById('setModalTitle').innerHTML = 'ADD <span class="blue-text">SET</span>';
    document.getElementById('setFormAction').value     = 'add';
    setForm.reset();
    document.getElementById('setTanggal').value = '';
    setSetGame('', '');
    setModal.style.display = 'flex';
}

function openEditSetModal(id) {
    fetch(`${SET_API}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (!data || data.error) return cardhavenAlert('error', 'Error', data.error || 'Failed to fetch set data.');

            clearAllErrors('setForm');
            document.getElementById('setModalTitle').innerHTML = '<span class="blue-text">EDIT</span> SET';
            document.getElementById('setFormAction').value     = 'edit';
            document.getElementById('setIdInput').value        = id;
            document.getElementById('setNama').value           = data.nama_set  || '';
            document.getElementById('setKode').value           = data.kode_set  || '';

            if (data.tanggal_rilis) document.getElementById('setTanggal').value = data.tanggal_rilis;

            setSetGame(data.id_game, data.nama_game);
            const setStatusDisplay = document.getElementById('setStatusDisplay');
            if (setStatusDisplay) {
            setStatusDisplay.value      = data.aktif == 1 ? 'Active' : 'Inactive';
            setStatusDisplay.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
            }

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

    const gameId     = document.getElementById('setGameId');
    const gameSearch = document.getElementById('setGameSearch');
    const nama = document.getElementById('setNama');
    const kode = document.getElementById('setKode');

    if (!gameId.value || gameSearch.value.trim() === '') {
    showError(gameSearch, 'Please select a game from the list!');
    isValid = false;
} else {
    clearError(gameSearch);
}
if (!nama.value.trim()) {
    showError(nama, 'Set name is required.');
    isValid = false;
} else if (nama.value.trim().length > 50) {
    showError(nama, 'Set name must not exceed 50 characters.');
    isValid = false;
} else {
    clearError(nama);
}
if (!kode.value.trim()) {
    showError(kode, 'Set code is required.');
    isValid = false;
} else if (kode.value.trim().length > 20) {
    showError(kode, 'Set code must not exceed 20 characters.');
    isValid = false;
} else {
    clearError(kode);
}

    if (!isValid) return;

    const submitBtn = setForm.querySelector('button[type="submit"]');
    submitBtn.disabled  = true;
    submitBtn.innerText = 'Processing...';

    try {
        const formData = new FormData(setForm);

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

    cardhavenConfirm(
        `${isActive ? 'Activate' : 'Deactivate'} Set?`,
        `Are you sure you want to ${isActive ? 'activate' : 'deactivate'} this set?`,
        isActive ? 'Activate' : 'Deactivate',
        () => {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id_set', id);

            fetch(SET_API, { method: 'POST', body: fd })
                .then(async res => JSON.parse(await res.text()))
                .then(res => {
                    if (res.status === 'success') {
                        cardhavenAlert('success', 'Success', `Set status has been ${label}.`, () => location.reload());
                    } else {
                        el.checked = !isActive;
                        cardhavenAlert('error', 'Failed', res.message);
                    }
                })
                .catch(err => {
                    console.error('toggleSetStatus error:', err);
                    el.checked = !isActive;
                    cardhavenAlert('error', 'Error', 'Connection error occurred.');
                });
        },
        () => { el.checked = !isActive; }
    );
}

function confirmDeleteSet(id) {
    cardhavenConfirm('Delete Set?', 'This action cannot be undone.', 'Yes, Delete', () => {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_set', id);

        fetch(SET_API, { method: 'POST', body: fd })
            .then(async res => JSON.parse(await res.text()))
            .then(res => {
                if (res.status === 'success') {
                    cardhavenAlert('success', 'Success', 'Set has been deleted.', () => location.reload());
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

function openDetailSetModal(id) {
    fetch(`${SET_API}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (!data || data.error) return cardhavenAlert('error', 'Error', data.error || 'Failed to fetch set data.');

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
        const game    = document.getElementById('setGameSearch').value.trim();
        const nama    = document.getElementById('setNama').value.trim();
        const kode    = document.getElementById('setKode').value.trim();
        const tanggal = document.getElementById('setTanggal').value;
        
        if (game !== '' || nama !== '' || kode !== '' || tanggal !== '') {
            setModal.style.display = 'none';
            let isConfirmed = false;
            
            const actionText = document.getElementById('setFormAction').value === 'edit' ? 'Edit' : 'Add';
            cardhavenConfirm(
                `Cancel ${actionText} Set?`, 
                "Any unsaved changes will be lost.", 
                "Yes, Exit", 
                () => {
                    isConfirmed = true;
                    setForm.reset();
                    clearAllErrors('setForm');
                }
            );

            const checkSwal = setInterval(() => {
                if (!Swal.isVisible()) {
                    clearInterval(checkSwal);
                    if (!isConfirmed) setModal.style.display = 'flex';
                }
            }, 15);
        } else {
            setModal.style.display = 'none';
        }
    }
    if (e.target === document.getElementById('setDetailModal')) {
        document.getElementById('setDetailModal').style.display = 'none';
    }
});