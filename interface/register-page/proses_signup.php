<?php
header('Content-Type: application/json');

require_once '../../connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required!']);
        exit;
    }

    if (strlen($password) < 8 || strlen($password) > 12) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be between 8 and 12 characters!']);
        exit;
    }

    try {
        $sqlCheck = "SELECT id_pengguna FROM pengguna WHERE username = ? OR email = ?";
        $paramsCheck = array($username, $email);
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);

        if ($stmtCheck === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_has_rows($stmtCheck)) {
            echo json_encode(['status' => 'error', 'message' => 'Username or Email already registered!']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sqlInsert = "INSERT INTO pengguna (username, email, password) VALUES (?, ?, ?)";
        $paramsInsert = array($username, $email, $hashedPassword);
        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);

        if ($stmtInsert) {
            echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
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