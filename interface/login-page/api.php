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
            "message" => "Koneksi database gagal."
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            "status" => "error",
            "target" => "general",
            "message" => "Metode request tidak diizinkan"
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
            "message" => "Action tidak valid"
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