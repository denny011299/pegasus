# deploy/

`manifest.json` in this folder is a snapshot of every git-tracked file this
app needs at runtime (`app/`, `bootstrap/`, `config/`, `database/`, `public/`,
`resources/`, `routes/`, `artisan`, `composer.json`, `composer.lock`) together
with a sha1 hash of each file's contents.

It exists because production is deployed by manually uploading files (not
via git), which makes it easy to silently miss a file. The
`/system/deployment-check` page (logged-in staff only, not in the sidebar)
compares this manifest against what's actually on disk on the server and
lists anything missing or changed.

## Before every release

1. From this repo (where `.git` exists — NOT on the production server), run:

   ```
   php artisan deploy:manifest
   ```

   This regenerates `deploy/manifest.json` from the current commit.

2. Make sure `deploy/manifest.json` is included in the files you upload to
   production for this release, same as any other changed file.

3. After uploading, open `/system/deployment-check` on the server (while
   logged in) to confirm nothing is missing or stale.

If you forget step 1/2, the check page will simply show the previous
release's manifest (or "manifest not found" if this is the very first time)
— it can only compare against whatever manifest was last uploaded.
