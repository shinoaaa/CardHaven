<?php

//require database connection disini gak tau yang mana
// belum jalan ya ngab ini
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Enkripsi Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cek apakah email sudah terdaftar
    $sql_check = "SELECT email FROM customer WHERE email = ?";
    $params_check = array($email);
    $stmt_check = sqlsrv_query($conn, $sql_check, $params_check);

    if (sqlsrv_has_rows($stmt_check)) {
        echo json_encode(['status' => 'error', 'message' => 'Email sudah digunakan']);
    } else {
        // Simpan ke database
        $sql_insert = "INSERT INTO customer (nama, email, password) VALUES (?, ?, ?)";
        $params_insert = array($username, $email, $hashed_password);
        $stmt_insert = sqlsrv_query($conn, $sql_insert, $params_insert);

        if ($stmt_insert) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data']);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun</title>
    <link rel="stylesheet" href="../global.css">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Sisi Kiri: Gambar -->
            <div class="image-section">
                <div class="image-wrapper">
                    <img src="../../assets/image/login-image.svg" style="object-fit: cover; height: 100%;">
                </div>
            </div>

            <!-- Sisi Kanan: Form -->
            <div class="form-section">
                <div class="form-container">
                    <h1>Sign Up</h1>
                    
                    <div class="social-login">
                        <button class="social-btn"><img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google"></button>
                        <button class="social-btn"><img src="https://img.icons8.com/ios-filled/50/000000/github.png" alt="Github"></button>
                        <button class="social-btn"><img src="https://img.icons8.com/ios-filled/50/000000/mac-os.png" alt="Apple"></button>
                    </div>

                    <div class="divider">
                        <span>Or</span>
                    </div>

                    <form id="signupForm">
                        <div class="form-group">
                            <label>Username<span class="required">*</span></label>
                            <input type="text" name="username" placeholder="enter username..." required>
                        </div>
                        <div class="form-group">
                            <label>Email<span class="required">*</span></label>
                            <input type="email" name="email" placeholder="enter email..." required>
                        </div>
                        <div class="form-group">
                            <label>Password<span class="required">*</span></label>
                            <input type="password" name="password" id="password" placeholder="enter password..." required>
                        </div>
                        <div class="form-group">
                            <label>Confirm password<span class="required">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="confirm password" required>
                        </div>

                        <button type="submit" class="btn-signup">Sign Up</button>
                    </form>

                    <p class="footer-text">Have an account? <a href="login.php">Log in</a></p>
                    <p class="terms-text">By Proceeding you agree to our terms of use</p>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>