<?php

namespace App\Services;

use App\Models\SmmCenterBalanceCheck;
use RuntimeException;
use Throwable;

class SmmCenterBalanceMonitor
{
    public function check(SmmCenterClient $client): SmmCenterBalanceCheck
    {
        try {
            $balance = $client->balance();
            $amount = $balance['balance'] ?? null;
            $currency = strtoupper((string) ($balance['currency'] ?? ''));

            if (!is_numeric($amount) || !is_finite((float) $amount)
                || !preg_match('/^[A-Z]{3}$/', $currency)) {
                throw new RuntimeException('Unexpected supplier balance response.');
            }

            return SmmCenterBalanceCheck::query()->create([
                'amount' => (float) $amount,
                'currency' => $currency,
                'status' => 'success',
                'checked_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return SmmCenterBalanceCheck::query()->create([
                'status' => 'failed',
                'error_message' => 'Could not retrieve the supplier balance. Check the supplier connection.',
                'checked_at' => now(),
            ]);
        }
    }
}
