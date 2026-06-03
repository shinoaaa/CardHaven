// =============================================================
//  SET – set_script.js
//  Pola identik dengan game_script.js
// =============================================================

const setModal = document.getElementById('setModal');
const setForm  = document.getElementById('setForm');

// Ambil id karyawan dari storage (sama persis dengan game_script.js)
function getEmployeeId() {
    const id = localStorage.getItem('id_karyawan');
    if (!id) {
        console.warn("ID Karyawan tidak ditemukan di storage!");
        return 2;
    }
    return id;
}

// ================================================================
// LOAD TABEL SET
// ================================================================
let currentSetPage = 1;

function loadSetTable(page) {
    currentSetPage = page;

    fetch(`controller_set.php?get_list=1&page=${page}`)
        .then(res => res.json())
        .then(res => {
            if (res.status !== 'success') return;

            const tbody = document.getElementById('setTableBody');
            tbody.innerHTML = '';

            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888;">Tidak ada data</td></tr>';
            } else {
                res.data.forEach(row => {
                    const statusText  = row.aktif == 1 ? 'Active'   : 'Inactive';
                    const statusColor = row.aktif == 1 ? '#27AE60'  : '#E74C3C';
                    const setIdLabel  = 'SET-' + String(row.id_set).padStart(3, '0');

                    tbody.innerHTML += `
                    <tr>
                        <td>${escHtml(row.nama_set)}</td>
                        <td>${escHtml(setIdLabel)}</td>
                        <td>${escHtml(row.nama_game)}</td>
                        <td style="color:${statusColor}; font-weight:bold;">${statusText}</td>
                        <td>
                            <div class="btn-action-group">
                                <button class="btn-edit-icon"   onclick="openEditSetModal(${row.id_set})">✏️</button>
                                <button class="btn-delete-icon" onclick="confirmDeleteSet(${row.id_set})">🗑️</button>
                            </div>
                        </td>
                    </tr>`;
                });
            }

            renderSetPagination(res.total_pages, res.current_page);
        })
        .catch(err => console.error("Gagal load set:", err));
}

function renderSetPagination(totalPages, currentPage) {
    const container = document.getElementById('setPaginationContainer');
    container.innerHTML = '';

    const prev = document.createElement('span');
    prev.className = 'page-num';
    prev.innerText = ' < ';
    if (currentPage > 1) prev.style.cursor = 'pointer';
    prev.onclick = () => { if (currentPage > 1) loadSetTable(currentPage - 1); };
    container.appendChild(prev);

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('span');
        btn.className = 'page-num' + (i === currentPage ? ' active' : '');
        btn.innerText = i;
        btn.style.cursor = 'pointer';
        btn.onclick = () => loadSetTable(i);
        container.appendChild(btn);
    }

    const next = document.createElement('span');
    next.className = 'page-num';
    next.innerText = ' > ';
    if (currentPage < totalPages) next.style.cursor = 'pointer';
    next.onclick = () => { if (currentPage < totalPages) loadSetTable(currentPage + 1); };
    container.appendChild(next);
}

// ================================================================
// DROPDOWN GAME (isi saat modal dibuka pertama kali)
// ================================================================
let gamesLoaded = false;

function loadGameOptions(selectedId) {
    if (gamesLoaded) {
        if (selectedId) document.getElementById('setGameId').value = selectedId;
        return;
    }

    fetch('controller_set.php?get_games=1')
        .then(res => res.json())
        .then(res => {
            const select = document.getElementById('setGameId');
            select.innerHTML = '<option value="">-- Pilih Game --</option>';
            res.data.forEach(g => {
                const opt = new Option(g.nama_game, g.id_game);
                select.appendChild(opt);
            });
            gamesLoaded = true;
            if (selectedId) select.value = selectedId;
        });
}

