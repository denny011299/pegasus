# Asisten AI — Referensi Developer

> File ini di-generate oleh `php artisan ai:dev-docs` dari `App\Services\AiAssistant\AssistantGuide`. Jangan edit manual.
> Halaman user: Pengaturan → Dokumentasi AI (`/settings/ai-docs`).

- Model teks: `openrouter/free` · Model gambar: `openrouter/free`
- Baris data ke model per pencarian: 10 · Konteks: 6 pesan terakhir

## Tools (semua read-only)

### `find_document` — Cari Dokumen

Find one document by the code shown in the app (invoice, SO, PO, tanda terima, produksi, opname, retur, transfer, surat jalan) and return its header, its items and related stock movements in a single call. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `code` | ya | Document code as shown to users, e.g. INV1098, SO1098, PO123 |
| `module` |  | Optional module to narrow the search |
| `include_items` |  | Include line items, default true |

### `search_records` — Cari / Filter Data

List or filter records of one business module. Supports exact/contains filters, "any of" lists, numeric ranges and comparisons, a date range, keyword search, sorting (newest first by default) and a row limit. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `module` | ya | Business module, e.g. pengiriman, pembelian, produksi, stok produk, kas gudang, customer, pemasok, riwayat stok. |
| `filters` |  | Field => condition. Value forms: "abc" (exact), ["a","b"] (any of), {"contains":"abc"}, {"min":1,"max":9}, {"op":">=","value":1}. Fields use app wording, e.g. "No. Invoice", "status", "customer". |
| `search` |  | Keyword searched across names, codes and notes |
| `date_from` |  | YYYY-MM-DD |
| `date_to` |  | YYYY-MM-DD |
| `order_by` |  | Field to sort by; defaults to the document date |
| `order` |  | terbaru = newest first (default) (pilihan: terbaru, terlama, desc, asc) |
| `limit` |  | Rows to return, max 50 |
| `include_inactive` |  | Also return deleted/rejected/cancelled records. Default false. |
| `fields` |  | Optional subset of fields to return |

### `summarize_records` — Rekap & Total

Count, total, average, minimum or maximum for one business module, optionally grouped by a field, by status, or by month/day. Use this for "berapa total", "berapa banyak", monthly recaps and rankings. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `module` | ya | Business module, e.g. pengiriman, pembelian, kas gudang |
| `metric` |  | count (default), sum, avg, min, max (pilihan: count, sum, avg, min, max) |
| `field` |  | Numeric field for sum/avg/min/max, e.g. "total", "qty", "nominal" |
| `group_by` |  | Field to group by, or "bulan", "tanggal", "status" |
| `filters` |  | Same condition forms as search_records |
| `date_from` |  | YYYY-MM-DD |
| `date_to` |  | YYYY-MM-DD |
| `limit` |  | Max groups returned, default 12, max 50 |
| `include_inactive` |  | Also count deleted/rejected/cancelled records. Default false. |

### `find_duplicates` — Cek Data Dobel

Find groups of records that share the same values (count > 1) on the given fields, optionally inside a date range, with who created them and how far apart they were created. Read-only. Max 50 groups.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `module` | ya | Business module, e.g. pengiriman, pembelian, produksi |
| `fields` | ya | Fields that must match for a record to count as duplicate, in app wording |
| `date_from` |  | YYYY-MM-DD |
| `date_to` |  | YYYY-MM-DD |

### `record_history` — Riwayat Dokumen

Audit trail of one record: who created it, who approved it, when it changed, plus related stock movements. Accepts the document code or the Ref returned by other tools. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `module` | ya | Business module the record belongs to |
| `code` |  | Document code as shown in the app |
| `ref` |  | Ref value returned by another tool |

### `find_item` — Cari Barang

Find a product or bahan mentah by name, SKU, barcode or partial/misspelled words. Returns exact matches, or similar items as suggestions when nothing matches exactly. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `keyword` | ya | Nama barang, SKU atau barcode seperti yang diketik user |
| `jenis` |  | produk = barang jadi, bahan = bahan mentah/kemasan. Default semua. (pilihan: semua, produk, bahan) |

### `item_activity` — Jejak Barang per Menu

Trace one product/bahan across transactions: how many documents and total qty per unit in pengiriman, transfer stok, stok opname, pembelian, produksi and retur, with the document list. Use for "berapa transfer stok produk X hari ini", "produk X dikirim ke mana saja". If the item is not found exactly it returns suggestions instead. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `item` | ya | Nama barang, SKU atau barcode |
| `modul` |  | semua (default), pengiriman, transfer stok, stok opname, pembelian, produksi, retur |
| `date_from` |  | YYYY-MM-DD |
| `date_to` |  | YYYY-MM-DD |
| `limit` |  | Dokumen per modul, max 50 |
| `include_inactive` |  | Ikutkan dokumen dihapus/ditolak/batal. Default false. |

