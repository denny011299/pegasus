---
name: pegasus-shared-helpers
description: >-
  Reuse shared UI helpers (LoadingButton, ResetLoadingButton, modals, autocomplete,
  notifikasi, popup-table) from footer-scripts / Custom_js/Shared instead of
  duplicating in page JS. Use when adding buttons with spinners, confirm modals,
  Select2 autocomplete, DataTables loading, or any UI pattern that appears on
  more than one Pegasus backoffice page.
---

# Pegasus shared helpers

Load this before inventing a new spinner, confirm modal, autocomplete, or “loading” helper in page JS.

## 1. Check first

```bash
rg -n "function LoadingButton|function showModal|function autocomplete|function notifikasi" resources/views/layout/partials/footer-scripts.blade.php
rg -n "" public/Custom_js/Shared/
```

## 2. Where to put new shared code

| Need | Put it here |
|------|-------------|
| Global JS used by many pages | `resources/views/layout/partials/footer-scripts.blade.php` |
| Global CSS | `header.blade.php` or `resources/views/layout/partials/pg-modal-styles.blade.php` |
| Multi-page JS without bloating Blade | `public/Custom_js/Shared/<name>.js` + load from footer / layout |

Do **not** paste the same helper into `public/Custom_js/Backoffice/<Area>/<Page>.js`.

## 3. Prefer these APIs

### Button spinner

```js
LoadingButton($btn);           // or LoadingButton('#btn-id')
// ... ajax ...
ResetLoadingButton($btn, '<i class="fe fe-check me-1"></i>Simpan');
```

Quirk: `ResetLoadingButton` clears inline `height`/`min-width`. Icon-only fixed-height buttons need a small wrapper that restores height (see `resetAddProductButton` in `Production.js`).

### Feedback / confirm

- `notifikasi(simbol, title, deskripsi)`
- `showModalKonfirmasi(text, buttonId, danger?)`
- `showModalDelete(text, buttonId)`
- `showModalDanger(text, buttonId)`

### Autocomplete (Select2)

Use existing `autocomplete*` in footer-scripts (`autocompleteWarehouse`, `autocompleteBom`, `autocompleteStaff`, …). Extend there if a new shared query is needed; don’t fork Select2 init per page unless one-off.

### Table / popup

- DataTables skeleton / `is-loading`: follow Cash / SOP DataTables in `pegasus-workflow-sop.mdc`
- Input-card + table rows: `public/Custom_js/Shared/popup-table.js` + skill `pegasus-modal-dropdown`

## 4. When to extract

- Pattern copied **twice** → extract to shared in the same PR if cheap, else next touch.
- Page-only one-liner → keep local.

## 5. Output to user

After adding a new shared helper: 1 sentence — nama fungsi + file, cara pakai singkat.
