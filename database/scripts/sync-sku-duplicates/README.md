# Sync SKU kembar — keep IPM + pasang ref PMO

**Arah benar (bukan pindah ke produk PMO):**

1. Varian lokal (tanpa ref) = **KEEP** — tetap di produk IPM (1 produk banyak varian)
2. Varian sync PMO (produk ber-ref) = **DROP** — dimatikan
3. `ref_product_id` dari produk PMO dipasang ke produk IPM (1 ref / produk)
4. SO / pengiriman / stok / BOM remap drop → keep

## Urutan di DEV

1. Restore dump **DEV(4)** (kalau staging sudah kena merge SKU arah lama)
2. `migrate-units-ipm-to-pmo/ALL_IN_ONE_phpmyadmin.sql` → cek → `COMMIT;`
3. File ini → Go (auto-COMMIT di akhir)
4. Cek sample AIR AKI: varian 14 di `product_id=3`, ada ref, 603 status=0

## Mapping

Lihat `mapping.md`, `ref-attach-plan.md`, `ambiguous.md`.