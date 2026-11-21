<?php

namespace Tests\Feature;

use App\Services\SmmCenterClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmmCenterOrderApiTest extends TestCase
{
    public function test_client_can_submit_an_order_and_fetch_its_status(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);

        Http::fake([
            'smm-center.com/api/v2' => Http::sequence()
                ->push(['order' => 7654321])
                ->push([
                    'charge' => '0.90',
                    'start_count' => '120',
                    'status' => 'In progress',
                    'remains' => '80',
                    'currency' => 'USD',
                ]),
        ]);

        $client = app(SmmCenterClient::class);
        $created = $client->addOrder(32748, 'https://www.instagram.com/example/', 200);
        $status = $client->orderStatus((int) $created['order']);

        $this->assertSame(7654321, (int) $created['order']);
        $this->assertSame('In progress', $status['status']);
        $this->assertSame('80', $status['remains']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['action'] === 'add'
            && $request['service'] == 32748
            && $request['quantity'] == 200
            && $request['key'] === 'test-provider-key');
        Http::assertSent(fn ($request) => $request['action'] === 'status'
            && $request['order'] == 7654321);
    }

    public function test_client_rejects_invalid_provider_order_details(): void
    {
        config([
            'services.smm_center.api_key' => 'test-provider-key',
            'services.smm_center.base_url' => 'https://smm-center.com',
        ]);

        $this->expectException(\RuntimeException::class);

        app(SmmCenterClient::class)->addOrder(0, 'not-a-url', 0);
    }
}
