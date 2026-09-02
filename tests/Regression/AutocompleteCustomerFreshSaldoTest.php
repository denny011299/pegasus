<?php

namespace Tests\Regression;

use App\Http\Controllers\AutocompleteController;
use App\Models\Customer;
use Illuminate\Http\Request;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #130 (item 37): opening "Tambah Aktivitas" for a Dompet Armada pre-selected from the page
 * filter used to reuse that filter dropdown's cached Select2 option data for `customer_saldo` —
 * stale the moment an "Uang Masuk Customer" activity changed the real balance in between, so the
 * default "Pengembalian Dana Langsung" nominal could show the wrong sign (Minus when the current
 * saldo was actually Plus). Cash_Operational.js now re-fetches via `/autocompleteCustomer` with an
 * exact `customer_id` right before filling that field. This covers the new `customer_id` filter
 * `autocompleteCustomer()` needed to make that fresh lookup possible.
 *
 * Called directly against the controller (rather than through `$this->post()`) because this
 * endpoint's `echo json_encode(...)` — not `return response()->json(...)` — response isn't
 * captured by `TestResponse` under this test harness; that's a pre-existing quirk of this one
 * controller method, unrelated to the fix under test here.
 */
class AutocompleteCustomerFreshSaldoTest extends TestCase
{
    use ActingAsStaff;

    /** @return array<int, array<string, mixed>> */
    private function callAutocomplete(array $params): array
    {
        ob_start();
        app(AutocompleteController::class)->autocompleteCustomer(new Request($params));
        $body = ob_get_clean();

        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded, 'autocompleteCustomer must echo valid JSON, got: ' . $body);

        return $decoded['data'] ?? [];
    }

    public function test_autocomplete_customer_by_exact_id_returns_the_current_saldo(): void
    {
        $this->actingAsSuperAdminStaff();

        $customer = Customer::where('status', 1)->firstOrFail();
        $customer->customer_saldo = -200000;
        $customer->save();

        $rows = $this->callAutocomplete(['customer_id' => $customer->customer_id]);
        $this->assertNotEmpty($rows, 'a valid customer_id must return exactly that customer');
        $this->assertSame($customer->customer_id, (int) $rows[0]['customer_id']);
        $this->assertSame(-200000, (int) $rows[0]['customer_saldo']);

        // Saldo changes (e.g. via an accepted "Uang Masuk Customer" activity) — the very next
        // lookup by the same customer_id must reflect it immediately, not a cached value.
        $customer->customer_saldo = 300000;
        $customer->save();

        $rows = $this->callAutocomplete(['customer_id' => $customer->customer_id]);
        $this->assertSame(300000, (int) $rows[0]['customer_saldo'], 'must reflect the current saldo, not a stale cached value');
    }

    public function test_autocomplete_customer_without_customer_id_still_works_as_a_keyword_search(): void
    {
        $this->actingAsSuperAdminStaff();

        $customer = Customer::where('status', 1)->firstOrFail();

        $rows = $this->callAutocomplete(['keyword' => $customer->customer_notes]);
        $this->assertNotEmpty($rows, 'the customer_id filter is additive — plain keyword search must keep working');
    }
}
