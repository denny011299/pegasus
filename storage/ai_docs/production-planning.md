# Production Planning (PP) & Work Order

Modul **baru** (fitur flag `FEATURE_PRODUCTION_PLANNING`). **Bukan** sama dengan menu Produksi lama (`productions` / No. Produksi).

## Tujuan
Rencana produksi per gudang: draft → release → Work Order (WO) per PIC → ambil bahan (STB) → ACC QC → ACC Kepala Ops (potong stok) → produksi → Form Gudang FG → ACC QC → ACC Ops (stok masuk + Tally).

## Kode dokumen
| Prefix | Arti |
|--------|------|
| `PP-…` | Production Planning |
| `WO-…` | Work Order (1 PIC / SPV per dokumen) |
| `STB-…` | Surat Ambil Bahan (material_issue) / kembalian bahan (material_return) |
| Form Gudang | Dokumen hasil jadi (`type=warehouse`) menunggu ACC |

**Wajib AI:** `PP…` / `WO…` / `STB…` → modul ini. `production_code` (Produksi lama) → dokumen `produksi.md`, jangan campur.

## Siapa yang bisa ACC Bahan / Form Gudang
1. **Staf QC Gudang** (assigned gudang) — tahap `awaiting_qc`
2. **Kepala Ops** (kepala cabang gudang) — tahap `awaiting_ops` (setelah QC); di sini stok bahan dipotong / FG masuk
3. Direksi / Developer (elevated) boleh keduanya

PIC hanya mengajukan ambil bahan / laporkan hasil — tidak ACC.

## Alur singkat
1. Buat / edit **draft** PP (+ item produk, qty, skala, PIC, armada bila ada).
2. **Release** ke produksi (cek stok bahan / roll-up sesuai aturan gudang).
3. Sistem / user buat **WO** per PIC.
4. PIC **Ambil Bahan** → STB `awaiting_qc` → ACC QC → `awaiting_ops` → ACC Kepala Ops → stok bahan keluar.
5. Produksi fisik; catat output / selesai produksi.
6. **Form Gudang** (FG) → ACC QC → ACC Kepala Ops → stok produk masuk + Tally; WO closed.

## Status PP (`pp_status`)
- `draft` — masih bisa diedit
- `released` — sudah release
- `work_order` / `inprod` / `done` — tahap eksekusi (ikuti UI tab)

## Status dokumen eksekusi (`document_status`)
- `awaiting_qc` — Menunggu ACC Staf QC
- `awaiting_ops` — Menunggu ACC Kepala Ops
- `approved` — selesai ACC

Tipe dokumen: `material_issue`, `material_return`, `warehouse` (Form Gudang).

## Fitur flag
Off (`FEATURE_PRODUCTION_PLANNING=false`): menu disembunyikan, route 404. On: sidebar Production Planning aktif.

## Tabel utama
`production_plannings`, `production_planning_items`, `production_work_orders`, `production_skalas`, `production_execution_documents`, `production_output_reports`, `production_execution_events`.

## Bedakan dari Produksi lama
| | Production Planning | Produksi (lama) |
|--|---------------------|-----------------|
| Menu | Production Planning | Produksi |
| Kode | PP / WO / STB | No. Produksi |
| ACC stok | QC lalu Kepala Ops per dokumen STB/FG | ACC produksi langsung |
