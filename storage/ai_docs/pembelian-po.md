# Pembelian (Purchase Order)

## Tujuan
Memesan bahan/barang ke pemasok, invoice, dan alur pembayaran. Fitur penerimaan/delivery PO terpisah sudah tidak dipakai — jangan dijelaskan sebagai alur aktif.

## Siapa yang bisa
Modul **Pembelian**: view / create / edit / delete / others (ACC/tolak PO, invoice).

## Alur
1. Buat PO + detail → status **Dibuat** (menunggu ACC).
2. ACC PO → status **Disetujui**, stok bertambah saat ACC; invoice pending dibuat.
3. Tolak PO → status **Ditolak** (jika sudah ACC, stok dibalik).
4. Hapus → status **Dihapus**.
5. Invoice PO: menunggu → disetujui / ditolak.
6. Progress pembayaran terlihat di status pembayaran PO.

## Cara input PO
1. Buka menu **Penjualan & Pembelian → Pembelian**, klik tombol tambah sampai muncul modal **Tambah Purchase Order**.
2. Isi **Nama Pemasok** (wajib) dan **Tanggal** (wajib).
3. Tambah barang ke tabel: cari produk/bahan, atau scan / ketik barcode lalu isi **Qty** dan klik tambah.
4. Di setiap baris barang cek **Variasi**, **SKU**, **Qty**, dan **Harga Beli**. **Harga Beli wajib diisi sesuai harga pemasok** — kalau dibiarkan 0, Subtotal, total PO, Tanda Terima, invoice, dan hutang ikut Rp 0.
5. Isi bila ada: **Tipe Diskon**, **Diskon**, **PPN**, **Biaya Pengiriman**.
6. Isi **Keterangan** (wajib) dan lampirkan **Foto Bukti** (wajib) lewat tombol **Foto**.
7. Klik **Tambah Pembelian**. PO tersimpan dengan status **Dibuat** dan menunggu ACC.
8. Yang punya akses ACC membuka PO tersebut lalu ACC → status **Disetujui**, stok bertambah, invoice pending dibuat.

## Tips mencegah salah input PO
- Jangan simpan PO dengan Harga Beli 0 kecuali barang memang bonus; kalau bonus, tulis "bonus" di Keterangan.
- Pastikan pemasok dan satuan/variasi benar sebelum ACC — setelah ACC, stok sudah bertambah.
- Harga belum pasti dari pemasok? Konfirmasi dulu sebelum membuat Tanda Terima, karena nominal TT mengikuti harga PO.

## Status PO
- Created/menunggu
- Confirmed/ACC
- hapus
- tolak

## Status Pembayaran PO
- belum dibayar
- dalam proses / terkait TT
- lunas

Transfer/bukti pembayaran dilakukan di luar sistem; di aplikasi yang dicatat adalah status pembayaran.
