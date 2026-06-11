<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require __DIR__ . '/../../connection.php';

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

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(["status" => "error", "target" => "email", "message" => "Email or password is incorrect"]);
            sqlsrv_free_stmt($stmt);
            exit;
        }


        if ($remember) {
            ini_set('session.cookie_lifetime', 604800);
            ini_set('session.gc_maxlifetime', 604800);
        } else {
            ini_set('session.cookie_lifetime', 0);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        echo json_encode([
                            "status" => "success", 
                            "message" => "Login successful", 
                            "role" => $user['role'], 
                            "id_pengguna" => $user['id_pengguna'],
                            "username" => $user['username']
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