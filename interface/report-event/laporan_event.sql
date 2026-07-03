-- ==========================================================
-- LAPORAN EVENT - USER DEFINED FUNCTIONS (UDF)
-- ==========================================================
-- Dipakai oleh: interface/report-event/controller_laporan_event.php
--
-- Catatan atribusi penjualan event:
--   Kolom detail_penjualan.id_produk_event TIDAK diisi oleh alur checkout
--   (event-transaction & preorder-transaction) maupun data seed, sehingga
--   penjualan event ditautkan lewat:
--     - id_produk yang terdaftar di produk_event milik event tsb,
--     - harga jual = harga_event (memastikan transaksi memakai harga event),
--     - tanggal penjualan berada dalam rentang [tanggal_mulai, tanggal_berakhir],
--     - status penjualan bukan dibatalkan/ditolak (NOT IN (7,8)).
--   Logika yang sama dipakai di kedua UDF agar total pada grid utama
--   selalu cocok dengan rincian pada modal detail.
-- ==========================================================

IF OBJECT_ID('dbo.udf_LaporanEvent', 'IF') IS NOT NULL DROP FUNCTION dbo.udf_LaporanEvent;
GO

-- ==========================================================
-- 1. LAPORAN EVENT (GRID UTAMA) - satu baris per event
-- ==========================================================
CREATE FUNCTION dbo.udf_LaporanEvent (
    @Tahun INT,
    @Bulan INT
)
RETURNS TABLE
AS
RETURN (
    SELECT
        e.id_event,
        e.nama_event,
        e.tipe_event,
        e.persen_diskon,
        e.tanggal_mulai,
        e.tanggal_berakhir,
        e.status_event,
        ISNULL(s.total_barang, 0) AS total_barang,   -- Total Items Sold
        ISNULL(s.total_harga, 0)  AS total_harga,     -- Total Revenue
        (
            SELECT STRING_AGG(p2.nama_produk, ', ')
            FROM dbo.produk_event pe2
            JOIN dbo.produk p2 ON p2.id_produk = pe2.id_produk
            WHERE pe2.id_event = e.id_event
              AND ISNULL(pe2.is_deleted, 0) = 0
              AND ISNULL(pe2.is_product_deleted, 0) = 0
        ) AS daftar_produk
    FROM dbo.event e
    OUTER APPLY (
        SELECT
            SUM(dp.jumlah_barang)  AS total_barang,
            SUM(dp.subtotal_harga) AS total_harga
        FROM dbo.produk_event pe
        JOIN dbo.detail_penjualan dp
            ON dp.id_produk    = pe.id_produk
           AND dp.harga_produk = pe.harga_event
        JOIN dbo.penjualan pj
            ON pj.id_penjualan       = dp.id_penjualan
           AND pj.status_penjualan NOT IN (7, 8)
           AND pj.tanggal_penjualan >= e.tanggal_mulai
           AND pj.tanggal_penjualan <  DATEADD(DAY, 1, e.tanggal_berakhir)
        WHERE pe.id_event = e.id_event
          AND ISNULL(pe.is_deleted, 0)         = 0
          AND ISNULL(pe.is_product_deleted, 0) = 0
    ) s
    WHERE ISNULL(e.is_deleted, 0) = 0
      AND (@Tahun = 0 OR YEAR(e.tanggal_mulai)  = @Tahun)
      AND (@Bulan = 0 OR MONTH(e.tanggal_mulai) = @Bulan)
);
GO

IF OBJECT_ID('dbo.udf_GetDetailProdukEvent', 'IF') IS NOT NULL DROP FUNCTION dbo.udf_GetDetailProdukEvent;
GO

-- ==========================================================
-- 2. DETAIL PRODUK EVENT (MODAL) - satu baris per produk event
-- ==========================================================
CREATE FUNCTION dbo.udf_GetDetailProdukEvent (
    @IdEvent INT
)
RETURNS TABLE
AS
RETURN (
    SELECT
        pe.id_produk,
        p.nama_produk,
        p.foto,
        p.harga_jual        AS harga_produk,   -- Normal Price
        pe.harga_event      AS harga_diskon,    -- Event Price
        e.persen_diskon     AS diskon,
        ISNULL(s.qty_terjual, 0)                    AS jumlah_barang,   -- Quantity Sold
        ISNULL(s.qty_terjual, 0) * pe.harga_event   AS subtotal_harga   -- Subtotal
    FROM dbo.produk_event pe
    JOIN dbo.event  e ON e.id_event  = pe.id_event
    JOIN dbo.produk p ON p.id_produk = pe.id_produk
    OUTER APPLY (
        SELECT SUM(dp.jumlah_barang) AS qty_terjual
        FROM dbo.detail_penjualan dp
        JOIN dbo.penjualan pj
            ON pj.id_penjualan       = dp.id_penjualan
           AND pj.status_penjualan NOT IN (7, 8)
           AND pj.tanggal_penjualan >= e.tanggal_mulai
           AND pj.tanggal_penjualan <  DATEADD(DAY, 1, e.tanggal_berakhir)
        WHERE dp.id_produk    = pe.id_produk
          AND dp.harga_produk = pe.harga_event
    ) s
    WHERE pe.id_event = @IdEvent
      AND ISNULL(pe.is_deleted, 0)         = 0
      AND ISNULL(pe.is_product_deleted, 0) = 0
);
GO