### `dashboard_summary` — Angka Dashboard

Read the same numbers shown on the Dashboard page for a week/month/year: KPI penjualan & retur, nilai persediaan, widget pengiriman/produksi/pembelian, top produk, stock aging, peringatan stok bahan, hutang jatuh tempo, antrian ACC, grafik. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `bagian` |  | Bagian dashboard. Default ringkasan. (pilihan: ringkasan, top produk, stock aging, peringatan stok, hutang jatuh tempo, antrian acc, grafik) |
| `periode` |  | Default bulan (pilihan: minggu, bulan, tahun) |
| `tanggal` |  | YYYY-MM-DD di dalam periode yang diminta; default hari ini |

### `item_stock` — Kartu Stok

Stock card of ONE product/bahan: current stock per gudang & satuan, masuk/keluar totals in a period grouped by source (opname, transfer, pengiriman, produksi, pembelian...), and the latest movements with running balance. Use for "stok X berapa", "kenapa stok X berkurang/bertambah". Suggests similar items when not exact. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `item` | ya | Nama barang, SKU atau barcode |
| `jenis` |  | (pilihan: semua, produk, bahan) |
| `gudang` |  | Nama gudang (opsional) |
| `date_from` |  | YYYY-MM-DD, default 30 hari terakhir |
| `date_to` |  | YYYY-MM-DD |
| `limit` |  | Jumlah mutasi terakhir, default 15, max 50 |

### `data_anomalies` — Cek Kejanggalan

Find data that needs attention: stok minus, documents waiting for ACC too long (pending lama), stock transfers sent but not received, large stock opname differences, overdue payables (hutang jatuh tempo). Use for "ada yang janggal?", "apa yang belum di-ACC?", "cek masalah data hari ini". Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `cek` |  | Default semua (pilihan: semua, stok minus, pending lama, transfer belum diterima, selisih opname, hutang jatuh tempo, harga nol) |
| `min_hari` |  | Pending/transfer dianggap lama setelah N hari. Default 3. |
| `limit` |  | Baris per pemeriksaan, default 5, max 30 |

### `staff_activity` — Aktivitas Staf

What a staff member did in a period: actions from the activity log (tambah/ubah/ACC/hapus per menu) and how many documents they created or approved per module. Without staff → ranking of the most active staff. Default today. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `staff` |  | Nama / username staf (kosong = semua staf) |
| `date_from` |  | YYYY-MM-DD, default hari ini |
| `date_to` |  | YYYY-MM-DD |
| `include_open_menu` |  | Ikutkan aktivitas "membuka menu". Default false. |
| `limit` |  | Aktivitas terakhir yang ditampilkan, default 15, max 50 |

### `trace_document` — Lacak Alur Dokumen

Trace the full chain of a document from any code in it: pengiriman/SO → invoice → surat jalan → retur; pembelian/PO → tanda terima → penerimaan barang (PDO) → invoice → produk bermasalah/retur; produksi → transfer stok. Shows each step with code, date, status and which steps are still missing. Use for "alur dokumen X", "PO ini sudah sampai mana", "SO ini sudah dikirim?". Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `code` | ya | Kode dokumen apa pun di rantai (INV, SO, PO, TT, PDO, SDO, PR, ST, PBJ...) |

### `period_report` — Laporan Periode

Period report across all main modules (pengiriman, pembelian, produksi, transfer, retur, opname, kas besar & operasional): number of documents and total nominal, compared with the previous period of the same length (naik/turun %). Use for "laporan hari ini", "rekap minggu ini", "bulan ini dibanding bulan lalu". Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `periode` |  | Default hari ini; abaikan bila date_from/date_to diisi (pilihan: hari ini, kemarin, minggu ini, bulan ini, bulan lalu, tahun ini) |
| `date_from` |  | YYYY-MM-DD |
| `date_to` |  | YYYY-MM-DD |
| `modul` |  | Opsional, batasi ke satu modul (mis. "kas besar", "pengiriman") |

### `partner_insight` — Customer & Pemasok

