/* ============================================================================
   CardHaven — Perbaikan alur stok Pre-Order / Promo
   Tanggal   : 2026-07-20
   Branch    : po-good
   Jalankan  : SQL Server Management Studio (SSMS) pada database CardHaven.
               Aman dijalankan ulang (ALTER TRIGGER + cleanup idempotent).

   Kenapa    : Dua trigger di bawah mengandalkan detail_penjualan.id_produk_event
               untuk membedakan barang reguler / promo / pre-order. Sebelumnya
               PHP tidak pernah mengisi kolom itu (0 dari 120 baris) sehingga
               logika event tidak pernah jalan, dan pemotongan stok manual di PHP
               sempat membuat stok_event minus. Perbaikan PHP (2 controller
               checkout) kini mengisi id_produk_event dan berhenti memotong stok
               manual — seluruh stok ditangani trigger di file ini.

   Perilaku yang diinginkan:
     Reguler  : stok fisik dipotong saat BAYAR (0->1); dikembalikan bila batal.
     Promo    : stok fisik + kuota event dipotong saat BAYAR (0->1); dikembalikan
                bila batal / tolak bayar.
     Pre-order: kuota (stok_event) DITAHAN saat order dibuat (insert baris);
                stok fisik dipotong saat DIKIRIM (->4), yaitu setelah barang
                masuk lewat restok. Batal/retur mengembalikan kuota (selalu) dan
                stok fisik (hanya bila sudah sempat dikirim).
     Anti-minus: transisi ditolak (ROLLBACK) bila membuat stok / kuota < 0.
   ========================================================================== */
USE [CardHaven];
GO

/* --------------------------------------------------------------------------
   0) Bersihkan sisa data stok_event negatif (akibat bug lama).
      Disetel ke 0; silakan admin cek kembali kuota yang benar bila perlu.
   -------------------------------------------------------------------------- */
UPDATE dbo.produk_event SET stok_event = 0 WHERE stok_event < 0;
GO

/* --------------------------------------------------------------------------
   1) INSTEAD OF INSERT pada detail_penjualan
      - Validasi ketersediaan (reguler=fisik, promo=fisik+kuota, preorder=kuota)
      - Simpan baris pesanan (pass-through)
      - PRE-ORDER: tahan kuota (stok_event) saat order dibuat
      - Pengaman kuota tidak boleh minus
   -------------------------------------------------------------------------- */
