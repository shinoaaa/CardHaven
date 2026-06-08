<?php
require_once 'connection.php'; 

$sql = "SELECT id_pengguna, password FROM dbo.pengguna";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) die(print_r(sqlsrv_errors(), true));

echo "Memulai hashing...<br>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = $row['id_pengguna'];
    $plain_password = $row['password'];

    // Cek apakah password sudah di-hash (biasanya hash dimulai dengan $)
    if (strpos($plain_password, '$2y$') === 0) {
        echo "ID $id sudah di-hash, dilewati.<br>";
        continue;
    }

    // Buat Hash baru
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

    // Update ke database
    $update_sql = "UPDATE dbo.pengguna SET password = ? WHERE id_pengguna = ?";
    sqlsrv_query($conn, $update_sql, [$hashed_password, $id]);

    echo "ID $id berhasil di-hash.<br>";
}

echo "Selesai! Hapus file ini dari server demi keamanan.";
?>