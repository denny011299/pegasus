---
name: pegasus-modal-dropdown
description: How to make a Bootstrap modal scroll internally (long tables, many rows) without breaking Select2 dropdowns inside it, in the okejob-pegasus Kanakku admin theme. Load before adding modal-dialog-scrollable to any modal, before debugging a modal whose footer is pushed off-screen or won't scroll, or before debugging a Select2/autocomplete dropdown that floats to the wrong place, gets cut off, or renders behind the modal.
---

# Bootstrap modal scrolling + Select2 dropdowns in Kanakku modals

This app's modals are hand-built Blade partials (`resources/views/components/modals/**`), not a
component library, so every "make this modal scroll" or "the dropdown is in the wrong place" bug
has to be solved by hand against Bootstrap 5's CSS + Select2 4.0's positioning JS. Both have sharp
edges that look like unrelated bugs but are really the same handful of root causes. This was all
learned the hard way fixing GitHub #101 (Produksi modal) — read this before repeating that work.

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
