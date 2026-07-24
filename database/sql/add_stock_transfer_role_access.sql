-- =============================================================================
-- PEGASUS - Permission role: Stock Transfer
-- Aman dijalankan ulang (cek NOT LIKE name).
-- =============================================================================

UPDATE roles
SET role_access = JSON_ARRAY_APPEND(
  IFNULL(role_access, '[]'),
  '$',
  '{"name":"Stock Transfer","akses":["create","edit","delete","view","others"]}'
)
WHERE status = 1
  AND (
    role_name LIKE '%Developer%'
    OR role_name = 'Direksi'
    OR role_name LIKE '%Superadmin%'
    OR role_name LIKE '%Super Admin%'
  )
  AND role_access NOT LIKE '%"name":"Stock Transfer"%';
