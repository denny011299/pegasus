# Unused Code / Orphaned Pages Record

Scan date: 2026-08-29
Scope: internal admin app (`resources/views/Backoffice`, `routes/web.php`, `public/Custom_js`) — dev/deploy/migration tooling excluded on purpose.

Method: every route in `routes/web.php` was cross-checked against `resources/views/layout/partials/sidebar.blade.php`'s `$canShow()` gating, then every Blade view / JS file / PDF template was grepped across `app/`, `routes/`, `resources/views/`, `database/`, `tests/` to confirm zero references before being listed here. Nothing below is a guess — each row was verified with `grep -rl` returning no hits (or documented as the one place it *is* used, when relevant).

---

## 1. Pages routed + permission-gated, but with zero sidebar entry

| Route | Controller | Module (permission) | Notes |
|---|---|---|---|
| `/profitLoss` | `ReportController::ProfitLoss` | `Untung & Rugi` | Sidebar `<li>` fully commented out ([sidebar.blade.php:1195-1199](../resources/views/layout/partials/sidebar.blade.php#L1195-L1199)) |
| `/inwardOutward` | `ReportController::InwardOutward` | `Barang Masuk Keluar` | Same — commented out ([:1201-1205](../resources/views/layout/partials/sidebar.blade.php#L1201-L1205)) |
| `/reportEfisiensiProduksi` | `ReportController::reportEfisiensiProduksi` | shares `Laporan Produksi` | Only the `reportProduksi` link was kept ([:1165-1169](../resources/views/layout/partials/sidebar.blade.php#L1165-L1169)); breadcrumb/filter components still special-case this route, so it was a real shipped feature, just unlinked |
| `/pettyCash` | `ReportController::PettyCash` | `Kas Kecil` | Already known-deprecated (see project memory `pegasus-pettycash-deprecated`) — consistent with what this scan found |
| `/profiles` | `SettingController::Profiles` | `Profil` | **Not fully orphaned** — reachable via the topbar avatar dropdown ([header.blade.php:752](../resources/views/layout/partials/header.blade.php#L752)); just absent from the left sidebar (whole "Settings" sidebar section is Blade-commented, [:1291-1308](../resources/views/layout/partials/sidebar.blade.php#L1291-L1308)) |
| `/settings` | `SettingController::Settings` | `Pengaturan` | Same as above — topbar-only, not sidebar |
| `/area` | `GeneralController::Area` | none dedicated (gated by `Kategori,Satuan,Variasi`) | Fully working page + JS (`Area.js` populates the table via ajax) but **no link anywhere at all** — not sidebar, not topbar, not any other page |
| `/testing` | `GeneralController::testing` | `Pengaturan` | Not a real page (returns nothing); force-resyncs `ProductStock::syncStock()` for **every active product**. No UI entry point at all — reachable only by typing the URL |

**Extra risk noted:** `Untung & Rugi`, `Barang Masuk Keluar`, `Kas Kecil`, `Profil`, `Pengaturan` do **not exist in `public/assets/json/permission.json`** at all, so no role can ever be granted these via the role editor (see project memory `pegasus-permission-json-module-list`). Combined with `RoleAccess::isSuperAdmin()` bypassing `check.access` entirely, these 5 routes are reachable **only by a super-admin typing the URL directly** — unreachable "secret" routes with no menu path and no way to grant/audit access to anyone else.

---

## 2. Entire dead Blade view directories (leftover theme scaffold)

Verified via `grep -rl` across `app/`, `routes/`, `database/`, `tests/` — zero references anywhere (no controller, no route, no test, no PDF loader).

| Directory | Files | Superseded by |
|---|---|---|
| `resources/views/Backoffice/Finance/` | 6 | (no equivalent module was ever built) |
| `resources/views/Backoffice/Order/` | 6 | `Customers/Sales_Order*`, `Suppliers/Purchase_Order*` |
| `resources/views/Backoffice/Outlet/` | 2 | `Warehouse/` |
| `resources/views/Backoffice/POS/` | 3 | (unused) |
| `resources/views/Backoffice/Penawaran/` | 2 | (unused) |
| `resources/views/Backoffice/People/` | 6 | `Customer/`, `Customers/`, `Suppliers/`, `User/` |
| `resources/views/Backoffice/Report/` (singular) | 5 | `Reports/` (plural — the real one) |
| `resources/views/Backoffice/Stock/` (singular) | 4 | `Inventory/Stock_*` |
| `resources/views/Backoffice/UserManagement/` | 5 | `User/` |
| `resources/views/Backoffice/Setting/` (singular — `Company-settings`, `Bank-settings-grid`, `Invoice_settings`, `Pos-settings`, `Prefixes`, `Tax-rates`, `settings-sidebar`) | 7 | `Settings/Settings.blade.php` — this cluster `@component`s itself internally, but no controller ever enters it |

Loose orphaned files (not in a dead directory, but individually unreferenced):
- `resources/views/Backoffice/Profile.blade.php`
- `resources/views/Backoffice/Customer/vendors.blade.php`
- `resources/views/Backoffice/Inventory/Stock.blade.php`

**~44 dead Blade files total.** Same origin as the ~500-line inert `@if (false) ... @endif` block already sitting in [sidebar.blade.php:316-815](../resources/views/layout/partials/sidebar.blade.php#L316-L815) — a disabled horizontal-menu template variant from the original "Kanakku" theme. Worth deleting together since it's the same leftover-scaffold problem.

Also dead within the otherwise-live `Backoffice/Product/` directory (mixed in with real files):
`Brand`, `CategoryInventory`, `Inventaris`, `ManageStocks`, `ProductIssues`, `ProductPrices`, `StockList`, `StockTransfer`, `Variant`, `add-product`, `edit-product`, `insert-product`, `manageInventory`, `product-details.blade.php` — 13 more orphaned files.

---

## 3. Dead PDF templates

Never passed to `Pdf::loadView()` anywhere:

- `resources/views/Backoffice/PDF/BarcodeSingle.blade.php`
- `resources/views/Backoffice/PDF/DeliveryNote.blade.php`
- `resources/views/Backoffice/PDF/GoodReceipt.blade.php`
- `resources/views/Backoffice/PDF/Invoicing.blade.php`
- `resources/views/Backoffice/PDF/ListOpname.blade.php`
- `resources/views/Backoffice/PDF/Mutasi.blade.php`

(The rest of `Backoffice/PDF/` is actively used — confirmed via `grep Pdf::loadView`.)

---

## 4. Dead / duplicate JS files

Not referenced by any `@section('custom_js')` include — literal leftover "copy" duplicates sitting next to the real files:

- `public/Custom_js/Backoffice/Customers/Sales_Order_detail copy.js`
- `public/Custom_js/Backoffice/Inventory/Stock_Product copy.js`
- `public/Custom_js/Backoffice/Inventory/CreateStockOpname cop.js`

**Different flavor — pre-unification per-type Pengembalian pages** (found 2026-09-02 while working GitHub #117):

- `public/Custom_js/Backoffice/Customers/Customer_Product_Return.js` (calls `/customerProductReturns`)
- `public/Custom_js/Backoffice/Customers/Customer_Supply_Return.js` (calls `/customerSupplyReturns`)

Neither is `<script src>`'d from any Blade view — confirmed via repo-wide grep, zero hits. Unlike the
"copy" files above, their **backend is still alive**: `CustomerProductReturnController` /
`CustomerSupplyReturnController` and their routes ([routes/web.php:342-347](../routes/web.php#L342-L347),
plus the matching store/update/destroy/accept/decline routes) are registered and would work if hit
directly. Git blame (`4721ce88`, "feat: unified pengembalian, ...") shows why: Produk/Produk-varian/Bahan
returns were merged into one shared table — `Customer_Return.js` / `#tableCustomerReturn` / the
`/customerReturns` endpoint, which is what actually renders on Pengiriman → Pengembalian today. These
two files (and arguably their controllers) are the pre-unification implementation left behind.

---

## 5. Dead functions

- `HelperController::fetchPengaturan()` — defined, never called anywhere.
- `DevCheatController::cheatFillStock()` — the whole controller (85 lines) has no matching route in `web.php` / `api.php` / `external-api/`. Its own docblock claims `GET /dev/cheat-stock`, but that route doesn't exist. Dev-tooling-flavored (like the excluded deploy/migration tools), so low priority — flagged only because it isn't just unused, it was never wired up at all.

---

## Not flagged (already covered elsewhere / confirmed intentional)

- Petty Cash module — `pegasus-pettycash-deprecated`
- Sales Order Delivery module — `pegasus-sales-order-delivery-deprecated`
- Manual PO Delivery workflow — `pegasus-po-manual-delivery-deprecated`
- Warehouse module WIP — `pegasus-warehouse-module-wip` (unmerged colleague branch, not dead code)

---

## Open follow-ups

- [ ] Decide whether to file GitHub issues for the two non-cleanup items: the super-admin-only unreachable routes (§1, permission.json gap) and `/testing`'s unguarded bulk stock resync (§1).
- [ ] Decide whether to delete the ~50+ confirmed-dead files in §2-§4, or review the list first.
