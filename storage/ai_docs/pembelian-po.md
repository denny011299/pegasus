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
