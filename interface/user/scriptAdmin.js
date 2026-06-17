const ADMIN_URL = '/cardhaven/interface/user/controller/controllerAdmin.php';
let overlay, modalDetail, modalAdd, modalEdit, modalAdminChange;
let adminVerifyStepDone = false;

document.addEventListener('DOMContentLoaded', () => {
    overlay     = document.getElementById('adminOverlay');
    modalDetail = document.getElementById('modalAdminDetail');
    modalAdd    = document.getElementById('modalAdminAdd');
    modalEdit   = document.getElementById('modalAdminEdit');
    modalAdminChange = document.getElementById('modalAdminChangePassword'); 

    attachLiveClear('addUsername', 'err-add-username');
    attachLiveClear('addEmail',    'err-add-email');
    attachLiveClear('addPassword', 'err-add-password');
    attachLiveClear('addConfirmPassword', 'err-add-confirm-password');
    
    attachLiveClear('editUsername', 'err-edit-username');
    attachLiveClear('editEmail',    'err-edit-email');
    attachLiveClear('editPassword', 'err-edit-password');
    attachLiveClear('editConfirmPassword', 'err-edit-confirm-password');
    attachLiveClear('adminChangeCreatedDate', 'err-admin-change-date');
    attachLiveClear('adminChangeNewPassword', 'err-admin-change-pass');
    attachLiveClear('adminChangeConfirmPassword', 'err-admin-change-confirm');
});

function attachLiveClear(inputId, errId) {
    const el = document.getElementById(inputId);
    if (!el) return;
    el.addEventListener('input', () => { clearErr(inputId, errId); });
}

function showErr(inputId, errId, msg) {
    const input = document.getElementById(inputId);
    const err   = document.getElementById(errId);
    if (input) input.classList.add('input-error');
    if (err)   err.textContent = msg;
}

function clearErr(inputId, errId) {
    const input = document.getElementById(inputId);
    const err   = document.getElementById(errId);
    if (input) input.classList.remove('input-error');
    if (err)   err.textContent = '';
}

function clearAllErrors(prefix) {
    clearErr(`${prefix}Username`, `err-${prefix}-username`);
    clearErr(`${prefix}Email`,    `err-${prefix}-email`);
    clearErr(`${prefix}Password`, `err-${prefix}-password`);
    clearErr(`${prefix}ConfirmPassword`, `err-${prefix}-confirm-password`);
    if(document.getElementById(`${prefix}Foto`)) {
        clearErr(`${prefix}Foto`, `err-${prefix}-foto`);
    }
}

function showOverlay() { overlay.classList.add('active'); }
function hideOverlay() { overlay.classList.remove('active'); }
function isValidEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim()); }
function isValidPhone(phone) { return /^[0-9+\-\s]{7,20}$/.test(phone.trim()); }

let addFormSnapshot  = null;
let editFormSnapshot = null;

function snapshotForm(prefix) {
    return {
        username: document.getElementById(`${prefix}Username`).value,
        email:    document.getElementById(`${prefix}Email`).value,
    };
}

function isDirty(prefix, snapshot) {
    const current = snapshotForm(prefix);
    return JSON.stringify(current) !== JSON.stringify(snapshot);
}

function handleOverlayClick(e) {
    if (e.target !== overlay) return;
    if (modalDetail.classList.contains('active')) { closeAdminModal(); return; }
    if (modalAdd.classList.contains('active'))    { closeAddModal();   return; }
    if (modalEdit.classList.contains('active'))   { closeEditModal();  return; }
}

