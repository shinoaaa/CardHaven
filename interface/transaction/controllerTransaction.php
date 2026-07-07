<?php
class controllerTransaction {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }

    public function fetchTransaksi($page = 1, $status = null, $search = '', $sortBy = 'DATE', $sortOrder = 'DESC') {
        $limit = 10;
        // Tarik semua data (filter status/search tetap di SP), lalu sort & paginate di PHP
        // supaya sort_by/sort_order berlaku lintas halaman tanpa mengubah stored procedure.
        $stmt = sqlsrv_query($this->conn, "{CALL dbo.sp_GetSalesList(?, ?, ?, ?)}", [1000000, 0, $status, $search]);
        if (!$stmt) return ['data' => [], 'total_pages' => 1];

        $total = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['total_data'] ?? 0;
        sqlsrv_next_result($stmt);
        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row; // biarkan tanggal_penjualan tetap DateTime untuk sorting
        }

        // Peta daftar produk per penjualan (via UDF, pola SELECT * FROM udf seperti laporan)
        $prodMap = [];
        $stmtProd = sqlsrv_query($this->conn, "SELECT id_penjualan, daftar_produk FROM dbo.udf_DaftarProdukPenjualan()");
        if ($stmtProd) {
            while ($pr = sqlsrv_fetch_array($stmtProd, SQLSRV_FETCH_ASSOC)) {
                $prodMap[$pr['id_penjualan']] = $pr['daftar_produk'];
            }
        }

        $sortBy = strtoupper($sortBy);
        $sortOrder = strtoupper($sortOrder);
        usort($data, function ($a, $b) use ($sortBy, $sortOrder) {
            if ($sortBy === 'PRICE') {
                $x = (float)$a['total_harga']; $y = (float)$b['total_harga'];
            } elseif ($sortBy === 'QTY') {
                $x = (int)$a['total_barang']; $y = (int)$b['total_barang'];
            } else { // DATE
                $x = ($a['tanggal_penjualan'] instanceof DateTime) ? $a['tanggal_penjualan']->getTimestamp() : 0;
                $y = ($b['tanggal_penjualan'] instanceof DateTime) ? $b['tanggal_penjualan']->getTimestamp() : 0;
            }
            if ($x == $y) return 0;
            return $sortOrder === 'DESC' ? (($x < $y) ? 1 : -1) : (($x < $y) ? -1 : 1);
        });

        $offset = ($page - 1) * $limit;
        $pageData = array_slice($data, $offset, $limit);
        foreach ($pageData as &$row) {
            if ($row['tanggal_penjualan'] instanceof DateTime) $row['tanggal_penjualan'] = $row['tanggal_penjualan']->format('d-m-Y H:i');
            $row['daftar_produk'] = $prodMap[$row['id_penjualan']] ?? '';
        }
        unset($row);

        return ['data' => $pageData, 'total_pages' => max(1, ceil($total / $limit))];
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
        // Serialize semua kolom DateTime agar tidak jadi objek saat json_encode
        foreach ($order as $k => $v) { if ($v instanceof DateTime) $order[$k] = $v->format('Y-m-d H:i:s'); }
        // Beberapa versi SP mengembalikan rek_tujuan; samakan ke no_rekening untuk modal
        if (!isset($order['no_rekening']) && isset($order['rek_tujuan'])) $order['no_rekening'] = $order['rek_tujuan'];

        sqlsrv_next_result($stmt);
        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $k => $v) { if ($v instanceof DateTime) $row[$k] = $v->format('Y-m-d H:i:s'); }
            $items[] = $row;
        }

        // transaction.js membaca field header di level teratas + array `items`.
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