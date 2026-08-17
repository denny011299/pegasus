# Fase 2 schema SQL

**Import 1 file saja:**

`database/sql/fase2_schema_all.sql`

- Semua perubahan tabel Fase 2 digabung
- Idempotent (IF NOT EXISTS / cek kolom)
- Tanpa seeder master data

File terpisah di folder ini = sumber potongan; untuk server cukup `fase2_schema_all.sql`.

**Hanya isi menu sidebar Gudang / Tipe Gudang** (kalau schema sudah jalan):

`database/sql/add_warehouse_sidebar_gudang_menus.sql`
