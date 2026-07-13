const ADMIN_URL = '/cardhaven/interface/user/controller/controllerSuperAdmin.php';
// id_pengguna pelaku untuk audit (created_by/modified_by/deleted_by).
function getActorId() { return localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna') || ''; }
let overlay, modalDetail, modalAdd, modalEdit;
let superVerifyStepDone = false;
let modalSuperChange;

document.addEventListener('DOMContentLoaded', () => {
    overlay     = document.getElementById('adminOverlay');
    modalDetail = document.getElementById('modalAdminDetail');
    modalAdd    = document.getElementById('modalAdminAdd');
    modalEdit   = document.getElementById('modalAdminEdit');
    modalSuperChange = document.getElementById('modalSuperChangePassword');
    

    attachLiveClear('addUsername', 'err-add-username');
    attachLiveClear('addEmail',    'err-add-email');
    attachLiveClear('addNoTelp',   'err-add-notelp');
    attachLiveClear('addPassword', 'err-add-password');
    attachLiveClear('addConfirmPassword', 'err-add-confirm-password');

    attachLiveClear('editConfirmPassword', 'err-edit-confirm-password');
    attachLiveClear('editPassword', 'err-edit-password');
    attachLiveClear('editNoTelp', 'err-edit-notelp');
    attachLiveClear('editUsername', 'err-edit-username');
    attachLiveClear('editEmail',    'err-edit-email');

     if (document.getElementById('super-toolbar')) {
        new UserMasterFilter({
            api: ADMIN_URL,
            toolbarId: 'super-toolbar', tbodyId: 'super-tbody', pagId: 'super-pag',
            colspan: 8,
            searchPlaceholder: 'Search manager name or email...',
            sortOptions: [
                { val: 'username', label: 'Sort: Name' },
                { val: 'email', label: 'Sort: Email' }
            ],
            renderRow: (r, no) => {
                const fotoPath = r.foto_profil ? `/cardhaven/image-profile/${mfEsc(r.foto_profil)}` : '/cardhaven/assets/image/user.svg';
                return `<tr>
                    <td>${no}</td>
                    <td><img src="${fotoPath}" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"></td>
                    <td style="font-weight: 600;">${mfEsc(r.username)}</td>
                    <td>${mfEsc(r.email)}</td>
                    <td>${mfEsc(r.no_telepon || '-')}</td>
                    <td>${r.created_date || '-'}</td>
                    <td>${mfStatusPill(r.status_akun)}</td>
                    <td>
                        <div class="btn-action-group">
                            <button class="btn-view-icon" onclick="openAdminModal(${r.id_pengguna})">...</button>
                            <button class="btn-edit-icon" onclick="openAdminEdit(${r.id_pengguna})"><img src="/cardhaven/assets/image/edit.svg"></button>
                            <button class="btn-delete-icon" onclick="deleteAdmin(${r.id_pengguna})"><img src="/cardhaven/assets/image/delete.svg"></button>
                            <label class="switch">
                                <input type="checkbox" ${parseInt(r.status_akun) === 1 ? 'checked' : ''} onchange="toggleAdmin(${r.id_pengguna}, this.checked, this)">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </td>
                </tr>`;
            }
        });
    }
    
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
            document.getElementById('detailNoTelp').textContent = d.no_telepon || '-';
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
    const password = document.getElementById('addPassword').value;
    const no_telp  = document.getElementById('addNoTelp').value.replace(/\s+/g, '');
    const confirmPassword = document.getElementById('addConfirmPassword').value;
    const foto     = document.getElementById('addFoto').files[0];

    if (!username) { showErr('addUsername', 'err-add-username', 'Username is required.'); valid = false; }
    if (!email) { showErr('addEmail', 'err-add-email', 'Email is required.'); valid = false; }
    if (!no_telp) { showErr('addNoTelp', 'err-add-notelp', 'Phone Number is required.'); valid = false; }
    else if (!isValidEmail(email)) { showErr('addEmail', 'err-add-email', 'Invalid email format.'); valid = false; }
    
    if (no_telp && !isValidPhone(no_telp)) { showErr('addNoTelp', 'err-add-notelp', 'Invalid phone number format.'); valid = false; }
    
    if (!password) { 
        showErr('addPassword', 'err-add-password', 'Password is required.'); valid = false; 
    } else if (password.length < 8 || password.length > 12) {
        showErr('addPassword', 'err-add-password', 'Password must be 8 - 12 characters long'); valid = false;
    } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        showErr('addPassword', 'err-add-password', 'Password must contain a special character'); valid = false;
    }

    if (!confirmPassword) {
        showErr('addConfirmPassword', 'err-add-confirm-password', 'Please confirm your password.'); valid = false;
    } else if (password !== confirmPassword) {
        showErr('addConfirmPassword', 'err-add-confirm-password', 'Passwords do not match.'); valid = false;
    }
    if (!valid) return;

    const body = new FormData();
    body.append('action', 'addAdmin');
    body.append('actor_id', getActorId());
    body.append('username', username);
    body.append('email', email);
    body.append('password', password);
    body.append('no_telepon', no_telp);
    if (foto) body.append('foto_profil', foto);

    fetch(ADMIN_URL, { method: 'POST', body })
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
            cardhavenAlert('success', 'Success!', 'Super Admin added successfully.', () => { location.reload(); });
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
            document.getElementById('editNoTelp').value   = d.no_telepon || ''; 

        
            document.getElementById('superChangeEmail').value = d.email || '';

            modalSuperChange = document.getElementById('modalSuperChangePassword');
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

function submitEditSupplier() {
    // Alias to match button trigger inside template
    submitEditAdmin();
}

function submitEditAdmin() {
    clearAllErrors('edit');
    let valid = true;

    const id       = document.getElementById('editAdminId').value;
    const username = document.getElementById('editUsername').value.trim();
    const email    = document.getElementById('editEmail').value.trim();
    const no_telp  = document.getElementById('editNoTelp').value.replace(/\s+/g, '');
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
    body.append('actor_id', getActorId());
    body.append('id_pengguna', id);
    body.append('username', username);
    body.append('email', email);
    body.append('no_telepon', no_telp);
    if (foto) body.append('foto_profil', foto);

    fetch(ADMIN_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                if (res.code === 'EMAIL_DUPLICATE') {
                    showErr('editEmail', 'err-edit-mail', res.message);
                } else {
                    cardhavenAlert('error', 'Failed', res.message);
                }
                return;
            }
            modalEdit.classList.remove('active');
            hideOverlay();
            cardhavenAlert('success', 'Updated!', 'Super Admin updated successfully.', () => { location.reload(); });
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error.'));
}

// ===================== DELETE =====================
function deleteAdmin(id) {
    cardhavenConfirm('Delete Super Admin?', 'This action cannot be undone.', 'Delete', () => {
        const body = new FormData();
        body.append('action', 'deleteAdmin');
        body.append('actor_id', getActorId());
        body.append('id_pengguna', id);

        fetch(ADMIN_URL, { method: 'POST', body })
            .then(r => r.json())
            .then(res => {
                if (!res.success) { cardhavenAlert('error', 'Failed', res.message); return; }
                cardhavenAlert('success', 'Deleted!', 'Super Admin has been removed.', () => { location.reload(); });
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
            body.append('actor_id', getActorId());
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
                            text: res.message || 'Failed to update account status.',
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
            // Revert checkbox jika user menekan 'Cancel' pada konfirmasi
            checkboxEl.checked = !isChecked; 
        }
    );
}

function openSuperChangePasswordModal() {
    modalEdit.classList.remove('active'); // Sembunyikan modal edit
    
    superVerifyStepDone = false;
    document.getElementById('superChangePasswordForm').reset();
    document.getElementById('super-verify-section').style.display = 'block';
    document.getElementById('super-reset-section').style.display = 'none';
    document.getElementById('btn-super-submit-change').textContent = 'Verify';
    
    modalSuperChange.classList.add('active');
}

function closeSuperChangePasswordModal() {
    modalSuperChange.classList.remove('active');
    modalEdit.classList.add('active'); // Kembali ke modal edit
}

// Handler Tahapan
function handleSuperPasswordStep() {
    if (!superVerifyStepDone) {
        verifySuperIdentity();
    } else {
        resetSuperPassword();
    }
}

async function verifySuperIdentity() {
    const email = document.getElementById('superChangeEmail').value;
    const date  = document.getElementById('superChangeCreatedDate').value;
    const actorId = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

    if (!date) {
        showErr('superChangeCreatedDate', 'err-super-change-date', 'Date is required');
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
            superVerifyStepDone = true;
            document.getElementById('super-verify-section').style.display = 'none';
            document.getElementById('super-reset-section').style.display = 'block';
            document.getElementById('btn-super-submit-change').textContent = 'Update Password';
        } else {
            showErr('superChangeCreatedDate', 'err-super-change-date', data.message);
        }
    } catch (e) {
        cardhavenAlert('error', 'Error', 'Network error');
    }
}

async function resetSuperPassword() {
    const password = document.getElementById('superChangeNewPassword').value;
    const confirm  = document.getElementById('superChangeConfirmPassword').value;
    const actorId = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

    let valid = true;
    if (password.length < 8 || password.length > 12 || !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        showErr('superChangeNewPassword', 'err-super-change-pass', 'Password must be 8-12 chars + symbol');
        valid = false;
    }
    if (password !== confirm) {
        showErr('superChangeConfirmPassword', 'err-super-change-confirm', 'Passwords do not match');
        valid = false;
    }

    if (!valid) return;

    const body = new FormData();
    body.append('action', 'changePasswordAdmin');
    body.append('password', password);
    body.append('confirm_password', confirm);
    body.append('actor_id', actorId);

    try {
        const response = await fetch(ADMIN_URL, { method: 'POST', body });
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

// Tambahkan handle click overlay untuk modalChange
function handleOverlayClick(e) {
    if (e.target !== overlay) return;
    if (modalDetail.classList.contains('active')) closeAdminModal();
    if (modalAdd.classList.contains('active')) closeAddModal();
    if (modalEdit.classList.contains('active')) closeEditModal();
    if (modalSuperChange.classList.contains('active')) {
        modalSuperChange.classList.remove('active');
        hideOverlay();
    }
}