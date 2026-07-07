const CUST_URL = '/cardhaven/interface/user/controller/controllerCustomer.php';
// id_pengguna pelaku untuk audit (created_by/modified_by/deleted_by).
function getActorId() { return localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna') || ''; }
let overlay, modalDetail, modalAdd, modalEdit, modalCustChange;
let custVerifyStepDone = false;

document.addEventListener('DOMContentLoaded', () => {
    overlay     = document.getElementById('customerOverlay');
    modalDetail = document.getElementById('modalCustomerDetail');
    modalAdd    = document.getElementById('modalCustomerAdd');
    modalEdit   = document.getElementById('modalCustomerEdit');
    modalCustChange = document.getElementById('modalCustChangePassword');

    attachLiveClear('addUsername', 'err-add-username');
    attachLiveClear('addEmail',    'err-add-email');
    attachLiveClear('addNoTelp',   'err-add-notelp');
    attachLiveClear('addPassword', 'err-add-password');
    attachLiveClear('addConfirmPassword', 'err-add-confirm-password');
    
    attachLiveClear('editUsername', 'err-edit-username');
    attachLiveClear('editEmail',    'err-edit-email');
    attachLiveClear('editNoTelp',   'err-edit-notelp');
    attachLiveClear('editPassword', 'err-edit-password');
    attachLiveClear('editConfirmPassword', 'err-edit-confirm-password');
    attachLiveClear('custChangeCreatedDate', 'err-cust-change-date');
    attachLiveClear('custChangeNewPassword', 'err-cust-change-pass');
    attachLiveClear('custChangeConfirmPassword', 'err-cust-change-confirm');
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
    clearErr(`${prefix}NoTelp`,   `err-${prefix}-notelp`);
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
        notelp:   document.getElementById(`${prefix}NoTelp`).value,
    };
}

function isDirty(prefix, snapshot) {
    const current = snapshotForm(prefix);
    return JSON.stringify(current) !== JSON.stringify(snapshot);
}

function handleOverlayClick(e) {
    if (e.target !== overlay) return;
    if (modalDetail.classList.contains('active')) { closeCustomerModal(); return; }
    if (modalAdd.classList.contains('active'))    { closeAddModal();   return; }
    if (modalEdit.classList.contains('active'))   { closeEditModal();  return; }
}

// ===================== DETAIL =====================
function openCustomerModal(id) {
    fetch(`${CUST_URL}?action=getCustomer&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                cardhavenAlert('error', 'Error', res.message || 'Failed to load data.');
                return;
            }
            const d = res.data;
            document.getElementById('detailUsername').textContent = d.username || '-';
            document.getElementById('detailEmail').textContent    = d.email || '-';
            document.getElementById('detailNoTelp').textContent   = d.no_telepon || '-';
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

function closeCustomerModal() {
    modalDetail.classList.remove('active');
    hideOverlay();
}

// ===================== ADD =====================
function openAddCustomerModal() {
    document.getElementById('customerAddForm').reset();
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

function submitAddCustomer() {
    clearAllErrors('add');
    let valid = true;
    
    const username = document.getElementById('addUsername').value.trim();
    const email    = document.getElementById('addEmail').value.trim();
    const no_telp  = document.getElementById('addNoTelp').value.replace(/\s+/g, '');
    const password = document.getElementById('addPassword').value;
    const confirmPassword = document.getElementById('addConfirmPassword').value;
    const foto     = document.getElementById('addFoto').files[0];

    if (!username) { showErr('addUsername', 'err-add-username', 'Name is required.'); valid = false; }
    if (!email) {
        showErr('addEmail', 'err-add-email', 'Email is required.'); valid = false;
    } else if (!isValidEmail(email)) {
        showErr('addEmail', 'err-add-email', 'Invalid email format.'); valid = false;
    }
    if (!no_telp) {
        showErr('addNoTelp', 'err-add-notelp', 'Phone Number is required.'); valid = false;
    } else if (!isValidPhone(no_telp)) {
        showErr('addNoTelp', 'err-add-notelp', 'Invalid phone number format.'); valid = false;
    }

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

    // 2. Validasi Konfirmasi Password
    if (!confirmPassword) {
        showErr('addConfirmPassword', 'err-add-confirm-password', 'Please confirm your password');
        valid = false;
    } else if (password !== confirmPassword) {
        showErr('addConfirmPassword', 'err-add-confirm-password', 'Confirm password does not match!');
        valid = false;
    }


    if (!valid) return;

    const body = new FormData();
    body.append('action', 'addCustomer');
    body.append('actor_id', getActorId());
    body.append('username', username);
    body.append('email', email);
    body.append('no_telepon', no_telp);
    body.append('password', password);
    if (foto) body.append('foto_profil', foto);

    fetch(CUST_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                if (res.code === 'EMAIL_DUPLICATE') {
                    showErr('addEmail', 'err-add-email', res.message);
                } else {
                    cardhavenAlert('error', 'Failed', res.message);
                }
                return;
            }
            modalAdd.classList.remove('active');
            hideOverlay();
            cardhavenAlert('success', 'Success!', 'Customer added successfully.', () => { location.reload(); });
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
}

// ===================== EDIT =====================
function openCustomerEdit(id) {
    fetch(`${CUST_URL}?action=getCustomer&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                cardhavenAlert('error', 'Error', res.message);
                return;
            }
            const d = res.data;
            document.getElementById('editCustomerId').value   = d.id_pengguna;
            document.getElementById('editUsername').value     = d.username || '';
            document.getElementById('editEmail').value        = d.email || '';
            document.getElementById('editNoTelp').value       = d.no_telepon || '';
            document.getElementById('custChangeEmail').value  = d.email || '';
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

function submitEditCustomer() {
    clearAllErrors('edit');
    let valid = true;

    const id       = document.getElementById('editCustomerId').value;
    const username = document.getElementById('editUsername').value.trim();
    const email    = document.getElementById('editEmail').value.trim();
    const no_telp  = document.getElementById('editNoTelp').value.replace(/\s+/g, '');
    const password = document.getElementById('editPassword').value;
    const confirmPassword = document.getElementById('editConfirmPassword').value;
    const foto     = document.getElementById('editFoto').files[0];

    if (!username) { showErr('editUsername', 'err-edit-username', 'Name is required.'); valid = false; }
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
    body.append('action', 'updateCustomer');
    body.append('actor_id', getActorId());
    body.append('id_pengguna', id);
    body.append('username', username);
    body.append('email', email);
    body.append('no_telepon', no_telp);
    if (password) body.append('password', password);
    if (foto) body.append('foto_profil', foto);

    fetch(CUST_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                if (res.code === 'EMAIL_DUPLICATE') {
                    showErr('editEmail', 'err-edit-email', res.message);
                } else {
                    cardhavenAlert('error', 'Failed', res.message);
                }
                return;
            }
            modalEdit.classList.remove('active');
            hideOverlay();
            cardhavenAlert('success', 'Updated!', 'Customer updated successfully.', () => { location.reload(); });
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
}

// ===================== DELETE & TOGGLE (Identik dengan Admin) =====================
function deleteCustomer(id) {
    cardhavenConfirm('Delete Customer?', 'This action cannot be undone.', 'Delete', () => {
        const body = new FormData();
        body.append('action', 'deleteCustomer');
        body.append('actor_id', getActorId());
        body.append('id_pengguna', id);

        fetch(CUST_URL, { method: 'POST', body })
            .then(r => r.json())
            .then(res => {
                if (!res.success) { cardhavenAlert('error', 'Failed', res.message); return; }
                cardhavenAlert('success', 'Deleted!', 'Customer has been removed.', () => { location.reload(); });
            })
            .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
    });
}

function toggleCustomer(id, isChecked, checkboxEl) {
    const newStatus = isChecked ? 1 : 0;
    const label     = isChecked ? 'activate' : 'deactivate';

    cardhavenConfirm(
        `${isChecked ? 'Activate' : 'Deactivate'} Account?`, 
        `Are you sure you want to ${label} this account?`, 
        isChecked ? 'Activate' : 'Deactivate', 
        () => {
            const body = new FormData();
            body.append('action', 'toggleCustomer');
            body.append('actor_id', getActorId());
            body.append('id_pengguna', id);
            body.append('status_akun', newStatus);

            fetch(CUST_URL, { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({ 
                            icon: 'success', 
                            iconColor: '#0088FF', 
                            title: 'Success!', 
                            text: 'Customer status has been changed.', 
                            showConfirmButton: false, 
                            timer: 1500, 
                            customClass: { title: 'coolveticaa' } 
                        }).then(() => {
                            location.reload(); // Reload agar badge status di tabel diperbarui
                        });
                    } else {
                        // Revert checkbox jika gagal di server
                        checkboxEl.checked = !isChecked;
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: res.message || 'Failed to update customer status.',
                            confirmButtonText: 'OK',
                            customClass: { title: 'coolveticaa' }
                        });
                    }
                })
                .catch(err => {
                    // Revert checkbox jika terjadi error jaringan
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
            // Revert checkbox jika user membatalkan konfirmasi
            checkboxEl.checked = !isChecked; 
        }
    );
}

function openCustChangePasswordModal() {
    modalEdit.classList.remove('active');
    custVerifyStepDone = false;
    const form = document.getElementById('custChangePasswordForm');
    if(form) form.reset();

    document.getElementById('cust-verify-section').style.display = 'block';
    document.getElementById('cust-reset-section').style.display = 'none';
    document.getElementById('btn-cust-submit-change').textContent = 'Verify';
    
    modalCustChange.classList.add('active');
}

function closeCustChangePasswordModal() {
    modalCustChange.classList.remove('active');
    modalEdit.classList.add('active');
}

function handleCustPasswordStep() {
    if (!custVerifyStepDone) {
        verifyCustIdentity();
    } else {
        resetCustPassword();
    }
}

async function verifyCustIdentity() {
    const email = document.getElementById('custChangeEmail').value;
    const date  = document.getElementById('custChangeCreatedDate').value;
    const actorId = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

    if (!date) {
        showErr('custChangeCreatedDate', 'err-cust-change-date', 'Date is required');
        return;
    }

    const body = new FormData();
    body.append('action', 'verifyCustomer');
    body.append('email', email);
    body.append('created_date', date);
    body.append('actor_id', actorId);

    try {
        const response = await fetch(CUST_URL, { method: 'POST', body });
        const data = await response.json();

        if (data.status === 'success') {
            custVerifyStepDone = true;
            document.getElementById('cust-verify-section').style.display = 'none';
            document.getElementById('cust-reset-section').style.display = 'block';
            document.getElementById('btn-cust-submit-change').textContent = 'Update Password';
        } else {
            showErr('custChangeCreatedDate', 'err-cust-change-date', data.message);
        }
    } catch (e) {
        cardhavenAlert('error', 'Error', 'Network error');
    }
}

async function resetCustPassword() {
    const password = document.getElementById('custChangeNewPassword').value;
    const confirm  = document.getElementById('custChangeConfirmPassword').value;
    const actorId  = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
    let valid = true;
    if (password.length < 8 || password.length > 12 || !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        showErr('custChangeNewPassword', 'err-cust-change-pass', 'Password must be 8-12 chars + symbol');
        valid = false;
    }
    if (password !== confirm) {
        showErr('custChangeConfirmPassword', 'err-cust-change-confirm', 'Confirm password does not match!');
        valid = false;
    }

    if (!valid) return;

    const body = new FormData();
    body.append('action', 'resetCustomerPassword');
    body.append('password', password);
    body.append('confirm_password', confirm);
    body.append('actor_id', actorId);

    try {
        const response = await fetch(CUST_URL, { method: 'POST', body });
        const data = await response.json();

        if (data.status === 'success') {
            Swal.fire({
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

// Update fungsi handleOverlayClick
function handleOverlayClick(e) {
    if (e.target !== overlay) return;
    if (modalDetail.classList.contains('active')) closeCustomerModal();
    if (modalAdd.classList.contains('active'))    closeAddModal();
    if (modalEdit.classList.contains('active'))   closeEditModal();
    if (modalCustChange && modalCustChange.classList.contains('active')) {
        modalCustChange.classList.remove('active');
        hideOverlay();
    }
}