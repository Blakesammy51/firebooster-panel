<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway
{
    public function initialize(array $payload): array
    {
        $response = Http::acceptJson()
            ->withToken((string) config('services.paystack.secret_key'))
            ->timeout(20)
            ->post(rtrim(config('services.paystack.base_url'), '/') . '/transaction/initialize', $payload);

        if (!$response->successful() || !$response->json('status')) {
            throw new RuntimeException('Paystack could not initialize this deposit. Please try again.');
        }

        return (array) $response->json('data');
    }

    public function verify(string $reference): array
    {
        $response = Http::acceptJson()
            ->withToken((string) config('services.paystack.secret_key'))
            ->timeout(20)
            ->get(rtrim(config('services.paystack.base_url'), '/') . '/transaction/verify/' . rawurlencode($reference));

        if (!$response->successful() || !$response->json('status')) {
            throw new RuntimeException('We could not verify this payment yet. Please check again shortly.');
        }

        return (array) $response->json('data');
    }

    public function validWebhookSignature(string $rawBody, ?string $paystackSignature, ?string $legacySignature): bool
    {
        $webhookSecret = (string) config('services.paystack.webhook_secret');
        if ($paystackSignature && $webhookSecret !== '') {
            $timestamp = null;
            $signatures = [];

            foreach (explode(',', $paystackSignature) as $part) {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
                if ($key === 't') {
                    $timestamp = ctype_digit((string) $value) ? (int) $value : null;
                } elseif ($key === 'v1' && is_string($value) && str_contains($value, ':')) {
                    $signatures[] = substr($value, strpos($value, ':') + 1);
                }
            }

            if ($timestamp === null || abs(time() - $timestamp) > 300) {
                return false;
            }

            $expected = hash_hmac('sha512', $timestamp . '.' . $rawBody, $webhookSecret);
            foreach ($signatures as $signature) {
                if (preg_match('/^[a-f0-9]{128}$/', $signature) && hash_equals($expected, $signature)) {
                    return true;
                }
            }
            return false;
        }

        // Backward compatibility for integrations still using Paystack's legacy header.
        $secretKey = (string) config('services.paystack.secret_key');
        if (!$paystackSignature && $legacySignature && $secretKey !== '') {
            $expected = hash_hmac('sha512', $rawBody, $secretKey);
            return preg_match('/^[a-f0-9]{128}$/', $legacySignature) === 1
                && hash_equals($expected, $legacySignature);
        }

        return false;
    }
}
