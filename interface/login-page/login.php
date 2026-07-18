<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require __DIR__ . '/../../connection.php';
    require_once __DIR__ . '/../../auth/session.php';

    if (!$conn) {
        echo json_encode([
            "status" => "error",
            "target" => "general",
            "message" => "The database connection failed."
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';

        if (empty($email)) {
            echo json_encode(["status" => "error", "target" => "email", "message" => "Please enter your email"]);
            exit;
        }
        if (empty($password)) {
            echo json_encode(["status" => "error", "target" => "password", "message" => "Please enter your password"]);
            exit;
        }

        // Hanya akun aktif & belum dihapus yang boleh login (akun di-delete/nonaktif ditolak).
        $sql = "SELECT id_pengguna, email, username, password, role FROM pengguna WHERE email = ? AND is_deleted = 0 AND status_akun = 1";
        $params = array($email);
        
        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if (!$stmt) {
            $errors = sqlsrv_errors();
            echo json_encode(["status" => "error", "target" => "general", "message" => "Query Error: " . $errors[0]['message']]);
            exit;
        }

        if (!sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            echo json_encode(["status" => "error", "target" => "general", "message" => "Query Execution Failed: " . $errors[0]['message']]);
            exit;
        }

        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(["status" => "error", "target" => "email", "message" => "Email or password is incorrect"]);
            sqlsrv_free_stmt($stmt);
            exit;
        }


        // Identitas disimpan HANYA di PHP session (server-side).
        // auth_login() sekalian me-regenerate session id untuk cegah session fixation.
        auth_login($user, $remember);

        // id_pengguna & role sengaja TIDAK dikirim balik ke browser — browser
        // tidak perlu tahu, dan tidak boleh dipakai sebagai dasar otorisasi.
        // Frontend cukup tahu ke mana harus diarahkan setelah login.
        echo json_encode([
                            "status" => "success",
                            "message" => "Login successful",
                            "redirect" => auth_is_staff()
                                ? "/CardHaven/dashboard/activity"
                                : "/CardHaven/home"
                        ]);
        sqlsrv_free_stmt($stmt);

    } else {
        echo json_encode(["status" => "error", "target" => "general", "message" => "Invalid request method"]);
    }
    sqlsrv_close($conn);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "target" => "general", "message" => "System Error: " . $e->getMessage()]);
}
?>