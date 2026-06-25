<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

header('Content-Type: application/json');

try {
    require_once '../../connection.php';
    
    if (!isset($conn) || !is_resource($conn)) {
        throw new Exception("Koneksi database tidak valid. Pastikan connection.php mendefinisikan \$conn sebagai resource sqlsrv_connect.");
    }
} catch (Throwable $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
    exit;
}

// Fungsi bantuan untuk menangkap detail error SQLSRV
function getSqlError() {
    $errors = sqlsrv_errors();
    return $errors[0]['message'] ?? 'Unknown SQL Server Error';
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'submit_buyback':
        try {
            
            if (!isset($_POST['nama_kartu']) || !is_array($_POST['nama_kartu'])) {
                throw new Exception("Data form tidak valid atau kosong.");
            }

            $id_customer = $_POST['id_pengguna'] ?? null;
            if (empty($id_customer)) {
                throw new Exception("Sesi pengguna terputus.");
            }
            $provider = $_POST['provider'] ?? '';
            $no_rekening = $_POST['no_rekening'] ?? '';
            $sqlUpdateBank = "UPDATE pengguna SET provider = ?, no_rekening = ? WHERE id_pengguna = ?";
            sqlsrv_query($conn, $sqlUpdateBank, [$provider, $no_rekening, $id_customer]);

            // Memulai transaksi menggunakan standar SQLSRV
            if (sqlsrv_begin_transaction($conn) === false) {
                throw new Exception(getSqlError());
            }
            
            $total_barang = count($_POST['nama_kartu']);
            $total_harga = isset($_POST['harga_beli']) && is_array($_POST['harga_beli']) ? array_sum($_POST['harga_beli']) : 0;
            $tanggal = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO pembelian_kartu (id_customer, tanggal_pembelian, total_barang, total_harga, status_pembelian, created_by, created_date) OUTPUT INSERTED.id_pembelian VALUES (?, ?, ?, ?, 0, ?, ?)";
            $params = [$id_customer, $tanggal, $total_barang, $total_harga, $id_customer, $tanggal];
            
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                throw new Exception("Gagal insert pembelian_kartu: " . getSqlError());
            }
            
            // Mengambil nilai OUTPUT INSERTED.id_pembelian
            sqlsrv_fetch($stmt);
            $id_pembelian = sqlsrv_get_field($stmt, 0);

            $uploadDir = '../../assets/image/buyback/';
            $dbPath = 'assets/image/buyback/'; 
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $sqlKartu = "INSERT INTO kartu_dibeli (id_pembelian, nama_kartu, foto_depan, foto_belakang, penawaran_customer, percobaan_penawaran) VALUES ( ?, ?, ?, ?, ?, 1)";

            for ($i = 0; $i < $total_barang; $i++) {
                $nama_kartu = $_POST['nama_kartu'][$i];
                $harga_beli = $_POST['harga_beli'][$i];
                
                if ($_FILES['foto_depan']['error'][$i] !== UPLOAD_ERR_OK || $_FILES['foto_belakang']['error'][$i] !== UPLOAD_ERR_OK) {
                    throw new Exception("File gambar rusak atau melampaui batas ukuran upload server.");
                }

                $fileNameDepan = uniqid('front_') . '_' . basename($_FILES['foto_depan']['name'][$i]);
                $fileNameBelakang = uniqid('back_') . '_' . basename($_FILES['foto_belakang']['name'][$i]);
                
                $uploadDepan = move_uploaded_file($_FILES['foto_depan']['tmp_name'][$i], $uploadDir . $fileNameDepan);
                $uploadBelakang = move_uploaded_file($_FILES['foto_belakang']['tmp_name'][$i], $uploadDir . $fileNameBelakang);

                if (!$uploadDepan || !$uploadBelakang) {
                    throw new Exception("Server gagal memindahkan file. Periksa hak akses folder (CHMOD).");
                }

                $pathDepan = $dbPath . $fileNameDepan;
                $pathBelakang = $dbPath . $fileNameBelakang;
                
                
                $paramsKartu = [$id_pembelian, $nama_kartu, $pathDepan, $pathBelakang, $harga_beli];
                $stmtKartu = sqlsrv_query($conn, $sqlKartu, $paramsKartu);
                
                if ($stmtKartu === false) {
                    throw new Exception("Gagal insert kartu_dibeli: " . getSqlError());
                }
            }

            sqlsrv_commit($conn);
            ob_clean();
            echo json_encode(["status" => "success", "message" => "Submission sent successfully!"]);
        } catch (Throwable $e) {
            sqlsrv_rollback($conn);
            ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'get_buyback_list':
        try {
            $role = $_GET['role'] ?? null;
            $id_pengguna = $_GET['id_pengguna'] ?? null;
            
            // Parameter Filter & Pagination
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? ''; 
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($role == 0) { 
                $whereClause .= " AND p.id_customer = ?";
                $params[] = $id_pengguna;
            } else if ($role != 2) {
                throw new Exception("Akses tidak diizinkan.");
            }

            // 1. Filter Pencarian
            if ($search !== '') {
                $whereClause .= " AND (c.username LIKE ? OR p.id_pembelian LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            // 2. Hitung Badge Status (Dihitung sebelum filter status aktif diterapkan)
            $sqlGroup = "SELECT p.status_pembelian, COUNT(*) as cnt FROM pembelian_kartu p LEFT JOIN pengguna c ON p.id_customer = c.id_pengguna $whereClause GROUP BY p.status_pembelian";
            $stmtGroup = sqlsrv_query($conn, $sqlGroup, $params);
            $statusCounts = [];
            if ($stmtGroup !== false) {
                while ($rowG = sqlsrv_fetch_array($stmtGroup, SQLSRV_FETCH_ASSOC)) {
                    $statusCounts[$rowG['status_pembelian']] = $rowG['cnt'];
                }
            }

            // 3. Filter Status Spesifik
            if ($status !== '') {
                $whereClause .= " AND p.status_pembelian = ?";
                $params[] = $status;
            }

            // 4. Hitung Total Data untuk Pagination
            $sqlCount = "SELECT COUNT(*) as total FROM pembelian_kartu p LEFT JOIN pengguna c ON p.id_customer = c.id_pengguna $whereClause";
            $stmtCount = sqlsrv_query($conn, $sqlCount, $params);
            $rowCount = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
            $totalData = $rowCount['total'];
            $totalPages = ceil($totalData / $limit);

            // 5. Ambil Data Utama dengan SQL Server Pagination (OFFSET FETCH)
            $sql = "SELECT p.*, c.username FROM pembelian_kartu p LEFT JOIN pengguna c ON p.id_customer = c.id_pengguna $whereClause ORDER BY p.created_date DESC OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) throw new Exception(getSqlError());
            
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                foreach ($row as $key => $val) {
                    if ($val instanceof DateTime) $row[$key] = $val->format('Y-m-d H:i:s');
                }
                $data[] = $row;
            }
            
            ob_clean();
            echo json_encode([
                "status" => "success", 
                "data" => $data,
                "pagination" => ["current_page" => $page, "total_pages" => $totalPages, "total_data" => $totalData],
                "status_counts" => $statusCounts
            ]);
        } catch (Throwable $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    case 'update_status':
        try {
            $id_pembelian = $_POST['id_pembelian'];
            $status_baru = $_POST['status'];
            $modified_by = $_POST['id_pengguna'];
            $tanggal = date('Y-m-d H:i:s');

            if (sqlsrv_begin_transaction($conn) === false) throw new Exception(getSqlError());

            $query = "UPDATE pembelian_kartu SET status_pembelian = ?, modified_by = ?, modified_date = ? ";
            $params = [$status_baru, $modified_by, $tanggal];

            if ($status_baru == 4 && isset($_POST['no_resi'])) {
                $query .= ", no_resi = ?, tanggal_pengiriman = ? ";
                array_push($params, $_POST['no_resi'], $tanggal);
            }

            // Jika status 3 (Offer Accepted), hitung ulang total harga final dari kartu_dibeli
            if ($status_baru == 3) {
                // 1. UPDATE harga_beli di tabel kartu_dibeli. Ambil penawaran admin, jika null ambil penawaran customer
                $sqlFinalKartu = "UPDATE kartu_dibeli SET harga_beli = ISNULL(penawaran_admin, penawaran_customer) WHERE id_pembelian = ?";
                if (sqlsrv_query($conn, $sqlFinalKartu, [$id_pembelian]) === false) throw new Exception(getSqlError());

                // 2. Kalkulasi total_harga transaksi
                $sqlSum = "SELECT SUM(harga_beli) as total_final FROM kartu_dibeli WHERE id_pembelian = ?";
                $stmtSum = sqlsrv_query($conn, $sqlSum, [$id_pembelian]);
                $rowSum = sqlsrv_fetch_array($stmtSum, SQLSRV_FETCH_ASSOC);
                
                $query .= ", total_harga = ? ";
                array_push($params, $rowSum['total_final']);
            }

            $query .= " WHERE id_pembelian = ?";
            array_push($params, $id_pembelian);

            if (sqlsrv_query($conn, $query, $params) === false) throw new Exception(getSqlError());
            
            sqlsrv_commit($conn);
            ob_clean();
            echo json_encode(["status" => "success", "message" => "Status updated successfully."]);
        } catch (Throwable $e) {
            sqlsrv_rollback($conn);
            ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'admin_negotiate':
        try {
            $id_pembelian = $_POST['id_pembelian'];
            $id_kartu = $_POST['id_kartu']; // Tambahkan ID Kartu
            $id_admin = $_POST['id_pengguna'];
            $penawaran_admin = $_POST['penawaran_admin'];
            $tanggal = date('Y-m-d H:i:s');

            if (sqlsrv_begin_transaction($conn) === false) throw new Exception(getSqlError());

            // Update hanya kartu spesifik
            $sqlKartu = "UPDATE kartu_dibeli SET penawaran_admin = ? WHERE id_kartu = ?";
            if (sqlsrv_query($conn, $sqlKartu, [$penawaran_admin, $id_kartu]) === false) throw new Exception(getSqlError());

            // Set status transaksi ke "Price Negotiation" (2)
            $sqlStatus = "UPDATE pembelian_kartu SET status_pembelian = 2, id_admin = ?, modified_by = ?, modified_date = ? WHERE id_pembelian = ?";
            sqlsrv_query($conn, $sqlStatus, [$id_admin, $id_admin, $tanggal, $id_pembelian]);

            sqlsrv_commit($conn);
            echo json_encode(["status" => "success"]);
        } catch (Throwable $e) {
            sqlsrv_rollback($conn);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    case 'customer_negotiate_item': // Case baru untuk per kartu
        try {
            $id_kartu = $_POST['id_kartu'];
            $id_pembelian = $_POST['id_pembelian'];
            $penawaran = $_POST['penawaran_customer'];
            
            // Cek attempt
            $sqlCek = "SELECT percobaan_penawaran FROM kartu_dibeli WHERE id_kartu = ?";
            $stmt = sqlsrv_query($conn, $sqlCek, [$id_kartu]);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row['percobaan_penawaran'] >= 3) throw new Exception("Max attempts reached for this card.");

            $sql = "UPDATE kartu_dibeli SET penawaran_customer = ?, percobaan_penawaran = percobaan_penawaran + 1, penawaran_admin = NULL WHERE id_kartu = ?";
            sqlsrv_query($conn, $sql, [$penawaran, $id_kartu]);
            
            // Kembalikan status transaksi ke Under Review (1) agar admin tahu ada tawaran masuk
            sqlsrv_query($conn, "UPDATE pembelian_kartu SET status_pembelian = 1 WHERE id_pembelian = ?", [$id_pembelian]);

            echo json_encode(["status" => "success"]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;case 'admin_negotiate':
        try {
            $id_kartu = $_POST['id_kartu']; // ID Kartu spesifik
            $penawaran_admin = $_POST['penawaran_admin'];

            // HANYA update harga admin per kartu, TIDAK mengubah status_pembelian transaksi
            $sqlKartu = "UPDATE kartu_dibeli SET penawaran_admin = ? WHERE id_kartu = ?";
            if (sqlsrv_query($conn, $sqlKartu, [$penawaran_admin, $id_kartu]) === false) {
                throw new Exception(getSqlError());
            }

            echo json_encode(["status" => "success"]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'customer_negotiate_item': 
        try {
            $id_kartu = $_POST['id_kartu'];
            $penawaran = $_POST['penawaran_customer'];
            
            // Cek attempt
            $sqlCek = "SELECT percobaan_penawaran FROM kartu_dibeli WHERE id_kartu = ?";
            $stmt = sqlsrv_query($conn, $sqlCek, [$id_kartu]);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row['percobaan_penawaran'] >= 3) throw new Exception("Max attempts reached for this card.");

            // Update penawaran customer dan tambah percobaan, tapi JANGAN ubah status transaksi di sini
            $sql = "UPDATE kartu_dibeli SET penawaran_customer = ?, percobaan_penawaran = percobaan_penawaran + 1 WHERE id_kartu = ?";
            sqlsrv_query($conn, $sql, [$penawaran, $id_kartu]);

            echo json_encode(["status" => "success"]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;case 'customer_negotiate_item': 
        try {
            $id_pengguna = $_POST['id_pengguna'] ?? null;
            $id_pembelian = $_POST['id_pembelian'] ?? null; 

            // Verifikasi Otorisasi Transaksi
            $sqlAuth = "SELECT id_customer FROM pembelian_kartu WHERE id_pembelian = ?";
            $stmtAuth = sqlsrv_query($conn, $sqlAuth, [$id_pembelian]);
            $auth = sqlsrv_fetch_array($stmtAuth, SQLSRV_FETCH_ASSOC);
            
            if (!$auth || $auth['id_customer'] != $id_pengguna) {
                throw new Exception("Akses Ditolak: Memanipulasi transaksi milik orang lain.");
            }
            $id_kartu = $_POST['id_kartu'];
            $penawaran = $_POST['penawaran_customer'];
            
            $sqlCek = "SELECT percobaan_penawaran FROM kartu_dibeli WHERE id_kartu = ?";
            $stmt = sqlsrv_query($conn, $sqlCek, [$id_kartu]);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row['percobaan_penawaran'] >= 3) throw new Exception("Max attempts reached for this card.");

            $sql = "UPDATE kartu_dibeli SET penawaran_customer = ?, percobaan_penawaran = percobaan_penawaran + 1, penawaran_admin = NULL WHERE id_kartu = ?";
            sqlsrv_query($conn, $sql, [$penawaran, $id_kartu]);
            
            // HAPUS QUERY UPDATE STATUS_PEMBELIAN DI SINI

            echo json_encode(["status" => "success"]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    case 'customer_accept_item': // Case baru untuk setuju per kartu
        try {
            $id_pengguna = $_POST['id_pengguna'] ?? null;
            $id_pembelian = $_POST['id_pembelian'] ?? null; 

            // Verifikasi Otorisasi Transaksi
            $sqlAuth = "SELECT id_customer FROM pembelian_kartu WHERE id_pembelian = ?";
            $stmtAuth = sqlsrv_query($conn, $sqlAuth, [$id_pembelian]);
            $auth = sqlsrv_fetch_array($stmtAuth, SQLSRV_FETCH_ASSOC);
            
            if (!$auth || $auth['id_customer'] != $id_pengguna) {
                throw new Exception("Akses Ditolak: Memanipulasi transaksi milik orang lain.");
            }
            $id_kartu = $_POST['id_kartu'];
            $harga_final = $_POST['harga_final'];
            
            // Kita tandai dengan menyamakan penawaran_customer dengan penawaran_admin
            $sql = "UPDATE kartu_dibeli SET penawaran_customer = ? WHERE id_kartu = ?";
            sqlsrv_query($conn, $sql, [$harga_final, $id_kartu]);
            
            echo json_encode(["status" => "success"]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'update_address': 
        try {
            $id_pembelian = $_POST['id_pembelian'] ?? null;
            $alamat = $_POST['alamat_retur'] ?? '';
            $id_pengguna = $_POST['id_pengguna'] ?? null;

            // 1. Verifikasi Otorisasi Transaksi (Mencegah IDOR)
            $sqlAuth = "SELECT id_customer FROM pembelian_kartu WHERE id_pembelian = ?";
            $stmtAuth = sqlsrv_query($conn, $sqlAuth, [$id_pembelian]);
            $auth = sqlsrv_fetch_array($stmtAuth, SQLSRV_FETCH_ASSOC);
            
            if (!$auth || $auth['id_customer'] != $id_pengguna) {
                throw new Exception("Akses Ditolak: Anda tidak memiliki izin untuk mengubah transaksi ini.");
            }

            // 2. Eksekusi Update Alamat
            $sql = "UPDATE pembelian_kartu SET alamat = ? WHERE id_pembelian = ?"; 
            $stmt = sqlsrv_query($conn, $sql, ["Return Address: " . $alamat, $id_pembelian]);
            
            if ($stmt === false) {
                throw new Exception(getSqlError());
            }

            echo json_encode(["status" => "success", "message" => "Return address submitted successfully."]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    case 'customer_negotiate':
        try {
            $id_pembelian = $_POST['id_pembelian'];
            $penawaran_customer = $_POST['penawaran_customer'];
            $tanggal = date('Y-m-d H:i:s');

            if (sqlsrv_begin_transaction($conn) === false) throw new Exception(getSqlError());

            $sqlCek = "SELECT MAX(percobaan_penawaran) as attempt FROM kartu_dibeli WHERE id_pembelian = ?";
            $stmtCek = sqlsrv_query($conn, $sqlCek, [$id_pembelian]);
            $row = sqlsrv_fetch_array($stmtCek, SQLSRV_FETCH_ASSOC);
            
            // Validasi maksimal 3 kali tawar
            if ($row['attempt'] >= 3) {
                throw new Exception("Maximum negotiation limit (3 times) reached.");
            }

            $sqlKartu = "UPDATE kartu_dibeli SET penawaran_customer = ?, percobaan_penawaran = percobaan_penawaran + 1 WHERE id_pembelian = ?";
            if (sqlsrv_query($conn, $sqlKartu, [$penawaran_customer, $id_pembelian]) === false) throw new Exception(getSqlError());

            $sqlStatus = "UPDATE pembelian_kartu SET status_pembelian = 1, modified_date = ? WHERE id_pembelian = ?";
            if (sqlsrv_query($conn, $sqlStatus, [$tanggal, $id_pembelian]) === false) throw new Exception(getSqlError());

            sqlsrv_commit($conn);
            ob_clean();
            echo json_encode(["status" => "success", "message" => "Counter-offer submitted."]);
        } catch (Throwable $e) {
            sqlsrv_rollback($conn);
            ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'get_detail':
        try {
            $id_pembelian = $_GET['id_pembelian'];
            $role = $_GET['role'] ?? null;
            $id_pengguna = $_GET['id_pengguna'] ?? null;
            
            $sqlPem = "SELECT p.*, c.username, c.no_rekening FROM pembelian_kartu p LEFT JOIN pengguna c ON p.id_customer = c.id_pengguna WHERE p.id_pembelian = ?";
            $params = [$id_pembelian];

            // Proteksi IDOR: Jika Customer, pastikan transaksi ini diverifikasi miliknya
            if ($role === '0') {
                $sqlPem .= " AND p.id_customer = ?";
                $params[] = $id_pengguna;
            }

            $stmtPem = sqlsrv_query($conn, $sqlPem, $params);
            if ($stmtPem === false) throw new Exception(getSqlError());
            
            $pembelian = sqlsrv_fetch_array($stmtPem, SQLSRV_FETCH_ASSOC);
            
            // Eksekusi mati (terminate) jika data tidak valid atau bukan milik pengguna
            if (!$pembelian) {
                throw new Exception("Akses Ditolak: Transaksi tidak ditemukan atau Anda tidak memiliki izin.");
            }
            
            if ($pembelian['tanggal_pembelian'] instanceof DateTime) {
                $pembelian['tanggal_pembelian'] = $pembelian['tanggal_pembelian']->format('Y-m-d H:i:s');
            }

            $sqlKartu = "SELECT * FROM kartu_dibeli WHERE id_pembelian = ?";
            $stmtKartu = sqlsrv_query($conn, $sqlKartu, [$id_pembelian]);
            if ($stmtKartu === false) throw new Exception(getSqlError());
            
            $kartu = [];
            while ($row = sqlsrv_fetch_array($stmtKartu, SQLSRV_FETCH_ASSOC)) {
                $kartu[] = $row;
            }
            
            ob_clean();
            echo json_encode(["status" => "success", "data" => ["pembelian" => $pembelian, "kartu" => $kartu]]);
        } catch (Throwable $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    case 'admin_send_payment':
        try {
            $id_pembelian = $_POST['id_pembelian'];
            $modified_by = $_POST['id_pengguna'];
            $tanggal = date('Y-m-d H:i:s');

            if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Payment proof file is invalid or missing.");
            }

            // Buat folder khusus untuk bukti pembayaran jika belum ada
            $uploadDir = '../../assets/image/buyback/payment/';
            $dbPath = 'assets/image/buyback/payment/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            // Bersihkan nama file agar tidak kepanjangan (kolom db varchar(100))
            $fileExtension = pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('pay_') . '.' . $fileExtension;
            
            if (!move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $uploadDir . $fileName)) {
                throw new Exception("Failed to save the payment proof file.");
            }

            $pathBukti = $dbPath . $fileName;

            // Update status ke 7 (Payment Sent) dan simpan path gambar
            $sql = "UPDATE pembelian_kartu SET status_pembelian = 7, bukti_pembayaran = ?, modified_by = ?, modified_date = ? WHERE id_pembelian = ?";
            $stmt = sqlsrv_query($conn, $sql, [$pathBukti, $modified_by, $tanggal, $id_pembelian]);

            if ($stmt === false) throw new Exception(getSqlError());

            ob_clean();
            echo json_encode(["status" => "success", "message" => "Payment sent and proof uploaded."]);
        } catch (Throwable $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    case 'submit_return_address':
        $id_pengguna = $_POST['id_pengguna'] ?? null;
            $id_pembelian = $_POST['id_pembelian'] ?? null; 

            // Verifikasi Otorisasi Transaksi
            $sqlAuth = "SELECT id_customer FROM pembelian_kartu WHERE id_pembelian = ?";
            $stmtAuth = sqlsrv_query($conn, $sqlAuth, [$id_pembelian]);
            $auth = sqlsrv_fetch_array($stmtAuth, SQLSRV_FETCH_ASSOC);
            
            if (!$auth || $auth['id_customer'] != $id_pengguna) {
                throw new Exception("Akses Ditolak: Memanipulasi transaksi milik orang lain.");
            }
        $alamat = $_POST['alamat_retur'];
        // Simpan alamat ke kolom catatan atau kolom baru 'alamat_retur'
        $sql = "UPDATE pembelian_kartu SET catatan_admin = ? WHERE id_pembelian = ?";
        sqlsrv_query($conn, $sql, ["RETURN TO: " . $alamat, $id_pembelian]);
        echo json_encode(["status" => "success", "message" => "Address saved. Admin will ship back your cards."]);
        break;
    case 'get_user_bank':
        try {
            $id_pengguna = $_GET['id_pengguna'] ?? null;
            $sql = "SELECT provider, no_rekening FROM pengguna WHERE id_pengguna = ?";
            $stmt = sqlsrv_query($conn, $sql, [$id_pengguna]);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            ob_clean();
            echo json_encode(["status" => "success", "data" => $row]);
        } catch (Throwable $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
}
?>