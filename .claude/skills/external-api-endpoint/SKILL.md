---
name: external-api-endpoint
description: Checklist and file layout for adding an External API endpoint to okejob-pegasus (the /api/external/{version} routes consumed by third-party systems, NOT the internal jQuery endpoints in routes/web.php). Load before creating, changing, or removing any External API endpoint, so the endpoint ships with its documentation, follows the standard response shape, and stays inside the platform built in Phase 1.
---

# Adding an External API endpoint

The External API platform (Phase 1) already handles authentication, logging,
versioning, error shape, and documentation rendering. When you add an endpoint,
you write **business logic + one documentation class** — nothing else.

**Do not** re-implement API key checking, request logging, error formatting, or
a documentation page. All of that is already wired.

## Is this actually an External API endpoint?

| | Internal (jQuery admin UI) | External API (this skill) |
|---|---|---|
| Route file | `routes/web.php` | `routes/external-api/{version}.php` |
| URL shape | `/getProduct`, `/insertProduct` | `/api/external/v1/products` |
| Auth | session (`checkLogin`, `check.access`) | API Key header |
| Response | `1`, `{status:-1}`, bare collection | `ApiResponse::success()` / `::error()` |
| Consumer | this app's own JavaScript | third-party server |

If the caller is this app's own Blade page, it is **not** an External API
endpoint — follow `pegasus-conventions` instead.

## The five steps

Do all five together. An endpoint merged without step 4 is incomplete.

### 1. Route

Add to `routes/external-api/v1.php` (or the newest version file):

```php
Route::get('/products', [ExternalProductController::class, 'index'])
    ->name('products.index');
```

Authentication, logging, the `externalApi.v1.` name prefix, and the `/api/external/v1`
prefix are applied by the group in `routes/api.php`. **Do not add middleware
here.**

The `/api/external` part comes from `config('externalapi.base_path')` and is the
**only** place it is written down. Never hardcode it — in a route, a doc class,
a test, or a message. Anything that needs to build or match an External API URL
goes through `App\ExternalApi\Support\ExternalApiPath`:

```php
ExternalApiPath::endpoint('v1', '/products');  // /api/external/v1/products
ExternalApiPath::baseUrl('v1');                // https://host/api/external/v1
ExternalApiPath::matches($request);            // is this an External API call?
```

### 2. Controller

Put External API controllers in `app/Http/Controllers/ExternalApi/` so they are
never confused with the admin controllers. Keep them thin and reuse the
existing models — an External API endpoint is a new *view* onto existing data,
not a reason to duplicate business logic.

```php
namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Auth\ExternalApiAuthenticator;
use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExternalProductController extends Controller
{
    public function index(Request $request)
    {
        // Aplikasi pemanggil, kalau perlu dipakai untuk membatasi data.
        $application = ExternalApiAuthenticator::application($request);

        $products = (new Product())->getProduct($request->all());

        return ApiResponse::success($products);
    }
}
```

Rules:
- **Always** return via `ApiResponse` — never `response()->json()` directly,
  never a bare model/collection. The response shape is a contract with people
  outside this codebase.
- Paginated lists use `ApiResponse::paginated($paginator)`.
- For business failures use `ApiResponse::error('kode_stabil', 'Pesan.', 422)`.
  The code is what clients branch on, so keep it stable once released.
- Uncaught exceptions are already converted to the standard error shape by
  `App\ExternalApi\Support\ExceptionRenderer` — do not add try/catch just to
  format errors.
- Unlike the internal endpoints, External API input **must** be validated —
  the caller is not our own form. `$request->validate()` is converted to a
  `validation_failed` error automatically.

### 3. Response fields

Third parties cannot read your mind or your database. Return explicit,
named fields; do not dump the whole Eloquent row. Never expose internal
columns (`status` soft-delete flags, `created_by` staff IDs, cost prices)
unless the integration genuinely needs them.

### 4. Documentation — REQUIRED, not optional

Create one class per endpoint in
`app/ExternalApi/Docs/Endpoints/V1/`, extending
`App\ExternalApi\Docs\ApiEndpointDoc`:

```php
class ProductListDoc extends ApiEndpointDoc
{
    public function key(): string { return 'produk-daftar'; }
    public function title(): string { return 'Daftar Produk'; }
    public function method(): string { return 'GET'; }
    public function path(): string { return '/products'; }
    public function group(): string { return 'produk'; }

    public function description(): string
    {
        return 'Mengambil daftar produk aktif beserta variannya.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false,
             'description' => 'Nomor halaman.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [['product_id' => 1, 'product_name' => 'Kaos Polos']],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'product_not_found', 'http_status' => 404,
             'message' => 'Produk tidak ditemukan.'],
        ];
    }
}
```

Then register it — this single line is what makes it appear on the
Dokumentasi API Eksternal page:

```php
// config/externalapi.php
'docs' => [
    \App\ExternalApi\Docs\Endpoints\V1\ProductListDoc::class,
],
```

Documentation checklist for every endpoint:
- [ ] title, method, path, description filled in
- [ ] `group()` is a key in `config('externalapi.doc_groups')` (add one if the
      endpoint belongs to a genuinely new area)
- [ ] every path / query / body parameter listed, with required flag
- [ ] `requestExample()` for anything with a body
- [ ] `responseExample()` shows a **realistic** payload in the standard shape
- [ ] `errors()` lists failures specific to this endpoint
- [ ] **do not** repeat the platform errors (invalid key, revoked, expired,
      disabled application) — those are already documented once on the page
- [ ] registered in `config('externalapi.docs')`

### 5. Verify

Open **Integrasi → Dokumentasi API Eksternal** and confirm the endpoint appears
with correct examples. Then call it for real with an API Key from
**Integrasi → Aplikasi Eksternal** and confirm the request shows up in
**Integrasi → Log API Eksternal**.

Documentation is **one page per module**, not one long page:

- `/externalApiDocumentation` — "Umum": authentication, base URL, headers,
  response format, platform errors, and a card per module.
- `/externalApiDocumentation/{group}` — one page per module, its endpoints only.

A new `group()` value therefore creates a whole new page by itself — no route
and no view to add. Put anything that applies to *every* endpoint on the Umum
page (`partials/doc-general.blade.php`), never in an endpoint class, so there is
only one copy of it.

## Adding a new API version

1. Add `'v2'` to `config('externalapi.versions')`.
2. Create `routes/external-api/v2.php`.
3. New doc classes override `version()` to return `'v2'`.

Old versions keep working; nothing needs to be moved.

## Things that are already handled — don't rebuild them

| Concern | Where it lives |
|---|---|
| API Key generation / hashing / comparison | `App\ExternalApi\ApiKeyManager` |
| Authentication + status/expiry/app checks | `App\ExternalApi\Auth\ExternalApiAuthenticator` |
| Applying auth to routes | `App\Http\Middleware\AuthenticateExternalApi` |
| Request logging (after response, no latency) | `App\Http\Middleware\LogExternalApiRequest` |
| Response & error shape | `App\ExternalApi\Http\ApiResponse` |
| Exception → JSON error | `App\ExternalApi\Support\ExceptionRenderer` |
| URL building / prefix matching | `App\ExternalApi\Support\ExternalApiPath` |
| Documentation rendering | `App\ExternalApi\Docs\ApiDocRegistry` + `Backoffice/ExternalApi/Documentation.blade.php` |

## Out of scope until a later phase

API scopes, rate limiting, IP whitelisting, key rotation, webhooks, analytics,
SDK generation, developer portal. The schema and layering leave room for these
— if an endpoint seems to need one, raise it rather than improvising a
one-off implementation inside the endpoint.
