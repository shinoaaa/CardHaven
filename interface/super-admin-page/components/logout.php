<div id='loginn' style='display: none; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.55); position: fixed; left: 0; top: 0; justify-content: center; align-items: center; z-index: 1000;'>
    <div style="width: 300px; padding: 20px; background-color: white; border-radius: 8px; text-align: center;">
        <h1 style="font-size: 1.2rem; color: black;"> Are you sure want to exit? </h1>
        <div style="margin-top: 20px;">
            <button id='cancel' style="padding: 8px 16px; cursor: pointer;"> Cancel </button>
            <button id='confirm' style="padding: 8px 16px; cursor: pointer; background-color: #ff4d4d; color: white; border: none; border-radius: 4px;"> Confirm </button>
        </div>
    </div>
</div>

<script>
    // 1. Ambil semua elemen yang dibutuhkan
    const modalCing = document.getElementById('loginn');
    const btnLogoutTrigger = document.getElementById('btnLogout'); // Ini ID yang kita tambahkan di <a> tadi
    const btnCancel = document.getElementById('cancel');
    const btnConfirm = document.getElementById('confirm');

    // --- LOGIKA MUNCULIN modalCing ---
    if (btnLogoutTrigger) {
        btnLogoutTrigger.addEventListener('click', (e) => {
            e.preventDefault(); // Biar gak refresh halaman
            modalCing.style.display = 'flex'; // Pakai flex supaya justify-content & align-items jalan
        });
    }

    // --- LOGIKA TUTUP modalCing ---
    const closemodalCing = () => {
        modalCing.style.display = 'none';
    };

    // Klik tombol Cancel
    btnCancel.addEventListener('click', closemodalCing);

    // Klik di luar area putih (di background hitam) untuk close
    modalCing.addEventListener('click', (e) => {
        if (e.target === modalCing) closemodalCing();
    });

    // --- LOGIKA PROSES LOGOUT ---
    btnConfirm.addEventListener('click', () => {
        // Hapus data storage
        localStorage.clear(); 
        sessionStorage.clear(); 
        
        // Opsional: Jika kamu pakai PHP Session, biasanya butuh hit file logout.php 
        // untuk session_destroy(). Tapi kalau cuma main LocalStorage, ini cukup:
        
        console.log("User logged out");
        window.location.href = '/CardHaven/home'; // Arahkan ke halaman login
    });
</script>