---
name: pegasus-testing
description: How automated testing works in okejob-pegasus — real MySQL test DB, session-based auth helper, folder-per-test-type conventions, and which test type to reach for. Load before writing, running, or extending ANY PHPUnit test in this repo (Smoke/Health/Workflow/Regression/DatabaseTransaction/Unit/Feature), so new tests follow the established setup instead of re-deriving it or defaulting to Laravel's sqlite/RefreshDatabase stock config.
---

# Automated testing in okejob-pegasus

Full plan, phase-by-phase status, and design rationale live in `cdocs/testing/` — this skill is
the fast-start version. Read `cdocs/testing/ROADMAP.md` for current progress and
`cdocs/testing/KNOWN_ISSUES.md` before assuming a test failure is your test's fault; it might be a
confirmed, deliberately-deferred bug already tracked there.

## Non-obvious setup — don't rebuild this

- Tests run against a **dedicated `pegasus_testing` MySQL database**, not sqlite. The codebase uses
  MySQL-only SQL (`orderByRaw('FIELD(...)')`, `DATE_FORMAT()`, a raw `UPDATE ... INNER JOIN`
  migration) that sqlite can't execute — a green test against sqlite would prove nothing.
  `.env.testing` points there; `phpunit.xml` has no DB env overrides so it inherits `.env.testing`.
- One-time per machine: `php artisan migrate --env=testing --force` then
  `php artisan db:seed --env=testing --force` (restores the committed seed snapshot — see memory
  `pegasus-seed-snapshot`). Re-run the seed command any time to reset to the committed baseline.
- `tests/TestCase.php` uses `DatabaseTransactions`, **not** `RefreshDatabase` — re-seeding ~39k rows
  per test would be too slow. Each test rolls back in a transaction; the seeded baseline persists
  across the whole run.
- Run with `php vendor/bin/phpunit` directly (`--testsuite=Smoke` etc. for a subset). `php artisan
  test --testsuite=X` does **not** resolve these custom suite names on this Laravel version.

## Auth in tests: no `actingAs()`

This app has no `Auth::` guard — login is `Session::put('user', $staffRow)`, checked by
`App\Support\RoleAccess`. Use the `Tests\Support\ActingAsStaff` trait instead:

- `actingAsSuperAdminStaff()` — `role_id = -1`, bypasses every permission check. Use for
  "does this page/flow work at all" tests.
- `actingAsStaffWithOnlyPermission($module, $abilities = ['view'])` — grants exactly one
  module/ability pair. **Use this, not super-admin, to prove a route's `check.access` module
  string is actually correct** — super-admin bypasses the module-name check entirely and can't
  catch a wrong middleware binding.
- `actingAsStaffWithNoAccess()` — logged in, zero permissions. Proves a route's middleware
  actually blocks (not just that a guest gets redirected).
- `withActiveWarehouse($id)` — only needed when a test must pin a specific non-default warehouse;
  `ProductStock`'s global scope otherwise falls back to session → main warehouse → first active.

## Folder-per-test-type (see `cdocs/testing/guides/*_GUIDE.md` for the full rationale of each)

| Path | When to add here |
|---|---|
| `tests/Smoke/` | A page/endpoint should return 200 (authorized), 403 (wrong permission), or redirect (guest) — never a 500. One class per sidebar group (`cdocs/docs/modules.md`), data-provider-driven. |
| `tests/Health/` | A data-consistency rule the DB itself doesn't enforce (no real FK constraints exist in this app): negative stock, duplicate SKU, orphan references, schema/model drift. |
| `tests/Workflow/` | A complete business process, asserting real DB state after each step — not just an HTTP 200. **Document the real flow in `cdocs/testing/workflows/<NAME>.md` first** (trace the actual controller → model calls; don't assume `cdocs/docs/AI_CONTEXT.md`'s example diagram is accurate for this codebase — it wasn't, for Purchase Order). |
| `tests/DatabaseTransaction/` | Proving what a flow's missing `DB::transaction()` actually does under a mid-operation failure — most risky flows here have no transaction at all (see the guide's table of which controllers do/don't). This is about documenting the real blast radius, not confirming safety. |
| `tests/Regression/` | Every confirmed bug, whether fixed or deliberately deferred. **Put it here regardless of also being covered by a Smoke/Health/Workflow test** — this folder is the permanent "never delete" record. A deferred bug gets a test that asserts its *current* (buggy) behavior on purpose, with a comment saying what to flip once it's fixed. |
| `tests/Unit/`, `tests/Feature/` | Stock Laravel dirs — isolated logic (no DB) and basic controller checks that don't fit the categories above. |

## Known gotchas worth not re-discovering

- **Warehouse fixtures already exist in the seed snapshot** (added 2026-08-01): `warehouse_types`
  id 1 = `Gudang Utama` (main, `is_main_warehouse=1`), id 2 = `Gudang Eceran` (non-main);
  `warehouses` id 1 = `Gudang Pusat` (the main one — every active `product_stocks`/
  `supplies_stocks` row is backfilled to `warehouse_id = 1`), id 2 = `Gudang Eceran Toko` (empty,
  no stock). Before this, both tables were completely empty and every stock row had
  `warehouse_id = NULL`, which broke `SalesOrderStock::mainWarehouseId()` outright. No
  `product_variant` in the seed data has `retail_unit` set yet, so retail-warehouse routing needs a
  fresh fixture — see `tests/Workflow/SalesOrderRetailAndUnitConversionFlowTest.php`, which also
  covers Sales Order's OWN separate unit-conversion implementation
  (`App\Support\SalesOrderStock`/`ProductUnitStock` — deliberately independent of Production's
  inline ladder logic per that class's own docblock, so don't assume the two share bugs or fixes).
- **`check.access.any:ModuleA,ModuleB,ModuleC,ability`** only ever enforces `ModuleA` with a
  hardcoded `view` — Laravel splits the comma string into separate positional args before calling
  `checkAccessAny::handle()`, which only declares one `$spec` param. Confirmed, deliberately
  deferred (not fixed) — see `cdocs/testing/KNOWN_ISSUES.md` and memory
  `pegasus-check-access-any-bug`. Don't "fix" this opportunistically in an unrelated task.
- **`boms.product_id` actually stores a `product_variant_id`**, not a `products.product_id` —
  confirmed from `Bom::getBom()`'s join to `product_variants`. A BOM/recipe is per product variant.
  Get this wrong and every BOM lookup silently returns nothing.
- **When a real flow's fixtures are too entangled to hand-pick cleanly** (Production/BOM: every
  real BOM in the seed data has 4-5 ingredients and hits unit-conversion or "dos/pack" special-case
  branches), build a **fully fresh fixture directly via Eloquent** in the test instead of reusing
  seeded data — see `tests/Workflow/ProductionFlowTest.php`'s `createFixture()`. Don't go through
  the app's own `insertX()` model methods for fixture setup; they carry validation/business logic
  you don't want in a fixture. `DatabaseTransactions` rolls it all back after, so this is safe.
