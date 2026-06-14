const SUPP_URL = '/cardhaven/interface/user/controller/controllerSupp.php';
const VIEW_URL = '/cardhaven/interface/event/components/detailEvent.php';
let overlay, modalDetail, modalAdd, modalEdit;

document.addEventListener('DOMContentLoaded', () => {
    overlay     = document.getElementById('supplierOverlay');
    modalDetail = document.getElementById('modalSupplierDetail');
    modalAdd    = document.getElementById('modalSupplierAdd');
    modalEdit   = document.getElementById('modalSupplierEdit');

    // Live-clear errors while typing
    attachLiveClear('addSupplierName',    'err-add-name');
    attachLiveClear('addSupplierMail',    'err-add-mail');
    attachLiveClear('addSupplierNum',     'err-add-num');
    attachLiveClear('addSupplierAddress', 'err-add-address');
    attachLiveClear('editSupplierName',   'err-edit-name');
    attachLiveClear('editSupplierMail',   'err-edit-mail');
    attachLiveClear('editSupplierNum',    'err-edit-num');
    attachLiveClear('editSupplierAddress','err-edit-address');
});

// ============================================================
//  HELPERS
// ============================================================

function attachLiveClear(inputId, errId) {
    const el = document.getElementById(inputId);
    if (!el) return;
    el.addEventListener('input', () => {
        clearErr(inputId, errId);
    });
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
    // cleaner version:
    clearErr(`${prefix}SupplierName`,    `err-${prefix}-name`);
    clearErr(`${prefix}SupplierMail`,    `err-${prefix}-mail`);
    clearErr(`${prefix}SupplierNum`,     `err-${prefix}-num`);
    clearErr(`${prefix}SupplierAddress`, `err-${prefix}-address`);
}

function showOverlay() {
    overlay.classList.add('active');
}
function hideOverlay() {
    overlay.classList.remove('active');
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}

function isValidPhone(phone) {
    return /^[0-9+\-\s]{7,20}$/.test(phone.trim());
}

// ============================================================
//  DIRTY FORM CHECK
//  Returns true if any field in the form has been changed
// ============================================================

let addFormSnapshot  = null;   // snapshot of add form when opened
let editFormSnapshot = null;   // snapshot of edit form when opened

function snapshotForm(prefix) {
    return {
        name:    document.getElementById(`${prefix}SupplierName`).value,
        mail:    document.getElementById(`${prefix}SupplierMail`).value,
        num:     document.getElementById(`${prefix}SupplierNum`).value,
        address: document.getElementById(`${prefix}SupplierAddress`).value,
    };
}

function isDirty(prefix, snapshot) {
    const current = snapshotForm(prefix);
    return JSON.stringify(current) !== JSON.stringify(snapshot);
}

// ============================================================
//  OVERLAY CLICK – close whichever modal is open
// ============================================================

function handleOverlayClick(e) {
    if (e.target !== overlay) return;
    if (modalDetail.classList.contains('active')) { closeSupplierModal(); return; }
    if (modalAdd.classList.contains('active'))    { closeAddModal();      return; }
    if (modalEdit.classList.contains('active'))   { closeEditModal();     return; }
}

// ============================================================
//  DETAIL MODAL
// ============================================================