// ===================== DETAIL =====================
function openAdminModal(id) {
    fetch(`${ADMIN_URL}?action=getAdmin&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                cardhavenAlert('error', 'Error', res.message || 'Failed to load data.');
                return;
            }
            const d = res.data;
            document.getElementById('detailUsername').textContent = d.username || '-';
            document.getElementById('detailEmail').textContent    = d.email || '-';
            document.getElementById('detailCreated').textContent  = d.created_date || '-';
            
            const fotoEl = document.getElementById('detailFoto');
            fotoEl.src = d.foto_profil ? `/cardhaven/image-profile/${d.foto_profil}` : '/cardhaven/assets/image/user.svg';

            const statusEl = document.getElementById('detailStatus');
            if (parseInt(d.status_akun) === 1) {
                statusEl.innerHTML = '<span class="badge-active">Active</span>';
            } else {
                statusEl.innerHTML = '<span class="badge-inactive">Inactive</span>';
            }
            showOverlay();
            modalDetail.classList.add('active');
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
}

function closeAdminModal() {
    modalDetail.classList.remove('active');
    hideOverlay();
}

// ===================== ADD =====================
function openAddAdminModal() {
    document.getElementById('adminAddForm').reset();
    clearAllErrors('add');
    addFormSnapshot = snapshotForm('add');
    showOverlay();
    modalAdd.classList.add('active');
    setTimeout(() => document.getElementById('addUsername').focus(), 100);
}

function closeAddModal() {
    if (isDirty('add', addFormSnapshot)) {
        modalAdd.classList.remove('active');
        cardhavenConfirm('Discard Changes?', 'You have unsaved data. Close anyway?', 'Discard', 
            () => { hideOverlay(); },
            () => { modalAdd.classList.add('active'); }
        );
    } else {
        modalAdd.classList.remove('active');
        hideOverlay();
    }
}

function submitAddAdmin() {
    clearAllErrors('add');
    let valid = true;
    
    const username = document.getElementById('addUsername').value.trim();
    const email    = document.getElementById('addEmail').value.trim();
    const no_telp  = document.getElementById('addNoTelp').value.trim();
    const password = document.getElementById('addPassword').value;
    const confirmPassword = document.getElementById('addConfirmPassword').value;
    const foto     = document.getElementById('addFoto').files[0];

    if (!username) { showErr('addUsername', 'err-add-username', 'Username is required.'); valid = false; }
    if (!email) { showErr('addEmail', 'err-add-email', 'Email is required.'); valid = false; }
    if (!no_telp) { showErr('addNoTelp', 'err-add-notelp', 'Phone Number is required.'); valid = false; }
    else if (!isValidEmail(email)) { showErr('addEmail', 'err-add-email', 'Invalid email format.'); valid = false; }
    
    if (!no_telp) { 
        showErr('addNoTelp', 'err-add-notelp', 'Phone Number is required.'); 
        valid = false; 
    } else if (!isValidPhone(no_telp)) { 
        showErr('addNoTelp', 'err-add-notelp', 'Invalid phone number format.'); 
        valid = false; 
    }

    // Validasi Password (Sesuai script_register.js)
    if (!password) { 
        showErr('addPassword', 'err-add-password', 'Password must be filled'); 
        valid = false; 
    } else if (password.length < 8 || password.length > 12) {
        showErr('addPassword', 'err-add-password', 'Password must be 8 - 12 characters long');
        valid = false;
    } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        showErr('addPassword', 'err-add-password', 'Password must contain a special character');
        valid = false;
    }

    // Validasi Konfirmasi Password (Sesuai script_register.js)
    if (!confirmPassword) {
        showErr('addConfirmPassword', 'err-add-confirm-password', 'Please confirm your password');
        valid = false;
    } else if (password !== confirmPassword) {
        showErr('addConfirmPassword', 'err-add-confirm-password', 'Confirm password does not match!');
        valid = false;
    }


    if (!valid) return;

    const body = new FormData();
    body.append('action', 'addAdmin');
    body.append('username', username);
    body.append('email', email);
    body.append('no_telepon', no_telp);
    body.append('password', password);
    if (foto) body.append('foto_profil', foto);

    fetch(ADMIN_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                // Tampilkan error duplicate email di tag span
                if (res.code === 'EMAIL_DUPLICATE') {
                    showErr('addEmail', 'err-add-email', res.message);
                } else {
                    cardhavenAlert('error', 'Failed', res.message);
                }
                return;
            }
            modalAdd.classList.remove('active');
            hideOverlay();
            cardhavenAlert('success', 'Success!', 'Admin added successfully.', () => { location.reload(); });
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
}

// ===================== EDIT =====================
function openAdminEdit(id) {
    fetch(`${ADMIN_URL}?action=getAdmin&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                cardhavenAlert('error', 'Error', res.message);
                return;
            }
            const d = res.data;
            document.getElementById('editAdminId').value       = d.id_pengguna;
            document.getElementById('editUsername').value     = d.username || '';
            document.getElementById('editEmail').value        = d.email || '';
            document.getElementById('editNoTelp').value        = d.no_telepon || '';
            
            document.getElementById('adminChangeEmail').value = d.email || '';
            
            const preview = document.getElementById('editFotoPreview');
            preview.src = d.foto_profil ? `/cardhaven/image-profile/${d.foto_profil}` : '/cardhaven/assets/image/user.svg';

            clearAllErrors('edit');
            editFormSnapshot = snapshotForm('edit');

            showOverlay();
            modalEdit.classList.add('active');
            setTimeout(() => document.getElementById('editUsername').focus(), 100);
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
}

