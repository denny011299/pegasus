-- Tambah modul Gudang & Tipe Gudang ke role_access
-- Kompatibel MariaDB / MySQL (tanpa CAST AS JSON, 1 append per query)

-- Cek dulu:
-- SELECT role_id, role_name FROM roles WHERE status = 1 AND (role_name LIKE '%Developer%' OR role_name = 'Direksi');

UPDATE roles
SET role_access = JSON_ARRAY_APPEND(
  IFNULL(role_access, '[]'),
  '$',
  '{"name":"Gudang","akses":["create","edit","delete","view","others"]}'
)
WHERE status = 1
  AND (
    role_name LIKE '%Developer%'
    OR role_name = 'Direksi'
    OR role_name LIKE '%Superadmin%'
    OR role_name LIKE '%Super Admin%'
  )
  AND role_access NOT LIKE '%"name":"Gudang"%';

UPDATE roles
SET role_access = JSON_ARRAY_APPEND(
  IFNULL(role_access, '[]'),
  '$',
  '{"name":"Tipe Gudang","akses":["create","edit","delete","view","others"]}'
)
WHERE status = 1
  AND (
    role_name LIKE '%Developer%'
    OR role_name = 'Direksi'
    OR role_name LIKE '%Superadmin%'
    OR role_name LIKE '%Super Admin%'
  )
  AND role_access NOT LIKE '%"name":"Tipe Gudang"%';