- **`log_stocks.log_item_id` is polymorphic on `log_type`**, undocumented anywhere else:
  `log_type=1` → `product_variant_id`, `log_type=2` → `supplies_id` (not `supplies_variant_id`).
  `log_category` is direction (1=masuk/in, 2=keluar/out), independent of `log_type`.
- **`log_stocks` sum-reconciliation is not feasible**: the seed snapshot caps it at the most recent
  1000 rows (`database/seeders/snapshot_manifest.php`), so it's a partial window, not the full
  movement history.
- **10 models point at wrong/missing tables or primary keys** — `tests/Health/SchemaConsistencyTest.php`
  tracks the current, authoritative list with the specific reason for each; don't re-derive this by
  hand from the old migration-drift memory, which is partially stale.
- PHPUnit data providers run **before** the Laravel app boots — don't use `app_path()` or other
  container-dependent helpers inside a `#[DataProvider]` method; use `__DIR__`-relative paths.
- **Never write a test that actually executes a `dd()`/`die()` code path.** `dd()` terminates the
  PHP process — PHPUnit can't catch it, and it aborts the ENTIRE test run mid-suite, not just one
  test. If a confirmed bug is a literal `dd()` left in shipped code (see
  `SalesOrderDeliveryDetail::insertSoDeliveryDetail()` in `KNOWN_ISSUES.md`), document it and route
  around it (e.g. an empty item list that never reaches the line) — don't add a regression test for
  it. Grep for `dd(` in any method you're about to call from a test if something feels off before
  you run it.
- **File-upload endpoints**: use `Illuminate\Http\UploadedFile::fake()->image(...)` in the POST
  payload array, not a real path — Laravel's test client auto-splits `UploadedFile` instances out
  of the data array into the files bag. Some accept endpoints (`accTt`, and probably others using
  the same `HelperController::insertFile()` pattern) unconditionally index `$data["tt_image"]`-style
  keys that are only populated when a real upload is present — the real frontend usually
  client-side-blocks the action without one, so this is a fixture requirement, not a bug (same
  precedent as CashArmada's always-required `photo` field). Test-uploaded files land in real
  `public/upload/<type>/` paths and aren't cleaned up automatically — feel free to delete stray
  ones you notice (`git status --porcelain public/upload`).
- **Bug-handling policy**: see memory `pegasus-testing-defer-bugs-policy` — confirmed bugs get
  documented in `KNOWN_ISSUES.md` (+ a regression test where safe) and the work continues; don't
  stop to ask fix-vs-defer per bug.

## Writing a new test — the loop that actually works

1. Read the real code path first (controller → model), don't assume the obvious business-flow
   diagram is accurate.
2. Query the real seeded data in `pegasus_testing` to pick concrete fixtures and to check whether
   the invariant you're about to assert already holds (it sometimes doesn't — see `DuplicateSkuTest`
   and its documented data-quality exceptions).
3. Write the test, run it. If it fails on the *first* try against real seeded data and real code,
   that's usually a genuine finding, not a test bug — verify with a quick raw SQL query or
   `php artisan tinker` before assuming your test is wrong.
4. Update `cdocs/testing/ROADMAP.md` / `TEST_MATRIX.md`, and `KNOWN_ISSUES.md` + a project memory
   if you found something that isn't fixed on the spot.
