<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();

        $orders = Order::query()
            ->with('service')
            ->whereHas('service', fn ($query) => $query->where('provider', 'smm_center'))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderByDesc('created_at')
            ->get();

        $paymentRefs = $orders->map(fn (Order $order) => 'ORDER-'.$order->id)->all();
        $refundRefs = $orders->map(fn (Order $order) => 'ORDER-'.$order->id.'-REFUND')->all();

        $payments = WalletTransaction::query()
            ->whereIn('reference', $paymentRefs)
            ->where('type', 'order_payment')
            ->where('status', 'completed')
            ->where('currency', 'NGN')
            ->get()
            ->keyBy('reference');

        $refunds = WalletTransaction::query()
            ->whereIn('reference', $refundRefs)
            ->where('type', 'order_refund')
            ->where('status', 'completed')
            ->where('currency', 'NGN')
            ->get()
            ->keyBy('reference');

        $rows = $orders->map(function (Order $order) use ($payments, $refunds) {
            $payment = $payments->get('ORDER-'.$order->id);
            $refund = $refunds->get('ORDER-'.$order->id.'-REFUND');
            $sale = $payment ? $payment->amount_kobo / 100 : 0;
            $refundAmount = $refund ? $refund->amount_kobo / 100 : 0;
            $currency = strtoupper((string) $order->provider_currency);
            $charge = $order->provider_charge === null ? null : (float) $order->provider_charge;
            $costNgn = null;

            if ($charge !== null && $currency === 'NGN') {
                $costNgn = $charge;
            } elseif ($charge !== null && $currency === 'USD' && $order->provider_fx_rate !== null) {
                $costNgn = $charge * (float) $order->provider_fx_rate;
            }

            return [
                'order' => $order,
                'paid' => $payment !== null,
                'sale' => $sale,
                'refund' => $refundAmount,
                'net_sale' => $sale - $refundAmount,
                'currency' => $currency,
                'charge' => $charge,
                'cost_ngn' => $costNgn,
                'margin' => $payment !== null && $costNgn !== null ? $sale - $refundAmount - $costNgn : null,
            ];
        });

        $chargedByCurrency = $rows->filter(fn ($row) => $row['charge'] !== null)
            ->groupBy(fn ($row) => $row['currency'] ?: 'Unknown')
            ->map(fn ($group) => $group->sum('charge'));

        $paidRows = $rows->where('paid', true);
        $marginRows = $paidRows->filter(fn ($row) => $row['margin'] !== null);

        return view('admin.reports.supplier', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'totalPaid' => $paidRows->sum('sale'),
            'totalRefunded' => $paidRows->sum('refund'),
            'netSales' => $paidRows->sum('net_sale'),
            'totalMargin' => $marginRows->sum('margin'),
            'marginOrders' => $marginRows->count(),
            'unpricedOrders' => $rows->filter(fn ($row) => $row['charge'] === null)->count(),
            'unconvertedUsdOrders' => $rows->filter(fn ($row) => $row['charge'] !== null && $row['currency'] === 'USD' && $row['cost_ngn'] === null)->count(),
            'chargedByCurrency' => $chargedByCurrency,
            'orderCount' => $rows->count(),
            'paidOrderCount' => $paidRows->count(),
        ]);
    }
}
