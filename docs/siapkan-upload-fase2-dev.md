# Siapkan upload DEV (`upload-fase2-dev`)

Panduan untuk komputer lain: generate folder upload dari branch **`fase2/main`**, lalu upload ke server DEV.

| | |
|--|--|
| Branch | `fase2/main` |
| Folder pack | `PMI/upload-fase2-dev/` (sibling repo `pegasus`, **di luar** git) |
| Server DEV | `/home/pegasuso/dev.mypegasus.online` |

## 1. Ambil kode terbaru

```bash
cd "<path-ke>/PMI/pegasus"   # atau worktree pegasus-main
git fetch origin
git checkout fase2/main
git pull origin fase2/main
```

Tip commit saat pack ini dibuat: **`d45522e1`**  
(`feat(external-api): lookup uang sangu Kas Gudang untuk PMO`)

## 2. Range delta (19 hari → tip)

```text
db8f4231..fase2/main
```

Isi: pengembalian + pengiriman QC/mismatch (highlight qty/satuan) + SKU retur API + uang sangu Kas Gudang.

Highlight salah qty/satuan pengiriman = commit **`61ff467a`**.

## 3. Generate pack (timpa folder yang sama)

Folder: `../upload-fase2-dev` relatif ke repo (atau `PMI/upload-fase2-dev`).

**PowerShell (Windows):**

```powershell
$repo = "C:\Ruben Data\Kerja Git\OKEJOB\PEGASUS\PMI\pegasus"   # sesuaikan
$pack = "C:\Ruben Data\Kerja Git\OKEJOB\PEGASUS\PMI\upload-fase2-dev"
cd $repo
git checkout fase2/main
git pull origin fase2/main

$base = "db8f4231^"
$tip  = "fase2/main"
$files = git diff --name-only "$base..$tip" | Where-Object {
  $_ -notmatch '^(tests/|\.cursor/|docs/fase2-snapshots/|tmp/|vendor/|node_modules/)' -and
  $_ -notmatch '^public/kas_admin/' -and
  $_ -notmatch '\.(png|jpg|jpeg|gif|webp)$' -and
  $_ -notmatch '^storage/framework/'
}

New-Item -ItemType Directory -Force -Path $pack | Out-Null
foreach ($f in $files) {
  $src = Join-Path $repo ($f -replace '/','\')
  if (-not (Test-Path -LiteralPath $src)) { continue }
  $dst = Join-Path $pack ($f -replace '/','\')
  New-Item -ItemType Directory -Force -Path (Split-Path $dst -Parent) | Out-Null
  Copy-Item -Force -LiteralPath $src $dst
}
```

**Jangan** upload seluruh sisa file lama di folder pack — ikut daftar runtime di `BACA-DULU.txt` / daftar di bawah.

## 4. File runtime yang di-upload (16)

Salin path yang sama ke root project server:

```text
app/ExternalApi/Docs/Endpoints/V1/CashGudangArmadaAllowanceDoc.php
app/ExternalApi/Support/ReturnItemResolver.php
app/Http/Controllers/CustomerController.php
app/Http/Controllers/CustomerReturnController.php
app/Http/Controllers/ExternalApi/V1/CashGudangAllowanceController.php
app/Models/SalesOrder.php
app/Models/SalesOrderDetail.php
config/ai_vocabulary.php
config/externalapi.php
database/migrations/2026_10_20_100000_add_indexes_for_sales_order_list.php
docs/external-api-cash-gudang-armada-allowance.json
public/Custom_js/Backoffice/Customers/Customer_Return.js
public/Custom_js/Backoffice/Customers/Sales_Order.js
resources/views/Backoffice/Customers/Sales_Order.blade.php
routes/external-api/v1.php
storage/ai_docs/pengiriman-sales-order.md
```

## 5. Di server DEV

```bash
cd /home/pegasuso/dev.mypegasus.online
# upload 16 file di atas (timpa)

php artisan migrate --path=database/migrations/2026_10_20_100000_add_indexes_for_sales_order_list.php --force
php artisan optimize:clear
```

## 6. Skip

- `tests/`, `.cursor/`, `docs/fase2-snapshots/`, `vendor/`, `node_modules/`, `.env`
- foto uji `public/kas_admin/`
- `storage/framework/views` / cache

## Catatan

- Pack folder = **satu** `upload-fase2-dev` (update in place), jangan buat `upload-YYYY-MM-DD-*` baru.
- Setelah `git pull`, kalau tip `fase2/main` sudah maju, regenerate pack dari range yang sama (atau perluas base bila ada commit baru yang ikut di-deploy).
