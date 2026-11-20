<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance_kobo' => 0, 'currency' => 'NGN']
        );

        return view('wallet.index', [
            'wallet' => $wallet,
            'transactions' => $wallet->transactions()->latest()->paginate(15),
        ]);
    }
}