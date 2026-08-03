# Pallet di Produksi (shortcut)

Hanya untuk **form Produksi** — mempercepat input qty.

## Cara kerja

1. Di master **Produk → Varian**, isi kolom opsional **Isi / Pallet**  
   contoh: `50` artinya **1 Pallet = 50 satuan default** (biasanya DOS).
2. Di **Tambah Produksi**, pilih produk → dropdown satuan muncul opsi  
   `PALLET (1 = 50 DOS)`.
3. Isi qty `3` + satuan Pallet → sistem menyimpan **`pd_qty = 150`** dengan **`unit_id` = satuan default**.
4. ACC / stok / BOM **tidak** mengenal unit Pallet — semua tetap di satuan biasa.

## Bukan

- Bukan relasi dinamis seperti `product_relations`
- Bukan fitur Stock Transfer
- Tidak menambah jalur konversi di backend ACC

## File terkait

- Migration: `qty_per_pallet` di `product_variants`
- Model: `ProductVariant`, expose di `Bom::getBom`
- UI produk: `insertProduct.js` / blade
- UI produksi: `Production.js` (`__PALLET__` → convert)
