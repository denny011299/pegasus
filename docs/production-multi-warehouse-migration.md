# SOP — Migrasi DB Production ke Multi-Gudang (Fase 2)

**Tujuan:** upgrade database production (schema fase1) ke fase2 multi-gudang **tanpa menghapus data live**.

**Branch:** `fase-2` / `fase2/main`  
**Cek otomatis:** `php docs/scripts/verify_production_import.php`  
**Seeder utama:** `ProductionMultiWarehouseSeeder`  
**Command:** `php artisan pegasus:production-upgrade` (default seeder) atau `--sql`

---

## 0. Ringkasan keputusan

| Lingkungan | Cara yang disarankan | Kenapa |
|---|---|---|
| **Lokal / staging (uji coba)** | Seeder **atau** `--sql` + verify | Bisa diulang, diverifikasi sebelum sentuh live |
| **Live production** | **Seeder** atau **`--sql`** in-place | DB live tetap jalan; tidak perlu import dump ulang |

### Dua opsi upgrade (pilih satu)

| Opsi | Perintah | Kapan dipakai |
|---|---|---|
| **A — Seeder** (default) | `php artisan pegasus:production-upgrade` | Paling mudah; sama dengan live |
| **B — SQL** | `php artisan pegasus:production-upgrade --sql` | Server tanpa artisan / prefer phpMyAdmin / mysql CLI |

> **Jangan** jalankan full SQL import dump **dan** seeder/upgrade in-place pada DB yang sama. Pilih **satu** jalur.

---

## 1. File terkait

| Path | Fungsi |
|---|---|
| `database/seeders/ProductionMultiWarehouseSeeder.php` | Entry point upgrade via PHP |
| `app/Console/Commands/ProductionUpgradeCommand.php` | `pegasus:production-upgrade` (--sql / seeder) |
| `database/sql/pegasuso_production_upgrade_in_place.sql` | **1 file SQL** upgrade in-place (generate via script) |
| `docs/scripts/build_production_upgrade_in_place_sql.php` | Generate file SQL in-place |
| `database/seeders/data/production_default_warehouse.json` | Konfigurasi 2 gudang seed |
| `database/sql/pegasuso_production_multi_warehouse_upgrade.sql` | DDL + backfill multi-gudang |
| `database/sql/pegasuso_production_fase2_schema_gap.sql` | Kolom/tabel fase2 yang belum ada di dump production |
| `database/sql/pegasuso_production_import_with_warehouses.sql` | Dump + warehouse_id (opsional, untuk UAT) |
| `docs/scripts/build_production_warehouse_sql.php` | Generate ulang file import dari dump terbaru |
| `docs/scripts/verify_production_import.php` | Bandingkan dump vs DB setelah import |

### Gudang default (dari config)

| ID | Nama | Peran |
|---|---|---|
| 1 | Gudang Hikari Pegasus Sidoarjo | Gudang utama — semua stok legacy (`warehouse_id = 1`) |
| 2 | Gudang Eceran Sidoarjo | Gudang eceran — baris stok 0 (seed) |

---

## 2. Aturan keamanan data

Script upgrade **TIDAK** melakukan:

- `DELETE` / `TRUNCATE` data bisnis
- Mengubah qty stok lama (`ps_stock`, `ss_stock`)
- Menghapus transaksi (`sales_orders`, `log_stocks`, dll.)

Yang ditambahkan:

- Tabel/kolom baru fase2 (idempotent)
- 2 gudang + `staff_warehouses`
- Backfill `warehouse_id = 1` untuk stok/opname/log lama
- Baris stok 0 di gudang eceran

Seeder **idempotent** — aman dijalankan ulang (skip jika sudah ada).

---

## 3. Setup lokal (wajib sebelum staging)

### 3.1 Prasyarat

```bash
# Clone / checkout branch fase-2
git checkout fase-2
composer install

# Buat DB kosong di MySQL lokal, contoh: pegasus_production_local
# Set .env:
# DB_DATABASE=pegasus_production_local
```

### 3.2 Jalur A — Seeder (disarankan)

```bash
php artisan pegasus:production-upgrade
# sama dengan:
php artisan db:seed --class=ProductionMultiWarehouseSeeder
```

### 3.3 Jalur B — SQL in-place (opsi alternatif)

```bash
# Generate / refresh file SQL gabungan
php docs/scripts/build_production_upgrade_in_place_sql.php

# Via artisan (include RoleWarehouseAccessSeeder)
php artisan pegasus:production-upgrade --sql

# Atau manual di MySQL / phpMyAdmin:
mysql -u root pegasus_production_local < database/sql/pegasuso_production_upgrade_in_place.sql
php artisan db:seed --class=RoleWarehouseAccessSeeder
```

### 3.4 Jalur C — SQL import gabungan dari dump (UAT saja)

```bash
# Generate file import dari dump terbaru (include schema gap di postfix)
php docs/scripts/build_production_warehouse_sql.php "C:\path\to\dump-terbaru.sql"

# Import ke DB lokal
mysql -u root pegasus_production_local < database/sql/pegasuso_production_import_with_warehouses.sql

# Verifikasi
php docs/scripts/verify_production_import.php "C:\path\to\dump-terbaru.sql" pegasus_production_local
```

