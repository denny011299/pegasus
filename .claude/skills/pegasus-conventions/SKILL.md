---
name: pegasus-conventions
description: Coding conventions for the okejob-pegasus app (Laravel 12 + Blade + jQuery/DataTables admin ERP, "Kanakku" theme). Load before writing or editing ANY backend (controller/model/route/middleware) or frontend (blade/JS/CSS/button/modal/theme) code in this repo, so new code matches existing patterns instead of introducing a new style.
---

# okejob-pegasus conventions

Laravel 12 backoffice/ERP app (sales, purchasing, production, inventory, POS) using
Blade + jQuery + DataTables + the **Kanakku** (Dreamguys/Dreamstechnologies) Bootstrap 5
admin theme. No SPA framework, no build-time JS modules for app logic — everything is
server-rendered Blade + a paired plain `<script>` file per page.

This codebase is **organically inconsistent** in places (multiple response shapes, some
dead/duplicate files). When in doubt: **open 1-2 sibling files in the same folder and match
them exactly**, rather than "improving" toward a single canonical style. The rules below call
out which variant is dominant and which is legacy-only.

## Stack cheat sheet
- Backend: Laravel 12, PHP 8.2, session-based auth (`Session::get('user')`, NOT `Auth::`/Sanctum).
- DB: raw Eloquent models, no `$fillable`, no relationships, no `SoftDeletes`, no `DB::transaction()`.
- Frontend: Blade views + jQuery 3.7 + DataTables (client-side paging) + Select2 + SweetAlert2 + Feather icons.
- Build: Vite only bundles `resources/css/app.css` + `resources/js/app.js`/`bootstrap.js` (mostly unused for
  app logic). All page-specific JS lives in **`public/Custom_js/Backoffice/<Area>/<Page>.js`**, loaded via a
  plain `<script src>` tag, no `import`/`export`, no npm packages beyond axios (present but not the AJAX
  pattern actually used — see below).

## Adding a new CRUD feature — end-to-end recipe
This is the most common task shape in this repo. Do these steps together, in this order:

1. **Route** — add to `routes/web.php` inside the appropriate `check.access:<ModuleName>|<ability>` group
   (create a new group per ability if this is a new module). `<ModuleName>` is an Indonesian, human-readable
   string that MUST exactly match an entry in the `role_access` permission JSON — pick a name consistent
   with the sidebar entry. URIs are flat/verb-prefixed (`/product`, `/getProduct`, `/insertProduct`,
   `/updateProduct`, `/deleteProduct`), not REST/nested. Always set `->name()` matching the URI.
2. **Model** — `app/Models/X.php`: explicit `$table`/`$primaryKey`/`$timestamps` header, no `$fillable`, no
   relationships. Implement `getX($data=[])`, `insertX($data)`, `updateX($data)`, `deleteX($data)` as instance
   methods that manually assign properties and `$this->save()`. Delete = soft flag, `$t->status = 0; $t->save();`
   (never `SoftDeletes`/`delete()`). Set `created_by`/`updated_by` from `Session::get('user')->staff_id`
   manually in every mutating method. If a human-readable code is needed, add a `generateXID()` method
   (pattern: prefix + zero-padded max-plus-one), no shared generator util.
