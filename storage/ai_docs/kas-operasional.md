# Kas Operasional (Admin / Gudang / Armada / Sales)

## Tujuan
Semacam petty cash yang dibagi per role/fungsi (Admin, Gudang, Armada, Sales), dengan ACC/tolak.

## Siapa yang bisa
Modul terpisah (nama permission bisa lama/baru):
- Kas Operasional Admin / Kas Admin
- Kas Operasional Gudang / Kas Gudang
- Kas Operasional Armada / Kas Armada
- Kas Operasional Sales / Kas Sales
Plus payung **Kas Operasional**. Ability: view/create/edit/delete/others.

Yang ACC/tolak: yang punya ability **others** di modul kas terkait.

## Alur umum
1. Buat transaksi (+ detail) → status **Menunggu**. Unggah bukti **selalu wajib**.
2. Accept → status **Diterima** (bisa berdampak ke Kas / saldo terkait).
3. Decline → status **Ditolak**.
4. Hapus → status **Dihapus**.
5. Kas Gudang yang di-ACC dapat otomatis membuat transaksi Kas Armada terkait.
