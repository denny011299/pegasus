# Audit API pengiriman → kekurangan → Production Planning

Tanggal: 23 September 2026. Pemeriksaan kode dan reproduksi HTTP dengan API key
pengujian pada `pegasus_testing`; data setiap tes di-rollback. Kode operasional API
tidak diubah. Lima tes regresi merekam perilaku bermasalah saat ini, bukan perbaikan.

## Temuan terkonfirmasi

1. **P1 — Kekurangan tidak otomatis masuk planning tanpa flag.**
   `ShipmentController.php:159,246` masih mensyaratkan `auto_create_shortage_doc=true`.
   Permintaan 24 dengan stok 5 tanpa flag tetap mendapat 201, tetapi tidak memiliki
   dokumen kekurangan atau PP. Ini sesuai kontrak API lama, namun bertentangan dengan
   kebutuhan sekarang bahwa setiap kekurangan harus masuk planning. Komentar pada
   `ShipmentShortageDocument` yang menyatakan flag hanya kompatibilitas belum sesuai
   implementasi controller. Perlu perubahan kontrak/dokumentasi bersama implementasinya.

2. **P1 — Retry shipment membuat kebutuhan produksi ganda.**
   `ShipmentController.php:258` selalu membuat dokumen baru. Idempotensi pada
   `ProductionPlanning.php:52` hanya berdasarkan ID dokumen, bukan shipment.
   Mengirim payload yang sama dua kali: satu SO, dua dokumen, dua PP aktif,
   masing-masing 19 unit; total rencana menjadi 38 padahal kekurangan 19.
   Pembaruan jumlah/stok juga tidak merekonsiliasi PP lama.
   Dokumen histori boleh tetap terpisah, tetapi kebutuhan produksi aktif perlu
   identitas dan rekonsiliasi tersendiri agar retry tidak menggandakan pekerjaan.

3. **P1 — Beberapa baris SKU memakai stok tersedia yang sama berulang kali.**
   `Concerns/ChecksStockAvailability.php:74–89` memanggil `totalAvailable` untuk tiap
   baris tanpa mengurangi saldo virtual. Dua baris masing-masing 4 unit dengan stok 5
   sama-sama dianggap cukup; tidak dibuat dokumen/PP meskipun total kekurangan 3.
   Perlu alokasi stok kumulatif per varian, termasuk jika satuan antarbaris berbeda.

4. **P1 — Gagal simpan item PP tetap dibalas sukses dan meninggalkan PP kosong.**
   `ShipmentShortageDocument.php:54–58` menangkap exception PP dan hanya melaporkannya.
   `ProductionPlanning.php:80–94` menyimpan header sebelum item tanpa transaksi lokal.
   Reproduksi menyuntikkan exception saat item dibuat: API tetap 201 dan
   `shortage_doc_created=true`, header PP tersimpan tetapi tidak punya item.
   Transaksi luar tidak rollback karena exception telah ditangkap. Retry draft untuk
   dokumen yang sama juga mengembalikan header yang sudah ada tanpa memperbaiki item.
   Harus ada jaminan atomik atau antrean pemulihan yang eksplisit dan dapat dipantau.

5. **P2 — SKU berbeda huruf besar/kecil kehilangan hubungan produk di PP.**
   API menerima SKU secara case-insensitive. `ProductionPlanning.php:130–135,174`
   menyusun dan mencari key SKU secara case-sensitive. SKU lowercase dari pemanggil
   menghasilkan item planning dengan `product_variant_id=null` walau SO mengenali
   produk. Reproduksi juga memastikan kuantitas 19 dan pemetaan ref_unit_id → unit_id
   benar; kegagalan berada pada hubungan produk. Gunakan normalisasi yang konsisten
   atau teruskan ID varian hasil resolusi API.

## Batas alur produksi saat ini

`approveDraft` mengubah status menjadi `released`. `assignWorkOrder` membuat WO per
PIC, mengisi skala/armada, lalu mengubah PP menjadi `inprod` (`ProductionPlanning.php:391`).
Jalur ini belum membuat transaksi `Production` atau mencatat hasil produksi/stok.
Pencarian di kode aplikasi juga belum menemukan transisi PP menjadi `done` yang
terhubung dengan hasil produksi. Jadi label In Production adalah status planning/WO,
bukan bukti barang kekurangan sudah diproduksi atau stok sudah bertambah.

## Verifikasi

- `tests/Regression/ShipmentPlanningAuditTest.php`: 5 tes, 18 assertion; seluruh
  reproduksi perilaku bermasalah berhasil, dengan empat deprecation notices.
- Database testing awalnya belum memiliki tabel PP. Migration PP diterapkan hanya
  ke `pegasus_testing`. Tidak ada migrasi atau perbaikan data database operasional.
- Tes workflow lama awalnya mengalami dua kegagalan karena kolom testing
  `external_api_synced_at` dan `ref_nota_id` belum tersedia; migration kedua kolom
  diterapkan hanya ke database testing sebelum pengujian ulang.
- Pengujian ulang juga menemukan migration pelebaran `customer_code` belum diterapkan
  pada testing. Setelah migration yang sudah tersedia diterapkan ke testing, seluruh
  workflow lama lulus: 12 tes, 65 assertion, empat deprecation notices. Kegagalan armada
  tersebut adalah ketertinggalan skema testing, bukan bug tambahan pada kode saat ini.
- Dokumen testing pada path `cdocs/testing/` yang disebut skill tidak tersedia;
  laporan ini menjadi catatan audit pada checkout ini.

## Perubahan form yang juga diselesaikan

Work Order memakai A6 portrait (105 × 148 mm) di controller dan Blade. Area tanda
tangan fixed dalam margin bawah yang disisihkan, berulang di setiap halaman.
PDF aktual WO-260923-003 diperiksa visual: satu halaman. Skenario 20 item bernama
panjang: tiga halaman, header tabel berulang dan tanda tangan tidak ditimpa tabel.
