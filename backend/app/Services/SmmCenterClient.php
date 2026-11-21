<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmmCenterClient
{
    private function request(bool $retry = true): PendingRequest
    {
        $key = (string) config('services.smm_center.api_key');

        if ($key === '') {
            throw new RuntimeException('SMM Center API key is not configured on the server.');
        }

        $request = Http::asForm()
            ->acceptJson()
            ->timeout(20)
            ->baseUrl(rtrim((string) config('services.smm_center.base_url'), '/'))
            ->withOptions(['connect_timeout' => 10]);

        return $retry ? $request->retry(2, 300) : $request;
    }

    private function call(array $payload, bool $retry = true): array
    {
        $response = $this->request($retry)->post('/api/v2', [
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
        ], false);

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

    /**
     * Fetch up to 100 order statuses in one supplier request.
     *
     * The SMM Center API returns a map keyed by provider order ID for batch
     * requests. Single-order responses are normalized to the same shape.
     */
    public function orderStatuses(array $providerOrderIds): array
    {
        $ids = array_values(array_unique(array_map('strval', $providerOrderIds)));

        if ($ids === [] || count($ids) > 100) {
            throw new RuntimeException('A batch must contain between 1 and 100 provider order IDs.');
        }

        foreach ($ids as $id) {
            if (!ctype_digit($id) || (int) $id < 1) {
                throw new RuntimeException('Invalid provider order ID in status batch.');
            }
        }

        if (count($ids) === 1) {
            return [$ids[0] => $this->orderStatus((int) $ids[0])];
        }

        $data = $this->call([
            'action' => 'status',
            'orders' => implode(',', $ids),
        ]);

        $statuses = [];
        foreach ($data as $id => $status) {
            if (is_array($status) && ctype_digit((string) $id) && isset($status['status'])) {
                $statuses[(string) $id] = $status;
            }
        }

        return $statuses;
    }
}
