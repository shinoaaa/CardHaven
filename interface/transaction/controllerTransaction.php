<?php
class controllerTransaction {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }

    public function fetchTransaksi($page = 1, $status = null, $search = '') {
        $limit = 10; $offset = ($page - 1) * $limit;
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_GetSalesList(?, ?, ?, ?)}", [$limit, $offset, $status, $search]);
        if (!$stmt) return ['data' => [], 'total_pages' => 1];
        
        $total = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['total_data'] ?? 0;
        sqlsrv_next_result($stmt);
        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['tanggal_penjualan'] instanceof DateTime) $row['tanggal_penjualan'] = $row['tanggal_penjualan']->format('d-m-Y H:i');
            $data[] = $row;
        }
        return ['data' => $data, 'total_pages' => max(1, ceil($total / $limit))];
    }

    public function countPerStatus() {
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_GetSalesStatusCount}");
        $counts = [];
        if ($stmt) while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $counts[$row['status_penjualan']] = $row['total'];
        return $counts;
    }

    public function fetchDetail($id) {
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_GetSalesDetail(?, 0)}", [$id]);
        if (!$stmt) return null;
        $order = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$order) return null;
        if ($order['tanggal_penjualan'] instanceof DateTime) $order['tanggal_penjualan'] = $order['tanggal_penjualan']->format('Y-m-d H:i:s');

        sqlsrv_next_result($stmt);
        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $items[] = $row;
        $order['items'] = $items;
        return $order;
    }

    public function updateStatus($id, $status, $mod_by, $resi = null) {
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_UpdateSalesStatus(?, ?, ?, ?)}", [$id, $status, $mod_by, $resi]);
        return $stmt !== false;
    }

    public function prosesOrder($id, $mod_by) { return $this->updateStatus($id, 3, $mod_by); }
    public function kirimOrder($id, $mod_by, $resi) { return $this->updateStatus($id, 4, $mod_by, $resi); }
    public function setDelivered($id, $mod_by) { return $this->updateStatus($id, 5, $mod_by); }
    public function cancelOrder($id, $mod_by) { return $this->updateStatus($id, 8, $mod_by); }
}
?>