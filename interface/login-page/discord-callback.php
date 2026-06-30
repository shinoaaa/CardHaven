<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../../connection.php';


$redirect_uri  = "http://localhost/cardhaven/interface/login-page/discord-callback.php";

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Tahap A: Tukar CODE dengan Access Token (menggunakan cURL)
    $token_url = "https://discord.com/api/v10/oauth2/token";
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
    // API Discord memerlukan HTTP headers tambahan & User-Agent agar tidak terblokir
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: DiscordBot (https://github.com/discord/discord-api-docs, 1.0.0)'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // Tahap B: Meminta Profil Data Pengguna dari Discord API
        $userinfo_url = "https://discord.com/api/v10/users/@me";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userinfo_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Discord memerlukan header Authorization: Bearer
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'User-Agent: DiscordBot (https://github.com/discord/discord-api-docs, 1.0.0)'
        ]);
        $user_response = curl_exec($ch);
        curl_close($ch);

        $user_info = json_decode($user_response, true);

        // Pastikan akun Discord pengguna sudah terverifikasi email-nya
        if (isset($user_info['email']) && !empty($user_info['email'])) {
            $email    = $user_info['email'];
            $username = $user_info['username']; // Username Discord unik pengguna

            // Tahap C: Memeriksa Data Pengguna di SQL Server (sqlsrv)
            $sql = "SELECT id_pengguna, email, username, role FROM pengguna WHERE email = ?";
            $params = array($email);
            $stmt = sqlsrv_prepare($conn, $sql, $params);
            
            if (!$stmt || !sqlsrv_execute($stmt)) {
                die("Gagal mengeksekusi pemeriksaan akun.");
            }

            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($user) {
                // Akun sudah ada
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
                    die("Pendaftaran otomatis akun Discord gagal.");
                }
            }

            // Simpan sesi login backend PHP
            $_SESSION['id_pengguna'] = $id_pengguna;
            $_SESSION['role']        = $role;

            // Tahap D: Set Sesi Javascript & Pengalihan Halaman
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

                    // SINKRONKAN DENGAN SCRIPT.JS KAMU
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
                        iconColor: '#5865F2', // Warna khas ungu Discord (blurple)
                        title: 'Login successful!',
                        text: 'Welcome back to CardHaven with Discord.',
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
            echo "Email Discord Anda tidak ditemukan atau belum terverifikasi. Silakan verifikasi email Anda terlebih dahulu di aplikasi Discord.";
        }
    } else {
        echo "Gagal menukar kode autentikasi dari Discord.";
    }
} else {
    echo "Callback tidak valid.";
}
?>