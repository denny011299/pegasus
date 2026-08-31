# deploy/

`manifest.json` in this folder is a snapshot of every git-tracked file this
app needs at runtime (`app/`, `bootstrap/`, `config/`, `database/`, `public/`,
`resources/`, `routes/`, `artisan`, `composer.json`, `composer.lock`) together
with a sha1 hash of each file's contents.

It also stores a **delta vs the previous manifest** when you regenerate:

- `added` — files baru di rilis ini
- `changed` — files yang hash-nya berubah
- `removed` — files yang hilang dari rilis ini

The `/system/deployment-check` page (logged-in staff only, not in the sidebar)
compares this manifest against what's actually on disk on the server and
lists anything missing, changed, **or newly added in this release**.

## Before every release

1. From this repo (where `.git` exists — NOT on the production server), run:

   ```
   php artisan deploy:manifest
   ```

   This regenerates `deploy/manifest.json` from the current commit and
   computes `added` / `changed` / `removed` against the previous
   `deploy/manifest.json` (if any).

2. Make sure `deploy/manifest.json` is included in the files you upload to
   production for this release, same as any other changed file. Prefer
   uploading at least everything in `added` + `changed`.

3. After uploading, open `/system/deployment-check` on the server (while
   logged in) to confirm nothing is missing or stale, and that new files
   from this release are present.

If you forget step 1/2, the check page will simply show the previous
release's manifest (or "manifest not found" if this is the very first time)
— it can only compare against whatever manifest was last uploaded.
