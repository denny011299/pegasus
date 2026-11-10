<?php

namespace Tests\Workflow;

use App\Models\CashGudang;
use App\Models\CashGudangDetail;
use App\Models\Customer;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * GET /api/external/v1/cash-gudang/armada-allowance — lookup nominal bekal
 * armada dari Kas Gudang (No. Pengiriman + opsional armada).
 * Default: hanya status ACC (2); pending/ditolak tidak dihitung.
 */
class ExternalApiCashGudangAllowanceFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private function seedDetail(Customer $armada, string $pengirimanNo, int $cgStatus): CashGudangDetail
    {
        $cg = new CashGudang();
        $cg->staff_id = 1;
        $cg->cash_id = 0;
        $cg->cg_date = now()->toDateString();
        $cg->cg_nominal = 1000000;
        $cg->cg_notes = 'Penyerahan kas armada dari gudang - test';
        $cg->cg_type = 2;
        $cg->cg_aksi = 2;
        $cg->status = $cgStatus;
        $cg->created_by = 1;
        $cg->save();

        $detail = new CashGudangDetail();
        $detail->cg_id = $cg->cg_id;
        $detail->customer_id = $armada->customer_id;
        $detail->cgd_nominal = 1000000;
        $detail->cgd_notes = $pengirimanNo;
        $detail->status = 1;
        if (Schema::hasColumn('cash_gudang_details', 'ref_shipment_id')) {
            $detail->ref_shipment_id = 'PMO-SHIP-'.uniqid();
        }
        $detail->save();

        return $detail;
    }

    public function test_lookup_by_pengiriman_no_returns_accepted_nominal(): void
    {
        if (! Schema::hasTable('cash_gudang_details') || ! Schema::hasTable('cash_gudangs')) {
            $this->markTestSkipped('Tabel Kas Gudang belum ada di DB testing.');
        }

        $headers = $this->externalApiHeaders();
        $armada = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();
        $pengirimanNo = '00056/FLMNT/TEST/'.uniqid();

        $this->seedDetail($armada, $pengirimanNo, 2);

        $response = $this->getJson(
            '/api/external/v1/cash-gudang/armada-allowance?'.http_build_query([
                'pengiriman_no' => $pengirimanNo,
                'armada_code' => $armada->customer_code,
            ]),
            $headers
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('meta.nominal', 1000000);
        $response->assertJsonPath('data.0.pengiriman_no', $pengirimanNo);
        $response->assertJsonPath('data.0.nominal', 1000000);
        $response->assertJsonPath('data.0.armada_code', $armada->customer_code);
        $response->assertJsonPath('data.0.cash_status', 'accepted');
    }

    public function test_lookup_pending_excluded_by_default(): void
    {
        if (! Schema::hasTable('cash_gudang_details') || ! Schema::hasTable('cash_gudangs')) {
            $this->markTestSkipped('Tabel Kas Gudang belum ada di DB testing.');
        }

        $headers = $this->externalApiHeaders();
        $armada = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();
        $pengirimanNo = '00056/FLMNT/PENDING/'.uniqid();

        $this->seedDetail($armada, $pengirimanNo, 1);

        $response = $this->getJson(
            '/api/external/v1/cash-gudang/armada-allowance?'.http_build_query([
                'pengiriman_no' => $pengirimanNo,
            ]),
            $headers
        );

        $response->assertStatus(404);
        $response->assertJsonPath('error.code', 'allowance_not_found');
    }

    public function test_lookup_declined_never_returned(): void
    {
        if (! Schema::hasTable('cash_gudang_details') || ! Schema::hasTable('cash_gudangs')) {
            $this->markTestSkipped('Tabel Kas Gudang belum ada di DB testing.');
        }

        $headers = $this->externalApiHeaders();
        $armada = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();
        $pengirimanNo = '00056/FLMNT/DECLINED/'.uniqid();

        $this->seedDetail($armada, $pengirimanNo, 3);

        $response = $this->getJson(
            '/api/external/v1/cash-gudang/armada-allowance?'.http_build_query([
                'pengiriman_no' => $pengirimanNo,
                'cash_status' => 'any',
            ]),
            $headers
        );

        $response->assertStatus(404);
        $response->assertJsonPath('error.code', 'allowance_not_found');
    }

    public function test_lookup_missing_returns_404(): void
    {
        $headers = $this->externalApiHeaders();

        $response = $this->getJson(
            '/api/external/v1/cash-gudang/armada-allowance?pengiriman_no=NO-SUCH-SHIPMENT-'.uniqid(),
            $headers
        );

        $response->assertStatus(404);
        $response->assertJsonPath('error.code', 'allowance_not_found');
    }

    public function test_lookup_requires_pengiriman_or_ref(): void
    {
        $headers = $this->externalApiHeaders();

        $response = $this->getJson(
            '/api/external/v1/cash-gudang/armada-allowance',
            $headers
        );

        $response->assertStatus(422);
    }
}
