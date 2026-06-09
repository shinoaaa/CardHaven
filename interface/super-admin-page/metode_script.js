const metodeModal = document.getElementById('metodeModal');
const metodeForm  = document.getElementById('metodeForm');
const METODE_API  = '/CardHaven/interface/super-admin-page/controller_metode.php';

// ==========================================
// BUKA MODAL TAMBAH
// ==========================================
function openAddMetode() {
    clearAllErrors('metodeForm');
    document.getElementById('metodeModalTitle').innerHTML = 'ADD <span class="blue-text">PAYMENT METHOD</span>';
    document.getElementById('metodeDisplayID').innerText  = '';
    document.getElementById('metodeFormAction').value     = 'add';
    document.getElementById('metodeLogSection').style.display = 'none';
    metodeForm.reset();
    metodeModal.style.display = 'flex';
}

// ==========================================
// BUKA MODAL EDIT
// ==========================================
function openEditMetode(id) {
    fetch(`${METODE_API}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.error) {
                alert('Failed to fetch data: ' + (data.error || 'Unknown error'));
                return;
            }

            clearAllErrors('metodeForm');
            document.getElementById('metodeModalTitle').innerHTML = '<span class="blue-text">PAYMENT METHOD</span> DETAIL';
            document.getElementById('metodeDisplayID').innerText  = 'MTD-' + String(id).padStart(3, '0');
            document.getElementById('metodeFormAction').value     = 'edit';
            document.getElementById('metodeIdInput').value        = id;

            document.getElementById('metodeNama').value     = data.nama_metode   || '';
            document.getElementById('metodeProvider').value = data.provider      || '';
            document.getElementById('metodeNoRek').value    = data.no_rekening   || '';
            document.getElementById('metodeAtasNama').value = data.atas_nama     || '';
            document.getElementById('metodeBiaya').value    = data.biaya_admin   || 0;

            document.getElementById('metodeLogSection').style.display = 'block';
            document.getElementById('metodeCreatedBy').innerText   = data.creator  || 'System';
            document.getElementById('metodeCreatedDate').innerText = data.created_date  || '-';
            document.getElementById('metodeEditedBy').innerText    = data.modifier || '-';
            document.getElementById('metodeEditedDate').innerText  = data.modified_date || '-';

            const statusLabel = document.getElementById('metodeStatusLabel');
            statusLabel.innerText   = data.aktif == 1 ? 'Active' : 'Inactive';
            statusLabel.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
            document.getElementById('metodeAktifStatus').value = data.aktif;

            metodeModal.style.display = 'flex';
        })
        .catch(err => {
            console.error('Error openEditMetode:', err);
            alert('Failed to connect to server.');
        });
}

// ==========================================
// SUBMIT FORM (ADD / EDIT)
// ==========================================
metodeForm.onsubmit = function(e) {
    e.preventDefault();
    let isValid = true;

    const nama  = document.getElementById('metodeNama');
    const biaya = document.getElementById('metodeBiaya');

    // Validasi nama wajib diisi
    if (!nama.value.trim()) {
        showError(nama, 'Method name is required!');
        isValid = false;
    } else {
        clearError(nama);
    }

    // Validasi biaya tidak boleh negatif
    if (biaya.value !== '' && parseFloat(biaya.value) < 0) {
        showError(biaya, 'Admin fee cannot be negative!');
        isValid = false;
    } else {
        clearError(biaya);
    }

    if (!isValid) return;

    const formData = new FormData(metodeForm);
    formData.append('id_pengguna_js', getEmpId());

    fetch(METODE_API, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                alert('Data saved successfully!');
                location.reload();
            } else {
                alert('Warning: ' + res.message);
            }
        })
        .catch(() => alert('Server connection error.'));
};

// ==========================================
// DELETE (NONAKTIFKAN)
// ==========================================
function confirmDeleteMetode(id) {
    if (confirm('Deactivate this payment method?')) {
        const formData = new FormData();
        formData.append('action',        'delete');
        formData.append('id_metode',     id);
        formData.append('id_pengguna_js', getEmpId());

        fetch(METODE_API, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') location.reload();
                else alert('Failed to deactivate: ' + res.message);
            });
    }
}

// ==========================================
// RESTORE (AKTIFKAN KEMBALI)
// ==========================================
function confirmRestoreMetode(id) {
    if (confirm('Reactivate this payment method?')) {
        const formData = new FormData();
        formData.append('action',        'restore');
        formData.append('id_metode',     id);
        formData.append('id_pengguna_js', getEmpId());

        fetch(METODE_API, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') location.reload();
                else alert('Failed to restore: ' + res.message);
            });
    }
}

// ==========================================
// TUTUP MODAL KLIK DI LUAR
// ==========================================
window.addEventListener('click', function(e) {
    if (e.target === metodeModal) metodeModal.style.display = 'none';
});
