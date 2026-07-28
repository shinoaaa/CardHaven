<?php
require_once __DIR__ . '/../../../connection.php'; // Asumsi pemanggilan conn DB
require_once __DIR__ . '/../../../auth/session.php';
header('Content-Type: application/json');

// Notifikasi bersifat pribadi: id_pengguna SELALU diambil dari session,
// tidak pernah dari query string / form. Kalau tidak, user bisa membaca
// notifikasi milik orang lain hanya dengan mengganti angka di URL.
$user = auth_api_require_login();
$user_id = $user['id'];

$action = $_GET['action'] ?? '';

/**
 * Nama produk sebuah order, dipakai menggantikan "#<id>" di notifikasi lama.
 * Kepemilikan dicek supaya angka yang kebetulan cocok tidak membocorkan
 * pesanan orang lain. Mengembalikan '' kalau order-nya bukan milik user ini.
 */
function orderProductLabel($conn, int $userId, int $idPenjualan): string
{
    static $cache = [];
    if (array_key_exists($idPenjualan, $cache)) return $cache[$idPenjualan];

    $stmt = sqlsrv_query(
        $conn,
        "SELECT p.daftar_produk
           FROM dbo.udf_DaftarProdukPenjualan() p
           JOIN dbo.penjualan j ON j.id_penjualan = p.id_penjualan
          WHERE p.id_penjualan = ? AND j.id_pengguna = ?",
        [$idPenjualan, $userId]
    );

    $label = '';
    if ($stmt !== false && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        $daftar = array_values(array_filter(array_map('trim', explode(',', (string)($row['daftar_produk'] ?? '')))));
        if ($daftar) {
            $sisa  = count($daftar) - 1;
            $label = $sisa > 0
                ? $daftar[0] . ' and ' . $sisa . ' other item' . ($sisa > 1 ? 's' : '')
                : $daftar[0];
        }
    }
    return $cache[$idPenjualan] = $label;
}

/**
 * Notifikasi lama (dibuat objek database) masih menyisipkan "#<id_penjualan>".
 * PK database tidak boleh tampil di aplikasi, jadi angka itu ditukar dengan
 * nama produk pesanannya; kalau tidak bisa dipetakan, referensinya dibuang.
 */
function scrubNotifId($conn, int $userId, $text): string
{
    $out = preg_replace_callback(
        '/\s*#(\d+)/',
        function ($m) use ($conn, $userId) {
            $nama = orderProductLabel($conn, $userId, (int)$m[1]);
            return $nama === '' ? '' : ' ' . $nama;
        },
        (string)$text
    );
    // Rapikan spasi ganda / spasi sebelum tanda baca akibat penghapusan.
    $out = preg_replace('/ +/', ' ', $out);
    return trim(preg_replace('/ +([.,!?])/', '$1', $out));
}

/** Cek notifikasi benar-benar milik user yang sedang login. */
function mailBelongsToUser($conn, int $userId, $idNotif): bool
{
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetNotifikasi(?)}", array($userId));
    if ($stmt === false) return false;

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ((string)$row['id_notifikasi'] === (string)$idNotif) return true;
    }
    return false;
}

if ($action === 'getMails') {
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetNotifikasi(?)}", array($user_id));

    $mails = [];
    $unread_count = 0;
    if ($stmt !== false) {
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if($row['tanggal_notifikasi'] instanceof DateTime) {
                $row['tanggal_notifikasi'] = $row['tanggal_notifikasi']->format('d M Y');
            }
            if($row['status_notifikasi'] == 0) $unread_count++;
            // Tidak ada id primary key yang boleh sampai ke tampilan.
            $row['judul'] = scrubNotifId($conn, (int)$user_id, $row['judul'] ?? '');
            $row['isi']   = scrubNotifId($conn, (int)$user_id, $row['isi'] ?? '');
            $mails[] = $row;
        }
    }
    echo json_encode(['status'=>'success', 'data'=>$mails, 'unread'=>$unread_count]);
}
elseif ($action === 'markRead') {
    $id_notif = $_POST['id_notifikasi'] ?? '';

    // Hanya boleh menandai notifikasi milik sendiri.
    if ($id_notif === '' || !mailBelongsToUser($conn, $user_id, $id_notif)) {
        auth_json_fail(403, 'You do not have permission to perform this action.');
    }

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_MarkNotifikasiRead(?)}", array($id_notif));

    echo json_encode(['status' => $stmt !== false ? 'success' : 'error']);
}
elseif ($action === 'markAllRead') {
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_MarkAllNotifikasiRead(?)}", array($user_id));

    echo json_encode(['status' => $stmt !== false ? 'success' : 'error']);
}
