<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmmCenterClient
{
    private function request(): PendingRequest
    {
        $key = (string) config('services.smm_center.api_key');

        if ($key === '') {
            throw new RuntimeException('SMM Center API key is not configured on the server.');
        }

        return Http::asForm()
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 300)
            ->baseUrl(rtrim((string) config('services.smm_center.base_url'), '/'))
            ->withOptions(['connect_timeout' => 10]);
    }

    private function call(array $payload): array
    {
        $response = $this->request()->post('/api/v2', [
            'key' => config('services.smm_center.api_key'),
            ...$payload,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('SMM Center returned HTTP '.$response->status().'.');
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new RuntimeException('SMM Center returned an invalid JSON response.');
        }

        if (isset($data['error'])) {
            throw new RuntimeException('SMM Center API error: '.(string) $data['error']);
        }

        return $data;
    }

    public function services(): array
    {
        $data = $this->call(['action' => 'services']);

        if (!array_is_list($data)) {
            throw new RuntimeException('SMM Center returned an unexpected service-list format.');
        }

        return $data;
    }

    public function balance(): array
    {
        return $this->call(['action' => 'balance']);
    }

    public function addOrder(int $serviceId, string $targetUrl, int $quantity): array
    {
        if ($serviceId < 1 || $quantity < 1 || filter_var($targetUrl, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Invalid supplier order details.');
        }

        $data = $this->call([
            'action' => 'add',
            'service' => $serviceId,
            'link' => $targetUrl,
            'quantity' => $quantity,
        ]);

        if (!isset($data['order']) || !is_numeric($data['order']) || (int) $data['order'] < 1) {
            throw new RuntimeException('SMM Center did not return a valid provider order ID.');
        }

        return $data;
    }

    public function orderStatus(int $providerOrderId): array
    {
        if ($providerOrderId < 1) {
            throw new RuntimeException('Invalid provider order ID.');
        }

        $data = $this->call([
            'action' => 'status',
            'order' => $providerOrderId,
        ]);

        if (!isset($data['status'])) {
            throw new RuntimeException('SMM Center returned an unexpected order-status format.');
        }

        return $data;
    }
}
