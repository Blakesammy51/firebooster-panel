<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Convert supplier category labels into customer-facing social platforms.
     * Unknown platforms retain their supplied label so new networks remain discoverable.
     */
    private function normalizePlatform(string $value): string
    {
        $label = trim($value);
        $text = mb_strtolower($label);

        $known = [
            'Instagram' => '/instagram|\big\b/',
            'Facebook' => '/facebook|\bfb\b/',
            'Telegram' => '/telegram|\btg\b/',
            'YouTube' => '/youtube|\byoutube\b|\byt\b/',
            'TikTok' => '/tiktok|tik tok/',
            'X (Twitter)' => '/twitter|\bx\s*(?:\(|$)/',
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

        return $label;
    }

    public function index(Request $request): View
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance_kobo' => 0, 'currency' => 'NGN']
        );

        // Supplier categories were historically stored in the platform column.
        // Build a display-label map so existing imports are corrected without a DB rewrite.
        $available = Service::query()
            ->where('is_active', true)
            ->where('price_per_1000', '>', 0);

        $rawPlatforms = (clone $available)
            ->whereNotNull('platform')
            ->where('platform', '!=', '')
            ->select('platform')
            ->distinct()
            ->orderBy('platform')
            ->pluck('platform');

        $platformMap = $rawPlatforms->mapWithKeys(fn ($raw) => [
            $raw => $this->normalizePlatform((string) $raw),
        ]);
        $platforms = $platformMap->values()->unique()->sort()->values();

        $selectedPlatform = trim((string) $request->query('platform', ''));
        $selectedCategory = trim((string) $request->query('category', ''));
        $search = trim((string) $request->query('q', ''));

        $matchingRawPlatforms = $selectedPlatform === ''
            ? []
            : $platformMap->filter(fn ($label) => mb_strtolower($label) === mb_strtolower($selectedPlatform))->keys()->all();

        $categoriesQuery = (clone $available)->whereNotNull('category')->where('category', '!=', '');
        if ($selectedPlatform !== '') {
            $categoriesQuery->whereIn('platform', $matchingRawPlatforms);
        }
        $categories = $categoriesQuery->select('category')->distinct()->orderBy('category')->pluck('category');

        $servicesQuery = (clone $available);
        if ($selectedPlatform !== '') {
            $servicesQuery->whereIn('platform', $matchingRawPlatforms);
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

        $services = $servicesQuery->orderBy('platform')->orderBy('name')->paginate(15)->withQueryString();
        $services->getCollection()->transform(function (Service $service) {
            $name = html_entity_decode((string) $service->name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $speed = null;
            $startTime = null;

            $name = preg_replace_callback('/\(\s*[^)]*?Speed\s*:\s*([^)]*?)\s*\)/iu', function ($matches) use (&$speed) {
                $speed = trim($matches[1]);
                return ' ';
            }, $name);
            $name = preg_replace_callback('/\(\s*[^)]*?Start\s*Time\s*:\s*([^)]*?)\s*\)/iu', function ($matches) use (&$startTime) {
                $startTime = trim($matches[1]);
                return ' ';
            }, $name);
            $name = preg_replace('/\(\s*[^)]*?Refill\s*:\s*[^)]*\)/iu', ' ', $name);
            $name = preg_replace('/\(\s*[^)]*?No\s+Refill\s*\)/iu', ' ', $name);
            $name = preg_replace('/\s{2,}/u', ' ', trim($name));

            $service->setAttribute('display_name', $name);
            $service->setAttribute('display_speed', $speed);
            $service->setAttribute('display_start_time', $startTime);
            $service->setAttribute('display_description', html_entity_decode((string) $service->description, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return $service;
        });

        return view('services.index', [
            'services' => $services,
            'wallet' => $wallet,
            'platforms' => $platforms,
            'platformMap' => $platformMap,
            'categories' => $categories,
            'selectedPlatform' => $selectedPlatform,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
        ]);
    }
}
