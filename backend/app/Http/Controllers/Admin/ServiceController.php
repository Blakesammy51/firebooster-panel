<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\SmmCenterClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ServiceController extends Controller
{
    private function normalizeSocialPlatform(string $name, string $category): string
    {
        $text = mb_strtolower($name.' '.$category);
        $known = [
            'Instagram' => '/instagram|\\big\\b/',
            'Facebook' => '/facebook|\\bfb\\b/',
            'Telegram' => '/telegram|\\btg\\b/',
            'YouTube' => '/youtube|\\byt\\b/',
            'TikTok' => '/tiktok|tik tok/',
            'X (Twitter)' => '/twitter|\\bx\\s*(?:\\(|$)/',
            'Threads' => '/threads/',
            'WhatsApp' => '/whatsapp/',
            'LinkedIn' => '/linkedin/',
            'Snapchat' => '/snapchat/',
            'Pinterest' => '/pinterest/',
            'Reddit' => '/reddit/',
            'Discord' => '/discord/',
            'Twitch' => '/twitch/',
            'Spotify' => '/spotify/',
            'SoundCloud' => '/soundcloud/',
        ];

        foreach ($known as $platform => $pattern) {
            if (preg_match($pattern, $text)) {
                return $platform;
            }
        }

        return mb_substr(trim($category), 0, 40);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'platform' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);
        $rawSearch = trim($filters['search'] ?? '');
        $isExactIdSearch = str_starts_with($rawSearch, '#')
            && ctype_digit(substr($rawSearch, 1));
        $search = $isExactIdSearch ? substr($rawSearch, 1) : $rawSearch;
        $status = $filters['status'] ?? 'all';

        $query = Service::query()->latest();
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }
        if (!empty($filters['category'])) {
            $query->whereRaw('LOWER(category) = ?', [mb_strtolower(trim($filters['category']))]);
        }
        if ($search !== '') {
            $query->where(function ($builder) use ($search, $isExactIdSearch) {
                if ($isExactIdSearch) {
                    $builder->where('id', (int) $search)
                        ->orWhere('provider_service_id', $search);

                    return;
                }

                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('platform', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%')
                    ->orWhere('provider_service_id', 'like', '%'.$search.'%');
            });
        }

        return view('admin.services.index', [
            'services' => $query->paginate(20)->withQueryString(),
            'providerServiceCount' => Service::query()->where('provider', 'smm_center')->count(),
            'activeServiceCount' => Service::query()->where('is_active', true)->count(),
            'inactiveServiceCount' => Service::query()->where('is_active', false)->count(),
            'search' => $rawSearch,
            'status' => $status,
            'platforms' => Service::query()->whereNotNull('platform')->where('platform', '!=', '')->distinct()->orderBy('platform')->pluck('platform'),
            'categories' => Service::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category'),
            'selectedPlatform' => $filters['platform'] ?? '',
            'selectedCategory' => $filters['category'] ?? '',
        ]);
    }

    public function syncSmmCenter(SmmCenterClient $client): RedirectResponse
    {
        try {
            $balance = $client->balance();
            $currency = strtoupper((string) ($balance['currency'] ?? ''));

            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                return back()->withErrors(['provider' => 'SMM Center did not return a valid account currency.']);
            }

            $remoteServices = $client->services();
            $imported = 0;

            DB::transaction(function () use ($remoteServices, $currency, &$imported) {
                foreach ($remoteServices as $remote) {
                    if (!is_array($remote)
                        || !isset($remote['service'], $remote['name'], $remote['category'], $remote['rate'], $remote['min'], $remote['max'])
                        || !is_numeric($remote['service'])
                        || !is_numeric($remote['rate'])
                        || !is_numeric($remote['min'])
                        || !is_numeric($remote['max'])) {
                        continue;
                    }

                    $providerId = (string) $remote['service'];
                    $name = mb_substr(trim((string) $remote['name']), 0, 255);
                    $category = mb_substr(trim((string) $remote['category']), 0, 255);
                    $minimum = (int) $remote['min'];
                    $maximum = (int) $remote['max'];
                    $rate = (float) $remote['rate'];

                    if ($providerId === '' || $name === '' || $category === ''
                        || $minimum < 1 || $maximum < $minimum || $rate < 0) {
                        continue;
                    }

                    $service = Service::query()->firstOrNew([
                        'provider' => 'smm_center',
                        'provider_service_id' => $providerId,
                    ]);

                    if (!$service->exists) {
                        $service->price_per_1000 = '0.00';
                    }

                    $service->fill([
                        'name' => $name,
                        'platform' => $this->normalizeSocialPlatform($name, $category),
                        'category' => $category,
                        'provider_type' => mb_substr((string) ($remote['type'] ?? 'Default'), 0, 120),
                        'provider_rate' => number_format($rate, 6, '.', ''),
                        'provider_currency' => $currency,
                        'provider_refill' => filter_var($remote['refill'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'provider_cancel' => filter_var($remote['cancel'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'description' => 'Imported from SMM Center. Supplier category: '.$category,
                        'minimum_quantity' => $minimum,
                        'maximum_quantity' => $maximum,
                    ])->save();

                    $imported++;
                }
            });

            return back()->with('status', $imported.' SMM Center services synchronized. Imported services remain inactive until you set their NGN selling prices and activate them.');
        } catch (Throwable $exception) {
            return back()->withErrors([
                'provider' => 'SMM Center sync failed: '.$exception->getMessage(),
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'platform' => ['required', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'price_per_1000' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'minimum_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'maximum_quantity' => ['required', 'integer', 'gte:minimum_quantity', 'max:1000000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        Service::query()->create($data);

        return back()->with('status', 'Service created.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'platform' => ['required', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'price_per_1000' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'minimum_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'maximum_quantity' => ['required', 'integer', 'gte:minimum_quantity', 'max:1000000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($service->provider !== null && $data['is_active'] && (float) $data['price_per_1000'] <= 0) {
            return back()->withErrors([
                'price_per_1000' => 'Set a selling price greater than zero before activating a supplier service.',
            ])->withInput();
        }

        $service->update($data);

        return back()->with('status', 'Service updated.');
    }
}
