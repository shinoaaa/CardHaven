const metodeModal = document.getElementById('metodeModal');
const metodeForm  = document.getElementById('metodeForm');
const METODE_API  = '/CardHaven/interface/product/controller_metode.php';

// ── Helper mandiri: metode master kini berdiri sendiri di halaman Transaction
//    (tanpa produk_script.js). Definisikan hanya bila belum ada agar tidak bentrok. ──
window.getEmpId = window.getEmpId || (() => CardHavenAuth.id() || null);
window.showError = window.showError || function (el, msg) {
    el.style.border = '2px solid #E74C3C';
    const err = el.closest('.modal-form-group').querySelector('.error-message');
    if (err) { err.innerText = msg; err.style.display = 'block'; err.style.color = '#E74C3C'; }
};
window.clearError = window.clearError || function (el) {
    el.style.border = '1.5px solid #888';
    const err = el.closest('.modal-form-group').querySelector('.error-message');
    if (err) err.innerText = '';
};
window.clearAllErrors = window.clearAllErrors || function (formId) {
    document.getElementById(formId).querySelectorAll('.modal-input').forEach(input => window.clearError(input));
};
document.addEventListener('DOMContentLoaded', function () {
    if (metodeForm) metodeForm.querySelectorAll('.modal-input').forEach(input => {
        input.addEventListener('input', function () { window.clearError(this); });
    });
});

function openAddMetode() {
    clearAllErrors('metodeForm');
    document.getElementById('metodeModalTitle').innerHTML = 'ADD <span class="blue-text">PAYMENT METHOD</span>';
    document.getElementById('metodeDisplayID').innerText  = '';
    document.getElementById('metodeFormAction').value     = 'add';
    metodeForm.reset();
    metodeModal.style.display = 'flex';
}
function loadMetodePage(page) {
    const container = document.getElementById('container-metode');
    container.style.opacity = '0.5';

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('pm', page);

    fetch(`${window.location.pathname}?${urlParams.toString()}`)
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            container.innerHTML = doc.getElementById('container-metode').innerHTML;
            container.style.opacity = '1';
            window.history.pushState({}, '', `${window.location.pathname}?${urlParams.toString()}`);
        });
}

