const modal = document.getElementById('gameModal');
const gameForm = document.getElementById('gameForm');

// Ambil ID Karyawan
const getEmpId = () => localStorage.getItem('id_karyawan') || 0;

function openAddModal() {
    document.getElementById('modalTitle').innerHTML = 'ADD <span class="blue-text">GAME</span>';
    document.getElementById('displayID').innerText = '';
    document.getElementById('formAction').value = 'add';
    document.getElementById('logSection').style.display = 'none';
    gameForm.reset();
    modal.style.display = 'flex';
}

function openEditModal(id) {
    // Pastikan path fetch ini benar sesuai lokasi file Anda
    fetch(`/cardhaven/interface/super-admin-page/controller_game.php?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.error) {
                alert("Error: " + JSON.stringify(data.error));
                return;
            }

            document.getElementById('modalTitle').innerHTML = '<span class="blue-text">GAME</span> DETAIL';
            document.getElementById('displayID').innerText = 'GAM-' + id;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formID').value = id;
            
            // Mengisi Input (Pastikan ID di HTML sama dengan nama ini)
            document.getElementById('nama_game').value = data.nama_game;
            document.getElementById('developer').value = data.developer;
            
            // Bagian Log / History
            document.getElementById('logSection').style.display = 'block';
            document.getElementById('createdBy').innerText = data.creator; // Berisi "User ID: 2"
            document.getElementById('createdDate').innerText = " (" + data.created_date + ")";
            document.getElementById('editedBy').innerText = data.modifier;
            document.getElementById('editedDate').innerText = data.modified_date !== '-' ? " (" + data.modified_date + ")" : "";
            
            // Status Aktif
            const lbl = document.getElementById('statusLabel');
            lbl.innerText = data.aktif == 1 ? 'Active' : 'Inactive';
            lbl.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
            document.getElementById('aktifStatus').value = data.aktif;
            
            modal.style.display = 'flex';
        })
        .catch(err => {
            console.error("Fetch error:", err);
            alert("Gagal mengambil data. Periksa console.");
        });
}

gameForm.onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(gameForm);
    formData.append('id_karyawan_js', getEmpId());

    fetch('/cardhaven/interface/super-admin-page/controller_game.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') location.reload();
        else alert(res.message);
    });
};

function confirmDelete(id) {
    if (confirm("Nonaktifkan game ini?")) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_game', id);
        fd.append('id_karyawan_js', getEmpId());
        fetch('/cardhaven/interface/super-admin-page/controller_game.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(res => location.reload());
    }
}

function confirmRestore(id) {
    if (confirm("Aktifkan kembali game ini?")) {
        const fd = new FormData();
        fd.append('action', 'restore'); // Kita buat action baru namanya 'restore'
        fd.append('id_game', id);
        fd.append('id_karyawan_js', getEmpId());
        
        fetch('/cardhaven/interface/super-admin-page/controller_game.php', { 
            method: 'POST', 
            body: fd 
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else alert(res.message);
        });
    }
}

window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; }