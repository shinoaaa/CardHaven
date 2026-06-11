<?php

class controllerEvent
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function fetchEvent($page = 1)
    {
        $limit = 7;
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                e.id_event,
                e.nama_event,
                e.tipe_event,
                e.tanggal_mulai,
                e.tanggal_berakhir,
                e.persen_diskon,
                COUNT(pe.id_produk) AS total_item,
                e.status_event
            FROM event e
            LEFT JOIN produk_event pe 
                ON pe.id_event = e.id_event
            WHERE ISNULL(e.is_deleted, 0) = 0
            GROUP BY 
                e.id_event,
                e.nama_event,
                e.tipe_event,
                e.tanggal_mulai,
                e.tanggal_berakhir,
                e.persen_diskon,
                e.status_event
            ORDER BY e.id_event DESC
            OFFSET ? ROWS
            FETCH NEXT ? ROWS ONLY
        ";

        $params = [$offset, $limit];

        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $data = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    public function countEvent()
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM event
            WHERE ISNULL(is_deleted, 0) = 0
        ";

        $stmt = sqlsrv_query($this->conn, $sql);

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        return (int)$row['total'];
    }
}
?>