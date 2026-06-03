<?php
// Helper: ambil list game aktif untuk dropdown di form set
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require __DIR__ . '/../../connection.php';

    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Koneksi database gagal."]);
        exit;
    }

    $sql  = "SELECT id_game, nama_game FROM dbo.game WHERE aktif = 1 ORDER BY nama_game ASC";
    $stmt = sqlsrv_query($conn, $sql);

    if (!$stmt) {
        throw new Exception("Query error: " . sqlsrv_errors()[0]['message']);
    }

    $games = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $games[] = [
            'id_game'   => $row['id_game'],
            'nama_game' => $row['nama_game'],
        ];
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo json_encode(["status" => "success", "data" => $games]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