function openSupplierModal(id) {
    fetch(`${SUPP_URL}?action=getSupplier&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                cardhavenAlert('error', 'Error', res.message || 'Failed to load supplier data.');
                return;
            }
            const d = res.data;
            document.getElementById('detailNama').textContent    = d.nama_suplier  || '-';
            document.getElementById('detailEmail').textContent   = d.email         || '-';
            document.getElementById('detailNoTelp').textContent  = d.no_telp       || '-';
            document.getElementById('detailAlamat').textContent  = d.alamat        || '-';
            document.getElementById('detailCreated').textContent = d.created_date  || '-';
            const statusEl = document.getElementById('detailStatus');
            if (parseInt(d.aktif) === 1) {
                statusEl.innerHTML = '<span class="badge-active">Active</span>';
            } else {
                statusEl.innerHTML = '<span class="badge-inactive">Inactive</span>';
            }
            showOverlay();
            modalDetail.classList.add('active');
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error. Please try again.'));
}

function closeSupplierModal() {
    modalDetail.classList.remove('active');
    hideOverlay();
}

// ============================================================
//  ADD MODAL
// ============================================================

function openAddEventModal() {
    // Reset form
    document.getElementById('suppAddForm').reset();
    clearAllErrors('add');
    addFormSnapshot = snapshotForm('add');   // blank snapshot
    showOverlay();
    modalAdd.classList.add('active');
    setTimeout(() => document.getElementById('addSupplierName').focus(), 100);
}

function closeAddModal() {
    if (isDirty('add', addFormSnapshot)) {
        // 1. Sembunyikan modal Add sementara
        modalAdd.classList.remove('active');
        
        cardhavenConfirm(
            'Discard Changes?',
            'You have unsaved data. Are you sure you want to close without saving?',
            'Discard',
            () => { 
                // Jika Confirm: Modal udah hilang, tinggal sembunyikan overlay
                hideOverlay(); 
            },
            () => {
                // Jika Cancel: Munculin modal Add lagi
                modalAdd.classList.add('active');
            }
        );
    } else {
        modalAdd.classList.remove('active');
        hideOverlay();
    }
}

function validateAddForm() {
    let valid = true;
    const name    = document.getElementById('addSupplierName').value.trim();
    const mail    = document.getElementById('addSupplierMail').value.trim();
    const num     = document.getElementById('addSupplierNum').value.trim();
    const address = document.getElementById('addSupplierAddress').value.trim();

    if (!name) {
        showErr('addSupplierName', 'err-add-name', 'Supplier name is required.');
        valid = false;
    }
    if (!mail) {
        showErr('addSupplierMail', 'err-add-mail', 'Email address is required.');
        valid = false;
    } else if (!isValidEmail(mail)) {
        showErr('addSupplierMail', 'err-add-mail', 'Please enter a valid email address.');
        valid = false;
    }
    if (!num) {
        showErr('addSupplierNum', 'err-add-num', 'Phone number is required.');
        valid = false;
    } else if (!isValidPhone(num)) {
        showErr('addSupplierNum', 'err-add-num', 'Please enter a valid phone number.');
        valid = false;
    }
    if (!address) {
        showErr('addSupplierAddress', 'err-add-address', 'Address is required.');
        valid = false;
    }
    return valid;
}

function submitAddSupplier() {
    if (!validateAddForm()) return;

    const body = new FormData();
    body.append('action',       'addSupplier');
    body.append('nama_suplier', document.getElementById('addSupplierName').value.trim());
    body.append('email',        document.getElementById('addSupplierMail').value.trim());
    body.append('no_telp',      document.getElementById('addSupplierNum').value.trim());
    body.append('alamat',       document.getElementById('addSupplierAddress').value.trim());

    fetch(SUPP_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                // Email duplicate check
                if (res.code === 'EMAIL_DUPLICATE') {
                    showErr('addSupplierMail', 'err-add-mail', 'This email address is already in use.');
                    return;
                }
                cardhavenAlert('error', 'Failed', res.message || 'Failed to add supplier.');
                return;
            }
            addFormSnapshot = snapshotForm('add'); // reset dirty flag
            modalAdd.classList.remove('active');
            hideOverlay();
            cardhavenAlert('success', 'Success!', 'Supplier has been added successfully.', () => {
                location.reload();
            });
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error. Please try again.'));
}

// ============================================================
//  EDIT MODAL
// ============================================================

function openSupplierEdit(id) {
    fetch(`${SUPP_URL}?action=getSupplier&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                cardhavenAlert('error', 'Error', res.message || 'Failed to load supplier data.');
                return;
            }
            const d = res.data;
            document.getElementById('editSuppId').value            = d.id_supplier;
            document.getElementById('editSupplierName').value      = d.nama_suplier || '';
            document.getElementById('editSupplierMail').value      = d.email        || '';
            document.getElementById('editSupplierNum').value       = d.no_telp      || '';
            document.getElementById('editSupplierAddress').value   = d.alamat       || '';

            clearAllErrors('edit');
            editFormSnapshot = snapshotForm('edit');  // snapshot of existing data

            showOverlay();
            modalEdit.classList.add('active');
            setTimeout(() => document.getElementById('editSupplierName').focus(), 100);
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error. Please try again.'));
}

function closeEditModal() {
    if (isDirty('edit', editFormSnapshot)) {
        // 1. Sembunyikan modal Edit sementara
        modalEdit.classList.remove('active');
        
        cardhavenConfirm(
            'Discard Changes?',
            'You have unsaved changes. Are you sure you want to close?',
            'Discard',
            () => { 
                // Jika Confirm: Modal udah hilang, tinggal sembunyikan overlay
                hideOverlay(); 
            },
            () => {
                // Jika Cancel: Munculin modal Edit lagi
                modalEdit.classList.add('active');
            }
        );
    } else {
        modalEdit.classList.remove('active');
        hideOverlay();
    }
}

