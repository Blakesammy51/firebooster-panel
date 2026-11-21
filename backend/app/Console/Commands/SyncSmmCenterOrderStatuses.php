<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\SmmCenterClient;
use Illuminate\Console\Command;
use Throwable;

class SyncSmmCenterOrderStatuses extends Command
{
    protected $signature = 'smm-center:sync-order-statuses';

    protected $description = 'Refresh outstanding SMM Center order statuses in batches';

    public function handle(SmmCenterClient $client): int
    {
        $updated = 0;
        $skipped = 0;
        $failedBatches = 0;

        Order::query()
            ->whereNotNull('provider_order_id')
            ->whereIn('status', ['pending', 'processing'])
            ->whereHas('service', fn ($query) => $query->where('provider', 'smm_center'))
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($client, &$updated, &$skipped, &$failedBatches): void {
                $ids = $orders->pluck('provider_order_id')->map(fn ($id) => (string) $id)->all();

                try {
                    $statuses = $client->orderStatuses($ids);
                } catch (Throwable $exception) {
                    $failedBatches++;
                    report($exception);
                    $this->warn('A supplier status batch failed; its existing order statuses were left unchanged.');

                    return;
                }

                foreach ($orders as $order) {
                    $providerId = (string) $order->provider_order_id;
                    $data = $statuses[$providerId] ?? null;

                    if (!is_array($data) || !isset($data['status']) || !is_string($data['status'])) {
                        $skipped++;
                        continue;
                    }

                    $providerStatus = trim($data['status']);
                    if ($providerStatus === '') {
                        $skipped++;
                        continue;
                    }

                    $normalized = strtolower($providerStatus);
                    $localStatus = match ($normalized) {
                        'completed' => 'completed',
                        'canceled', 'cancelled' => 'cancelled',
                        'partial' => 'partial',
                        'failed' => 'failed',
                        default => 'processing',
                    };

                    $changes = ['provider_status' => $providerStatus, 'status' => $localStatus];

                    if (isset($data['charge']) && is_numeric($data['charge'])) {
                        $changes['provider_charge'] = $data['charge'];
                    }
                    if (isset($data['currency']) && is_string($data['currency'])
                        && preg_match('/^[A-Za-z]{3}$/', $data['currency'])) {
                        $changes['provider_currency'] = strtoupper($data['currency']);
                    }
                    if (isset($data['start_count']) && is_numeric($data['start_count'])) {
                        $changes['provider_start_count'] = max(0, (int) $data['start_count']);
                    }
                    if (isset($data['remains']) && is_numeric($data['remains'])) {
                        $changes['provider_remains'] = max(0, (int) $data['remains']);
                    }

                    // Do not overwrite an admin refund/cancellation or another
                    // terminal transition that happened while the supplier call ran.
                    $affected = Order::query()
                        ->whereKey($order->id)
                        ->where('provider_order_id', $providerId)
                        ->whereIn('status', ['pending', 'processing'])
                        ->update($changes);

                    $affected === 1 ? $updated++ : $skipped++;
                }
            });

        $this->info("Supplier status sync finished: {$updated} updated, {$skipped} skipped, {$failedBatches} failed batches.");

        return self::SUCCESS;
    }
}