ALTER TRIGGER [dbo].[TRG_Penjualan_CegahMinus]
ON [dbo].[detail_penjualan]
INSTEAD OF INSERT
AS
BEGIN
    SET NOCOUNT ON;

    -- 1. Validasi ketersediaan
    IF EXISTS (
        SELECT 1
        FROM inserted i
        LEFT JOIN dbo.produk        p  ON i.id_produk       = p.id_produk
        LEFT JOIN dbo.produk_event  pe ON i.id_produk_event = pe.id_produk_event
        LEFT JOIN dbo.event         e  ON pe.id_event        = e.id_event
        WHERE
            (i.id_produk_event IS NULL AND p.stok < i.jumlah_barang)
            OR (i.id_produk_event IS NOT NULL AND e.tipe_event = 'promo'
                    AND (pe.stok_event < i.jumlah_barang OR p.stok < i.jumlah_barang))
            OR (i.id_produk_event IS NOT NULL AND e.tipe_event = 'preorder'
                    AND pe.stok_event < i.jumlah_barang)
    )
    BEGIN
        RAISERROR('Transaksi Gagal: Stok fisik atau kuota Event tidak mencukupi.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- 2. Simpan baris pesanan
    INSERT INTO dbo.detail_penjualan
        (id_penjualan, id_produk, id_produk_event, jumlah_barang, harga_produk, subtotal_harga)
    SELECT id_penjualan, id_produk, id_produk_event, jumlah_barang, harga_produk, subtotal_harga
    FROM inserted;

    -- 3. PRE-ORDER: tahan kuota saat order dibuat (promo/reguler tidak di sini)
    UPDATE pe
        SET pe.stok_event = pe.stok_event - agg.qty
    FROM dbo.produk_event pe
    INNER JOIN (
        SELECT i.id_produk_event, SUM(i.jumlah_barang) AS qty
        FROM inserted i
        INNER JOIN dbo.produk_event pe2 ON pe2.id_produk_event = i.id_produk_event
        INNER JOIN dbo.event        e   ON e.id_event          = pe2.id_event
        WHERE i.id_produk_event IS NOT NULL AND e.tipe_event = 'preorder'
        GROUP BY i.id_produk_event
    ) agg ON agg.id_produk_event = pe.id_produk_event;

    -- 4. Pengaman: kuota tidak boleh minus (mis. order pre-order barengan)
    IF EXISTS (
        SELECT 1
        FROM dbo.produk_event pe
        INNER JOIN inserted i ON i.id_produk_event = pe.id_produk_event
        WHERE pe.stok_event < 0
    )
    BEGIN
        RAISERROR('Transaksi Gagal: Kuota pre-order tidak mencukupi.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END
END
GO

/* --------------------------------------------------------------------------
   2) AFTER UPDATE pada penjualan — pemotongan / pengembalian stok
   -------------------------------------------------------------------------- */
ALTER TRIGGER [dbo].[TRG_Penjualan_PotongStok]
ON [dbo].[penjualan]
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    /* A. PEMBAYARAN (0 -> 1) */
    IF EXISTS (SELECT 1 FROM inserted i JOIN deleted d ON i.id_penjualan = d.id_penjualan
               WHERE d.status_penjualan = 0 AND i.status_penjualan = 1)
    BEGIN
        -- Stok fisik: reguler ATAU promo
        UPDATE p SET p.stok = p.stok - dp.jumlah_barang
        FROM dbo.produk p
        INNER JOIN dbo.detail_penjualan dp ON p.id_produk = dp.id_produk
        LEFT  JOIN dbo.produk_event pe ON dp.id_produk_event = pe.id_produk_event
        LEFT  JOIN dbo.event e ON pe.id_event = e.id_event
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN deleted  d ON i.id_penjualan = d.id_penjualan
        WHERE d.status_penjualan = 0 AND i.status_penjualan = 1
          AND (dp.id_produk_event IS NULL OR e.tipe_event = 'promo');

        -- Kuota event: PROMO saja (pre-order sudah ditahan saat order dibuat)
        UPDATE pe SET pe.stok_event = pe.stok_event - dp.jumlah_barang
        FROM dbo.produk_event pe
        INNER JOIN dbo.detail_penjualan dp ON pe.id_produk_event = dp.id_produk_event
        INNER JOIN dbo.event e ON pe.id_event = e.id_event
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN deleted  d ON i.id_penjualan = d.id_penjualan
        WHERE d.status_penjualan = 0 AND i.status_penjualan = 1
          AND e.tipe_event = 'promo';
    END

    /* B. PENGIRIMAN (<4 -> 4): potong stok fisik untuk PRE-ORDER */
    IF EXISTS (SELECT 1 FROM inserted i JOIN deleted d ON i.id_penjualan = d.id_penjualan
               WHERE d.status_penjualan < 4 AND i.status_penjualan = 4)
    BEGIN
        UPDATE p SET p.stok = p.stok - dp.jumlah_barang
        FROM dbo.produk p
        INNER JOIN dbo.detail_penjualan dp ON p.id_produk = dp.id_produk
        INNER JOIN dbo.produk_event pe ON dp.id_produk_event = pe.id_produk_event
        INNER JOIN dbo.event e ON pe.id_event = e.id_event
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN deleted  d ON i.id_penjualan = d.id_penjualan
        WHERE d.status_penjualan < 4 AND i.status_penjualan = 4
          AND dp.id_produk_event IS NOT NULL AND e.tipe_event = 'preorder';
    END

    /* C. BATAL / RETUR (-> 7 atau 8): kembalikan stok */
    IF EXISTS (SELECT 1 FROM inserted i JOIN deleted d ON i.id_penjualan = d.id_penjualan
               WHERE i.status_penjualan IN (7, 8) AND d.status_penjualan NOT IN (7, 8))
    BEGIN
        -- Stok fisik kembali: reguler/promo bila sudah dibayar (d>=1);
        --                     pre-order bila sudah dikirim (d>=4)
        UPDATE p SET p.stok = p.stok + dp.jumlah_barang
        FROM dbo.produk p
        INNER JOIN dbo.detail_penjualan dp ON p.id_produk = dp.id_produk
        LEFT  JOIN dbo.produk_event pe ON dp.id_produk_event = pe.id_produk_event
        LEFT  JOIN dbo.event e ON pe.id_event = e.id_event
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN deleted  d ON i.id_penjualan = d.id_penjualan
        WHERE i.status_penjualan IN (7, 8) AND d.status_penjualan NOT IN (7, 8)
          AND (
                ((dp.id_produk_event IS NULL OR e.tipe_event = 'promo') AND d.status_penjualan >= 1)
             OR (e.tipe_event = 'preorder' AND d.status_penjualan >= 4)
          );

        -- Kuota event kembali: promo bila sudah dibayar (d>=1);
        --                      pre-order SELALU (ditahan sejak order dibuat)
        UPDATE pe SET pe.stok_event = pe.stok_event + dp.jumlah_barang
        FROM dbo.produk_event pe
        INNER JOIN dbo.detail_penjualan dp ON pe.id_produk_event = dp.id_produk_event
        INNER JOIN dbo.event e ON pe.id_event = e.id_event
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN deleted  d ON i.id_penjualan = d.id_penjualan
        WHERE i.status_penjualan IN (7, 8) AND d.status_penjualan NOT IN (7, 8)
          AND (
                (e.tipe_event = 'promo' AND d.status_penjualan >= 1)
             OR (e.tipe_event = 'preorder')
          );
    END

    /* D. TOLAK PEMBAYARAN (1 -> 0): kembalikan yang dipotong saat bayar
          (reguler/promo). Pre-order tidak terpengaruh. */
    IF EXISTS (SELECT 1 FROM inserted i JOIN deleted d ON i.id_penjualan = d.id_penjualan
               WHERE d.status_penjualan = 1 AND i.status_penjualan = 0)
    BEGIN
        UPDATE p SET p.stok = p.stok + dp.jumlah_barang
        FROM dbo.produk p
        INNER JOIN dbo.detail_penjualan dp ON p.id_produk = dp.id_produk
        LEFT  JOIN dbo.produk_event pe ON dp.id_produk_event = pe.id_produk_event
        LEFT  JOIN dbo.event e ON pe.id_event = e.id_event
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN deleted  d ON i.id_penjualan = d.id_penjualan
        WHERE d.status_penjualan = 1 AND i.status_penjualan = 0
          AND (dp.id_produk_event IS NULL OR e.tipe_event = 'promo');

        UPDATE pe SET pe.stok_event = pe.stok_event + dp.jumlah_barang
        FROM dbo.produk_event pe
        INNER JOIN dbo.detail_penjualan dp ON pe.id_produk_event = dp.id_produk_event
        INNER JOIN dbo.event e ON pe.id_event = e.id_event
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN deleted  d ON i.id_penjualan = d.id_penjualan
        WHERE d.status_penjualan = 1 AND i.status_penjualan = 0
          AND e.tipe_event = 'promo';
    END

    /* E. PENGAMAN ANTI-MINUS: tolak transisi bila ada stok/kuota jadi < 0 */
    IF EXISTS (
        SELECT 1 FROM dbo.detail_penjualan dp
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN dbo.produk p ON p.id_produk = dp.id_produk
        WHERE p.stok < 0
    )
    OR EXISTS (
        SELECT 1 FROM dbo.detail_penjualan dp
        INNER JOIN inserted i ON dp.id_penjualan = i.id_penjualan
        INNER JOIN dbo.produk_event pe ON pe.id_produk_event = dp.id_produk_event
        WHERE pe.stok_event < 0
    )
    BEGIN
        RAISERROR('Transaksi Gagal: Stok tidak mencukupi (mencegah stok minus).', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END
END
GO
