const metodeModal = document.getElementById('metodeModal');
const metodeForm  = document.getElementById('metodeForm');
const METODE_API  = '/CardHaven/interface/super-admin-page/controller_metode.php';
var getEmpId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

// ==========================================
// BUKA MODAL TAMBAH
// ==========================================
function openAddMetode() {
    clearAllErrors('metodeForm');
    document.getElementById('metodeModalTitle').innerHTML = 'ADD <span class="blue-text">PAYMENT METHOD</span>';
    document.getElementById('metodeDisplayID').innerText  = '';
    document.getElementById('metodeFormAction').value     = 'add';
    metodeForm.reset();
    metodeModal.style.display = 'flex';
}

// ==========================================
// BUKA MODAL EDIT
// ==========================================
function openEditMetode(id) {
    fetch(`${METODE_API}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (!data || data.error) {
                cardhavenAlert('error', 'Error', data.error || 'Failed to fetch data.');
                return;
            }

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

            // hidden aktif untuk dikirim saat edit
            document.getElementById('metodeAktifStatus').value = data.aktif;

            metodeModal.style.display = 'flex';
        })
        .catch(err => {
            console.error('openEditMetode error:', err);
            cardhavenAlert('error', 'System Error', 'Failed to connect to server.');
        });
}

// ==========================================
// SUBMIT FORM (ADD / EDIT)
// ==========================================
metodeForm.onsubmit = async function(e) {
    e.preventDefault();
    let isValid = true;

    const nama      = document.getElementById('metodeNama');
    const provider  = document.getElementById('metodeProvider');
    const noRek     = document.getElementById('metodeNoRek');
    const atasNama  = document.getElementById('metodeAtasNama');
    const biaya     = document.getElementById('metodeBiaya');

    if (!nama.value.trim())     { showError(nama,     'Method name is required!');    isValid = false; } else clearError(nama);
    if (!provider.value.trim()) { showError(provider, 'Provider is required!');       isValid = false; } else clearError(provider);
    if (!noRek.value.trim())    { showError(noRek,    'Account number is required!'); isValid = false; } else clearError(noRek);
    if (!atasNama.value.trim()) { showError(atasNama, 'Account name is required!');   isValid = false; } else clearError(atasNama);
    if (biaya.value === '' || biaya.value === null) {
        showError(biaya, 'Admin fee is required!');
        isValid = false;
    } else if (parseFloat(biaya.value) < 0) {
        showError(biaya, 'Admin fee cannot be negative!');
        isValid = false;
    } else {
        clearError(biaya);
    }

    if (!isValid) return;

    const submitBtn = metodeForm.querySelector('button[type="submit"]');
    submitBtn.disabled  = true;
    submitBtn.innerText = 'Saving...';

    try {
        const formData = new FormData(metodeForm);
        formData.append('id_pengguna_js', getEmpId());

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

// ==========================================
// TOGGLE AKTIF / NONAKTIF
// ==========================================
function toggleMetode(id, isActive, el) {
    const action = isActive ? 'aktifkan' : 'nonaktifkan';
    const label  = isActive ? 'activated' : 'deactivated';

    const fd = new FormData();
    fd.append('action',        action);
    fd.append('id_metode',     id);
    fd.append('id_pengguna_js', getEmpId());

    fetch(METODE_API, { method: 'POST', body: fd })
        .then(async res => JSON.parse(await res.text()))
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    iconColor: '#0088FF',
                    title: 'Success!',
                    text: `Payment method has been ${label}.`,
                    showConfirmButton: false,
                    timer: 1500,
                    background: '#ffffff',
                    customClass: { title: 'coolveticaa' }
                }).then(() => location.reload());
            } else {
                el.checked = !isActive;
                cardhavenAlert('error', 'Failed', res.message);
            }
        })
        .catch(err => {
            console.error(err);
            el.checked = !isActive;
            cardhavenAlert('error', 'System Error', 'Connection error.');
        });
}

// ==========================================
// DELETE (HARD DELETE / is_deleted)
// ==========================================
function confirmDeleteMetode(id) {
    cardhavenConfirm('Delete Payment Method?', 'This payment method will be permanently deleted. Are you sure?', 'Yes, Delete', () => {
        const fd = new FormData();
        fd.append('action',        'delete');
        fd.append('id_metode',     id);
        fd.append('id_pengguna_js', getEmpId());

        fetch(METODE_API, { method: 'POST', body: fd })
            .then(async res => JSON.parse(await res.text()))
            .then(res => {
                if (res.status === 'success') location.reload();
                else cardhavenAlert('error', 'Failed', res.message);
            });
    });
}

// ==========================================
// DETAIL (READ ONLY) — hanya tombol dulu
// ==========================================
function openDetailMetode(id) {
    fetch(`${METODE_API}?get_detail=${id}`)
        .then(async res => JSON.parse(await res.text()))
        .then(data => {
            if (!data || data.error) {
                cardhavenAlert('error', 'Error', data.error || 'Failed to fetch data.');
                return;
            }

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

// ==========================================
// TUTUP MODAL KLIK DI LUAR
// ==========================================
window.addEventListener('click', function(e) {
    // Validasi Form Add/Edit Metode Pembayaran
    if (e.target === metodeModal) {
        const nama = document.getElementById('metodeNama').value.trim();
        const provider = document.getElementById('metodeProvider').value.trim();
        const norek = document.getElementById('metodeNoRek').value.trim();

        if (nama !== '' || provider !== '' || norek !== '') {
            cardhavenConfirm(
                "Close Form?", 
                "Unsaved data will be lost. Are you sure you want to cancel?", 
                "Yes, Close", 
                () => { metodeModal.style.display = 'none'; }
            );
        } else {
            metodeModal.style.display = 'none';
        }
    }

    // Tutup Modal Detail Metode
    if (e.target === document.getElementById('metodeDetailModal')) {
        document.getElementById('metodeDetailModal').style.display = 'none';
    }
});