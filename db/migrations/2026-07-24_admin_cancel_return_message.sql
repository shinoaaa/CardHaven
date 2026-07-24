/* ============================================================================
   CardHaven — Pesan admin saat Cancel / Return dikirim sebagai notifikasi
   Tanggal   : 2026-07-24
   Jalankan  : SQL Server Management Studio (SSMS) pada database CardHaven.
               Aman dijalankan ulang (CREATE OR ALTER, tidak mengubah objek lain).

   Kenapa    : Saat admin membatalkan (cancel) pesanan atau menolak & mengembalikan
               (reject & return) permintaan buyback, customer tidak pernah tahu
               alasannya. Sekarang admin WAJIB menulis pesan, dan pesan itu masuk
               ke Mailbox customer — sama seperti alur "Reject Payment" pada Sales.

   Objek baru: dbo.sp_AddNotifikasiTransaksi
               Satu-satunya objek yang dibuat file ini. Stored procedure lain
               (sp_UpdateSalesStatus, sp_UpdateBuybackStatus) TIDAK disentuh,
               jadi perilaku status/stok yang sudah ada tetap sama persis.

   Dipakai   : interface/transaction/controllerTransaction.php  (sumber 'penjualan')
               interface/buyback/controller_buyback.php          (sumber 'pembelian')

   Catatan   : SP ini mengandaikan skema berikut. Kalau nama kolom di database
               berbeda, sesuaikan di satu tempat ini saja — PHP tidak memuat SQL
               inline sama sekali.
                 dbo.notifikasi(id_pengguna, judul, isi, tanggal_notifikasi, status_notifikasi)
                 dbo.penjualan(id_penjualan, id_pengguna)
                 dbo.pembelian(id_pembelian, id_pengguna)
               Bagian 2 di bawah mencetak struktur asli ketiga tabel tersebut
               supaya bisa dicek sebelum/ sesudah menjalankan file ini.
   ========================================================================== */
USE [CardHaven];
GO

/* --------------------------------------------------------------------------
   1) SP pengirim notifikasi ke pemilik transaksi.
      @sumber       : 'penjualan' (order/sales) atau 'pembelian' (buyback)
      @id_transaksi : id_penjualan / id_pembelian
      Notifikasi dibuat sebagai BELUM DIBACA (status_notifikasi = 0) supaya
      badge merah di navbar customer langsung bertambah.
   -------------------------------------------------------------------------- */
CREATE OR ALTER PROCEDURE dbo.sp_AddNotifikasiTransaksi
    @sumber       VARCHAR(20),
    @id_transaksi INT,
    @judul        NVARCHAR(255),
    @isi          NVARCHAR(MAX)
AS
BEGIN
    SET NOCOUNT ON;

    IF @judul IS NULL OR LTRIM(RTRIM(@judul)) = ''
        THROW 51000, 'Judul notifikasi tidak boleh kosong.', 1;
    IF @isi IS NULL OR LTRIM(RTRIM(@isi)) = ''
        THROW 51000, 'Isi notifikasi tidak boleh kosong.', 1;

    DECLARE @id_pengguna INT;

    IF @sumber = 'penjualan'
        SELECT @id_pengguna = id_pengguna FROM dbo.penjualan WHERE id_penjualan = @id_transaksi;
    ELSE IF @sumber = 'pembelian'
        SELECT @id_pengguna = id_pengguna FROM dbo.pembelian WHERE id_pembelian = @id_transaksi;
    ELSE
        THROW 51000, 'Sumber notifikasi tidak dikenal (pakai ''penjualan'' atau ''pembelian'').', 1;

    IF @id_pengguna IS NULL
        THROW 51000, 'Transaksi tidak ditemukan, notifikasi tidak dikirim.', 1;

    INSERT INTO dbo.notifikasi (id_pengguna, judul, isi, tanggal_notifikasi, status_notifikasi)
    VALUES (@id_pengguna, @judul, @isi, GETDATE(), 0);

    SELECT SCOPE_IDENTITY() AS id_notifikasi, @id_pengguna AS id_pengguna;
END
GO

/* --------------------------------------------------------------------------
   2) Verifikasi skema — jalankan dan cocokkan dengan asumsi di header.
      Kalau ada kolom yang namanya beda, ubah SP di bagian 1 lalu jalankan ulang.
   -------------------------------------------------------------------------- */
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM   INFORMATION_SCHEMA.COLUMNS
WHERE  TABLE_NAME IN ('notifikasi', 'penjualan', 'pembelian')
ORDER  BY TABLE_NAME, ORDINAL_POSITION;
GO
