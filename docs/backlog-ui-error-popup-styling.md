# Backlog — Sweep sisa popup error ke `showPgErrorModal()`

Status: belum dikerjakan. Dicatat 2026-09-01 setelah GitHub #101.

## Konteks

`showPgErrorModal()` (global, `resources/views/layout/partials/footer-scripts.blade.php`) adalah
popup error bertema PG (rounded-16px, tombol `pg-btn-confirm--danger`, ikon merah) — versi generik
dari `showSoErrorModal()` di `Sales_Order.js` (commit `e90f255`). Sejauh ini cuma dipakai di:

- `Sales_Order.js` — alur ACC Pengiriman (`showSoErrorModal`, aslinya).
- `Production.js` — `handleProductionValidationError()` dan `promptRecipeNeedsUpdate()` (GitHub
  #101, popup error cek stok saat tambah baris produk / submit).

Popup error lain di aplikasi (mayoritas lewat `notifikasi("error", ...)`, yang cuma
`Swal.fire({icon, title, text})` polos tanpa styling PG) belum disamakan.

## Yang perlu disapu nanti

- **`Production.js`** — masih ada ~9 pemanggilan `notifikasi("error", ...)` lain di file yang sama
  (validasi field form: "Qty Tidak Valid", "Gudang Tujuan Wajib", "Pallet Tidak Valid", dll.) yang
  belum dipindah ke `showPgErrorModal()`. Sengaja tidak disentuh saat #101 karena di luar scope
  (bukan bagian dari alur cek stok), tapi konsisten kalau disamakan juga.
- **Modul lain** — grep `notifikasi("error"` / `notifikasi('error'` lintas
  `public/Custom_js/Backoffice/**` untuk daftar lengkap kandidat. Prioritaskan modul dengan modal
  `pg-modal--*` (form/confirm/danger) yang sudah di-redesign — di situ kontras dengan popup error
  default SweetAlert2 paling kentara.

## Cara kerja

Ganti pola `notifikasi("error", header, message)` → `showPgErrorModal(header, message)`. Perhatikan
`showPgErrorModal()` merender `message` sebagai HTML (di-escape lewat `$("<div>").text(...).html()`)
supaya line-break (`\n`) dari pesan gabungan (mis. daftar nama produk yang dipisah koma) tetap
tampil rapi — beda dari `notifikasi()`'s `text:` (plain text, tidak wrap HTML).

Bukan pekerjaan berisiko/urgent — murni konsistensi visual, tidak ada perubahan perilaku. Kerjakan
kapan ada waktu luang, tidak perlu PR tersendiri buru-buru.
