# Production Planning / WO lifecycle

Implementation work in progress, 23 September 2026.

- PP draft → released: Kepala Operasional approves. Keep SPKP reference to PP.
- Released → inprod: assign items to supervisor, scale, armada, production line;
  one WO per PIC. Existing WO assignment must be locked against duplicate submits.
- Production reports: append-only batches per WO, explicit confirmation, client request
  ID for idempotency. Input pallet uses master qty_per_pallet in product default unit;
  alternative unit must belong to the variant conversion chain. Store original input,
  pallet number, conversion snapshot, actor and server timestamp. Sum normalized
  quantity per planning item; surplus is OK and shown separately.
- Once every WO item meets target, issue one FG covering its items. Ops approves
  first, QC second. Qty received starts empty. Stock credit happens only in final QC
  transaction, protected by document/WO and inventory locks. Tally FORM-OPS-19 is
  generated only after both approvals, with the same final warehouse timestamp.
- Signatures: uploaded PNG/JPG in master staff. Approval stores immutable name/signature
  snapshots rather than reading the current master on old documents.
- Material handover per PIC can combine items. Initial received qty null; confirmation
  and QC approval deduct actual received stock. Return of unused materials requires
  entered qty and QC approval before stock is credited. Never exceed issued quantity
  net of previously approved returns.
- Production completion and full closure are distinct: WO/PP Done requires approved
  FG and no unresolved handovers/returns. Retain complete event timestamps and links.
- A5 WO / FG / Tally print; tablet-friendly detail/confirmation modal; production line
  dashboard and large read-only overall monitor. DataTables use server-side pagination.

Verification must cover conversion, partial/excess results, duplicate requests,
approval order/role/warehouse/PIC scope, rollback on stock failure, returned materials,
signature snapshot preservation, and closure prerequisites against pegasus_testing.
