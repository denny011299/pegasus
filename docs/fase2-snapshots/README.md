# fase-2 file snapshots (cadangan fisik)

Folder ini menyimpan **salinan file** fitur fase-2 biar aman kalau merge/rebase menghapus isi.

## Isi

| Path | Apa |
|------|-----|
| `a5103d2/` | Tree salinan: **semua Controllers/Models/Support** yang beda vs `main` + FE kritis + 49 modal |
| `a5103d2/MANIFEST.txt` | Commit SHA, tanggal, jumlah file |
| `fase2-a5103d2-FULL.zip` | Arsip **seluruh** tip `fase-2` via `git archive` (~233MB) â€” **lokal only**, tidak di-commit |

## Restore satu file

```powershell
Copy-Item -Force docs/fase2-snapshots/a5103d2/app/Support/ProductUnitStock.php app/Support/ProductUnitStock.php
```

## Restore semua modal

```powershell
Copy-Item -Recurse -Force docs/fase2-snapshots/a5103d2/resources/views/components/modals/* resources/views/components/modals/
Copy-Item -Force docs/fase2-snapshots/a5103d2/resources/views/components/modal-popup.blade.php resources/views/components/modal-popup.blade.php
```

## Restore dari zip penuh (kalau folder snapshot kurang)

```powershell
# extract ke folder sementara, lalu copy path yang hilang
Expand-Archive docs/fase2-snapshots/fase2-a5103d2-FULL.zip -DestinationPath tmp-fase2-restore -Force
```

Atau dari git (lebih bersih):

```bash
git checkout a5103d2 -- path/yang/hilang
```

## Refresh snapshot (setelah fitur baru di fase-2)

```bash
php docs/scripts/snapshot-fase2-files.php
php docs/scripts/verify-fase2-inventory.php
```

Lihat juga: `docs/fase2-merge-inventory.md`