// ================================================================
// MODAL ADD
// ================================================================
function openAddSetModal() {
    document.getElementById('setModalTitle').innerHTML = 'ADD <span class="blue-text">SET</span>';
    document.getElementById('setDisplayID').innerText = '';
    document.getElementById('setFormAction').value = 'add';
    document.getElementById('setLogSection').style.display = 'none';
    setForm.reset();
    loadGameOptions(null);
    setModal.style.display = 'flex';
}

// ================================================================
// MODAL EDIT
// ================================================================
function openEditSetModal(id) {
    fetch(`/CardHaven/interface/super-admin-page/controller_set.php?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('setModalTitle').innerHTML = '<span class="blue-text">SET</span> DETAIL';
            document.getElementById('setDisplayID').innerText = 'SET-' + String(id).padStart(3, '0');
            document.getElementById('setFormAction').value = 'edit';
            document.getElementById('setIdInput').value   = id;
            document.getElementById('setNama').value      = data.nama_set;
            document.getElementById('setKode').value      = data.kode_set;
            document.getElementById('setTanggal').value   = data.tanggal_rilis || '';
            document.getElementById('setLogSection').style.display = 'block';
            document.getElementById('setCreatedBy').innerText   = data.creator  || 'System';
            document.getElementById('setCreatedDate').innerText = data.created_date || '-';
            document.getElementById('setEditedBy').innerText    = data.modifier  || '-';
            document.getElementById('setEditedDate').innerText  = data.modified_date || '-';

            const statusLabel = document.getElementById('setStatusLabel');
            statusLabel.innerText   = data.aktif == 1 ? 'Active' : 'Inactive';
            statusLabel.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
            document.getElementById('setAktifStatus').value = data.aktif;

            loadGameOptions(data.id_game);
            setModal.style.display = 'flex';
        });
}

// ================================================================
// SUBMIT FORM (add / edit)
// ================================================================
setForm.onsubmit = function(e) {
    e.preventDefault();

    const nama  = document.getElementById('setNama').value.trim();
    const kode  = document.getElementById('setKode').value.trim();
    const game  = document.getElementById('setGameId').value;

    if (nama === '' || kode === '' || game === '') {
        alert("Nama set, kode set, dan game wajib diisi!");
        return;
    }

    const formData = new FormData(setForm);
    formData.append('id_karyawan_js', getEmployeeId());

    fetch('/CardHaven/interface/super-admin-page/controller_set.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                alert("Data berhasil disimpan");
                setModal.style.display = 'none';
                loadSetTable(currentSetPage);
            } else {
                alert("Peringatan: " + res.message);
            }
        })
        .catch(() => alert("Terjadi kesalahan pada server"));
};

// ================================================================
// DELETE (soft delete)
// ================================================================
function confirmDeleteSet(id) {
    if (confirm("Nonaktifkan set ini? (Soft Delete)")) {
        const formData = new FormData();
        formData.append('action',         'delete');
        formData.append('id_set',         id);
        formData.append('id_karyawan_js', getEmployeeId());

        fetch('/CardHaven/interface/super-admin-page/controller_set.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') loadSetTable(currentSetPage);
                else alert("Gagal menghapus: " + res.message);
            });
    }
}
function confirmRestoreSet(id) {
    if (confirm("Aktifkan kembali set ini?")) {
        const formData = new FormData();
        formData.append('action',         'restore');
        formData.append('id_set',         id);
        formData.append('id_karyawan_js', getEmployeeId());

        fetch('/CardHaven/interface/super-admin-page/controller_set.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') loadSetTable(currentSetPage);
                else alert("Gagal mengembalikan: " + res.message);
            });
    }
}

// ================================================================
// TUTUP MODAL klik di luar box
// ================================================================
window.addEventListener('click', function(e) {
    if (e.target === setModal) setModal.style.display = 'none';
});

// ================================================================
// HELPER: escape HTML biar aman dari XSS
// ================================================================
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ================================================================
// INIT — load tabel saat halaman pertama kali dibuka
// ================================================================
document.addEventListener('DOMContentLoaded', () => loadSetTable(1));