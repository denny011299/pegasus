<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Support\ArmadaUpsert;
use Tests\TestCase;

/**
 * App\Support\ArmadaUpsert (GitHub #187) — shared by ShipmentController::scheduled()/shipped() so
 * an armada_code that hasn't been created yet (via Sinkronisasi Armada or PUT /armada/{code})
 * doesn't block a shipment from being scheduled/shipped. Semantics mirror
 * MasterArmadaController::update()'s upsert (bare create, reactivate-if-inactive) minus any
 * profile payload, since a shipment body never carries PIC/No Pol/etc.
 */
class ArmadaUpsertTest extends TestCase
{
    public function test_creates_a_bare_customer_when_the_code_does_not_exist(): void
    {
        $code = 'NEW-'.uniqid();

        $customer = ArmadaUpsert::resolveOrCreate($code);

        $this->assertSame($code, $customer->customer_code);
        $this->assertSame(1, (int) $customer->status);
        $this->assertNull($customer->customer_pic);
        $this->assertNull($customer->customer_notes);
        $this->assertNotNull($customer->external_api_synced_at);
    }

    public function test_returns_the_existing_active_customer_untouched(): void
    {
        $customer = new Customer();
        $customer->customer_code = 'EXIST-'.uniqid();
        $customer->customer_name = 'Armada Sudah Ada';
        $customer->customer_pic = 'Budi';
        $customer->status = 1;
        $customer->save();

        $resolved = ArmadaUpsert::resolveOrCreate($customer->customer_code);

        $this->assertSame($customer->customer_id, $resolved->customer_id);
        $this->assertSame('Budi', $resolved->customer_pic, 'existing profile data must not be wiped');
    }

    public function test_reactivates_an_inactive_customer(): void
    {
        $customer = new Customer();
        $customer->customer_code = 'INACTIVE-'.uniqid();
        $customer->customer_name = 'Armada Nonaktif';
        $customer->status = 0;
        $customer->save();

        $resolved = ArmadaUpsert::resolveOrCreate($customer->customer_code);

        $this->assertSame($customer->customer_id, $resolved->customer_id);
        $this->assertSame(1, (int) $resolved->fresh()->status);
    }
}
