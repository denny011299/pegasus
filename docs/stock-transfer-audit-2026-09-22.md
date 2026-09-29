# Audit Stock Transfer — 22 September 2026

Scope: internal stock transfer create/update, source stock check, shipping/unpacking,
receipt/conversion/roll-up, cancellation. Operational code was not changed.
Database checks used `pegasus_testing` with rollback through `Tests\TestCase`.

## Confirmed reproductions

### P1 — Stock check passes but shipping fails without a target-unit stock row

Location: `app/Support/ProductUnitStock.php`, `deductQty`, lines 582–584.

With 1 DOS = 12 Piece, source stock has one DOS and no Piece row. Request 2 Piece:
`checkItems` reports sufficient stock, but shipping throws
`Satuan stok tidak ditemukan di gudang`. The method requires an existing target-unit
row before attempting to unpack an ancestor. This differs from a zero-stock Piece row,
which works. The shipping transaction rolls back; the transfer remains pending.

Suggested fix: permit creating a missing target stock row inside the locked transaction
when an allowed ancestor can cover the quantity; keep failure atomic.

### P1 — Cancel shipment fails after another transaction uses the unpacked remainder

Location: `app/Http/Controllers/StockTransferController.php`, `restoreSourceStock`,
especially lines 2573–2607.

Start with 1 DOS and 0 Piece. Ship 2 Piece: unpacking leaves 0 DOS and 10 Piece.
Another transaction consumes those 10 Piece. Cancel shipment then tries to restore
the original composition: add 1 DOS and subtract 10 Piece. The subtraction fails
with `Stok satuan kirim tidak mencukupi`, and the transaction rolls back.
The shipment cannot be cancelled even though returning its 2 Piece is valid.
There is no partial stock credit because of the transaction.

Suggested fix: return the shipped physical equivalent when the original composition
can no longer be restored; preserve total equivalent quantity and retail warehouse rules.

### P2 — Invalid detail is silently omitted before persistence validation

Location: `app/Http/Controllers/StockTransferController.php`, `normalizeItems`,
lines 2727–2732; called by insert and update before validation/persistence.

A payload with a valid item plus an item whose unit_id is zero becomes a one-item
payload. No validation error remains for the discarded row. Insert/update can return
success after saving only the valid items. During update, old active details are
deactivated and replaced by this reduced set. Frontend validation reduces normal UI
exposure, but the backend does not protect malformed/incomplete requests.

The regression reproduces normalization directly; persistence impact follows from
the insert/update loops over its result, rather than a separate HTTP reproduction.

Suggested fix: reject the complete payload with a row-specific error before dropping
or merging anything.

## Additional static finding

### P1 — Update can race with shipping and replace already-shipped details

Location: `app/Http/Controllers/StockTransferController.php`, lines 1444 and 1533–1570.

Update reads a pending header outside its transaction, then replaces details using
that object without locking/rechecking header status inside the transaction. If shipping
commits in between, update can still replace quantities/units or warehouses after stock
was deducted. Receipt then reads the new details. Shipping and receipt themselves do
recheck status under a header lock; update does not.

This is a code-level concurrency finding, not reproduced with simultaneous database
connections in this audit. Suggested fix: acquire the header lock and recheck pending
status and permissions before modifying either header or details.

## Verification

- Existing `StockTransferWorkflowTest`: 10 tests, 84 assertions, 9 passing and 1 failing.
  The failure is `test_main_warehouse_cannot_edit_retail_stock_request` at line 550:
  expected HTTP 200, received 403. It does not demonstrate a conversion failure;
  middleware/permission expectations require separate investigation.
- New `StockTransferConversionAuditTest`: 3 tests, 12 assertions, all pass by asserting
  the current buggy behavior. These are reproduction records, not proof of fixes.
- Both runs report four deprecation notices.
- Existing passing coverage includes main-to-retail conversion, main-destination
  roll-up, unpacking with an existing lower-unit stock row, ordinary cancellation,
  and retail-to-main requests deducted in retail units.
- No operational data repair or production deployment was performed. The testing
  documentation paths `cdocs/testing/KNOWN_ISSUES.md` and `ROADMAP.md` referenced by
  the skill are absent in this checkout, so findings are recorded here.