3. **Controller** — thin dispatcher: `function getX(Request $req){ return (new X())->getX($req->all()); }` style.
   No `Validator::make`/`$request->validate()` anywhere in this codebase — trust `$req->all()`, validate on
   the frontend instead. Return shape depends on the endpoint type, match siblings in the same controller:
   - list/GET endpoints: `response()->json($data)` (or bare `return $data;` if that's what neighbors do).
   - simple insert/update/delete: `return 1;` on success.
   - business-rule failures: `return ["status" => -1, "message" => "..."];` (JS checks `.status`).
   No try/catch unless sibling methods in that controller already use it. No DB transactions unless sibling
   methods already use them.
4. **Blade view** — `resources/views/Backoffice/<Area>/X.blade.php`: `@extends('layout.mainlayout')`,
   `@section('custom_css')` / `@section('content')` / `@section('custom_js')`. List pages render an **empty**
   `<tbody>` — DataTables fills it entirely from AJAX. Don't build an inline "Add" modal or filter bar in the
   page itself — instead add a new `@if(Route::is(['yourRouteName']))...@endif` block to the shared
   `components/page-header.blade.php` (Add button), `components/search-filter.blade.php` (filter bar), and
   `components/modal-popup.blade.php` (add/edit modal), gated with `@roleCan('ModuleName','create')` for the
   Add button. Load the page's JS at the bottom: `<script src="{{asset('Custom_js/Backoffice/Area/X.js')}}"></script>`.
5. **JS file** — `public/Custom_js/Backoffice/<Area>/X.js`, follow the skeleton in "AJAX / JS pattern" below.
6. **Sidebar** — add the module to `resources/views/layout/partials/sidebar.blade.php`: extend/add a
   `$showX` boolean via `$akses->firstWhere('name','ModuleName')`, wrap the `<li>` in `@if(...)`, active-state
   via `Request::is('routeUri')` (sidebar uses `Request::is`, not `Route::is` — that's the one place it differs).
7. **Permissions**: the module name string must be usable in three places consistently — route middleware
   (`check.access:ModuleName|ability`), Blade (`@roleCan('ModuleName','ability')`), and JS
   (`hasAccessAction('ModuleName','ability')` used inside `roleIconEdit`/`roleIconDelete`/`roleIconView`).
   These are three independent implementations of the same rule — update all three, server-side middleware
   is the real enforcement, the other two are just UI hiding.

## AJAX / JS pattern (`public/Custom_js/Backoffice/**`)
Standard file skeleton — copy this shape for new pages:
```js
var mode = 1;       // 1 = insert, 2 = update
var table;
$(document).ready(function () {
    inisialisasi();
    refreshX();
});
function inisialisasi() {
    table = $('#tableX').DataTable({ /* bFilter, sDom:'fBtlpi', lengthMenu, initComplete moves search box */ });
}
function refreshX() {
    $.ajax({
        url: "/getX", method: "get",
        success: function (e) {
            if (!Array.isArray(e)) e = e.original || [];
            table.clear().draw();
            for (let i = 0; i < e.length; i++) {
                // format dates/currency, build e[i].action html via roleIconEdit/roleIconDelete
            }
            table.rows.add(e).draw();
            feather.replace();
        },
        error: function (err) { console.error("Gagal load:", err); }
    });
}
```
- CSRF: use the global `var token = "{{csrf_token()}}"` (set in `mainlayout.blade.php`) inside the `data:`
  object of every POST (`data: {..., _token: token}`). Only the `autocompleteX` helpers use the
  `$('meta[name="csrf-token"]')` lookup instead — don't introduce a third pattern.
- Delete flow (fixed 3-step pattern, reuse the shared modal from `components/modal-popup.blade.php`):
  1. row's delete icon click → grab row data → `showModalDelete("Apakah yakin ingin menghapus X ini?", "btn-delete-x")` → stash id as an attr on the confirm button.
  2. confirm button click (`#btn-delete-x`) → `$.ajax` POST to `/deleteX` with `_token`.
  3. on success: `$('.modal').modal("hide"); refreshX(); notifikasi('success', "Berhasil Delete", "...");`
- Notifications: use the global `notifikasi(simbol, title, deskripsi)` helper (wraps SweetAlert2
  `Swal.fire`) for both success and error. Toastr is loaded but dead — don't use `toastr.*`.
- Buttons during async submit: `LoadingButton(this)` to swap in a spinner + disable, `ResetLoadingButton(selector, text)` to restore.
- Client-side validation: loop inputs with class `.fill`, mark empty ones `.is-invalid`, block submit, then
  `notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda')`. This is the only validation
  layer that exists — Laravel-side field errors are not surfaced individually.
- Currency helpers: `formatRupiah`/`convertToAngka` (no negatives) vs `formatRupiahMinus`/`convertToAngkaMinus`
  (allows negatives, used for ledger/cash entries). Pick based on whether the value can go negative.
- New autocomplete/select2 fields: copy one of the ~25 existing `autocompleteX()` functions in
  `footer-scripts.blade.php` (POST to `/autocompleteX`, wire via `$(id).select2({ajax:{...}})`) — there is no
  shared factory, copy-paste-and-rename is the convention.
- Action-column icons: build via the global `roleIconView/roleIconEdit/roleIconDelete(moduleName, cssClass, extraAttrsHtml)`
  helpers (defined inline in `mainlayout.blade.php`), which internally check `hasAccessAction(moduleName, ability)`
  against `window.permissionList`/`window.userRoleId` and return `''` if not permitted — concatenate their
  results into `e[i].action`.
- Naming: Indonesian names are idiomatic and expected for functions/vars tied to domain concepts
  (`inisialisasi`, `notifikasi`, `konfirmasi`, `tolak`, `terima`) — don't translate them to English.
- **Ignore as dead/reference-only**: any filename containing " copy" or an obvious lowercase duplicate
  (e.g. `Sales_Order_detail copy.js`, `CreateStockOpname cop.js`, `Suppliers/tt.js` next to `Tt.blade.php`) —
  these are abandoned backups, not the canonical file for that page.

## Blade / buttons / icons / theme
- Icons: **Feather** (`<i class="fe fe-xxx">`) for table row action icons and most menu icons — call
  `feather.replace()` after any DOM update that injects new feather markup (e.g. after `table.rows.add()`).
  **Font Awesome** (`fa fa-plus-circle me-2`) for "Tambah X" (Add) buttons and header icons. Bootstrap Icons
  is loaded via CDN but essentially unused — don't reach for it by default.
- Forms are inert HTML (`<form action="#">`, no `@csrf`, no Laravel form binding) — all submission goes
  through the paired JS file's button click handler + `$.ajax`. Don't add a real Laravel form submission
  flow unless explicitly asked; it would be inconsistent with every existing page.
- Shared modals (`#modalDelete`, `#modalKonfirmasi`, `#modalPhoto`, `#modalViewPhoto`) live once in
  `components/modal-popup.blade.php` and are opened via the JS helpers — don't duplicate a delete-confirm
  modal inside a page-specific view.
- Layout chrome (`mainlayout.blade.php`, `sidebar.blade.php`, `header.blade.php`) contains large dead blocks
  from the original Kanakku vendor demo, wrapped in `@if(false)` or guarded by `Route::is([...])` against
  demo route names that don't exist in this app (e.g. horizontal-layout markup, unrelated vendor charts).
  Don't extend those dead blocks — find the live equivalent (usually further down the same file) and extend
  that instead.
- Theme light/dark/blue switching lives in `layout/partials/theme-settings.blade.php` +
  `assets/js/theme-settings.js` via `data-layout-mode`. This app effectively only supports the vertical
  sidebar layout — horizontal/RTL markup present in the theme is dead code.

## Known inconsistencies (match the nearest sibling file, don't "fix" these globally)
- Controller GET endpoints return either `response()->json($data)` or a bare Eloquent collection — JS
  defensively unwraps both (`if (!Array.isArray(e)) e = e.original || [];`).
- Some POST handlers set `headers: {'X-CSRF-TOKEN': token}` in addition to `data:{_token: token}` —
  redundant but harmless; not required for new code, `data:{_token: token}` alone is sufficient.
- Models sometimes instantiated as `(new X())->method()` for CRUD and used statically (`X::find()`/`X::where()`)
  for simple lookups within the same controller file — both are fine, follow the local file.
- Sidebar active-state uses `Request::is()`; the rest of the app (page-header/search-filter `@if` gating)
  uses `Route::is()` — keep that split, don't unify.
- `modal-popup.blade.php` sometimes checks permissions via a raw `$akses->firstWhere(...)` + `in_array('others', ...)`
  instead of `@roleCan` — used specifically for approve/decline-style "others"-ability buttons in modal
  footers. Match whichever the surrounding block in that file already does.

## Access control model
- `ABILITIES = ['view','create','edit','delete','others']` — fixed closed set. `'others'` is the catch-all
  for non-CRUD actions (approve/decline/accept/reject, etc.).
- `role_id === -1` (JS: `window.userRoleId === -1`) = super admin, bypasses all checks everywhere.
- Source of truth: `App\Support\RoleAccess::can($user, $module, $ability)`, mirrored in JS as
  `hasAccessAction(moduleName, ability)` — both read `Session::get('user')->role_access` /
  `window.permissionList`, shaped as `[{name: "ModuleName", akses: ["view","create",...]}]`.
- Route middleware (`check.access:Module|ability` / `check.access.any:Mod1,Mod2,...,ability`) is the actual
  enforcement; Blade/JS gating only hides UI and must never be treated as sufficient security on its own.
