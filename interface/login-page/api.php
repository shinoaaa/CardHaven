<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require __DIR__ . '/../../connection.php';
require __DIR__ . '/ForgotPasswordController.php';

session_start();

try {
    if (!$conn) {
        echo json_encode([
            "status" => "error",
            "target" => "general",
            "message" => "The database connection failed."
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            "status" => "error",
            "target" => "general",
            "message" => "The request method is not allowed"
        ]);
        exit;
    }

    $controller = new ForgotPasswordController($conn);
    $action = $_POST['action'] ?? '';

    if ($action === 'verify') {
        $controller->verifyIdentity(
            $_POST['email'] ?? '',
            $_POST['created_date'] ?? ''
        );
    } elseif ($action === 'reset') {
        $controller->resetPassword(
            $_POST['password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );
    } else {
        echo json_encode([
            "status" => "error",
            "target" => "general",
            "message" => "Action invalid"
        ]);
        exit;
    }

    sqlsrv_close($conn);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "target" => "general",
        "message" => "System Error: " . $e->getMessage()
    ]);
}