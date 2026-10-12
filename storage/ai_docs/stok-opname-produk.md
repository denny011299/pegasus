# Stok Opname Produk

## Tujuan
Menghitung stok fisik produk dan menyesuaikan sistem setelah disetujui.

## Siapa yang bisa
Modul **Stok Opname Produk**: view / create / edit / delete / others (ACC/tolak).

## Alur
1. Buat dokumen (bisa disimpan sebagai draft atau langsung diajukan).
2. Isi qty fisik per baris.
3. Submit draft → keluar dari draft (status tetap menunggu).
4. ACC → status **Disetujui**, stok sistem disesuaikan.
5. Tolak → status **Ditolak**.
6. Hapus → status **Dihapus**.

Operasional memakai **versi terbaru** (lines / v2), bukan alur lama.

## Aturan input
Yang punya akses create/others boleh terlibat. Jika di sistem ada **stok opname freeze**, hanya **satu orang** yang boleh menginput untuk dokumen tersebut.

## Status
- Draft: ya/tidak (terpisah dari status)
- Menunggu
- Disetujui
- Ditolak
- hapus
