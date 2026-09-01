---
name: pegasus-modal-dropdown
description: How to make a Bootstrap modal scroll internally (long tables, many rows) without breaking Select2 dropdowns inside it, in the okejob-pegasus Kanakku admin theme. Load before adding modal-dialog-scrollable to any modal, before debugging a modal whose footer is pushed off-screen or won't scroll, before debugging a Select2/autocomplete dropdown that floats to the wrong place, gets cut off, or renders behind the modal, or before touching any "input card + table of rows" popup (see the PG Popup Table pattern below, GitHub #111).
---

# Bootstrap modal scrolling + Select2 dropdowns in Kanakku modals

This app's modals are hand-built Blade partials (`resources/views/components/modals/**`), not a
component library, so every "make this modal scroll" or "the dropdown is in the wrong place" bug
has to be solved by hand against Bootstrap 5's CSS + Select2 4.0's positioning JS. Both have sharp
edges that look like unrelated bugs but are really the same handful of root causes. This was all
learned the hard way fixing GitHub #101 (Produksi modal) — read this before repeating that work.

## Popup modals with an input card + a table of rows (GitHub #111)

If the modal's content is "an input card, then a table where each add appends a row" (Produksi,
Sales Order, Stock Transfer, PO, Cash Armada/Gudang/Sales, Product Issues, etc.), don't reach for
the generic `modal-dialog-scrollable` + `.modal-body` scroll recipe below — use the **PG Popup
Table** pattern instead, which scrolls the *table* internally and keeps the input card fixed above
it. This is the standard now; only fall back to scrolling the whole modal body for modals that
aren't input-card-plus-table shaped.

- Shared constants + behavior: `public/Custom_js/Shared/popup-table.js`, loaded globally from
  `footer-scripts.blade.php` — `PG_POPUP_TABLE.MAX_VISIBLE_ROWS` (table caps at N rows tall, then
  scrolls) and `PG_POPUP_TABLE.ROW_INSERT_POSITION` (`'top'` or `'bottom'`) are the **one place**
  to change either behavior app-wide.
- Shared CSS: `.pg-popup-table-input` / `.pg-popup-table-scroll` / `.pg-popup-table-empty` in
  `resources/views/layout/partials/pg-modal-styles.blade.php`.
- Blade shape:
  ```html
  <div class="pg-popup-table-input"> ...input fields, "+" button... </div>
  <div class="table-responsive pg-popup-table-scroll">
    <table>
      <thead>...</thead>
      <tbody><!-- empty-state <tr class="pg-popup-table-empty"> when no rows --></tbody>
      <tfoot>...totals...</tfoot>
    </table>
  </div>
  ```
  Input card comes **first** in the DOM, table second — that's what gives the "input above table"
  UX the issue asked for. `.pg-popup-table-input` already matches the table's cell padding
  (`12px 16px`) and adds the gap below it; don't re-add ad-hoc padding/`mb-3` around either block.
- JS: use `pgPopupTableInsert(list, item)` instead of `list.push(item)`/`list.unshift(item)`
  directly when adding a new row, so the insert position stays driven by the shared constant.
  Table height is recalculated automatically (MutationObserver on the `tbody` + `shown.bs.modal` +
  `resize`) — no manual height math needed, don't hardcode a `max-height` px value per modal.
- After a genuinely new row is added, call `pgPopupTableScrollToEdge($tableScrollEl)` so it's
  immediately visible without the user having to scroll manually (scrolls to top or bottom to
  match `ROW_INSERT_POSITION`). Call it **only** at the call site that means "user added a row" —
  not from inside a generic re-render helper like `addRow()` itself, since that's also called on
  delete/reset/view in this codebase's pattern (clear + full re-append), and those aren't "new
  row" events even though they touch the DOM the same way. See the call right after
  `addRow(items)` in `continueAddProduct()` for the guarded example.
