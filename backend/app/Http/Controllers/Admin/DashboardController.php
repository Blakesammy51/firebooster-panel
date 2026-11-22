<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Models\SmmCenterBalanceCheck;
use App\Models\User;
use App\Services\SmmCenterBalanceMonitor;
use App\Services\SmmCenterClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
            'latestBalanceCheck' => SmmCenterBalanceCheck::query()->latest('checked_at')->first(),
            'latestSuccessfulBalance' => SmmCenterBalanceCheck::query()
                ->where('status', 'success')->latest('checked_at')->first(),
        ]);
    }

    public function refreshSupplierBalance(
        SmmCenterClient $client,
        SmmCenterBalanceMonitor $monitor
    ): RedirectResponse {
        $check = $monitor->check($client);

        if ($check->status !== 'success') {
            return redirect()->route('admin.dashboard')->withErrors([
                'supplier_balance' => 'Could not retrieve the SMM Center balance. Check the supplier connection and try again.',
            ]);
        }

        return redirect()->route('admin.dashboard')->with('status', 'Supplier balance refreshed.');
    }
}