function closeEditModal() {
    if (isDirty('edit', editFormSnapshot)) {
        modalEdit.classList.remove('active');
        cardhavenConfirm('Discard Changes?', 'You have unsaved changes. Close anyway?', 'Discard',
            () => { hideOverlay(); },
            () => { modalEdit.classList.add('active'); }
        );
    } else {
        modalEdit.classList.remove('active');
        hideOverlay();
    }
}

function submitEditAdmin() {
    clearAllErrors('edit');
    let valid = true;

    const id       = document.getElementById('editAdminId').value;
    const username = document.getElementById('editUsername').value.trim();
    const email    = document.getElementById('editEmail').value.trim();
    const no_telp  = document.getElementById('editNoTelp').value.trim();
    const foto     = document.getElementById('editFoto').files[0];

    if (!username) { showErr('editUsername', 'err-edit-username', 'Username is required.'); valid = false; }
    if (!email) { showErr('editEmail', 'err-edit-email', 'Email is required.'); valid = false; }
    else if (!isValidEmail(email)) { showErr('editEmail', 'err-edit-email', 'Invalid email format.'); valid = false; }

    if (!no_telp) { 
        showErr('editNoTelp', 'err-edit-notelp', 'Phone Number is required.'); 
        valid = false; 
    } else if (!isValidPhone(no_telp)) { 
        showErr('editNoTelp', 'err-edit-notelp', 'Invalid phone number format.'); 
        valid = false; 
    }

    if (!valid) return;

    const body = new FormData();
    body.append('action', 'updateAdmin');
    body.append('id_pengguna', id);
    body.append('username', username);
    body.append('email', email);
    body.append('no_telepon', no_telp);
    if (foto) body.append('foto_profil', foto);

    fetch(ADMIN_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                // Tampilkan error duplicate email di tag span
                if (res.code === 'EMAIL_DUPLICATE') {
                    showErr('editEmail', 'err-edit-email', res.message);
                } else {
                    cardhavenAlert('error', 'Failed', res.message);
                }
                return;
            }
            modalEdit.classList.remove('active');
            hideOverlay();
            cardhavenAlert('success', 'Updated!', 'Admin updated successfully.', () => { location.reload(); });
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
}

// ===================== DELETE =====================
function deleteAdmin(id) {
    cardhavenConfirm('Delete Admin?', 'This action cannot be undone.', 'Delete', () => {
        const body = new FormData();
        body.append('action', 'deleteAdmin');
        body.append('id_pengguna', id);

        fetch(ADMIN_URL, { method: 'POST', body })
            .then(r => r.json())
            .then(res => {
                if (!res.success) { cardhavenAlert('error', 'Failed', res.message); return; }
                cardhavenAlert('success', 'Deleted!', 'Admin has been removed.', () => { location.reload(); });
            })
            .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
    });
}

// ===================== TOGGLE STATUS =====================
function toggleAdmin(id, isChecked, checkboxEl) {
    const newStatus = isChecked ? 1 : 0;
    const label     = isChecked ? 'activate' : 'deactivate';

    cardhavenConfirm(
        `${isChecked ? 'Activate' : 'Deactivate'} Account?`, 
        `Are you sure you want to ${label} this account?`, 
        isChecked ? 'Activate' : 'Deactivate', 
        () => {
            const body = new FormData();
            body.append('action', 'toggleAdmin');
            body.append('id_pengguna', id);
            body.append('status_akun', newStatus);

            fetch(ADMIN_URL, { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({ 
                            icon: 'success', 
                            iconColor: '#0088FF', 
                            title: 'Success!', 
                            text: 'Account status has been changed.', 
                            showConfirmButton: false, 
                            timer: 1500, 
                            customClass: { title: 'coolveticaa' } 
                        }).then(() => {
                            location.reload(); // Reload agar badge status terupdate otomatis
                        });
                    } else {
                        // Revert checkbox jika gagal di server
                        checkboxEl.checked = !isChecked;
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: res.message || 'Failed to update status.',
                            confirmButtonText: 'OK',
                            customClass: { title: 'coolveticaa' }
                        });
                    }
                })
                .catch(err => {
                    // Revert checkbox jika error koneksi
                    checkboxEl.checked = !isChecked;
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Connection error occurred.',
                        confirmButtonText: 'OK',
                        customClass: { title: 'coolveticaa' }
                    });
                });
        },
        () => { 
            // Revert checkbox jika user membatalkan konfirmasi (klik Batal)
            checkboxEl.checked = !isChecked; 
        }
    );
}

