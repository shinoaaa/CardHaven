<?php
// Soft delete: set aktif = 0, bukan hapus fisik
// Karena set bisa sudah punya kartu / relasi lain
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require __DIR__ . '/../../connection.php';

    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Koneksi database gagal."]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["status" => "error", "message" => "Metode request tidak diizinkan."]);
        exit;
    }

    $idSet      = isset($_POST['id_set'])      ? (int)$_POST['id_set']      : 0;
    $modifiedBy = isset($_POST['modified_by']) ? (int)$_POST['modified_by'] : 1; // nanti diganti session

    if ($idSet <= 0) {
        echo json_encode(["status" => "error", "message" => "ID set tidak valid."]);
        exit;
    }

    // Cek apakah set ada dan masih aktif
    $sqlCek  = "SELECT aktif FROM dbo.set_kartu WHERE id_set = ?";
    $stmtCek = sqlsrv_query($conn, $sqlCek, [$idSet]);
    $row     = sqlsrv_fetch_array($stmtCek, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmtCek);

    if (!$row) {
        echo json_encode(["status" => "error", "message" => "Set tidak ditemukan."]);
        exit;
    }
    if ((int)$row['aktif'] === 0) {
        echo json_encode(["status" => "error", "message" => "Set sudah dalam kondisi inactive."]);
        exit;
    }

    // Soft delete
    $sql  = "UPDATE dbo.set_kartu SET aktif = 0, modified_by = ?, modified_date = GETDATE() WHERE id_set = ?";
    $stmt = sqlsrv_query($conn, $sql, [$modifiedBy, $idSet]);

    if (!$stmt) {
        $err = sqlsrv_errors();
        throw new Exception("Delete error: " . $err[0]['message']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo json_encode(["status" => "success", "message" => "Set berhasil dinonaktifkan."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
