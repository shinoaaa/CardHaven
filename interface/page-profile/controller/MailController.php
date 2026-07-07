<?php
require_once __DIR__ . '/../../../connection.php'; // Asumsi pemanggilan conn DB
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'getMails') {
    $user_id = $_GET['id_pengguna'] ?? '';
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
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_MarkNotifikasiRead(?)}", array($id_notif));

    echo json_encode(['status' => $stmt !== false ? 'success' : 'error']);
}
elseif ($action === 'markAllRead') {
    $user_id = $_POST['id_pengguna'] ?? $_GET['id_pengguna'] ?? '';
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_MarkAllNotifikasiRead(?)}", array($user_id));

    echo json_encode(['status' => $stmt !== false ? 'success' : 'error']);
}
