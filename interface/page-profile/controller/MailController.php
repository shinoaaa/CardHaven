<?php
require_once __DIR__ . '/../../../connection.php'; // Asumsi pemanggilan conn DB
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'getMails') {
    $user_id = $_GET['id_pengguna'] ?? '';
    $sql = "SELECT id_notifikasi, judul, isi, tanggal_notifikasi, status_notifikasi FROM notifikasi WHERE id_pengguna = ? ORDER BY tanggal_notifikasi DESC";
    $stmt = sqlsrv_query($conn, $sql, array($user_id));
    
    $mails = [];
    $unread_count = 0;
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if($row['tanggal_notifikasi'] instanceof DateTime) {
            $row['tanggal_notifikasi'] = $row['tanggal_notifikasi']->format('d M Y');
        }
        if($row['status_notifikasi'] == 0) $unread_count++;
        $mails[] = $row;
    }
    echo json_encode(['status'=>'success', 'data'=>$mails, 'unread'=>$unread_count]);
}
elseif ($action === 'markRead') {
    $id_notif = $_POST['id_notifikasi'] ?? '';
    $sql = "UPDATE notifikasi SET status_notifikasi = 1 WHERE id_notifikasi = ?";
    $stmt = sqlsrv_query($conn, $sql, array($id_notif));
    
    echo json_encode(['status' => $stmt ? 'success' : 'error']);
}