- **If a row array has a same-indexed companion array** (e.g. Produksi's `items[]` /
  `list_bahan[]`), and `ROW_INSERT_POSITION` is `'top'`, you must `unshift` a matching placeholder
  into the companion array too when a *new* row is added (not when an existing row's qty is
  merged) — see `continueAddProduct()` in `Production.js` for the worked example. Skipping this
  desyncs the two arrays by one index and produces a row that shows another row's detail data.
- Column with only a button and no label (e.g. the "+" add button) needs an invisible spacer
  label (`<label class="mb-2">&nbsp;</label>`) above it inside the input card, or it renders at
  the wrong vertical position relative to the labeled fields next to it — this bit us in the
  Produksi rollout, check any new "+"-only column for it.
- **`MAX_VISIBLE_ROWS` above ~3-4 will silently under-cap the table if you miss this**: when the
  row count is `<= MAX_VISIBLE_ROWS`, `pgPopupTableRefresh()` must clear the JS override with
  `max-height: none`, not `max-height: ""` — an empty string just deletes the inline override and
  falls back to the CSS placeholder (`--pg-popup-table-max-height`, a fixed `260px` meant only to
  cover the instant before JS first runs), which is shorter than most real N-row heights. Confirmed
  live 2026-09-02 raising `MAX_VISIBLE_ROWS` to 6 — the table capped at ~260px (~3 rows) instead of
  6 until this was fixed. If you ever touch `popup-table.js`, keep that branch as `"none"`.
- **`ResetLoadingButton(id, text)` does not restore a button's original inline `height`** — it does
  `css({height: ''})`, which *removes* whatever height JS last set rather than reverting to the
  raw-HTML `style="...height:42px..."` value (once jQuery has touched a live inline-style property,
  the original attribute text is gone for good). Any fixed-height icon-only button (like the "+" add
  button) that goes through `LoadingButton()`/`ResetLoadingButton()` will shrink after its first
  loading cycle, and passing plain `"+"` as the reset text also drops the feather icon in favor of a
  literal character. Fix: don't call `ResetLoadingButton` directly on that button — wrap it in a
  small helper that resets to the icon markup and re-applies `height` explicitly (see
  `resetAddProductButton()` in `Production.js`). This is a `LoadingButton`/`ResetLoadingButton`
  quirk, not specific to PG Popup Table — watch for it on any other fixed-height icon button.
- Worked reference: `resources/views/components/modals/production/add-production.blade.php` +
  `public/Custom_js/Backoffice/Production/Production.js` (`addRow`, `continueAddProduct`,
  the `.btnAdd`/`.btn_view` reset handlers).

## Symptom → root cause map

| Symptom | Root cause |
|---|---|
| Footer buttons (Simpan/Terima/Tolak) pushed below viewport, unreachable | Modal has no height cap at all — it just grows with content. |
| Modal doesn't scroll *at all* after adding `modal-dialog-scrollable` | Bare Bootstrap classes aren't enough once `<form>` wraps `.modal-body`/`.modal-footer` — every flex level between `.modal-content` and `.modal-body` needs explicit `min-height:0`, or `.modal-content`'s `overflow:hidden` just clips instead of letting `.modal-body` scroll. |
| Modal is full-viewport-tall even with 1-2 rows (empty state wastes space) | `.modal-content`/`.modal-dialog` forced to a fixed `height`, not just capped with `max-height`. |
| Modal caps/scrolls fine when empty but overflows past the viewport at some medium row count, no scrollbar | `.modal-content { max-height: 100% }` (a *percentage*) depends on `.modal-dialog` resolving a *definite* height. If `.modal-dialog` is `height:auto` (needed for the empty-state fix above), that resolution is ambiguous/unreliable across browsers. Use an absolute cap (`calc(100dvh - Xrem)`) on `.modal-content` instead of a percentage — it doesn't depend on the parent at all. |
| Select2 dropdown renders far away from its field (e.g. top-left of modal) | `dropdownParent` was set to the outer `.modal` element itself. `.modal` is `position:fixed`, and jQuery's `.offset()` math is unreliable against a `position:fixed` ancestor. Never use the outer `.modal` as `dropdownParent`. |
| Select2 dropdown drifts further from its field with every row added, only while the modal is scrollable | `dropdownParent` was set to the *scrolling element itself* (`.modal-body`). Select2 computes the dropdown's position from `.offset()` values that don't compensate for how far you've had to scroll to reach the field, so the error grows with scroll distance (which grows with row count if the field is pinned near the bottom of a growing list). Point `dropdownParent` at a non-scrolling ancestor instead — normally `.modal-content` (see below). |
| Select2 dropdown gets cut off / clipped at the modal's edge near the bottom of a long list | `.modal-content` now needs `overflow:hidden` for the scroll fix above, and the dropdown is a DOM child of `dropdownParent` — if `dropdownParent` is `.modal-content` (or anything inside it), the same `overflow:hidden` clips the dropdown once it grows past that box. |
| Select2 dropdown renders but appears *behind* the modal, invisible | Only happens once you move `dropdownParent` to `body` (see below): Bootstrap's `.modal` is `z-index:1055`, Select2's dropdown defaults to `z-index:1051` — lower. Once the dropdown is a sibling of `.modal` in the DOM, it needs a z-index bump above 1055 or the modal paints over it. |

