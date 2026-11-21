<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\Order;
use App\\Models\\Service;
use App\\Models\\User;
use App\\Services\\SmmCenterClient;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Log;
use Illuminate\\View\\View;
use RuntimeException;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::query()->count(),
            'adminUsers' => User::query()->where('role', 'admin')->count(),
            'customerUsers' => User::query()->where('role', 'customer')->count(),
            'totalServices' => Service::query()->count(),
            'activeServices' => Service::query()->where('is_active', true)->count(),
            'totalOrders' => Order::query()->count(),
            'pendingOrders' => Order::query()->where('status', 'pending')->count(),
        ]);
    }

    public function refreshSupplierBalance(Request $request, SmmCenterClient $client): RedirectResponse
    {
        try {
            $balance = $client->balance();
            $amount = $balance['balance'] ?? null;
            $currency = strtoupper((string) ($balance['currency'] ?? ''));

            if (!is_numeric($amount) || !is_finite((float) $amount)
                || !preg_match('/^[A-Z]{3}$/', $currency)) {
                throw new RuntimeException('SMM Center returned an unexpected balance response.');
            }

            return redirect()->route('admin.dashboard')->with('supplier_balance', [
                'amount' => (float) $amount,
                'currency' => $currency,
                'checked_at' => now()->format('Y-m-d H:i:s'),
                'is_low' => $currency === 'USD' && (float) $amount < 5,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('admin.dashboard')
                ->withErrors(['supplier_balance' => 'Could not retrieve the SMM Center balance. Check the supplier connection and try again.']);
        }
    }
}