Customer or supplier (pemasok) insight: top partners by transactions in a period, partners that stopped ordering (tidak aktif), and for pemasok the average lead time PO → barang diterima, produk bermasalah count and unpaid PO total. With "nama" returns a profile of one partner. Default last 90 days. Read-only.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `jenis` |  | Default customer (pilihan: customer, pemasok) |
| `nama` |  | Opsional: nama / kode / plat satu partner |
| `date_from` |  | YYYY-MM-DD, default 90 hari lalu |
| `date_to` |  | YYYY-MM-DD |
| `tidak_aktif_hari` |  | Dianggap tidak aktif bila transaksi terakhir lebih dari N hari. Default 60. |
| `limit` |  | Default 10, max 30 |

### `reorder_suggestion` — Saran Beli Bahan

Suggest which bahan mentah to buy again: current stock (satuan default, semua gudang), average daily usage for produksi, days of stock left, lead time, reorder point and suggested qty. Also flags bahan below its stock alert. Use for "bahan apa yang harus dibeli", "stok X cukup berapa hari", "kapan harus order X". Read-only suggestion.

| Parameter | Wajib | Keterangan |
|---|---|---|
| `item` |  | Opsional: nama bahan (kosong = semua bahan yang perlu dibeli) |
| `hari_pemakaian` |  | Hitung rata-rata pemakaian dari N hari terakhir. Default 30. |
| `target_hari` |  | Stok setelah beli cukup untuk N hari (di luar lead time). Default 14. |
| `limit` |  | Default 15, max 50 |

## Perkiraan penyebab (`Tools/Concerns/CauseEstimates.php`)

Bobot dasar per jenis, digeser sinyal data; total selalu 100%. Penyebab hanya operasional, tidak pernah "bug sistem".

### `stok_minus`

| Penyebab | Bobot dasar | Alasan (dijelaskan bila user minta) |
|---|---|---|
| Urutan input (barang keluar dicatat/di-ACC sebelum barang masuk) | 35% | Stok sementara minus karena dokumen masuk (PO/produksi/transfer) belum di-ACC saat pengiriman diproses. |
| Salah pilih gudang | 25% | Barang dikeluarkan dari gudang yang tidak menyimpan stoknya. |
| Salah satuan / qty (human error) | 20% | Qty atau satuan keluar lebih besar dari yang seharusnya. |
| Input dobel (koneksi lambat / klik ulang) | 10% | Tombol simpan/ACC ditekan lebih dari sekali saat jaringan lambat. |
| Hasil opname belum disesuaikan | 10% | Selisih fisik belum dimasukkan lewat stok opname. |

Sinyal:

- Minus kecil (≤ 5) → "Urutan input" naik.
- Ada 2+ log keluar dengan kode & qty sama dalam 90 hari → "Input dobel" naik.

### `pending_lama`

| Penyebab | Bobot dasar | Alasan (dijelaskan bila user minta) |
|---|---|---|
| Lupa / belum sempat di-ACC | 45% | Approver belum membuka menu terkait atau tidak tahu ada dokumen menunggu. |
| Sebenarnya batal tapi tidak dihapus | 25% | Dokumen dibiarkan pending setelah transaksinya tidak jadi. |
| Menunggu kelengkapan (barang / dokumen fisik) | 20% | ACC ditahan sampai barang datang atau berkas lengkap. |
| Notifikasi tidak terbaca / koneksi lambat | 10% | Pengaju mengira sudah terkirim, padahal belum sampai ke approver. |

Sinyal:

- Dokumen > 30 hari → "Sebenarnya batal tapi tidak dihapus" naik.
- Dokumen ≤ 30 hari → "Lupa / belum sempat di-ACC" naik.

### `transfer_belum_diterima`

| Penyebab | Bobot dasar | Alasan (dijelaskan bila user minta) |
|---|---|---|
| Gudang tujuan lupa klik Terima | 45% | Barang sudah sampai tapi penerimaan belum dicatat di sistem. |
| Barang masih di perjalanan | 20% | Pengiriman fisik memang belum tiba. |
| Salah pilih gudang tujuan | 20% | Transfer diarahkan ke gudang yang tidak menerima barangnya. |
| Koneksi lambat saat proses Terima | 15% | Penerimaan sudah diklik tapi gagal tersimpan karena jaringan. |

Sinyal:

- Semua ≤ 3 hari → "Barang masih di perjalanan" naik.
- Ada yang > 3 hari → "Gudang tujuan lupa klik Terima" naik.

### `selisih_opname`

| Penyebab | Bobot dasar | Alasan (dijelaskan bila user minta) |
|---|---|---|
| Salah hitung / salah ketik qty (human error) | 35% | Angka fisik keliru saat dihitung atau diketik. |
| Transaksi belum diinput sebelum opname | 25% | Barang keluar/masuk fisik belum dicatat sehingga stok sistem berbeda. |
| Salah satuan | 15% | Fisik dihitung dalam satuan lain (mis. ml vs liter, pcs vs dus). |
| Salah pilih gudang / barang tertukar | 15% | Barang dihitung di gudang atau varian yang salah. |
| Susut / rusak / hilang | 10% | Selisih fisik nyata yang belum dicatat sebagai barang bermasalah. |

