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
