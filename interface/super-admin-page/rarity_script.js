const modalRarity = document.getElementById('rarityModal');
const rarityForm = document.getElementById('rarityForm');

// Murni mengatur UI karena proses simpan (POST) sudah ditangani langsung oleh PHP di index.php
function openModalRarity() {
    document.getElementById('modalTitleRarity').innerText = "Add New Rarity";
    document.getElementById('inputIdRarity').value = "0";
    document.getElementById('inputGameRarity').value = "";
    document.getElementById('inputNamaRarity').value = "";
    document.getElementById('inputKodeRarity').value = "";
    modalRarity.style.display = 'flex';
}

function closeModalRarity() {
    modalRarity.style.display = 'none';
}

// Kerangka fungsi Edit menyesuaikan tombol di tabel Rarity
function openEditRarity(id) {
    // Nantinya logika fetch() seperti milik temanmu bisa dimasukkan ke sini
    alert("Fungsi Edit untuk Rarity ID: " + id + " siap diimplementasikan.");
}

// Kerangka fungsi Delete menyesuaikan tombol di tabel Rarity
function confirmDeleteRarity(id) {
    if (confirm("Nonaktifkan rarity ini?")) {
        // Nantinya logika penghapusan via fetch() atau postback bisa dimasukkan ke sini
        alert("Fungsi Delete untuk Rarity ID: " + id + " siap diimplementasikan.");
    }
}

// Standar penutupan modal jika klik di luar area kotak (Menyamai JS Game)
window.addEventListener('click', (e) => { 
    if (e.target == modalRarity) modalRarity.style.display = "none"; 
});