Sinyal:

- Rasio fisik/sistem tepat 10/12/24/100/1000 (±1%) → "Salah satuan".
- Rasio ≥ 10 lainnya → "Salah hitung / salah ketik".
- Fisik 0, sistem > 0 → "Salah pilih gudang" & "Transaksi belum diinput" naik.

### `harga_nol`

| Penyebab | Bobot dasar | Alasan (dijelaskan bila user minta) |
|---|---|---|
| Harga belum diisi saat input PO (human error) | 45% | Kolom harga terlewat; PO tetap tersimpan dengan Rp 0 lalu terbawa ke Tanda Terima, invoice, hutang. |
| Harga menunggu konfirmasi pemasok | 20% | PO dibuat dulu untuk ambil barang, harga final belum dikabari pemasok. |
| Barang bonus / sampel dari pemasok | 20% | Memang gratis, tapi sebaiknya diberi catatan di PO. |
| Salah pilih varian/satuan yang belum punya harga | 15% | Varian atau satuan yang dipilih belum punya harga master. |

Sinyal:

- Satu pemasok ≥ 50% dari PO Rp 0 → "Harga menunggu konfirmasi pemasok" naik.

### `hutang_jatuh_tempo`

| Penyebab | Bobot dasar | Alasan (dijelaskan bila user minta) |
|---|---|---|
| Memang belum dibayar | 40% | Pembayaran tertunda karena jadwal kas. |
| Sudah dibayar tapi belum ditandai lunas | 35% | Pembayaran sudah dilakukan, status PO di sistem belum diperbarui. |
| Tanggal jatuh tempo salah input | 15% | Tempo invoice diisi lebih pendek dari kesepakatan dengan pemasok. |
| Menunggu tukar faktur / dokumen | 10% | Pembayaran menunggu faktur asli atau tanda terima. |

Sinyal:

- Telat > 60 hari → "Sudah dibayar tapi belum ditandai lunas" naik.
- Invoice Rp 0 → peringatan harga PO belum diisi.

## Modul data (`config/ai_vocabulary.php`)

- `pengiriman` — Pengiriman (Sales Order)
- `surat jalan` — Surat Jalan Pengiriman
- `invoice penjualan` — Invoice Penjualan
- `pembelian` — Pembelian (Purchase Order)
- `tanda terima` — Tanda Terima Pembelian
- `invoice pembelian` — Invoice Pembelian
- `penerimaan pembelian` — Penerimaan Barang Pembelian (riwayat)
- `produksi` — Produksi
- `resep` — Resep (BOM)
- `opname produk` — Stok Opname Produk
- `opname bahan` — Stok Opname Bahan Mentah
- `produk bermasalah` — Produk Bermasalah
- `retur ke pemasok` — Retur ke Pemasok
- `retur produk dari customer` — Retur Produk dari Customer
- `retur bahan dari customer` — Retur Bahan dari Customer
- `transfer stok` — Transfer Stok antar Gudang
- `stok produk` — Stok Produk
- `stok bahan` — Stok Bahan Mentah
- `riwayat stok` — Riwayat Pergerakan Stok
- `pengelolaan bahan` — Pengelolaan Stok Manual
- `kas umum` — Kas (Kas Besar)
- `kas admin` — Kas Operasional Admin
- `kas gudang` — Kas Operasional Gudang
- `kas armada` — Kas Operasional Armada
- `kas sales` — Kas Operasional Sales
- `petty cash` — Petty Cash
- `kategori kas` — Kategori Kas
- `bank` — Akun Bank
- `produk` — Produk
- `varian produk` — Varian Produk
- `konversi satuan produk` — Konversi Satuan Produk
- `bahan` — Bahan Mentah
- `varian bahan` — Varian Bahan Mentah
- `konversi satuan bahan` — Konversi Satuan Bahan
- `kategori` — Kategori
- `satuan` — Satuan
- `variasi` — Variasi
- `customer` — Customer
- `pemasok` — Pemasok
- `pengguna` — Pengguna
- `peran` — Peran
- `gudang` — Gudang
- `tipe gudang` — Tipe Gudang
- `penempatan pengguna` — Penempatan Pengguna di Gudang
- `area` — Area
- `aktivitas` — Aktivitas Pengguna
