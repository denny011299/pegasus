# Gudang & Tipe Gudang

## Tujuan
Master data lokasi stok (gudang besar, gudang eceran/cabang, dll.) dan tipenya.

## Siapa yang bisa
Modul **Gudang** / **Tipe Gudang**: view / create / edit / delete sesuai hak akses. Akses menu juga bisa dibatasi per gudang aktif staf.

## Alur
1. Kelola tipe gudang (contoh: gudang utama vs eceran).
2. Kelola daftar gudang: nama, tipe, status aktif.
3. Staf dapat ditempatkan ke gudang; beberapa transaksi (stok, transfer, opname, kas gudang) mengikuti gudang aktif.

## Catatan
Stock Transfer, Stok Produk, Stok Bahan, Opname, dan beberapa laporan selalu terikat ke gudang. Saat user bertanya stok/transfer, sebutkan juga nama gudang seperti di UI.
