<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require __DIR__ . '/../../connection.php';

    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Koneksi database gagal."]);
        exit;
    }

    // Parameter paginasi & filter
    $page     = isset($_GET['page'])   ? max(1, (int)$_GET['page'])    : 1;
    $limit    = isset($_GET['limit'])  ? max(1, (int)$_GET['limit'])   : 10;
    $search   = isset($_GET['search']) ? trim($_GET['search'])          : '';
    $gameId   = isset($_GET['game'])   ? (int)$_GET['game']            : 0;
    $status   = isset($_GET['status']) ? $_GET['status']               : '';

    $offset = ($page - 1) * $limit;

    // ---- Bangun WHERE ----
    $conditions = [];
    $params     = [];

    if ($search !== '') {
        $conditions[] = "(s.nama_set LIKE ? OR s.kode_set LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($gameId > 0) {
        $conditions[] = "s.id_game = ?";
        $params[] = $gameId;
    }

    if ($status !== '') {
        $conditions[] = "s.aktif = ?";
        $params[] = (int)$status;
    }

    $where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // ---- Hitung total ----
    $sqlCount = "SELECT COUNT(*) AS total
                 FROM dbo.set_kartu s
                 $where";
    $stmtCount = sqlsrv_query($conn, $sqlCount, $params);
    if (!$stmtCount) {
        throw new Exception("Query count error: " . sqlsrv_errors()[0]['message']);
    }
    $rowCount = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
    $total    = (int)$rowCount['total'];
    sqlsrv_free_stmt($stmtCount);

    // ---- Ambil data ----
    $paramsData = array_merge($params, [$offset, $limit]);
    $sqlData = "SELECT
                    s.id_set,
                    s.nama_set,
                    s.kode_set,
                    s.tanggal_rilis,
                    s.aktif,
                    g.nama_game,
                    s.id_game
                FROM dbo.set_kartu s
                INNER JOIN dbo.game g ON s.id_game = g.id_game
                $where
                ORDER BY s.id_set ASC
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

    $stmtData = sqlsrv_query($conn, $sqlData, $paramsData);
    if (!$stmtData) {
        throw new Exception("Query data error: " . sqlsrv_errors()[0]['message']);
    }

    $sets = [];
    while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
        // Format tanggal (datetime -> string)
        $tanggal = null;
        if ($row['tanggal_rilis'] instanceof DateTime) {
            $tanggal = $row['tanggal_rilis']->format('Y-m-d');
        }

        $sets[] = [
            'id_set'        => $row['id_set'],
            'nama_set'      => $row['nama_set'],
            'kode_set'      => $row['kode_set'],
            'tanggal_rilis' => $tanggal,
            'aktif'         => $row['aktif'],
            'nama_game'     => $row['nama_game'],
            'id_game'       => $row['id_game'],
        ];
    }
    sqlsrv_free_stmt($stmtData);
    sqlsrv_close($conn);

    echo json_encode([
        "status" => "success",
        "data"   => $sets,
        "meta"   => [
            "total"       => $total,
            "page"        => $page,
            "limit"       => $limit,
            "total_pages" => (int)ceil($total / $limit),
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
