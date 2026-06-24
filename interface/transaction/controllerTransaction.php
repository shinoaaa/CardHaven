<?php

class controllerTransaction
{
    private $conn;

    // Status mapping
    const STATUS = [
        0 => 'Pending Payment',
        1 => 'Paid',
        2 => 'Waiting Stock',
        3 => 'Processing',
        4 => 'Shipped',
        5 => 'Delivered',
        6 => 'Completed',
        7 => 'Returned',
        8 => 'Cancelled',
    ];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // ── LIST dengan filter & search ───────────────────────────────────────────
    public function fetchTransaksi($page = 1, $status = null, $search = '')
    {
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $params = [];
        $where  = ['1=1'];

        if ($status !== null && $status !== '') {
            $where[]  = 'p.status_penjualan = ?';
            $params[] = (int)$status;
        }

        if ($search !== '') {
            $where[]  = "(pg.username LIKE ? OR CAST(p.id_penjualan AS VARCHAR) LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereStr = implode(' AND ', $where);

        $sql = "
            SELECT
                p.id_penjualan,
                p.tanggal_penjualan,
                p.total_barang,
                p.total_harga,
                p.status_penjualan,
                p.no_resi,
                p.bukti_pembayaran,
                p.alamat,
                p.tanggal_pengiriman,
                pg.username,
                pg.email,
                pg.no_telepon,
                m.nama_metode,
                m.provider
            FROM dbo.penjualan p
            LEFT JOIN dbo.pengguna pg ON pg.id_pengguna = p.id_pengguna
            LEFT JOIN dbo.metode_pembayaran m ON m.id_metode = p.id_metode
            WHERE $whereStr
            ORDER BY p.tanggal_penjualan DESC
            OFFSET ? ROWS
            FETCH NEXT ? ROWS ONLY
        ";

        $params[] = $offset;
        $params[] = $limit;

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) return [];

        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['tanggal_penjualan'] instanceof DateTime)
                $row['tanggal_penjualan'] = $row['tanggal_penjualan']->format('d-M-Y H:i');
            if ($row['tanggal_pengiriman'] instanceof DateTime)
                $row['tanggal_pengiriman'] = $row['tanggal_pengiriman']->format('d-M-Y');
            $row['total_harga'] = number_format((float)$row['total_harga'], 0, ',', '.');
            $data[] = $row;
        }

        return $data;
    }

    public function countTransaksi($status = null, $search = '')
    {
        $params = [];
        $where  = ['1=1'];

        if ($status !== null && $status !== '') {
            $where[]  = 'p.status_penjualan = ?';
            $params[] = (int)$status;
        }

        if ($search !== '') {
            $where[]  = "(pg.username LIKE ? OR CAST(p.id_penjualan AS VARCHAR) LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereStr = implode(' AND ', $where);

        $sql = "
            SELECT COUNT(*) AS total
            FROM dbo.penjualan p
            LEFT JOIN dbo.pengguna pg ON pg.id_pengguna = p.id_pengguna
            WHERE $whereStr
        ";

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) return 0;

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    // ── COUNT per status untuk tab badge ─────────────────────────────────────
    public function countPerStatus()
    {
        $sql = "
            SELECT status_penjualan, COUNT(*) AS total
            FROM dbo.penjualan
            GROUP BY status_penjualan
        ";

        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) return [];

        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[(int)$row['status_penjualan']] = (int)$row['total'];
        }
        return $data;
    }

    // ── DETAIL 1 transaksi ────────────────────────────────────────────────────
    public function fetchDetail($id_penjualan)
    {
        // Header
        $sql = "
            SELECT
                p.id_penjualan,
                p.tanggal_penjualan,
                p.total_barang,
                p.total_harga,
                p.status_penjualan,
                p.no_resi,
                p.bukti_pembayaran,
                p.alamat,
                p.tanggal_pengiriman,
                p.created_date,
                p.modified_date,
                pg.username,
                pg.email,
                pg.no_telepon,
                m.nama_metode,
                m.provider,
                m.no_rekening,
                m.atas_nama,
                m.biaya_admin
            FROM dbo.penjualan p
            LEFT JOIN dbo.pengguna pg ON pg.id_pengguna = p.id_pengguna
            LEFT JOIN dbo.metode_pembayaran m ON m.id_metode = p.id_metode
            WHERE p.id_penjualan = ?
        ";

        $stmt = sqlsrv_query($this->conn, $sql, [$id_penjualan]);
        if ($stmt === false) return null;

        $header = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$header) return null;

        foreach (['tanggal_penjualan', 'tanggal_pengiriman', 'created_date', 'modified_date'] as $f) {
            if (isset($header[$f]) && $header[$f] instanceof DateTime)
                $header[$f] = $header[$f]->format('d-M-Y H:i');
        }
        $header['total_harga']  = number_format((float)$header['total_harga'], 0, ',', '.');
        $header['biaya_admin']  = number_format((float)($header['biaya_admin'] ?? 0), 0, ',', '.');

        // Items
        $sqlItems = "
            SELECT
                dp.id_detail_penjualan,
                dp.jumlah_barang,
                dp.harga_produk,
                dp.subtotal_harga,
                pr.nama_produk,
                pr.foto,
                pr.kondisi,
                pr.tipe_produk
            FROM dbo.detail_penjualan dp
            LEFT JOIN dbo.produk pr ON pr.id_produk = dp.id_produk
            WHERE dp.id_penjualan = ?
        ";

        $stmtItems = sqlsrv_query($this->conn, $sqlItems, [$id_penjualan]);
        $items = [];
        if ($stmtItems !== false) {
            while ($row = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC)) {
                $row['harga_produk']  = number_format((float)$row['harga_produk'], 0, ',', '.');
                $row['subtotal_harga'] = number_format((float)$row['subtotal_harga'], 0, ',', '.');
                $items[] = $row;
            }
        }

        return ['header' => $header, 'items' => $items];
    }

    // ── UPDATE STATUS ─────────────────────────────────────────────────────────
    public function updateStatus($id_penjualan, $new_status, $modified_by, $extra = [])
    {
        $sets   = ['status_penjualan = ?', 'modified_by = ?', 'modified_date = GETDATE()'];
        $params = [(int)$new_status, (int)$modified_by];

        if (!empty($extra['no_resi'])) {
            $sets[]   = 'no_resi = ?';
            $params[] = trim($extra['no_resi']);
        }

        if (!empty($extra['tanggal_pengiriman'])) {
            $sets[]   = 'tanggal_pengiriman = ?';
            $params[] = $extra['tanggal_pengiriman'];
        }

        $params[] = (int)$id_penjualan;
        $setStr   = implode(', ', $sets);

        $sql  = "UPDATE dbo.penjualan SET $setStr WHERE id_penjualan = ?";
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        return $stmt !== false;
    }

    // ── KONFIRMASI PEMBAYARAN (status 0 → 1) ─────────────────────────────────
    public function konfirmasiPembayaran($id_penjualan, $modified_by)
    {
        return $this->updateStatus($id_penjualan, 1, $modified_by);
    }

    // ── PROSES / PACKING (status 1/2 → 3) ────────────────────────────────────
    public function prosesOrder($id_penjualan, $modified_by)
    {
        return $this->updateStatus($id_penjualan, 3, $modified_by);
    }

    // ── KIRIM (status 3 → 4) + simpan no_resi + tanggal_pengiriman ───────────
    public function kirimOrder($id_penjualan, $modified_by, $no_resi, $tanggal_pengiriman)
    {
        return $this->updateStatus($id_penjualan, 4, $modified_by, [
            'no_resi'            => $no_resi,
            'tanggal_pengiriman' => $tanggal_pengiriman,
        ]);
    }

    // ── DELIVERED (status 4 → 5) — admin set tiba ────────────────────────────
    public function setDelivered($id_penjualan, $modified_by)
    {
        return $this->updateStatus($id_penjualan, 5, $modified_by);
    }

    // ── TOLAK/CANCEL (→ 8) + kembalikan stok ─────────────────────────────────
    public function cancelOrder($id_penjualan, $modified_by)
    {
        // Kembalikan stok
        $sqlItems = "
            SELECT id_produk, jumlah_barang
            FROM dbo.detail_penjualan
            WHERE id_penjualan = ?
        ";
        $stmtItems = sqlsrv_query($this->conn, $sqlItems, [$id_penjualan]);
        if ($stmtItems !== false) {
            while ($row = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC)) {
                $sqlStok = "UPDATE dbo.produk SET stok = stok + ? WHERE id_produk = ?";
                sqlsrv_query($this->conn, $sqlStok, [(int)$row['jumlah_barang'], (int)$row['id_produk']]);
            }
        }

        return $this->updateStatus($id_penjualan, 8, $modified_by);
    }
}
