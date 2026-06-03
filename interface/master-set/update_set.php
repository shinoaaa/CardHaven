<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require __DIR__ . '/../../connection.php';

    if (!$conn) {
        echo json_encode(["status" => "error", "target" => "general", "message" => "Koneksi database gagal."]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["status" => "error", "target" => "general", "message" => "Metode request tidak diizinkan."]);
        exit;
    }

    // Ambil & sanitasi input
    $idSet        = isset($_POST['id_set'])        ? (int)$_POST['id_set']          : 0;
    $namaSet      = isset($_POST['nama_set'])      ? trim($_POST['nama_set'])        : '';
    $kodeSet      = isset($_POST['kode_set'])      ? trim($_POST['kode_set'])        : '';
    $idGame       = isset($_POST['id_game'])       ? (int)$_POST['id_game']          : 0;
    $tanggalRilis = isset($_POST['tanggal_rilis']) ? trim($_POST['tanggal_rilis'])   : null;
    $aktif        = isset($_POST['aktif'])         ? (int)$_POST['aktif']            : 1;
    $modifiedBy   = isset($_POST['modified_by'])  ? (int)$_POST['modified_by']      : 1; // nanti diganti session

    // Validasi
    if ($idSet <= 0) {
        echo json_encode(["status" => "error", "target" => "general", "message" => "ID set tidak valid."]);
        exit;
    }
    if ($namaSet === '') {
        echo json_encode(["status" => "error", "target" => "nama_set", "message" => "Nama set tidak boleh kosong."]);
        exit;
    }
    if (strlen($namaSet) > 50) {
        echo json_encode(["status" => "error", "target" => "nama_set", "message" => "Nama set maksimal 50 karakter."]);
        exit;
    }
    if ($kodeSet === '') {
        echo json_encode(["status" => "error", "target" => "kode_set", "message" => "Kode set tidak boleh kosong."]);
        exit;
    }
    if (strlen($kodeSet) > 20) {
        echo json_encode(["status" => "error", "target" => "kode_set", "message" => "Kode set maksimal 20 karakter."]);
        exit;
    }
    if ($idGame <= 0) {
        echo json_encode(["status" => "error", "target" => "id_game", "message" => "Pilih game terlebih dahulu."]);
        exit;
    }

    // Cek duplikat kode_set (kecuali data sendiri)
    $sqlCek  = "SELECT COUNT(*) AS cnt FROM dbo.set_kartu WHERE kode_set = ? AND id_set <> ?";
    $stmtCek = sqlsrv_query($conn, $sqlCek, [$kodeSet, $idSet]);
    $rowCek  = sqlsrv_fetch_array($stmtCek, SQLSRV_FETCH_ASSOC);
    if ((int)$rowCek['cnt'] > 0) {
        sqlsrv_free_stmt($stmtCek);
        echo json_encode(["status" => "error", "target" => "kode_set", "message" => "Kode set sudah digunakan oleh set lain."]);
        exit;
    }
    sqlsrv_free_stmt($stmtCek);

    // Format tanggal (opsional)
    $tanggalParam = null;
    if ($tanggalRilis !== null && $tanggalRilis !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $tanggalRilis);
        if (!$dt) {
            echo json_encode(["status" => "error", "target" => "tanggal_rilis", "message" => "Format tanggal tidak valid."]);
            exit;
        }
        $tanggalParam = $dt->format('Y-m-d H:i:s');
    }

    $sql = "UPDATE dbo.set_kartu SET
                id_game        = ?,
                nama_set       = ?,
                kode_set       = ?,
                tanggal_rilis  = ?,
                aktif          = ?,
                modified_by    = ?,
                modified_date  = GETDATE()
            WHERE id_set = ?";

    $params = [$idGame, $namaSet, $kodeSet, $tanggalParam, $aktif, $modifiedBy, $idSet];
    $stmt   = sqlsrv_query($conn, $sql, $params);

    if (!$stmt) {
        $err = sqlsrv_errors();
        throw new Exception("Update error: " . $err[0]['message']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo json_encode(["status" => "success", "message" => "Set berhasil diperbarui."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "target" => "general", "message" => $e->getMessage()]);
}
?>