function openAdminChangePasswordModal() {
    modalEdit.classList.remove('active'); // Tutup modal edit
    
    adminVerifyStepDone = false;
    // 2. RESET FORM (Gunakan ID Form yang benar)
    const form = document.getElementById('adminChangePasswordForm');
    if(form) form.reset();

    document.getElementById('admin-verify-section').style.display = 'block';
    document.getElementById('admin-reset-section').style.display = 'none';
    document.getElementById('btn-admin-submit-change').textContent = 'Verify';
    
    modalAdminChange.classList.add('active'); // Buka modal khusus admin
}

function closeAdminChangePasswordModal() {
    modalAdminChange.classList.remove('active');
    modalEdit.classList.add('active'); // Kembali ke modal edit
}

function handleAdminPasswordStep() {
    if (!adminVerifyStepDone) {
        verifyAdminIdentity();
    } else {
        resetAdminPassword();
    }
}
async function verifyAdminIdentity() {
    const email = document.getElementById('adminChangeEmail').value;
    const date  = document.getElementById('adminChangeCreatedDate').value;
    const actorId = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
    if (!date) {
        showErr('adminChangeCreatedDate', 'err-admin-change-date', 'Date is required');
        return;
    }
    

    const body = new FormData();
    body.append('action', 'verifyAdmin');
    body.append('email', email);
    body.append('created_date', date);
    body.append('actor_id', actorId);

    try {
        const response = await fetch(ADMIN_URL, { method: 'POST', body });
        const data = await response.json();

        if (data.status === 'success') {
            adminVerifyStepDone = true;
            document.getElementById('admin-verify-section').style.display = 'none';
            document.getElementById('admin-reset-section').style.display = 'block';
            document.getElementById('btn-admin-submit-change').textContent = 'Update Password';
        } else {
            showErr('adminChangeCreatedDate', 'err-admin-change-date', data.message);
        }
    } catch (e) {
        cardhavenAlert('error', 'Error', 'Network error');
    }
}

async function resetAdminPassword() {
    const password = document.getElementById('adminChangeNewPassword').value;
    const confirm  = document.getElementById('adminChangeConfirmPassword').value;

    let valid = true;
    if (password.length < 8 || password.length > 12 || !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        showErr('adminChangeNewPassword', 'err-admin-change-pass', 'Password must be 8-12 chars + symbol');
        valid = false;
    }
    if (password !== confirm) {
        showErr('adminChangeConfirmPassword', 'err-admin-change-confirm', 'Confirm password does not match!');
        valid = false;
    }

    if (!valid) return;
    const actorId = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
    const body = new FormData();
    body.append('action', 'resetAdminPassword');
    body.append('password', password);
    body.append('confirm_password', confirm);
    body.append('actor_id', actorId);

    try {
        const response = await fetch(ADMIN_URL, { method: 'POST', body });
        const data = await response.json();

        if (data.status === 'success') {
            wal.fire({
                icon: 'success',
                iconColor: '#0088FF',
                title: 'Success!',
                text: 'The password has been successfully changed.',
                confirmButtonColor: '#0088FF',
                customClass: { title: 'coolveticaa' }
            }).then(() => {
                location.reload(); // Refresh halaman setelah sukses
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: data.message,
                confirmButtonColor: '#E74C3C',
                customClass: { title: 'coolveticaa' }
            });

        }
    } catch (e) {
        cardhavenAlert('error', 'Error', 'Network error');
    }
}

// 3. UPDATE FUNGSI OVERLAY CLICK
function handleOverlayClick(e) {
    if (e.target !== overlay) return;
    if (modalDetail.classList.contains('active')) closeAdminModal();
    if (modalAdd.classList.contains('active')) closeAddModal();
    if (modalEdit.classList.contains('active')) closeEditModal();
    
    // TAMBAHKAN INI:
    if (modalAdminChange && modalAdminChange.classList.contains('active')) {
        closeAdminChangePasswordModal();
    }
}