const modalRarity = document.getElementById('rarityModal');
const rarityForm = document.getElementById('rarityForm');

// Kunci rute API secara absolut agar kebal dari bentrok router
const API_URL = '/CardHaven/interface/super-admin-page/controller_rarity.php';

function openModalRarity() {
    document.getElementById('modalTitleRarity').innerHTML = 'ADD <span class="blue-text">RARITY</span>';
    document.getElementById('displayIDRarity').innerText = '';
    document.getElementById('formActionRarity').value = 'add';
    document.getElementById('logSectionRarity').style.display = 'none';
    rarityForm.reset();
    document.getElementById('inputIdRarity').value = "0";
    modalRarity.style.display = 'flex';
}

function confirmRestoreRarity(id) {
    if (confirm("Aktifkan kembali rarity ini?")) {
        const fd = new FormData();
        fd.append('action', 'restore');
        fd.append('id_rarity', id);
        fd.append('id_pengguna_js', getEmpId()); 
        
        fetch(API_URL,  { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else alert(res.message); 
        })
        .catch(err => alert("Gagal mengaktifkan kembali data. Periksa koneksi PHP Anda."));
    }
}

function openEditRarity(id) {
    fetch(`${API_URL}?get_detail=${id}`)
        .then(async res => {
            const rawText = await res.text(); 
            try {
                return JSON.parse(rawText); 
            } catch (e) {
                
                alert("CRASH PELADEN (GET):\n\n" + rawText);
                throw new Error("Transmisi bukan JSON");
            }
        })
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            
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
        })
        .catch(err => console.error("Proses Edit dihentikan:", err));
}

rarityForm.onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(rarityForm);
    formData.append('id_pengguna_js', getEmpId());

    fetch(API_URL, { method: 'POST', body: formData })
        .then(async res => {
            const rawText = await res.text();
            try {
                return JSON.parse(rawText);
            } catch (e) {
                alert("CRASH PELADEN (POST):\n\n" + rawText);
                throw new Error("Transmisi bukan JSON");
            }
        })
        .then(res => {
            if (res.status === 'success') location.reload();
            else alert(res.message);
        })
        .catch(err => console.error("Proses Simpan dihentikan:", err));
};

function confirmDeleteRarity(id) {
    if (confirm("Nonaktifkan rarity ini?")) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_rarity', id);
        fd.append('id_pengguna_js', getEmpId());
        
        fetch(API_URL, { method: 'POST', body: fd })
            .then(async res => {
                const rawText = await res.text();
                try {
                    return JSON.parse(rawText);
                } catch (e) {
                    alert("CRASH PELADEN (DELETE):\n\n" + rawText);
                    throw new Error("Transmisi bukan JSON");
                }
            })
            .then(res => {
                if (res.status === 'success') location.reload();
                else alert(res.message);
            })
            .catch(err => console.error("Proses Hapus dihentikan:", err));
    }
}

window.addEventListener('click', (e) => { 
    if (e.target == modalRarity) modalRarity.style.display = "none"; 
});