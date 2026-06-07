const modal = document.getElementById('gameModal');
const gameForm = document.getElementById('gameForm');

const URL_GAME = '/cardhaven/interface/super-admin-page/controller_game.php';
// Ambil ID Pengguna
const getEmpId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

document.querySelectorAll('#gameForm .modal-input').forEach(input => {
    input.addEventListener('input', function() { clearError(this); });
});

function openAddModal() {
    clearAllErrors('gameForm');
    document.getElementById('modalTitle').innerHTML = 'ADD <span class="blue-text">GAME</span>';
    document.getElementById('displayID').innerText = '';
    document.getElementById('formAction').value = 'add';
    document.getElementById('logSection').style.display = 'none';
    gameForm.reset();
    modal.style.display = 'flex';
}

function openEditModal(id) {
    fetch(`${URL_GAME}?get_detail=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.error) {
                alert("Error: " + JSON.stringify(data.error));
                return;
            }

            clearAllErrors('gameForm');
            document.getElementById('modalTitle').innerHTML = '<span class="blue-text">GAME</span> DETAIL';
            document.getElementById('displayID').innerText = 'GAM-' + id;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formID').value = id;
            
            document.getElementById('nama_game').value = data.nama_game;
            document.getElementById('developer').value = data.developer;

            document.getElementById('logSection').style.display = 'block';
            document.getElementById('createdBy').innerText = data.creator; 
            document.getElementById('createdDate').innerText = " (" + data.created_date + ")";
            document.getElementById('editedBy').innerText = data.modifier;
            document.getElementById('editedDate').innerText = data.modified_date !== '-' ? " (" + data.modified_date + ")" : "";
            
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


    const inputNama = document.getElementById('nama_game');
    const inputDev = document.getElementById('developer');

    let isValid = true;


    if (!inputNama.value.trim()) {
        showError(inputNama, "Nama game wajib diisi!");
        isValid = false;
    } else {
        clearError(inputNama);
    }


    if (!inputDev.value.trim()) {
        showError(inputDev, "Developer wajib diisi!");
        isValid = false;
    } else {
        clearError(inputDev);
    }


    if (isValid) {
        const formData = new FormData(gameForm);
        formData.append('id_pengguna_js', getEmpId());

        fetch(URL_GAME, { 
            method: 'POST', 
            body: formData 
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                location.reload();
            } else {
                
                alert(res.message);
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("Terjadi kesalahan sistem.");
        });
    }
};

function confirmDelete(id) {
    if (confirm("Nonaktifkan game ini?")) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_game', id);
        fd.append('id_pengguna_js', getEmpId());
        fetch(URL_GAME, { method: 'POST', body: fd })
        .then(res => res.json()).then(res => location.reload());
    }
}

function confirmRestore(id) {
    if (confirm("Aktifkan kembali game ini?")) {
        const fd = new FormData();
        fd.append('action', 'restore'); 
        fd.append('id_game', id);
        fd.append('id_pengguna_js', getEmpId());
        
        fetch(URL_GAME, { 
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