<?php
// Contoh koneksi (Sesuaikan dengan file koneksi Anda)
// include 'config/db.php'; 

/**
 * Fungsi untuk mengambil data dengan urutan:
 * 1. Status Aktif (1) di depan, Inaktif (0) di belakang
 * 2. Urut berdasarkan ID
 */
function fetchData($conn, $table) {
    // Sesuaikan query jika nama kolom status di tiap tabel berbeda
    $query = "SELECT * FROM $table ORDER BY status DESC, id ASC LIMIT 7"; 
    // LIMIT 7 untuk Products, untuk yang lain mungkin butuh pagination logic
    return mysqli_query($conn, $query);
}

// Dummy Data untuk simulasi jika DB belum siap (Hapus jika DB sudah konek)
$products = [
    ['name'=>'Rayquaza V', 'pid'=>'#CRD-1003', 'game'=>'Pokemon', 'set'=>'Scarlet and Violet', 'stock'=>82, 'cond'=>'NM', 'price'=>'$10.22', 'status'=>1],
    // ... ulangi sampai 7 data
];
// Untuk implementasi asli gunakan: $resProducts = fetchData($conn, 'products');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Super Admin</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script>
        (function() {
            const token = localStorage.getItem("token") || sessionStorage.getItem("token");
            const role = localStorage.getItem("role") || sessionStorage.getItem("role");
            
            // Jika token kosong ATAU role bukan 1 (Superadmin), tendang!
            if (!token || role !== "1") {
                window.location.replace("/CardHaven");
            }
        })();
    </script>
</head>
<body>
    <div class="container" style="justify-content: flex-start; align-items: flex-start;">
        <div class="sideBar">
            <?php include 'components/sideBar.php' ?>
        </div>
        
        <div class="main-content">
            
            <!-- 1. TABEL PRODUK (Fixed Height for 7 Rows) -->
            <div class="content-card">
                <div class="card-title-row">
                    <h2 class="coolveticaa">Products</h2>
                    <button class="btn-add-green">+ Add Product</button>
                </div>
                
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Product ID</th>
                            <th>Game</th>
                            <th>Set</th>
                            <th>Stock</th>
                            <th>Condition</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Loop data produk (Ganti dengan fetch dari mysqli)
                        for($i=0; $i<7; $i++): 
                            $item = $products[0]; // Pakai dummy
                        ?>
                        <tr>
                            <td><?= $item['name'] ?></td>
                            <td><?= $item['pid'] ?></td>
                            <td><?= $item['game'] ?></td>
                            <td><?= $item['set'] ?></td>
                            <td><?= $item['stock'] ?></td>
                            <td><?= $item['cond'] ?></td>
                            <td><?= $item['price'] ?></td>
                            <td>
                                <div class="btn-action-group">
                                    <button class="btn-edit-icon">✏️</button>
                                    <button class="btn-delete-icon">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <div class="pagination-container">
                    <span class="page-num"> < </span>
                    <span class="page-num">1</span>
                    <span class="page-num active">2</span>
                    <span class="page-num">3</span>
                    <span class="page-num">...</span>
                    <span class="page-num">82</span>
                    <span class="page-num"> > </span>
                </div>
            </div>

            <!-- 2. WRAPPER MASTER DATA -->
            <div class="master-data-wrapper">
                
                <!-- TABEL GAME -->
                <div class="master-table-card">
                    <div>
                        <div class="card-title-row">
                            <h2 class="coolveticaa" style="font-size: 1.2rem;">Game</h2>
                            <button class="btn-add-green">+ Add Game</button>
                        </div>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Game Name</th>
                                    <th>Game ID</th>
                                    <th>Developer</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for($i=0; $i<3; $i++): ?>
                                <tr>
                                    <td>Pokemon</td>
                                    <td>GM-099</td>
                                    <td>Stepanus</td>
                                    <td>Active</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon">✏️</button>
                                            <button class="btn-delete-icon">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <span class="page-num"> < </span>
                        <span class="page-num">1</span>
                        <span class="page-num active">2</span>
                        <span class="page-num"> > </span>
                    </div>
                </div>

                <!-- TABEL SET -->
                <div class="master-table-card">
                    <div>
                        <div class="card-title-row">
                            <h2 class="coolveticaa" style="font-size: 1.2rem;">Set</h2>
                            <button class="btn-add-green">+ Add Set</button>
                        </div>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Set Name</th>
                                    <th>Set ID</th>
                                    <th>Game</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Scarlet & Violet Primastic</td>
                                    <td>SET-001</td>
                                    <td>Pokemon</td>
                                    <td>Active</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon">✏️</button>
                                            <button class="btn-delete-icon">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <span class="page-num"> < </span>
                        <span class="page-num">1</span>
                        <span class="page-num active">2</span>
                        <span class="page-num"> > </span>
                    </div>
                </div>

                <!-- TABEL RARITY -->
                <div class="master-table-card">
                    <div>
                        <div class="card-title-row">
                            <h2 class="coolveticaa" style="font-size: 1.2rem;">Rarity</h2>
                            <button class="btn-add-green">+ Add Rarity</button>
                        </div>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Rarity Name</th>
                                    <th>Rarity ID</th>
                                    <th>Game</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Super Rare</td>
                                    <td>RAR-001</td>
                                    <td>Pokemon</td>
                                    <td>Active</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon">✏️</button>
                                            <button class="btn-delete-icon">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <span class="page-num"> < </span>
                        <span class="page-num">1</span>
                        <span class="page-num active">2</span>
                        <span class="page-num"> > </span>
                    </div>
                </div>

            </div> <!-- End Master Wrapper -->

        </div> <!-- End Main Content -->
    </div>

    <script src="game_script.js"></script>
</body>
</html>