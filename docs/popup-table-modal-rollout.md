# PG Popup Table — rollout tracker (GitHub #111)

**Issue:** [#111 — [Enhancement] [Fase 2] Modal Popup Table Enhancement](https://github.com/denny011299/pegasus/issues/111)
**Pilot branch:** `feat/popup-table-produksi` (commit `c4e3fd7`)
**Shared implementation:** `public/Custom_js/Shared/popup-table.js` (constants + behavior) +
`.pg-popup-table-input` / `.pg-popup-table-scroll` / `.pg-popup-table-empty` in
`resources/views/layout/partials/pg-modal-styles.blade.php`. How-to + gotchas documented in the
`pegasus-modal-dropdown` skill (`.claude/skills/pegasus-modal-dropdown/SKILL.md`).

## Criteria (from the issue)

A modal qualifies if its content is a table with an "input row" — i.e. either:
- **(A) input-card-above-table**: a card of fields + an add button sits above a table that the
  add button pushes new rows into, or
- **(B) inline-edit table**: every row already on the table is itself editable (qty inputs etc.),
  even without a separate "add new row" card.

Excluded on purpose:
- Pure read-only view/detail/recap modals (nothing in them is an input).
- "Riwayat"/history modals — a date-range **filter** sits above a **read-only** log table; that's
  a different UX pattern (filter-a-list, not add-a-row) and out of #111's stated scope.
- Deprecated modules ([[pegasus-pettycash-deprecated]]).

Progress is tracked here so the rollout (one modal at a time, user-checked before continuing) doesn't
lose track of what's left. Check off + note the PR/commit when a row ships.

## Status

| # | Modal | File(s) | Variant | Notes | Status |
|---|---|---|---|---|---|
| 1 | Produksi → Tambah Produksi | `components/modals/production/add-production.blade.php` + `Production.js` | A | Pilot — reference implementation | ✅ Done (`feat/popup-table-produksi`) |
| 2 | Kas Operasional → Kas Admin | `components/modals/operational-cash/add-cash-admin.blade.php` | A | `btn-add-catatan` | ✅ Done (GitHub #130 branch `fix/kas-operasional-module-130`) |
| 3 | Kas Operasional → Kas Armada | `components/modals/operational-cash/add-cash-armada.blade.php` | A | already uses `input_table` class | ✅ Done (GitHub #130 branch `fix/kas-operasional-module-130`) |
| 4 | Kas Operasional → Kas Gudang | `components/modals/operational-cash/add-cash-gudang.blade.php` | A | already uses `input_table` class | ✅ Done (GitHub #130 branch `fix/kas-operasional-module-130`) |
| 5 | Kas Operasional → Kas Sales | `components/modals/operational-cash/add-cash-sales.blade.php` | A | already uses `input_table` class | ✅ Done (GitHub #130 branch `fix/kas-operasional-module-130`) |
| 6 | Purchase Order → Tambah Purchase Order | `components/modals/purchase-order/add-purchase-order.blade.php` | A | SKU/scan input above table, no scroll cap today | ⬜ To do |
| 7 | Purchase Order Detail → Retur | `components/modals/purchase-order-detail/add-retur.blade.php` | A | already uses `input_table` class | ⬜ To do |
| 8 | Sales Order → Tambah Sales Order | `components/modals/sales-order/add-sales-order.blade.php` | A | has its own **broken** ad-hoc `max-height:300px` cap — blade has a comment admitting it doesn't actually apply; migrating fixes this as a side effect | ⬜ To do |
| 9 | Sales Order Detail → Tambah Catatan Pengiriman | `components/modals/sales-order-detail/add-sales-delivery.blade.php` | A | | ⬜ To do |
| 10 | Stock Transfer → Tambah Stock Transfer | `components/modals/stock-transfer/add-stock-transfer.blade.php` | A | already uses `input_table` class | ⬜ To do |
| 11 | Stock Transfer → Terima (Accept) | `components/modals/stock-transfer/accept-stock-transfer.blade.php` | B | no add-row card — search box above table instead; each row's Qty Terima is inline-editable; has ad-hoc `min-height:240px` | ⬜ To do |
| 12 | Customer Return → Tambah Pengembalian | `components/modals/customer-return/add-customer-return.blade.php` | A | already hand-rolled `max-height:280px` + sticky thead — good migration candidate | ⬜ To do |
| 13 | Product Issue → Tambah Produk Bermasalah | `components/modals/product-issue/add-product-issues.blade.php` | A | | ⬜ To do |
| 14 | BOM → Tambah Resep Bahan | `components/modals/bom/add-bom.blade.php` | A | | ⬜ To do |
| 15 | Produksi → Fix Recipe BOM | `components/modals/production/fix-recipe-bom.blade.php` | A/B | bahan list inside the recipe-fix modal | ⬜ To do |
| 16 | Bahan Mentah → Tambah Bahan Mentah | `components/modals/supplies/add-supplies.blade.php` | A | **2 tables** in one modal: `productVariantTable` (variant, `btnAddRow`) and the relasi-unit table (`btnAddRowRelasi`) — both qualify | ⬜ To do |
| 17 | Stock Product → Safety Stock | `components/modals/stock-product/modal-safety-stock.blade.php` | B | `table_safety_edit` (per-unit qty inputs, active). Its 2nd table (`table_safety_transfer`, "Transfer ke Stok Produk") is `d-none` / dead per its own comment — skip that section | ⬜ To do |

### Not in scope (checked, excluded)

| Modal | File | Why excluded |
|---|---|---|
| Riwayat Stok Produk | `components/modals/stock-product/add-stock-product.blade.php` | Date-range filter + read-only log table, not an input-row table |
| Riwayat Stok Bahan | `components/modals/stock-supplies/add-stock-supplies.blade.php` | Same filter+log pattern as above |
| Stock Transfer → Detail (view) | `components/modals/stock-transfer/view-stock-transfer.blade.php` | Read-only |
| Supplier → Detail (view) | `components/modals/supplier/view-supplier.blade.php` | Read-only |
| Cash → Detail Sales | `components/modals/cash/modal-detail-sales.blade.php` | Read-only |
| Purchase Order Detail → Modal Terima | `components/modals/purchase-order-detail/modal-terima.blade.php` | Read-only recap (2 tables), confirmation only — no input row |
| Purchase Order Detail → Tambah Delivery Notes | `components/modals/purchase-order-detail/add-purchase-delivery.blade.php` | Table is a read-only recap of PO items, no add-row |
| Petty Cash → Tambah Petty Cash | `components/modals/petty-cash/add-petty-cash.blade.php` | Module deprecated ([[pegasus-pettycash-deprecated]]) — not touched |

## How to work a row

1. Load the `pegasus-modal-dropdown` skill first (has the PG Popup Table section + known gotchas:
   the `max-height:"none"` vs `""` trap, `ResetLoadingButton` losing a fixed-height button, and the
   companion-array desync when `ROW_INSERT_POSITION` is `'top'`).
2. Move the input card above the table in the blade (or convert an existing ad-hoc-scrolled table),
   add `.pg-popup-table-input` / `.pg-popup-table-scroll` / empty-state row.
3. In the page's JS, switch direct `push`/`unshift` calls to `pgPopupTableInsert()`, and call
   `pgPopupTableScrollToEdge()` right after a genuinely new row is added (not from delete/reset).
4. Update this table's Status column + note the PR/commit, then move to the next row.