> Regenerate file import setiap kali ada dump production baru atau perubahan schema gap.

### 3.5 Smoke test setelah upgrade

```bash
php artisan serve
```

Cek manual:

- [ ] Login berhasil, gudang aktif muncul di navbar
- [ ] Stok produk & bahan — gudang Hikari = stok lama
- [ ] Gudang Eceran — stok 0 (belum ada transaksi)
- [ ] Edit produk (kolom safety stock tidak error)
- [ ] Sales order / PO / log stok masih ada

```bash
php artisan test --filter=StockTransferWorkflowTest
```

---

## 4. Deploy ke staging

Urutan:

1. **Push** branch `fase-2` (atau merge ke `fase2/main` sesuai workflow tim)
2. Deploy kode ke server staging
3. **Backup DB staging** dulu
4. Jalankan seeder di staging:

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan db:seed --class=ProductionMultiWarehouseSeeder
```

5. Verifikasi (jika punya dump staging sebelum upgrade):

```bash
php docs/scripts/verify_production_import.php /path/dump-staging.sql nama_db_staging
```

6. Smoke test + `/system/deployment-check`

---

## 5. Deploy ke live (production)

### Sebelum cutover

- [ ] Backup penuh DB live (`mysqldump`) + folder `storage/`
- [ ] Uji seeder di staging dengan clone DB live — verify PASS
- [ ] Siapkan jadwal maintenance (user tidak transaksi)

### Hari H

```bash
# 1. Maintenance ON

# 2. Deploy kode fase-2 / fase2/main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Upgrade DB (IN-PLACE — pilih salah satu)
php artisan pegasus:production-upgrade          # seeder (default)
# atau
php artisan pegasus:production-upgrade --sql      # SQL in-place

# 4. Smoke test (stok, SO, PO, edit produk, gudang)

# 5. /system/deployment-check

# 6. Maintenance OFF
```

### Kenapa live pakai seeder, bukan SQL import?

| | Seeder in-place | Full SQL import |
|---|---|---|
| Data transaksi setelah dump | **Tetap ada** | **Hilang** (snapshot dump) |
| Downtime | Lebih singkat | Lebih lama |
| Risiko dobel import | Rendah | Tinggi kalau import 2x |

---

## 6. Verifikasi otomatis

```bash
php docs/scripts/verify_production_import.php [dump.sql] [database_name]
```

Default:

- Dump: `C:/Users/Ruben/Downloads/pegasuso_pegasus_production 31-08-26.sql`
- DB: `pegasus_production_local`

Yang dicek:

| Cek | Keterangan |
|---|---|
| Row count 75+ tabel | Harus identik dengan dump |
| MAX primary key | `sales_orders`, `log_stocks`, dll. tidak boleh kurang |
| Stok gudang utama | `warehouse_id=1` — jumlah baris & total qty identik dump |
| Schema fase2 | `warehouse_id`, `ps_safety_stock`, approval ST, kolom SO baru |

**Expected tambahan** (bukan kehilangan data):

- `product_stocks` +N baris stok 0 gudang eceran
- `supplies_stocks` +N baris stok 0 gudang eceran
- `warehouses` +2, `warehouse_types` +2, `staff_warehouses` +N
- `migrations` +N (patch schema)

---

## 7. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `Unknown column ps_safety_stock` | Schema gap belum jalan | Run seeder lagi atau `pegasuso_production_fase2_schema_gap.sql` |
| Stok gudang Hikari kosong | `warehouse_id` belum di-backfill | Run seeder; cek `production_default_warehouse.json` |
| Duplikat stok eceran | Seeder di-run berkali-kali dengan kondisi anomali | Cek warning duplikat di output seeder; unique index seharusnya mencegah |
| Verify FAIL — baris kurang | Import dump tidak lengkap / DB salah | Import ulang dump mentah + seeder (jangan patch 2x di DB yang sudah lengkap) |
| `warehouse_id` NULL di stok lama | Backfill belum jalan | Seeder `backfillWarehouseIds()` — run seeder |

---

## 8. Checklist cepat

### Lokal baru

```
[ ] git checkout fase-2
[ ] composer install
[ ] .env → DB lokal
[ ] import dump mentah
[ ] php artisan db:seed --class=ProductionMultiWarehouseSeeder
[ ] php docs/scripts/verify_production_import.php → PASS
[ ] smoke test manual
```

### Staging / Live

```
[ ] backup DB
[ ] maintenance ON (live)
[ ] deploy kode
[ ] php artisan db:seed --class=ProductionMultiWarehouseSeeder
[ ] smoke test
[ ] deployment-check
[ ] maintenance OFF (live)
```

---

## 9. Dokumen terkait

- `docs/fase2-merge-inventory.md` — inventaris fitur fase2
- `docs/backlog-stock-multi-gudang.md` — keputusan bisnis multi-gudang
- `database/seeders/ProductionMultiWarehouseSeeder.php` — komentar inline di class
