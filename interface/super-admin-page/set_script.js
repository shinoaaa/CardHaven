
const setModal = document.getElementById('setModal');
const setForm  = document.getElementById('setForm');


const SET_API_URL_PATH = '/CardHaven/interface/super-admin-page/controller_set.php'; 

let setGamesLoaded = false;
function loadGameOptionsForSet(selectedId) {
    if (setGamesLoaded && selectedId) {
        document.getElementById('setGameId').value = selectedId;
        return;
    }

    fetch(`${SET_API_URL_PATH}?get_games=1`)
        .then(res => res.json())
        .then(res => {
            const select = document.getElementById('setGameId');
            select.innerHTML = '<option value="">-- Pilih Game --</option>';
            res.data.forEach(g => {
                const opt = new Option(g.nama_game, g.id_game);
                select.appendChild(opt);
            });
            setGamesLoaded = true;
            if (selectedId) select.value = selectedId;
        });
}

function openAddSetModal() {
    document.getElementById('setModalTitle').innerHTML = 'ADD <span class="blue-text">SET</span>';
    document.getElementById('setDisplayID').innerText = '';
    document.getElementById('setFormAction').value = 'add';
    document.getElementById('setLogSection').style.display = 'none';
    setForm.reset();

    document.getElementById('setTanggal').value = ''; 
    loadGameOptionsForSet(null);
    setModal.style.display = 'flex';
}


function openEditSetModal(id) {
    fetch(`${SET_API_URL_PATH}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(!data || data.error) return alert("Gagal mengambil data detail");

            document.getElementById('setModalTitle').innerHTML = '<span class="blue-text">SET</span> DETAIL';
            document.getElementById('setDisplayID').innerText = 'SET-' + String(id).padStart(3, '0');
            document.getElementById('setFormAction').value = 'edit';
            document.getElementById('setIdInput').value   = id;
            document.getElementById('setNama').value      = data.nama_set;
            document.getElementById('setKode').value      = data.kode_set;
            
            // Format tanggal untuk input type="date" (Y-m-d)
            if(data.tanggal_rilis) {
                document.getElementById('setTanggal').value = data.tanggal_rilis;
            }

            document.getElementById('setLogSection').style.display = 'block';
            document.getElementById('setCreatedBy').innerText   = data.creator  || 'System';
            document.getElementById('setCreatedDate').innerText = data.created_date || '-';
            document.getElementById('setEditedBy').innerText    = data.modifier  || '-';
            document.getElementById('setEditedDate').innerText  = data.modified_date || '-';

            const statusLabel = document.getElementById('setStatusLabel');
            statusLabel.innerText   = data.aktif == 1 ? 'Active' : 'Inactive';
            statusLabel.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
            document.getElementById('setAktifStatus').value = data.aktif;

            loadGameOptionsForSet(data.id_game);
            setModal.style.display = 'flex';
        })
        .catch(err => console.error("Error Edit Modal:", err));
}

setForm.onsubmit = function(e) {
    e.preventDefault();

    const formData = new FormData(setForm);
    formData.append('id_karyawan_js', getEmpId());

    fetch(SET_API_URL_PATH, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                alert("Data berhasil disimpan");
                location.reload(); // REFRESH AGAR LOGIKA PHP MENAMPILKAN DATA TERBARU
            } else {
                alert("Peringatan: " + res.message);
            }
        })
        .catch(err => alert("Terjadi kesalahan sistem saat menyimpan."));
};


function confirmDeleteSet(id) {
    if (confirm("Nonaktifkan set ini? (Soft Delete)")) {
        const formData = new FormData();
        formData.append('action',         'delete');
        formData.append('id_set',         id);
        formData.append('id_karyawan_js', getEmpId());

        fetch(SET_API_URL_PATH, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') location.reload();
                else alert("Gagal menghapus: " + res.message);
            });
    }
}

function confirmRestoreSet(id) {
    if (confirm("Aktifkan kembali set ini?")) {
        const formData = new FormData();
        formData.append('action',         'restore');
        formData.append('id_set',         id);
        formData.append('id_karyawan_js', getEmpId());

        fetch(SET_API_URL_PATH, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') location.reload();
                else alert("Gagal mengembalikan: " + res.message);
            });
    }
}

window.addEventListener('click', function(e) {
    if (e.target === setModal) setModal.style.display = 'none';
});