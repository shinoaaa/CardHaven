<div class="main-content" style="display: flex; justify-content: center;">
    <div class="content-card">
        <div class="list-user">
            <button class="unselectedRole" id="superAdminButton">Super Admin</button>
            <button class="unselectedRole" id="AdminButton">Admin</button>
            <button class="unselectedRole" id="customernButton">Customer</button>
            <button class="unselectedRole" id="supplierButton">Supplier</button>
        </div>
        
        <?php 
            $role = isset($_GET['role']) ? (int)$_GET['role'] : 1; // Default ke 1 jika belum dipilih
            
            switch ($role) {
                case 1:
                    include __DIR__ . '/../../interface/user/indexSuperAdmin.php'; // Sesuaikan namanya jika ada
                    break;
                case 2:
                    include __DIR__ . '/../../interface/user/indexAdmin.php'; // Sesuaikan namanya jika ada
                    break;
                case 3:
                    include __DIR__ . '/../../interface/user/indexCustomer.php'; // Sesuaikan namanya jika ada
                    break;
                case 4:
                    include __DIR__ . '/../../interface/user/indexSupplier.php';
                    break;
                default:
                    include __DIR__ . '/../../interface/user/indexSuperAdmin.php';
                    break;
            }
        ?>
    </div>
</div>

<script>
    // Mengambil nilai 'role' dari URL saat ini untuk digunakan oleh JavaScript
    const urlParams = new URLSearchParams(window.location.search);
    let selected = parseInt(urlParams.get('role')) || 1; 

    const superAdminButton = document.getElementById('superAdminButton');
    const AdminButton = document.getElementById('AdminButton');
    const customernButton = document.getElementById('customernButton');
    const supplierButton = document.getElementById('supplierButton');

    const buttons = [superAdminButton, AdminButton, customernButton, supplierButton];

    // 1. Jalankan fungsi untuk menandai tombol aktif saat halaman pertama kali dimuat
    updateActiveButton();

    // 2. Tambahkan event listener klik pada tombol
    buttons.forEach((button, index) => {
        button.addEventListener('click', () => {
            let selectedRole = index + 1;
            
            // Alihkan halaman sambil membawa parameter 'role' baru ke URL
            window.location.search = `?role=${selectedRole}`;
        });
    });

    // Fungsi untuk mengatur class aktif (diperbaiki dari fungsi bawaan Anda)
    function updateActiveButton() {
        // Reset semua tombol ke unselectedRole
        buttons.forEach(button => {
            button.classList.remove('selectedRole');
            button.classList.add('unselectedRole'); // Huruf R kapital diperbaiki
        });

        // Tambah class selectedRole ke tombol yang sedang aktif
        if (selected >= 1 && selected <= 4) {
            buttons[selected - 1].classList.remove('unselectedRole');
            buttons[selected - 1].classList.add('selectedRole');
        }
    }
</script>