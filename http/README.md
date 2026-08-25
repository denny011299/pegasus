# Calling the API from VS Code (no Postman)

This is the extension the "call REST directly from VS Code" demos use:
**REST Client** by Huawei (`humao.rest-client`). It reads plain-text
`.http` files committed to the repo and puts a clickable "Send Request"
link above every request — everything stays local and in git, no account,
no cloud sync, no external service in the middle.

## Setup

1. Install the extension:
   ```
   code --install-extension humao.rest-client
   ```
2. Copy the key template and fill in a real key:
   ```
   cp http/.env.example http/.env
   ```
   Get a key from Backoffice > Aplikasi Eksternal > (pick an app) > tambah
   kunci. `http/.env` matches the repo's root `.env` gitignore pattern, so
   it never gets committed.
3. Open [external-api-v1.http](external-api-v1.http), click "Send Request"
   above any request. Response shows in a split pane (status, headers,
   timing, formatted JSON body).

If your local host isn't `pegasus.test`, edit `@baseUrl` at the top of the
`.http` file.

## Adding more requests

- Separate requests with a line of `###`.
- Reuse `{{baseUrl}}` / `{{apiKey}}` (defined at the top of the file) —
  don't hardcode the host or key again.
- To chain requests (e.g. use one response's value in the next call), name
  a request with `# @name someName` above it and reference
  `{{someName.response.body.$.data.id}}` later in the file.

## Where the endpoint contracts live

This file is for manual testing, not documentation — field names, error
codes, and business rules for each endpoint are documented in:
- `cdocs/integrations/202607282149-external-api-v1/` (specs/design docs)
- `config('externalapi.docs')` (source of truth also rendered as the
  in-app API docs pages)
- The controller docblocks under `app/Http/Controllers/ExternalApi/V1/`

For automated coverage, see the PHPUnit tests under `tests/` instead (see
the `pegasus-testing` skill) — this collection is for poking at the API by
hand.
