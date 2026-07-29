# Database snapshot seeders

Versioned dev-environment data. `database/seeders/data/*.json` is a committed
snapshot of a working database — one file per table, so changes show up as a
readable git diff instead of an opaque SQL blob.

## Restore a dev environment

```bash
php artisan db:seed
```

Truncates every table in the snapshot and reloads it. To rebuild the schema too:

```bash
php artisan migrate:fresh --seed
```

> **Heads up:** this repo currently has migrations that were never applied to the
> live database (`php artisan migrate:status` shows them Pending, while the tables
> already exist). Until that is reconciled, `migrate:fresh` does **not** reproduce
> the real schema, and the seeder will report the difference and skip what does not
> fit. Seeding onto an existing, correct schema works fine.

## Update the snapshot

```bash
php artisan seed:dump              # every table in the manifest
php artisan seed:dump products,product_variants   # just these
```

Then commit the JSON diff. That is the whole workflow — the snapshot grows with
the repo because the dump is opt-out: any table a future migration adds is picked
up automatically on the next run.

## Why it works across branches

The loader filters every row against `Schema::getColumnListing()` on the branch
you are actually on:

| Situation | Behaviour |
| --- | --- |
| Snapshot has a column this branch lacks | Column dropped, noted in the run output |
| Branch has a column the snapshot lacks | Falls back to the schema default |
| Snapshot has a table this branch lacks | Table skipped, noted in the run output |

So one committed snapshot restores a usable environment from an older branch
without being regenerated.

## IDs and foreign keys

Primary keys are written literally rather than regenerated, so every foreign key
still resolves after a restore. `AUTO_INCREMENT` is then explicitly pushed past
the highest restored id per table.

This database has **no real FK constraints** — the relationships are plain
`unsignedBigInteger` columns — so MySQL enforces no insert ordering. The manifest
still loads masters first (and truncates in reverse) so the data stays coherent
if constraints are ever added.

## Configuration

`snapshot_manifest.php`:

- `skip` / `skip_patterns` — never dumped (sessions, cache, jobs, `backup_*`, and
  the `migrations` ledger itself).
- `cap` — append-only tables where only the newest N rows are kept
  (`log_stocks`, `dashboard_change_logs`, `external_api_request_logs`).
- `priority` — tables loaded first; everything else follows alphabetically.

## Contents

The snapshot carries **real production data, including staff password hashes and
customer/supplier contact details**, by deliberate choice. Treat the repo
accordingly.
