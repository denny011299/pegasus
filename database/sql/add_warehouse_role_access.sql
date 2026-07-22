-- Tambah modul Gudang & Tipe Gudang ke role_access (format sama Kategori/Variasi)
-- Target contoh: Okejob (Developer) / Direksi
-- Jalankan di MySQL/MariaDB jika tidak ingin pakai seeder.

-- Cek dulu:
-- SELECT role_id, role_name FROM roles WHERE status = 1 AND (role_name LIKE '%Developer%' OR role_name = 'Direksi');

UPDATE roles
SET role_access = JSON_ARRAY_APPEND(
  COALESCE(role_access, JSON_ARRAY()),
  '$',
  CAST('{"name":"Gudang","akses":["create","edit","delete","view","others"]}' AS JSON),
  '$',
  CAST('{"name":"Tipe Gudang","akses":["create","edit","delete","view","others"]}' AS JSON)
)
WHERE status = 1
  AND (
    role_name LIKE '%Developer%'
    OR role_name = 'Direksi'
    OR role_name LIKE '%Superadmin%'
    OR role_name LIKE '%Super Admin%'
  )
  AND role_access NOT LIKE '%"name":"Gudang"%';

-- Catatan: jika modul sudah pernah ditambahkan, query di atas tidak akan double-insert
-- karena filter NOT LIKE. Untuk update akses penuh pakai seeder Laravel:
-- php artisan db:seed --class=RoleWarehouseAccessSeeder