## The fix, as a checklist

**1. Making a modal scroll internally (long table / many rows), footer pinned:**

Add `modal-dialog-scrollable` to the `.modal-dialog` class list, then add a scoped `<style>`
block (see `resources/views/components/modals/warehouse/add-warehouse.blade.php` or the fully
worked example in `resources/views/components/modals/production/add-production.blade.php`) that:

- Cancels Bootstrap's forced full-height so short content doesn't force a tall empty modal:
  `#id .modal-dialog { height: auto !important; max-height: calc(100dvh - 2rem) !important; }`
- Caps `.modal-content` with an **absolute** cap, not a percentage:
  `#id .modal-content { height: auto !important; max-height: calc(100dvh - 2rem) !important; min-height: 0 !important; display: flex !important; flex-direction: column !important; overflow: hidden !important; }`
- Forces `min-height: 0 !important` on **every** flex level in between if the modal wraps
  `.modal-body`/`.modal-footer` in a `<form>` (very common in this codebase) — on the `form` itself
  and on `.modal-body`, each with `flex: 1 1 auto` so `.modal-body` is the thing that actually
  scrolls (`overflow-y: auto`), not the whole modal.
- Pins `.modal-header`/`.modal-footer` with `flex: 0 0 auto`.

Don't skip the `min-height:0` chain — it's the single most common way this half-works ("scrolls a
little, then clips" or "doesn't scroll at all").

**2. Any Select2/autocomplete `dropdownParent` inside that same modal:**

- Default choice, and what the majority of existing modals in this app already do: point
  `dropdownParent` at `"#modalId .modal-content"` (a non-scrolling ancestor). This also gets you a
  free correctness bonus — Select2 locks/freezes the scroll of any scrollable ancestor
  (`.modal-body`) while its dropdown is open, so the position can't go stale mid-session.
- **Only** if that modal's `.modal-content` has `overflow:hidden` (i.e. you did step 1 above) AND
  the field can end up near the bottom of a long scrolled list, `.modal-content` will clip the
  dropdown. In that case use `dropdownParent: "body"` instead (there's precedent:
  `Stock_Transfer.js`'s warehouse filter already does this) — and pair it with a z-index bump:
  `.select2-dropdown { z-index: 1065 !important; }` (anything comfortably above Bootstrap's modal
  `z-index:1055`) somewhere in that modal's own `<style>` block.
- Never point `dropdownParent` at the outer `.modal` element, and never at `.modal-body` if
  `.modal-body` is the actual scrolling container.

## Where to look for a worked example

`resources/views/components/modals/production/add-production.blade.php` +
`public/Custom_js/Backoffice/Production/Production.js` (the `#product_id` /
`#production_destination_warehouse_id` autocompletes) is the fully-debugged reference — it hit
every row in the symptom table above, in the order listed, across GitHub issue #101. Diff its
`<style>` block and `dropdownParent` calls against a broken modal before re-deriving any of this
from scratch.