function openEditMetode(id) {
    fetch(`${METODE_API}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (!data || data.error) return cardhavenAlert('error', 'Error', data.error || 'Failed to fetch data.');

            clearAllErrors('metodeForm');
            document.getElementById('metodeModalTitle').innerHTML = '<span class="blue-text">EDIT</span> PAYMENT METHOD';
            document.getElementById('metodeDisplayID').innerText  = 'MTD-' + String(id).padStart(3, '0');
            document.getElementById('metodeFormAction').value     = 'edit';
            document.getElementById('metodeIdInput').value        = id;

            document.getElementById('metodeNama').value     = data.nama_metode  || '';
            document.getElementById('metodeProvider').value = data.provider     || '';
            document.getElementById('metodeNoRek').value    = data.no_rekening  || '';
            document.getElementById('metodeAtasNama').value = data.atas_nama    || '';
            document.getElementById('metodeBiaya').value    = data.biaya_admin  || 0;
            if (document.getElementById('metodeAktifStatus')) document.getElementById('metodeAktifStatus').value = data.aktif;
            const statusDisplay = document.getElementById('metodeStatusDisplay');
            if (statusDisplay) {
            statusDisplay.value      = data.aktif == 1 ? 'Active' : 'Inactive';
            statusDisplay.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
}

            metodeModal.style.display = 'flex';
        })
        .catch(err => {
            console.error('openEditMetode error:', err);
            cardhavenAlert('error', 'System Error', 'Failed to connect to server.');
        });
}

metodeForm.onsubmit = async function(e) {
    e.preventDefault();
    let isValid = true;

    const nama      = document.getElementById('metodeNama');
    const provider  = document.getElementById('metodeProvider');
    const noRek     = document.getElementById('metodeNoRek');
    const atasNama  = document.getElementById('metodeAtasNama');
    const biaya     = document.getElementById('metodeBiaya');

if (!nama.value.trim()) {
    showError(nama, 'Method name is required.');
    isValid = false;
} else if (!/^[A-Za-z ]+$/.test(nama.value.trim())) {
    showError(nama, 'Method name must contain letters only (no numbers or symbols).');
    isValid = false;
} else {
    clearError(nama);
}
if (!provider.value.trim()) {
    showError(provider, 'Provider is required.');
    isValid = false;
} else if (!/^[A-Za-z0-9 .]+$/.test(provider.value.trim()) || !/[A-Za-z]/.test(provider.value.trim())) {
    showError(provider, 'Provider must contain letters (not only numbers or symbols).');
    isValid = false;
} else {
    clearError(provider);
}
if (!noRek.value.trim()) {
    showError(noRek, 'Account number is required.');
    isValid = false;
} else if (!/^\d+$/.test(noRek.value.trim())) {
    showError(noRek, 'Account number must contain numbers only.');
    isValid = false;
} else if (noRek.value.trim().length < 5) {
    showError(noRek, 'Account number must be at least 5 digits.');
    isValid = false;
} else if (noRek.value.trim().length > 20) {
    showError(noRek, 'Account number must not exceed 20 digits.');
    isValid = false;
} else {
    clearError(noRek);
}
if (!atasNama.value.trim()) {
    showError(atasNama, 'Account name is required.');
    isValid = false;
} else if (!/^[A-Za-z ]+$/.test(atasNama.value.trim())) {
    showError(atasNama, 'Account name must contain letters only (no numbers or symbols).');
    isValid = false;
} else {
    clearError(atasNama);
}
if (biaya.value !== '' && parseFloat(biaya.value) < 0) {
    showError(biaya, 'Admin fee cannot be negative.');
    isValid = false;
} else {
    clearError(biaya);
}

    if (!isValid) return;

    const submitBtn = metodeForm.querySelector('button[type="submit"]');
    submitBtn.disabled  = true;
    submitBtn.innerText = 'Processing...';

    try {
        const formData = new FormData(metodeForm);

        const res    = await fetch(METODE_API, { method: 'POST', body: formData });
        const result = JSON.parse(await res.text());

        if (result.status === 'success') {
            cardhavenAlert('success', 'Success', 'Payment method saved successfully.', () => location.reload());
        } else {
            cardhavenAlert('error', 'Failed', result.message);
            submitBtn.disabled  = false;
            submitBtn.innerText = 'Save Method';
        }
    } catch (err) {
        console.error(err);
        cardhavenAlert('error', 'System Error', 'Connection error. Please try again.');
        submitBtn.disabled  = false;
        submitBtn.innerText = 'Save Method';
    }
};

function toggleMetode(id, isActive, el) {
    const action = isActive ? 'activate' : 'deactivate';
    const label  = isActive ? 'activated' : 'deactivated';

    cardhavenConfirm(
        `${isActive ? 'Activate' : 'Deactivate'} Payment Method?`,
        `Are you sure you want to ${isActive ? 'activate' : 'deactivate'} this payment method?`,
        isActive ? 'Activate' : 'Deactivate',
        () => {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id_metode', id);

            fetch(METODE_API, { method: 'POST', body: fd })
                .then(async res => JSON.parse(await res.text()))
                .then(res => {
                    if (res.status === 'success') {
                        cardhavenAlert('success', 'Success', `Payment method has been ${label}.`, () => location.reload());
                    } else {
                        el.checked = !isActive;
                        cardhavenAlert('error', 'Failed', res.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    el.checked = !isActive;
                    cardhavenAlert('error', 'Error', 'Connection error occurred.');
                });
        },
        () => { el.checked = !isActive; }
    );
}

function confirmDeleteMetode(id) {
    cardhavenConfirm('Delete Payment Method?', 'This payment method will be permanently deleted. Are you sure?', 'Yes, Delete', () => {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_metode', id);

        fetch(METODE_API, { method: 'POST', body: fd })
            .then(async res => JSON.parse(await res.text()))
            .then(res => {
                if (res.status === 'success') {
                    cardhavenAlert('success', 'Deleted!', 'Payment method has been deleted.', () => location.reload());
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

function openDetailMetode(id) {
    fetch(`${METODE_API}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (!data || data.error) return cardhavenAlert('error', 'Error', data.error || 'Failed to fetch data.');

            document.getElementById('metodeDetailDisplayID').innerText = 'MTD-' + String(id).padStart(3, '0');
            document.getElementById('detailMetodeNama').innerText     = data.nama_metode  || '-';
            document.getElementById('detailMetodeProvider').innerText = data.provider     || '-';
            document.getElementById('detailMetodeNoRek').innerText    = data.no_rekening  || '-';
            document.getElementById('detailMetodeAtasNama').innerText = data.atas_nama    || '-';
            document.getElementById('detailMetodeBiaya').innerText    = 'Rp. ' + parseFloat(data.biaya_admin || 0).toLocaleString('id-ID');

            const statusEl = document.getElementById('detailMetodeStatus');
            if (data.aktif == 1) {
                statusEl.innerText   = 'Active';
                statusEl.style.color = '#27AE60';
                statusEl.style.fontWeight = '700';
            } else {
                statusEl.innerText   = 'Inactive';
                statusEl.style.color = '#E74C3C';
                statusEl.style.fontWeight = '700';
            }

            document.getElementById('metodeDetailModal').style.display = 'flex';
        })
        .catch(err => {
            console.error('openDetailMetode error:', err);
            cardhavenAlert('error', 'System Error', 'Failed to connect to server.');
        });
}

window.addEventListener('click', function(e) {
    if (e.target === metodeModal) {
        const nama     = document.getElementById('metodeNama').value.trim();
        const provider = document.getElementById('metodeProvider').value.trim();
        const noRek    = document.getElementById('metodeNoRek').value.trim();
        const atasNama = document.getElementById('metodeAtasNama').value.trim();
        const biaya    = document.getElementById('metodeBiaya').value;
        
        if (nama !== '' || provider !== '' || noRek !== '' || atasNama !== '' || (biaya !== '' && biaya !== '0')) {
            metodeModal.style.display = 'none';
            let isConfirmed = false;
            
            const actionText = document.getElementById('metodeFormAction').value === 'edit' ? 'Edit' : 'Add';
            cardhavenConfirm(
                `Cancel ${actionText} Method?`, 
                "Any unsaved changes will be lost.", 
                "Yes, Exit", 
                () => {
                    isConfirmed = true;
                    metodeForm.reset();
                    clearAllErrors('metodeForm');
                }
            );

            const checkSwal = setInterval(() => {
                if (!Swal.isVisible()) {
                    clearInterval(checkSwal);
                    if (!isConfirmed) metodeModal.style.display = 'flex';
                }
            }, 15);
        } else {
            metodeModal.style.display = 'none';
        }
    }
    if (e.target === document.getElementById('metodeDetailModal')) {
        document.getElementById('metodeDetailModal').style.display = 'none';
    }
});