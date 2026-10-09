# Produksi

## Tujuan
Mencatat produksi produk jadi dari resep, memotong stok bahan, menambah stok produk setelah ACC.

## Siapa yang bisa
Modul **Produksi**: view / create / edit / delete / others (ACC / tolak / batal).

## Alur
1. Produksi fisik di pabrik dilakukan dulu.
2. Buat produksi di sistem (+ detail, foto jika ada) → status Pending.
3. ACC → Berhasil (stok bahan turun, produk naik).
4. Tolak → Tolak.
5. Pembatalan setelah proses: lewat alur "menunggu batal" (status 4); yang boleh membatalkan = yang punya ability untuk batal (others sesuai peran).

## Status header
- Pending
- Berhasil
- Tolak
- Menunggu batal
