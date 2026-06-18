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
                e.is_hide,
                COUNT(pe.id_produk) AS total_item,
                e.status_event
            FROM event e
            LEFT JOIN produk_event pe 
                ON pe.id_event = e.id_event
            WHERE ISNULL(e.is_deleted, 0) = 0 AND ISNULL(pe.is_deleted, 0) = 0
            GROUP BY 
                e.id_event,
                e.nama_event,
                e.tipe_event,
                e.tanggal_mulai,
                e.tanggal_berakhir,
                e.is_hide,
                e.persen_diskon,
                e.status_event
            ORDER BY e.status_event DESC
            OFFSET ? ROWS
            FETCH NEXT ? ROWS ONLY
        ";

        $stmt = sqlsrv_query($this->conn, $sql, [$offset, $limit]);

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

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function fetchEventById($id_event)
    {
        $sql = "
            SELECT 
                id_event,
                nama_event,
                tipe_event,
                tanggal_mulai,
                tanggal_berakhir,
                tanggal_sampai,
                persen_diskon,
                maks_pembelian,
                status_event
            FROM event
            WHERE id_event = ? AND ISNULL(is_deleted, 0) = 0
        ";

        $stmt = sqlsrv_query($this->conn, $sql, [$id_event]);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function fetchDetail($id_event)
    {
        $sql = "
            SELECT 
                pe.id_produk_event,
                pe.id_produk,
                pe.harga_event,
                pe.stok_event,
                p.nama_produk,
                p.tipe_produk,
                g.nama_game
            FROM produk_event pe
            LEFT JOIN produk p ON p.id_produk = pe.id_produk
            LEFT JOIN game g ON g.id_game = p.id_game
            WHERE pe.id_event = ?
            AND ISNULL(pe.is_deleted, 0) = 0
            AND ISNULL(pe.is_product_deleted, 0) = 0
            ORDER BY pe.id_produk_event ASC
        ";

        $stmt = sqlsrv_query($this->conn, $sql, [$id_event]);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    // ── FUNGSI BARU UNTUK SEARCH PRODUK ──────────────────────────────────────────
    public function searchProduk($keyword)
    {
        // Pakai % supaya bisa nyari kata yang ada di tengah-tengah nama produk
        $searchParam = '%' . trim($keyword) . '%';
        $sql = "
            SELECT TOP 10
                id_produk,
                nama_produk,
                tipe_produk,
                harga_jual,
                stok
            FROM produk
            WHERE nama_produk LIKE ? 
              AND ISNULL(is_deleted, 0) = 0 
              AND stok > 0
            ORDER BY nama_produk ASC
        ";

        $stmt = sqlsrv_query($this->conn, $sql, [$searchParam]);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Pastikan format tipe data sesuai biar nggak error pas di JavaScript
            $data[] = [
                'id_produk'   => (int)$row['id_produk'],
                'nama_produk' => $row['nama_produk'],
                'tipe_produk' => $row['tipe_produk'],
                'harga_jual'  => (float)$row['harga_jual'],
                'stok'        => (int)$row['stok']
            ];
        }

        return $data;
    }

    // ── FUNGSI BARU UNTUK AUTO UPDATE STATUS (LAZY UPDATE) ───────────────────
    public function autoUpdateStatusEvent()
    {
        // 1. Ubah Upcoming (2) jadi Running (1) jika hari ini sudah masuk atau lewat tanggal_mulai
        $sqlStart = "
            UPDATE event 
            SET status_event = 1 
            WHERE status_event = 2 
              AND ISNULL(is_deleted, 0) = 0
              AND CAST(GETDATE() AS DATE) >= CAST(tanggal_mulai AS DATE)
        ";
        sqlsrv_query($this->conn, $sqlStart);

        // 2. Ubah Running (1) jadi Complete (0) jika hari ini sudah lewat dari tanggal_berakhir
        $sqlEnd = "
            UPDATE event 
            SET status_event = 0 
            WHERE status_event = 1 
              AND ISNULL(is_deleted, 0) = 0
              AND CAST(GETDATE() AS DATE) > CAST(tanggal_berakhir AS DATE)
        ";
        sqlsrv_query($this->conn, $sqlEnd);
        
        // Catatan: Status 3 (Hidden) sengaja tidak diubah otomatis agar tetap tersembunyi 
        // sampai admin mengubahnya sendiri.
    }
}
?>