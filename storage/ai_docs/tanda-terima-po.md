# Tanda Terima PO

## Tujuan
Membuat tanda terima (TT) untuk kumpulan PO yang siap ditagih/dibayar. Bukti nota terkait TT.

## Siapa yang bisa
Modul **Tanda Terima PO**: view / create / edit / delete / others (ACC/tolak TT). Siapa pun yang punya hak akses modul ini.

## Alur
1. Buat TT dari PO yang memenuhi syarat (status pembayaran memenuhi syarat & PO belum masuk tanda terima lain). Unggah bukti wajib bila form meminta.
2. ACC TT → TT **Disetujui**, status pembayaran PO terkait menjadi **Lunas**.
3. Tolak TT → TT **Ditolak**, PO dilepas dari TT dan status pembayarannya kembali **Belum dibayar**.
