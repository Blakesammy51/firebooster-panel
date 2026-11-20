<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
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
        ]);
    }
}
