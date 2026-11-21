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

        // Only customer-ready services should influence catalogue navigation or results.
        $available = Service::query()
            ->where('is_active', true)
            ->where('price_per_1000', '>', 0);

        $platforms = (clone $available)
            ->whereNotNull('platform')
            ->where('platform', '!=', '')
            ->select('platform')
            ->distinct()
            ->orderBy('platform')
            ->pluck('platform');

        $selectedPlatform = trim((string) $request->query('platform', ''));
        $selectedCategory = trim((string) $request->query('category', ''));
        $search = trim((string) $request->query('q', ''));

        $categoriesQuery = (clone $available)->whereNotNull('category')->where('category', '!=', '');
        if ($selectedPlatform !== '') {
            $categoriesQuery->whereRaw('LOWER(platform) = ?', [mb_strtolower($selectedPlatform)]);
        }
        $categories = $categoriesQuery->select('category')->distinct()->orderBy('category')->pluck('category');

        $servicesQuery = (clone $available);
        if ($selectedPlatform !== '') {
            $servicesQuery->whereRaw('LOWER(platform) = ?', [mb_strtolower($selectedPlatform)]);
        }
        if ($selectedCategory !== '') {
            $servicesQuery->whereRaw('LOWER(category) = ?', [mb_strtolower($selectedCategory)]);
        }
        if ($search !== '') {
            $servicesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('provider_service_id', 'like', '%' . $search . '%');
            });
        }

        return view('services.index', [
            'services' => $servicesQuery->orderBy('platform')->orderBy('name')->paginate(15)->withQueryString(),
            'wallet' => $wallet,
            'platforms' => $platforms,
            'categories' => $categories,
            'selectedPlatform' => $selectedPlatform,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
        ]);
    }
}
