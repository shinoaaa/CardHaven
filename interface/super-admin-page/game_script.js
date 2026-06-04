const modal = document.getElementById('gameModal');
const gameForm = document.getElementById('gameForm');


function getEmployeeId() {

    const id = localStorage.getItem('id_karyawan');
    if (!id) {
        console.warn("ID Karyawan tidak ditemukan di storage!");
        return 2; 
    }
    return id;
}

function openAddModal() {
    document.getElementById('modalTitle').innerHTML = 'ADD <span class="blue-text">GAME</span>';
    document.getElementById('displayID').innerText = '';
    document.getElementById('formAction').value = 'add';
    document.getElementById('logSection').style.display = 'none';
    gameForm.reset();
    modal.style.display = 'flex';
}

function openEditModal(id) {
    fetch(`controller_game.php?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modalTitle').innerHTML = '<span class="blue-text">GAME</span> DETAIL';
            document.getElementById('displayID').innerText = 'GAM-' + String(id).padStart(3, '0');
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formID').value = id;
            document.getElementById('nama_game').value = data.nama_game;
            document.getElementById('developer').value = data.developer;
            document.getElementById('logSection').style.display = 'block';
            document.getElementById('createdBy').innerText = data.creator || 'System';
            document.getElementById('createdDate').innerText = data.created_date;
            document.getElementById('editedBy').innerText = data.modifier || '-';
            document.getElementById('editedDate').innerText = data.modified_date || '-';
            
            const statusLabel = document.getElementById('statusLabel');
            statusLabel.innerText = data.aktif == 1 ? 'Active' : 'Inactive';
            statusLabel.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
            document.getElementById('aktifStatus').value = data.aktif;

            modal.style.display = 'flex';
        });
}

gameForm.onsubmit = function(e) {
    e.preventDefault();
    
    const nama = document.getElementById('nama_game').value.trim();
    const dev = document.getElementById('developer').value.trim();
    
    if (nama === "" || dev === "") {
        alert("Semua kolom bertanda * wajib diisi!");
        return;
    }

    const formData = new FormData(gameForm);
    
    formData.append('id_karyawan_js', getEmployeeId());

    fetch('controller_game.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            alert("Data berhasil disimpan");
            location.reload();
        } else {

            alert("Peringatan: " + res.message);
        }
    })
    .catch(err => alert("Terjadi kesalahan pada server"));
};

function confirmDelete(id) {
    if (confirm("Nonaktifkan game ini? (Soft Delete)")) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id_game', id);
        
        formData.append('id_karyawan_js', getEmployeeId());

        fetch('controller_game.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else alert("Gagal menghapus: " + res.message);
        });
    }
}

window.onclick = function(event) {
    if (event.target == modal) modal.style.display = "none";
}