function validateEditForm() {
    let valid = true;
    const name    = document.getElementById('editSupplierName').value.trim();
    const mail    = document.getElementById('editSupplierMail').value.trim();
    const num     = document.getElementById('editSupplierNum').value.trim();
    const address = document.getElementById('editSupplierAddress').value.trim();

    if (!name) {
        showErr('editSupplierName', 'err-edit-name', 'Supplier name is required.');
        valid = false;
    }
    if (!mail) {
        showErr('editSupplierMail', 'err-edit-mail', 'Email address is required.');
        valid = false;
    } else if (!isValidEmail(mail)) {
        showErr('editSupplierMail', 'err-edit-mail', 'Please enter a valid email address.');
        valid = false;
    }
    if (!num) {
        showErr('editSupplierNum', 'err-edit-num', 'Phone number is required.');
        valid = false;
    } else if (!isValidPhone(num)) {
        showErr('editSupplierNum', 'err-edit-num', 'Please enter a valid phone number.');
        valid = false;
    }
    if (!address) {
        showErr('editSupplierAddress', 'err-edit-address', 'Address is required.');
        valid = false;
    }
    return valid;
}

function submitEditSupplier() {
    if (!validateEditForm()) return;

    const id = document.getElementById('editSuppId').value;

    const body = new FormData();
    body.append('action',       'updateSupplier');
    body.append('id_supplier',  id);
    body.append('nama_suplier', document.getElementById('editSupplierName').value.trim());
    body.append('email',        document.getElementById('editSupplierMail').value.trim());
    body.append('no_telp',      document.getElementById('editSupplierNum').value.trim());
    body.append('alamat',       document.getElementById('editSupplierAddress').value.trim());

    fetch(SUPP_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                if (res.code === 'EMAIL_DUPLICATE') {
                    showErr('editSupplierMail', 'err-edit-mail', 'This email is already used by another supplier.');
                    return;
                }
                cardhavenAlert('error', 'Failed', res.message || 'Failed to update supplier.');
                return;
            }
            editFormSnapshot = snapshotForm('edit'); // reset dirty flag
            modalEdit.classList.remove('active');
            hideOverlay();
            cardhavenAlert('success', 'Updated!', 'Supplier has been updated successfully.', () => {
                location.reload();
            });
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Network error. Please try again.'));
}

// ============================================================
//  DELETE
// ============================================================

function deleteSupplier(id) {
    cardhavenConfirm(
        'Delete Supplier?',
        'This action cannot be undone. The supplier will be permanently removed.',
        'Delete',
        () => {
            const body = new FormData();
            body.append('action',      'deleteSupplier');
            body.append('id_supplier', id);

            fetch(SUPP_URL, { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        cardhavenAlert('error', 'Failed', res.message || 'Failed to delete supplier.');
                        return;
                    }
                    cardhavenAlert('success', 'Deleted!', 'Supplier has been deleted.', () => {
                        location.reload();
                    });
                })
                .catch(() => cardhavenAlert('error', 'Error', 'Network error. Please try again.'));
        }
    );
}

// ============================================================
//  TOGGLE ACTIVE / INACTIVE
// ============================================================

function toggleSupplier(id, isChecked, checkboxEl) {
    const newStatus = isChecked ? 1 : 0;
    const label     = isChecked ? 'activate' : 'deactivate';

    cardhavenConfirm(
        `${isChecked ? 'Activate' : 'Deactivate'} Supplier?`,
        `Are you sure you want to ${label} this supplier?`,
        isChecked ? 'Activate' : 'Deactivate',
        () => {
            const body = new FormData();
            body.append('action',      'toggleSupplier');
            body.append('id_supplier', id);
            body.append('aktif',       newStatus);

            fetch(SUPP_URL, { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        // Revert toggle on failure
                        checkboxEl.checked = !isChecked;
                        cardhavenAlert('error', 'Failed', res.message || 'Failed to update status.');
                        return;
                    }
                    // Update the status text in the row
                    const row    = checkboxEl.closest('tr');
                    const badge  = row?.querySelector('.status-badge');
                    if (badge) {
                        badge.textContent  = isChecked ? 'Active' : 'inactive';
                        badge.style.color  = isChecked ? '#27AE60' : '#E74C3C';
                    }
                })
                .catch(() => {
                    checkboxEl.checked = !isChecked;
                    cardhavenAlert('error', 'Error', 'Network error. Please try again.');
                });
        },
        () => {
            // Cancelled – revert the visual toggle
            checkboxEl.checked = !isChecked;
        }
    );
}