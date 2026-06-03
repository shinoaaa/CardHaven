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
            document.getElementById('editedDate').innerText = data.modified_date;
            
            const lbl = document.getElementById('statusLabel');
            lbl.innerText = data.aktif == 1 ? 'Active' : 'Inactive';
            lbl.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
            document.getElementById('aktifStatus').value = data.aktif;
            modal.style.display = 'flex';
        });
}

gameForm.onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(gameForm);
    formData.append('id_karyawan_js', getEmpId());

    fetch('controller_game.php', { method: 'POST', body: formData })
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
        fetch('controller_game.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(res => location.reload());
    }
}

window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; }