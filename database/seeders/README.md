# Database snapshot seeders

Versioned dev-environment data. `database/seeders/data/*.json` is a committed
snapshot of a working database — one file per table, so changes show up as a
readable git diff instead of an opaque SQL blob.

There can be **more than one** snapshot: `data/` is the "default" one, and
`database/seeders/snapshots/<name>/` holds any additional named ones (a
different client's data, a point-in-time backup, etc.), each in the exact
same one-file-per-table JSON shape. `php artisan snapshot:list` shows every
snapshot currently committed; the `/deploy/console` picker (see
`cdocs/docs/deploy-shared-hosting.md`) lets an operator pick among them
before restoring.

## Restore a dev environment

```bash
php artisan db:seed              # always restores the "default" snapshot
php artisan snapshot:restore     # same thing, explicit name (defaults to "default")
php artisan snapshot:restore client-acme-2026-08   # restores a named snapshot instead
```

Truncates every table in the chosen snapshot and reloads it. To rebuild the schema too:

```bash
php artisan migrate:fresh --seed
```

(`migrate:fresh --seed` only ever runs the *default* seeder chain, so it
restores the "default" snapshot — run `snapshot:restore <name>` separately
afterward for a named one.)

> **Heads up:** this repo currently has migrations that were never applied to the
> live database (`php artisan migrate:status` shows them Pending, while the tables
> already exist). Until that is reconciled, `migrate:fresh` does **not** reproduce
> the real schema, and the seeder will report the difference and skip what does not
> fit. Seeding onto an existing, correct schema works fine.

## Update the "default" snapshot from the live/dev database

```bash
php artisan seed:dump              # every table in the manifest
php artisan seed:dump products,product_variants   # just these
```

Then commit the JSON diff. That is the whole workflow — the snapshot grows with
the repo because the dump is opt-out: any table a future migration adds is picked
up automatically on the next run.

## Create a new NAMED snapshot from a .sql dump file

For turning a full `mysqldump` export (a `.sql` file, possibly very large)
into a new, separately-restorable snapshot — e.g. a client's production dump
you want available as staging data without overwriting the "default" one:

```bash
php artisan snapshot:import-sql "/path/to/dump.sql" client-acme-2026-08 \
    --label="Acme prod, 2026-08-10" \
    --description="Requested by PM to reproduce ticket #123"
```

What it does, without ever loading the .sql file into PHP or reading it by
hand: creates a disposable scratch database on your local MySQL server,
imports the dump into it with the `mysql` client, dumps that scratch
database into `database/seeders/snapshots/client-acme-2026-08/*.json` using
the exact same manifest/table rules as `seed:dump`, then drops the scratch
database again. Review the generated JSON diff and commit the folder — only
what's committed shows up in `snapshot:list` / the deploy console picker.

Needs a `mysql` client binary reachable locally (checks `--mysql-bin`, then
`MYSQL_CLI_PATH` in `.env`, then a few common install paths) and CREATE
DATABASE privilege on your configured DB connection. Local/dev tool only —
same as `seed:dump`, it is never reachable over HTTP.

Run `php artisan snapshot:list` any time to see every snapshot currently
committed (name, label, row counts, when it was generated).

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

`snapshot_manifest.php` is shared by every snapshot (default and named alike):

- `skip` / `skip_patterns` — never dumped (sessions, cache, jobs, `backup_*`, and
  the `migrations` ledger itself).
- `cap` — append-only tables where only the newest N rows are kept
  (`log_stocks`, `dashboard_change_logs`, `external_api_request_logs`).
- `priority` — tables loaded first; everything else follows alphabetically.

## Layout

```
database/seeders/
  data/                       # the "default" snapshot
    _snapshot.json
    <table>.json ...
  snapshots/
    <name>/                   # any additional named snapshot
      _snapshot.json          # same shape, plus optional "label" / "description" /
      <table>.json ...        # "source_sql_file" written by snapshot:import-sql
```

`SnapshotRegistry` (`app/Support/SnapshotRegistry.php`) is what enumerates these —
`snapshot:list`, `snapshot:restore`, and the deploy console picker all go through it
rather than trusting a name from the outside directly.

## Contents

Every snapshot here (default and named) is expected to carry **real production
data, including staff password hashes and customer/supplier contact details**,
by deliberate choice. Treat the repo accordingly. The raw `.sql` dump files a
named snapshot was imported *from* are not meant to be committed themselves —
they're typically large, single-use, and fully reproduced by the generated
JSON; keep them out of git (see `.gitignore`) and delete/archive them locally
once the snapshot is imported.
