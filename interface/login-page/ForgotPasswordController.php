<?php

class ForgotPasswordController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function respond($data)
    {
        echo json_encode($data);
        exit;
    }

    public function verifyIdentity($email, $createdDate)
    {
        $email = trim($email);
        $createdDate = trim($createdDate);

        if ($email === '') {
            $this->respond([
                "status" => "error",
                "target" => "email",
                "message" => "Please enter your email"
            ]);
        }

        if ($createdDate === '') {
            $this->respond([
                "status" => "error",
                "target" => "created_date",
                "message" => "Please enter your password"
            ]);
        }

        $sql = "
            SELECT id_pengguna, email, created_date
            FROM pengguna
            WHERE email = ?
              AND CONVERT(date, created_date) = ?
        ";

        $params = [$email, $createdDate];
        $stmt = sqlsrv_prepare($this->conn, $sql, $params);

        if (!$stmt) {
            $errors = sqlsrv_errors();
            $this->respond([
                "status" => "error",
                "target" => "general",
                "message" => "Query Error: " . ($errors[0]['message'] ?? 'Unknown error')
            ]);
        }

        if (!sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            $this->respond([
                "status" => "error",
                "target" => "general",
                "message" => "Querry Execution Failed: " . ($errors[0]['message'] ?? 'Unknown error')
            ]);
        }

        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$user) {
            $this->respond([
                "status" => "error",
                "target" => "email",
                "message" => "Email or create date is incorrect"
            ]);
        }

        $_SESSION['forgot_verified'] = true;
        $_SESSION['forgot_email'] = $email;

        $this->respond([
            "status" => "success",
            "message" => "The information matches. Please enter your new password."
        ]);
    }

    public function resetPassword($password, $confirmPassword)
    {
        $password = trim($password);
        $confirmPassword = trim($confirmPassword);

        if (empty($_SESSION['forgot_verified']) || empty($_SESSION['forgot_email'])) {
            $this->respond([
                "status" => "error",
                "target" => "general",
                "message" => "Please verify the information first."
            ]);
        }

        if ($password === '') {
            $this->respond([
                "status" => "error",
                "target" => "password",
                "message" => "Please enter your password"
            ]);
        }

        if (strlen($password) < 8 || strlen($password) > 12) {
            $this->respond([
                "status" => "error",
                "target" => "password",
                "message" => "Password must be 8 - 12 characters long"
            ]);
        }

        if ($confirmPassword === '') {
            $this->respond([
                "status" => "error",
                "target" => "confirm_password",
                "message" => "Confirm password cannot be empty"
            ]);
        }

        if ($password !== $confirmPassword) {
            $this->respond([
                "status" => "error",
                "target" => "confirm_password",
                "message" => "Confirm password does not match"
            ]);
        }

        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE pengguna SET password = ? WHERE email = ?";
        $params = [$hashedPassword, $_SESSION['forgot_email']];

        $stmt = sqlsrv_prepare($this->conn, $sql, $params);

        if (!$stmt) {
            $errors = sqlsrv_errors();
            $this->respond([
                "status" => "error",
                "target" => "general",
                "message" => "Query Error: " . ($errors[0]['message'] ?? 'Unknown error')
            ]);
        }

        if (!sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            $this->respond([
                "status" => "error",
                "target" => "general",
                "message" => "Password update failed: " . ($errors[0]['message'] ?? 'Unknown error')
            ]);
        }

        sqlsrv_free_stmt($stmt);

        unset($_SESSION['forgot_verified']);
        unset($_SESSION['forgot_email']);

        $this->respond([
            "status" => "success",
            "message" => "Your password has been successfully changed. Please log in again."
        ]);
    }
}