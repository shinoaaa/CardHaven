<?php
class controllerEvent {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function autoUpdateStatusEvent() {
        sqlsrv_query($this->conn, "{CALL dbo.sp_AutoUpdateEventStatus}");
    }

    public function countEvent() {
        return $GLOBALS['total_event_count'] ?? 0;
    }

    public function fetchEvent($page = 1) {
        $limit = 7;
        $offset = ($page - 1) * $limit;
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_GetEventList(?, ?)}", [$limit, $offset]);
        if ($stmt === false) die(print_r(sqlsrv_errors(), true));

        // Result 1: Total Event
        $rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $GLOBALS['total_event_count'] = (int)($rowTotal['total'] ?? 0);

        // Result 2: Daftar Event
        sqlsrv_next_result($stmt);
        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    public function fetchEventById($id_event) {
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_GetEventDetail(?)}", [$id_event]);
        if ($stmt === false) die(print_r(sqlsrv_errors(), true));
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function fetchDetail($id_event) {
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_GetEventDetail(?)}", [$id_event]);
        if ($stmt === false) die(print_r(sqlsrv_errors(), true));
        
        sqlsrv_next_result($stmt); // Lewati Result Header
        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    public function searchProduk($keyword) {
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_SearchProductForEvent(?)}", [trim($keyword)]);
        $data = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['id_produk']   = (int)$row['id_produk'];
                $row['harga_jual']  = (float)$row['harga_jual'];
                $row['stok']        = (int)$row['stok'];
                $data[] = $row;
            }
        }
        return $data;
    }
}
?>