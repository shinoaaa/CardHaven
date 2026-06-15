const CUST_URL = '/cardhaven/interface/user/controller/controllerCustomer.php';
let overlay, modalDetail, modalAdd, modalEdit;

document.addEventListener('DOMContentLoaded', () => {
    overlay     = document.getElementById('customerOverlay');
    modalDetail = document.getElementById('modalCustomerDetail');
    modalAdd    = document.getElementById('modalCustomerAdd');
    modalEdit   = document.getElementById('modalCustomerEdit');

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
    const no_telp  = document.getElementById('addNoTelp').value.trim();
    const password = document.getElementById('addPassword').value;
    const confirmPassword = document.getElementById('addConfirmPassword').value;
    const foto     = document.getElementById('addFoto').files[0];

    if (!username) { showErr('addUsername', 'err-add-username', 'Name is required.'); valid = false; }
    if (!email) { showErr('addEmail', 'err-add-email', 'Email is required.'); valid = false; }
    else if (!isValidEmail(email)) { showErr('addEmail', 'err-add-email', 'Invalid email format.'); valid = false; }
    
    if (no_telp && !isValidPhone(no_telp)) { showErr('addNoTelp', 'err-add-notelp', 'Invalid phone number format.'); valid = false; }

    if (!password) { 
        showErr('addPassword', 'err-add-password', 'Password is required.'); 
        valid = false; 
    }
    if (!confirmPassword) {
        showErr('addConfirmPassword', 'err-add-confirm-password', 'Please confirm your password.');
        valid = false;
    } else if (password !== confirmPassword) {
        showErr('addConfirmPassword', 'err-add-confirm-password', 'Passwords do not match.');
        valid = false;
    }

    if (!valid) return;

    const body = new FormData();
    body.append('action', 'addCustomer');
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
            document.getElementById('editPassword').value     = '';
            document.getElementById('editConfirmPassword').value = '';
            
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
    const no_telp  = document.getElementById('editNoTelp').value.trim();
    const password = document.getElementById('editPassword').value;
    const confirmPassword = document.getElementById('editConfirmPassword').value;
    const foto     = document.getElementById('editFoto').files[0];

    if (!username) { showErr('editUsername', 'err-edit-username', 'Name is required.'); valid = false; }
    if (!email) { showErr('editEmail', 'err-edit-email', 'Email is required.'); valid = false; }
    else if (!isValidEmail(email)) { showErr('editEmail', 'err-edit-email', 'Invalid email format.'); valid = false; }
    
    if (no_telp && !isValidPhone(no_telp)) { showErr('editNoTelp', 'err-edit-notelp', 'Invalid phone number format.'); valid = false; }

    if (password || confirmPassword) {
        if (password !== confirmPassword) {
            showErr('editConfirmPassword', 'err-edit-confirm-password', 'Passwords do not match.');
            valid = false;
        }
    }

    if (!valid) return;

    const body = new FormData();
    body.append('action', 'updateCustomer');
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

    cardhavenConfirm(`${isChecked ? 'Activate' : 'Deactivate'} Account?`, `Are you sure you want to ${label} this account?`, isChecked ? 'Activate' : 'Deactivate', 
        () => {
            const body = new FormData();
            body.append('action', 'toggleCustomer');
            body.append('id_pengguna', id);
            body.append('status_akun', newStatus);

            fetch(CUST_URL, { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        checkboxEl.checked = !isChecked;
                        cardhavenAlert('error', 'Failed', res.message);
                        return;
                    }
                    const row = checkboxEl.closest('tr');
                    const badge = row?.querySelector('.status-badge');
                    if (badge) {
                        badge.textContent = isChecked ? 'Active' : 'Inactive';
                        badge.style.color = isChecked ? '#27AE60' : '#E74C3C';
                    }
                })
                .catch(() => {
                    checkboxEl.checked = !isChecked;
                    cardhavenAlert('error', 'Error', 'Network error.');
                });
        },
        () => { checkboxEl.checked = !isChecked; }
    );
}