<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../../connection.php';
$client_id;
$client_secret;
$redirect_uri  = "http://localhost/cardhaven/interface/login-page/google-callback.php";

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Tahap A: Tukar CODE dengan Access Token (menggunakan cURL) [1.1.1]
    $token_url = "https://oauth2.googleapis.com/token";
    $post_fields = [
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // Tahap B: Meminta Profil Data Pengguna dari Google API [1.1.1]
        $userinfo_url = "https://www.googleapis.com/oauth2/v3/userinfo?access_token=" . $access_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userinfo_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_response = curl_exec($ch);
        curl_close($ch);

        $user_info = json_decode($user_response, true);

        $email = $user_info['email'];
        $nama  = $user_info['name'];

        // Tahap C: Memeriksa Data Pengguna di SQL Server (sqlsrv) [1.1.1]
        $sql = "SELECT id_pengguna, email, username, role FROM pengguna WHERE email = ?";
        $params = array($email);
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        
        if (!$stmt || !sqlsrv_execute($stmt)) {
            die("Failed to carry out the account check.");
        }

        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($user) {
            // Pengguna sudah terdaftar di database, ambil nilainya [1.1.1]
            $id_pengguna = $user['id_pengguna'];
            $username    = $user['username'];
            $role        = $user['role'];
        } else {
            // Pengguna belum terdaftar, kita daftarkan otomatis (Register Otomatis) [1.1.1]
            $username = explode('@', $email)[0]; // Ambil nama depan email sebagai username
            $role     = 0; // Default role
            
            // Kami generate password acak yang tidak mungkin ditebak agar field database tetap aman terisi 
            $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

            // query INSERT sqlsrv (Kami menggunakan OUTPUT INSERTED untuk mendapatkan ID baru dari server)
            $insert_sql = "INSERT INTO pengguna (email, username, password, role) OUTPUT INSERTED.id_pengguna VALUES (?, ?, ?, ?)";
            $insert_params = array($email, $username, $random_password, $role);
            $insert_stmt = sqlsrv_prepare($conn, $insert_sql, $insert_params);

            if ($insert_stmt && sqlsrv_execute($insert_stmt)) {
                $inserted_user = sqlsrv_fetch_array($insert_stmt, SQLSRV_FETCH_ASSOC);
                $id_pengguna = $inserted_user['id_pengguna'];
            } else {
                die("Automatic Google account registration failed.");
            }
        }

        // Simpan sesi login di sisi server (backend PHP)
        $_SESSION['id_pengguna'] = $id_pengguna;
        $_SESSION['role']        = $role;

        // Tahap D: Set Sesi Javascript & Pengalihan Halaman
        // Mengeluarkan kode JS agar data tersimpan di sessionStorage & localStorage sesuai logika script.js
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Processing...</title>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                const emailInput = "<?php echo addslashes($email); ?>";
                const roleInput  = "<?php echo $role; ?>";
                const idInput    = "<?php echo $id_pengguna; ?>";
                const userInput  = "<?php echo addslashes($username); ?>";

                // Memasukkan session ke local & session storage agar kompatibel dengan sistem JavaScript kamu
                localStorage.setItem("userEmail", emailInput);
                localStorage.setItem("role", roleInput);
                localStorage.setItem("id_pengguna", idInput);
                localStorage.setItem("username", userInput);

                sessionStorage.setItem("userEmail", emailInput);
                sessionStorage.setItem("role", roleInput);
                sessionStorage.setItem("id_pengguna", idInput);
                sessionStorage.setItem("username", userInput);

                Swal.fire({
                    icon: 'success',
                    title: 'Login successful!',
                    text: 'Welcome back to CardHaven.',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    const role = parseInt(roleInput);
                    if (role === 1 || role === 2 || role === 3) {
                        window.location.replace("/CardHaven/dashboard/activity");
                    } else {
                        window.location.replace("/CardHaven/home");
                    }
                });
            </script>
        </body>
        </html>
        <?php
        exit;

    } else {
        echo "Failed to redeem the authentication code from Google.";
    }
} else {
    echo "Invalid callback.";
}
?>