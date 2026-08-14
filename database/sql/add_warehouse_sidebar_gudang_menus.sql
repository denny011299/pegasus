-- =============================================================================
-- Tambah "Gudang" + "Tipe Gudang" ke whitelist warehouses.sidebar_menus
-- Hanya gudang yang SUDAH punya whitelist (JSON terisi).
-- NULL / [] = allow all → tidak diubah.
-- Aman diulang.
-- =============================================================================

-- Cek dulu:
-- SELECT id, warehouse_name, sidebar_menus
-- FROM warehouses
-- WHERE status <> 0;

UPDATE warehouses
SET sidebar_menus = JSON_ARRAY_APPEND(sidebar_menus, '$', 'Gudang'),
    updated_at = NOW()
WHERE status <> 0
  AND sidebar_menus IS NOT NULL
  AND TRIM(CAST(sidebar_menus AS CHAR)) NOT IN ('', '[]', 'null')
  AND sidebar_menus NOT LIKE '%"Gudang"%';

UPDATE warehouses
SET sidebar_menus = JSON_ARRAY_APPEND(sidebar_menus, '$', 'Tipe Gudang'),
    updated_at = NOW()
WHERE status <> 0
  AND sidebar_menus IS NOT NULL
  AND TRIM(CAST(sidebar_menus AS CHAR)) NOT IN ('', '[]', 'null')
  AND sidebar_menus NOT LIKE '%"Tipe Gudang"%';

SELECT
  id,
  warehouse_name,
  CASE
    WHEN sidebar_menus IS NULL OR CAST(sidebar_menus AS CHAR) IN ('[]', 'null', '') THEN 'ALL'
    ELSE 'WHITELIST'
  END AS menu_mode,
  CASE WHEN CAST(sidebar_menus AS CHAR) LIKE '%"Gudang"%' THEN 'Y' ELSE 'N' END AS has_gudang,
  CASE WHEN CAST(sidebar_menus AS CHAR) LIKE '%"Tipe Gudang"%' THEN 'Y' ELSE 'N' END AS has_tipe_gudang
FROM warehouses
WHERE status <> 0
ORDER BY id;
