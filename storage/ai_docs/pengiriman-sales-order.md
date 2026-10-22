# Pengiriman (Sales Order)

## Tujuan
Mencatat pesanan penjualan ke armada/customer dan pengirimannya. Pengiriman baru umumnya masuk dari PMO lewat External API (jadwal / shipped), bukan dibuat manual di halaman ini.

## Siapa yang bisa
Modul **Pengiriman**: view / create / edit / delete. ACC & tolak mengikuti tahap approval di bawah (bukan ability others lagi).

## Alur ACC 2 tahap (berurutan, tidak bisa lompat)
1. **Staf QC & Gudang** — konfirmasi buta: jumlah & satuan asli TIDAK ditampilkan; staf mengisi jumlah + satuan hasil hitung fisik, lalu Terima. Sistem mencocokkan dengan data pengiriman (qty dibandingkan setelah konversi satuan rantai produk). Kosong → baris ditandai merah; tidak cocok → ditolak dengan pesan item bermasalah, approval tidak dicatat.
2. **Kepala Operasional** — approve setelah QC. Di tahap ini stok dicek & dipotong dan status menjadi **Diterima**. Stok kurang → approval Ops batal, pengiriman tetap Pending. Tahap Ops wajib dari gudang aktif = gudang utama; tahap QC boleh dari gudang aktif mana pun.

Pengganti (pola sama seperti Stock Transfer): **Direksi** dan **Developer** boleh menggantikan QC atau Kepala Ops secara berurutan. Staf yang sekaligus QC ter-assign dan kepala gudang (flag kepala cabang) bisa mengisi dua tahap sendiri. QC wajib role Staf QC & Gudang yang ter-assign ke gudang utama; Kepala Ops wajib flag kepala di gudang utama.

Tolak bisa di tahap QC atau Ops dengan alasan wajib; tidak ada mutasi stok.

## Status di list Pengiriman
- **Requested** — Pending, menunggu Staf QC & Gudang.
- **Need Approval** — sudah di-ACC QC, menunggu Kepala Operasional.
- **Diterima** — ACC lengkap, stok sudah dipotong.
- **Ditolak** — ditolak di tahap QC/Ops.
- **Dijadwalkan / Belum Terkirim / Sudah Terkirim / Dibatalkan** — status dari External API (PMO).

Kolom status juga menampilkan ikon segitiga peringatan (⚠) bila pengiriman punya kekurangan stok.

## Kekurangan stok & Form Kekurangan Barang
Saat pengiriman dari PMO terdeteksi stoknya kurang, sistem mencatat dokumen kekurangan (nomor BG-xxxx: diminta vs tersedia vs kurang per item). Di list Pengiriman, baris tersebut berikon ⚠ dan tombol printer untuk mencetak **Form Kekurangan Barang** (PDF A5). Print ini hanya mencetak form — tidak otomatis membuat Production Planning.

## Stok
Stok produk berkurang saat ACC Kepala Operasional selesai (tahap 2), bukan saat SO dibuat dan bukan saat ACC QC.
