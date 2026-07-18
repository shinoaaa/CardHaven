<?php
require_once __DIR__ . '/../../auth/session.php';
auth_session_start();

require __DIR__ . '/../../connection.php';
require __DIR__ . '/../../diagnose.php';

$client_id = $_ENV['FACEBOOK_CLIENT_ID'];
$client_secret = $_ENV['FACEBOOK_CLIENT_SECRET'];
$redirect_uri  = "http://localhost/cardhaven/interface/login-page/facebook-callback.php";

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Tahap A: Tukar CODE dengan Access Token dari Facebook Graph API (menggunakan cURL)
    $token_url = "https://graph.facebook.com/v20.0/oauth/access_token";
    $post_fields = [
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri
    ];

    $ch = curl_init();
    // Facebook Graph API menggunakan metode GET query string untuk token penukaran
    curl_setopt($ch, CURLOPT_URL, $token_url . '?' . http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // Tahap B: Meminta Profil Data Pengguna dari Graph API
        $userinfo_url = "https://graph.facebook.com/v20.0/me?fields=id,name,email&access_token=" . $access_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userinfo_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_response = curl_exec($ch);
        curl_close($ch);

        $user_info = json_decode($user_response, true);

        // Memastikan akun Facebook memiliki email (beberapa user mendaftar FB pakai HP)
        if (isset($user_info['email']) && !empty($user_info['email'])) {
            $email    = $user_info['email'];
            // Generate username dari nama facebook (hilangkan spasi & kecilkan huruf)
            $username = strtolower(str_replace(' ', '', $user_info['name'])); 

            // Tahap C: Memeriksa Data Pengguna di SQL Server (sqlsrv)
            $sql = "SELECT id_pengguna, email, username, role FROM pengguna WHERE email = ?";
            $params = array($email);
            $stmt = sqlsrv_prepare($conn, $sql, $params);
            
            if (!$stmt || !sqlsrv_execute($stmt)) {
                die("Failed to carry out the account check.");
            }

            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($user) {
                // Akun sudah terdaftar
                $id_pengguna = $user['id_pengguna'];
                $username    = $user['username'];
                $role        = $user['role'];
            } else {
                // Registrasi Otomatis karena akun belum ada
                $role = 0; // Default role
                $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

                $insert_sql = "INSERT INTO pengguna (email, username, password, role) OUTPUT INSERTED.id_pengguna VALUES (?, ?, ?, ?)";
                $insert_params = array($email, $username, $random_password, $role);
                $insert_stmt = sqlsrv_prepare($conn, $insert_sql, $insert_params);

                if ($insert_stmt && sqlsrv_execute($insert_stmt)) {
                    $inserted_user = sqlsrv_fetch_array($insert_stmt, SQLSRV_FETCH_ASSOC);
                    $id_pengguna = $inserted_user['id_pengguna'];
                } else {
                    die("Automatic registration of a Facebook account has failed.");
                }
            }

            // Simpan sesi login backend PHP. Identitas tidak dititipkan ke browser.
            auth_login([
                'id_pengguna' => $id_pengguna,
                'role'        => $role,
                'username'    => $username,
                'email'       => $email,
            ]);

            // Tahap D: Pengalihan Halaman sesuai role dari session.
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Processing...</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            </head>
            <body>
                <script>
                    // Tujuan redirect ditentukan server dari role di session.
                    const redirectTo = "<?php echo auth_is_staff() ? '/CardHaven/dashboard/activity' : '/CardHaven/home'; ?>";

                    Swal.fire({
                        icon: 'success',
                        iconColor: '#1877F2', // Warna khas biru Facebook
                        title: 'Login successful!',
                        text: 'Welcome back to CardHaven with Facebook.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.replace(redirectTo);
                    });
                </script>
            </body>
            </html>
            <?php
            exit;
        } else {
            echo "The Facebook email address could not be found or is not permitted. Please ensure your Facebook account includes a valid email address.";
        }
    } else {
        echo "Failed to exchange the authentication code from Facebook.";
    }
} else {
    echo "Invalid callback.";
}
?>