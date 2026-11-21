<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance_kobo' => 0, 'currency' => 'NGN']
        );

        return view('services.index', [
            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('platform')
                ->orderBy('name')
                ->paginate(15),
            'wallet' => $wallet,
        ]);
    }
}
