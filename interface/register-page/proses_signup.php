<?php
header('Content-Type: application/json');

// 1. Koneksi Database (Gunakan path relatif yang aman)
require_once '../../connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi!']);
        exit;
    }

    if (strlen($password) < 8 || strlen($password) > 12) {
        echo json_encode(['status' => 'error', 'message' => 'Password harus 8-12 karakter!']);
        exit;
    }

    try {
        // --- PROSES SQL SERVER ---

        // 1. Cek apakah username atau email sudah ada
        $sqlCheck = "SELECT id_customer FROM customer WHERE username = ? OR email = ?";
        $paramsCheck = array($username, $email);
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);

        if ($stmtCheck === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_has_rows($stmtCheck)) {
            echo json_encode(['status' => 'error', 'message' => 'Username atau Email sudah terdaftar!']);
            exit;
        }

        // 2. Hash Password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 3. Insert Data Baru
        $sqlInsert = "INSERT INTO customer (username, email, password) VALUES (?, ?, ?)";
        $paramsInsert = array($username, $email, $hashedPassword);
        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);

        if ($stmtInsert) {
            echo json_encode(['status' => 'success', 'message' => 'Registrasi berhasil']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ke database']);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Detail Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak diizinkan']);
}
?>