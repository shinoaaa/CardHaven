const modalRarity = document.getElementById('rarityModal');
const rarityForm = document.getElementById('rarityForm');

const getEmpId = () => localStorage.getItem('id_karyawan') || 0;

function openModalRarity() {
    document.getElementById('modalTitleRarity').innerHTML = 'ADD <span class="blue-text">RARITY</span>';
    document.getElementById('displayIDRarity').innerText = '';
    document.getElementById('formActionRarity').value = 'add';
    document.getElementById('logSectionRarity').style.display = 'none';
    rarityForm.reset();
    modalRarity.style.display = 'flex';
}

function openEditRarity(id) {
    fetch(`controller_rarity.php?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modalTitleRarity').innerHTML = '<span class="blue-text">RARITY</span> DETAIL';
            document.getElementById('displayIDRarity').innerText = 'RAR-' + String(id).padStart(3, '0');
            document.getElementById('formActionRarity').value = 'edit';
            document.getElementById('inputIdRarity').value = id;
            document.getElementById('inputGameRarity').value = data.id_game;
            document.getElementById('inputNamaRarity').value = data.nama_rarity;
            document.getElementById('inputKodeRarity').value = data.kode_rarity;
            
            document.getElementById('logSectionRarity').style.display = 'block';
            document.getElementById('createdByRarity').innerText = data.creator || 'System';
            document.getElementById('createdDateRarity').innerText = data.created_date;
            document.getElementById('editedByRarity').innerText = data.modifier || '-';
            document.getElementById('editedDateRarity').innerText = data.modified_date;
            
            const lbl = document.getElementById('statusLabelRarity');
            lbl.innerText = data.aktif == 1 ? 'Active' : 'Inactive';
            lbl.style.color = data.aktif == 1 ? '#27AE60' : '#E74C3C';
            document.getElementById('aktifStatusRarity').value = data.aktif;
            
            modalRarity.style.display = 'flex';
        });
}

rarityForm.onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(rarityForm);
    formData.append('id_karyawan_js', getEmpId());

    fetch('controller_rarity.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') location.reload();
        else alert(res.message);
    });
};

function confirmDeleteRarity(id) {
    if (confirm("Nonaktifkan rarity ini?")) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_rarity', id);
        fd.append('id_karyawan_js', getEmpId());
        
        fetch('controller_rarity.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else alert(res.message);
        });
    }
}

window.addEventListener('click', (e) => { 
    if (e.target == modalRarity) modalRarity.style.display = "none"; 
});