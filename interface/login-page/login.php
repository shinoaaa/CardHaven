<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require __DIR__ . '/../../connection.php';

    if (!$conn) {
        echo json_encode([
            "status" => "error",
            "target" => "general",
            "message" => "Koneksi database gagal."
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';

        if (empty($email)) {
            echo json_encode(["status" => "error", "target" => "email", "message" => "Email harus diisi"]);
            exit;
        }
        if (empty($password)) {
            echo json_encode(["status" => "error", "target" => "password", "message" => "Password harus diisi"]);
            exit;
        }

        $sql = "SELECT id_pengguna, email, username, password, role FROM pengguna WHERE email = ?";
        $params = array($email);
        
        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if (!$stmt) {
            $errors = sqlsrv_errors();
            echo json_encode(["status" => "error", "target" => "general", "message" => "Query Error: " . $errors[0]['message']]);
            exit;
        }

        if (!sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            echo json_encode(["status" => "error", "target" => "general", "message" => "Eksekusi Query Gagal: " . $errors[0]['message']]);
            exit;
        }

        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["status" => "error", "target" => "email", "message" => "Email tidak terdaftar"]);
            sqlsrv_free_stmt($stmt);
            exit;
        }

        if ($password !== $user['password']) {
            echo json_encode(["status" => "error", "target" => "password", "message" => "Password yang dimasukkan salah"]);
            sqlsrv_free_stmt($stmt);
            exit;
        }

        // Sinkronisasi umur session di server dengan JS Storage
        if ($remember) {
            ini_set('session.cookie_lifetime', 604800);
            ini_set('session.gc_maxlifetime', 604800);
        } else {
            ini_set('session.cookie_lifetime', 0);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // =========================================================================
        $maskedToken = bin2hex(random_bytes(16));

        $_SESSION['isLoggedIn'] = true;
        $_SESSION['id_pengguna'] = $user['id_pengguna'];
        $_SESSION['userEmail'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['userToken'] = $maskedToken;

        echo json_encode([
                            "status" => "success", 
                            "message" => "Login sukses", 
                            "token" => $maskedToken, 
                            "role" => $user['role'], 
                            "id_pengguna" => $user['id_pengguna'],
                            "username" => $user['username']
                        ]);
        sqlsrv_free_stmt($stmt);

    } else {
        echo json_encode(["status" => "error", "target" => "general", "message" => "Metode request tidak diizinkan"]);
    }
    sqlsrv_close($conn);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "target" => "general", "message" => "System Error: " . $e->getMessage()]);
}
?>