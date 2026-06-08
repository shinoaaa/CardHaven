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
                "message" => "Email harus diisi"
            ]);
        }

        if ($createdDate === '') {
            $this->respond([
                "status" => "error",
                "target" => "created_date",
                "message" => "Created date harus diisi"
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
                "message" => "Eksekusi Query Gagal: " . ($errors[0]['message'] ?? 'Unknown error')
            ]);
        }

        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$user) {
            $this->respond([
                "status" => "error",
                "target" => "email",
                "message" => "Email atau created date tidak cocok"
            ]);
        }

        $_SESSION['forgot_verified'] = true;
        $_SESSION['forgot_email'] = $email;

        $this->respond([
            "status" => "success",
            "message" => "Data cocok. Silakan masukkan password baru."
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
                "message" => "Silakan verifikasi data terlebih dahulu."
            ]);
        }

        if ($password === '') {
            $this->respond([
                "status" => "error",
                "target" => "password",
                "message" => "Password harus diisi"
            ]);
        }

        if (strlen($password) < 8) {
            $this->respond([
                "status" => "error",
                "target" => "password",
                "message" => "Password minimal 8 karakter"
            ]);
        }

        if ($confirmPassword === '') {
            $this->respond([
                "status" => "error",
                "target" => "confirm_password",
                "message" => "Konfirmasi password harus diisi"
            ]);
        }

        if ($password !== $confirmPassword) {
            $this->respond([
                "status" => "error",
                "target" => "confirm_password",
                "message" => "Konfirmasi password tidak cocok"
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
                "message" => "Gagal update password: " . ($errors[0]['message'] ?? 'Unknown error')
            ]);
        }

        sqlsrv_free_stmt($stmt);

        unset($_SESSION['forgot_verified']);
        unset($_SESSION['forgot_email']);

        $this->respond([
            "status" => "success",
            "message" => "Password berhasil diubah. Silakan login kembali."
        ]);
    }
}