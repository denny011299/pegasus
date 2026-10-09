# Transfer Stok & Produk Bermasalah (dua modul terpisah)

Dokumen ini **wajib dibaca bersama**. Kedua menu tampak mirip (produk bergerak / stok berubah) tetapi **bukan satu alur** dan **tidak saling menggantikan**.

---

## Aturan pembeda (paling penting)

| | Transfer Stok (Stock Transfer) | Produk Bermasalah |
|--|-------------------------------|-------------------|
| Menu sidebar | **Stock Transfer** (+ Laporan Stock Transfer) | **Produk Bermasalah** |
| Kode dokumen | `ST…` (contoh ST0087) | `PI…` (contoh PI0204) |
| Tujuan | Pindah stok **antar gudang** (asal → tujuan) | Catat produk rusak/cacat/bermasalah lalu sesuaikan stok setelah ACC |
| Hubungan | **Tidak ada** hubungan alur dengan Produk Bermasalah | **Tidak ada** hubungan alur dengan Transfer Stok |

**Wajib untuk asisten AI:**
1. User sebut `ST…` → cari **hanya** Transfer Stok. Jangan buka Produk Bermasalah.
2. User sebut `PI…` → cari **hanya** Produk Bermasalah. Jangan buka Transfer Stok.
3. Jika kode tidak ada di modul yang sesuai prefix-nya → jawab **tidak ditemukan**. Jangan mengalihkan ke modul lain, jangan mengarang catatan silang (“ternyata ini PI…”).

---

## A. Transfer Stok (Stock Transfer)

### Tujuan
Memindahkan stok produk dari satu gudang ke gudang lain (gudang besar ↔ eceran, transfer hasil produksi, dll.).

### Siapa yang bisa
Modul **Stock Transfer**: view / create / edit / delete / others (ACC kirim, ACC terima, tolak, approval QC/Ops sesuai rute).

### Alur
1. Buat Transfer Stok (+ rincian produk/qty) → **Pending**. Stok sumber belum dipotong.
2. Sesuai jenis rute:
   - **Permintaan eceran → gudang utama:** approval QC lalu Kepala Ops di gudang asal; setelah lengkap otomatis **Kirim** (stok sumber dipotong).
   - **Lainnya (produksi / eceran↔eceran, dll.):** tanpa QC/Ops; Acc/Tolak Kirim lalu Acc/Tolak Terima.
3. **Kirim:** stok keluar dari gudang asal.
4. **Terima (Terkirim):** stok masuk gudang tujuan.
5. **Tolak/Batal** dari Pending → Batal (stok sumber tidak berubah).
6. **Batal Kirim** setelah sudah Kirim → stok dikembalikan ke sumber.

### Status Transfer Stok
- Dihapus (dari Pending)
- Pending
- Kirim
- Batal / Ditolak
- Terkirim
- Batal Kirim

### Yang biasa ditampilkan ke user
No. Transfer, tanggal, gudang asal, gudang tujuan, pengirim/penerima, item (produk, varian, qty kirim, qty diterima, satuan), status, catatan.

### Stok
- Pending: belum bergerak  
- Kirim: stok sumber berkurang  
- Terkirim: stok tujuan bertambah  
- Batal Kirim: stok sumber dikembalikan  

---

## B. Produk Bermasalah

### Tujuan
Mencatat produk rusak/bermasalah/cacat (catatan bebas sesuai kejadian) dan menyesuaikan stok setelah ACC.

### Siapa yang bisa
Modul **Produk Bermasalah**: view / create / edit / delete / others (ACC / decline).

### Alur
1. Buat dokumen Produk Bermasalah (+ detail item/qty) → menunggu.
2. ACC → stok berkurang sesuai detail.
3. Tolak / hapus → sesuai pola modul (tetap bisa tampil di daftar).

### Status Produk Bermasalah
- Menunggu
- Disetujui (ACC, stok turun)
- Ditolak / Dihapus

### Yang biasa ditampilkan ke user
No. Dokumen (`PI…`), tanggal, jenis masalah, tipe retur (jika ada), status, item (produk/qty/satuan), pembuat, penyetuju.

---

## C. Cara menjawab pertanyaan user (contoh)

- “Isi ST0087 produk apa?” → Transfer Stok ST0087 + daftar item.  
- “ST0101 isinya apa?” → jika tidak ada: “Transfer Stok ST0101 tidak ditemukan.” **Bukan** PI0204.  
- “PI0204 apa statusnya?” → Produk Bermasalah PI0204 saja.  
- Jangan menulis catatan seperti “kode ST ini ternyata Produk Bermasalah …” — itu salah.

