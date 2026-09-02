# Table ACC/Tolak action buttons — color rollout tracker (GitHub #117)

**Issue:** [#117 — [Enhancement] [Fase 2] UI enhancement for action buttons](https://github.com/denny011299/pegasus/issues/117)
**Ask (Bahasa Indonesia):** "Tombol acc dan tolak diberi warna dan bold agar bisa terlihat (Semua menu
yang berhubungan dengan acc dan tolak di table)" — every ACC/Tolak (approve/reject) action button that
lives **in a DataTable row** (not inside a modal footer) should be colored + bold so it's visually
distinct from the plain edit/view/delete icons next to it.

**Scope grew during rollout**: once approve/reject were fixed on a row, the neighboring view/edit/delete
icons on that same row were still using the same defeated inline-`style=` pattern (only their color
was forced, background stayed white per the base rule) — so per-row requests came in to fix those too.
`.btn-action-view`/`.btn-action-edit`/`.btn-action-delete` were added alongside approve/reject for this
(see Root cause). Applied so far only to `Customer_Return.js` (Pengiriman → Pengembalian tab); other
tables' view/edit/delete icons haven't been swept yet — do it opportunistically per-row like the rest
of this tracker, not as a blanket pass.

## ⚠️ Root cause (found while fixing row #6, Tanda Terima PO)

`resources/views/layout/partials/header.blade.php` (loaded on every Backoffice page) has a global rule:

```css
.btn-action-icon {
    background: #ffffff !important;
    color: #64748b !important;
    ...
}
```

`!important` on an external stylesheet beats **any** inline `style="..."` and any Bootstrap utility
class (`bg-success`, `text-light`, etc.) with equal/lower specificity. This means **every row below
marked "Already colored" before this was discovered is actually not visibly colored** — they were
never actually verified in a browser, only by reading the JS source. Confirmed via DevTools Computed
panel on row #6 (Tanda Terima PO): `background-color` computed to `rgb(255,255,255)` even though
`element.style` had `background:#059669`.

**The fix**: two new global classes added next to the existing `.text-success`/`.text-danger`/
`.text-warning` convention in `header.blade.php`, each with its own `!important` (so it actually wins).
Final style is a soft tint + border, matching the same visual language as the delete icon's `:hover`
state (`#fef2f2`/`#fecaca`/`#dc2626`) but kept on permanently since approve/reject need to read as
colored at a glance:

```css
.btn-action-icon.btn-action-approve { background:#ecfdf5 !important; border:1px solid #a7f3d0 !important; color:#059669 !important; font-weight:700 !important; }
.btn-action-icon.btn-action-reject  { background:#fef2f2 !important; border:1px solid #fecaca !important; color:#dc2626 !important; font-weight:700 !important; }
```

(plus matching `:hover` variants, deeper tint). Rows below must add `btn-action-approve` /
`btn-action-reject` to the anchor's class list — **inline `style=` or Bootstrap `bg-*` utility
classes will silently do nothing**, don't use them.

## Scope

In scope: the small round icon buttons (`.btn-action-icon`) DataTables render per row for
approve/reject-style actions on list pages.

Out of scope (not "di table"):
- Modal footer buttons (e.g. `#btn-terima` / `#btn-tolak` in Produksi's add-production modal,
  `#btn-acc-po` / `#btn-tolak-po` in Purchase Order Detail, `#btn-acc-sto` in Stock Opname) — these
  are full-width buttons inside a popup, already visually prominent, not a small row icon.
- Status **badges** that happen to say "Tolak" (`ReportEfisiensiProduksi.js`, `ReportProduction.js`)
  — these are read-only state labels, not clickable actions.
- [[pegasus-pettycash-deprecated]] / [[pegasus-sales-order-delivery-deprecated]] /
  [[pegasus-po-manual-delivery-deprecated]] / [[pegasus-warehouse-module-wip]] modules.

## Status

