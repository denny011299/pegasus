# Pengiriman (Sales Order)

## Tujuan
Mencatat pesanan penjualan ke armada/customer dan pengirimannya.

## Siapa yang bisa
Modul **Pengiriman**: view / create / edit / delete / others (ACC & tolak).

## Alur
1. Buat Sales Order (+ detail).
2. ACC (status **Disetujui**) atau tolak/hapus (status **Ditolak/Dihapus**) — butuh ability **others** untuk ACC/tolak.
3. Buat delivery order dari SO.
4. Update/hapus delivery sesuai izin.

## Status SO
- menunggu ACC (atau diinput ulang setelah ditolak)
- disetujui (ACC)
- ditolak / dihapus (sengaja sama): permintaan bisnis agar SO yang dihapus tetap tampil, jadi aksi hapus memakai status tolak

## Stok
Stok produk berkurang saat pengiriman (delivery / ACC pengiriman), bukan saat SO dibuat.