| # | Menu / Table | File | Current style | Status |
|---|---|---|---|---|
| 1 | Pengiriman (Sales Order list) → Konfirmasi | `Customers/Sales_Order.js` (`btn_confirm`) | was inline `style=` green tint, defeated by the global `!important` | ✅ Done — switched to `.btn-action-approve` |
| 2 | Retur Pelanggan (Produk/Bahan, unified table) → Konfirmasi | `Customers/Customer_Return.js` (`cr-confirm`) | was inline style, same problem — this is the live implementation for the "Pengembalian" tab on the Sales Order page (`#tableCustomerReturn`, endpoint `/customerReturns`), covers all return types | ✅ Done — switched to `.btn-action-approve` |
| 3 | ~~Retur Pelanggan (Produk, varian modal)~~ | `Customers/Customer_Product_Return.js` (`cpr-confirm`) | **Likely orphaned/dead code** — not `@include`d or `<script src>`'d from any `.blade.php` found in the whole repo (hits `/customerProductReturns`, superseded by row #2's unified table). Edited anyway (harmless) but needs an unused-code audit before anyone relies on this page being reachable. | ✅ Edited (see caveat) |
| 4 | ~~Retur Pelanggan (Bahan)~~ | `Customers/Customer_Supply_Return.js` (`csr-confirm`) | Same orphaned-code caveat as row #3 (hits `/customerSupplyReturns`) | ✅ Edited (see caveat) |
| 5 | ~~Product Issue (Produk Bermasalah) list~~ → Terima / Tolak | `Inventory/Product_Issues.js` (`.btn_acc`/`.btn_decline`) | **Miscategorized in the original pass** — the table row only ever renders a "Lihat" icon; Terima/Tolak are static buttons (`#btn-terima`/`#btn-tolak`) inside `add-product-issues.blade.php`'s modal footer, not a table row icon | ➡️ Moved to "Not in scope" below |
| 6 | Tanda Terima PO (`tt`) list → Terima / Tolak | `Suppliers/tt.js` (`btn_acc_tt` / `btn_decline_tt`) | root-caused here; found the global `!important` override | ✅ Done — `.btn-action-approve`/`.btn-action-reject` classes (defined in `header.blade.php`), gradient matches `.pg-btn-accept`/`.pg-btn-decline`; also got shimmer loading + PG modal styling for its Konfirmasi Terima popup |
| 7 | Stock Transfer list → ACC Terkirim | `Inventory/Stock_Transfer.js` (`btnAccept`, ~line 664) | was `text-info` only, then inline style (also defeated) | ✅ Done — switched to `.btn-action-approve` class |

All 5 real table-row targets (rows #1, #2, #6, #7, plus the two orphaned files #3/#4) are now on the
shared `.btn-action-approve`/`.btn-action-reject` classes with the gradient fill from `header.blade.php`.

### Not in scope (checked, excluded)

| Menu | File | Why excluded |
|---|---|---|
| Produksi → Terima/Tolak Produksi | `components/modals/production/add-production.blade.php` + `Production.js` | Modal footer button, not a table row icon |
| Purchase Order Detail → Terima/Tolak PO | `Suppliers/Purchase_Order_Detail.js` (`#btn-acc-po`, `#btn-tolak-po`) | Modal footer button |
| Stock Opname / Stock Opname Bahan → Konfirmasi ACC | `Inventory/CreateStockOpname(Supplies).js` (`#btn-acc-sto` / `#btn-acc-stob`) | Modal footer button |
| Kas Operasional (Admin/Gudang/Armada/Sales) → ACC/Tolak | `Reports/Cash.js`, `Reports/Cash_Operational.js` (`.btn_acc`, `.btn_decline`) | Modal buttons, not row icons |
| Sales Order Detail → Tolak (delivery/invoice) | `Customers/Sales_Order_detail.js` (`.btn-decline`) | Modal footer button |
| ReportEfisiensiProduksi / ReportProduction "Tolak" | `Reports/ReportEfisiensiProduksi.js`, `Reports/ReportProduction.js` | Read-only status badge, not an action button |
| Purchase Order list (Suppliers) | `Suppliers/Purchase_Order.js` | Only has Lihat/Hapus row icons; ACC/Tolak happens in the PO Detail modal instead |
| Product Issue (Produk Bermasalah) list | `Inventory/Product_Issues.js` + `add-product-issues.blade.php` | Table row only shows Lihat; Terima/Tolak are modal footer buttons (`#btn-terima`/`#btn-tolak`), not row icons |

## How to work a row

1. Add class `btn-action-approve` to the accept button's anchor and `btn-action-reject` to the
   reject button's anchor (alongside the existing `btn-action-icon` class). Do **not** rely on inline
   `style=` or Bootstrap `bg-*`/`text-*` utility classes — they lose to `header.blade.php`'s global
   `.btn-action-icon { ... !important }` rule (see Root cause above).
2. Bump that page's `<script src="...?v=N">` cache-buster in its blade file so the browser picks up
   the JS change (several of these still use a hardcoded `?v=1` that's never bumped).
3. **Actually verify in a browser** (not just by reading the JS) — this bug was invisible from source
   alone. Hard-refresh, or check DevTools Computed panel if in doubt.
4. Update this table's Status column + note the PR/commit, then move to the next row.
