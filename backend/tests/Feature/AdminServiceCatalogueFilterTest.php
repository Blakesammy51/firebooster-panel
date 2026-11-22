<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceCatalogueFilterTest extends TestCase
{
    use RefreshDatabase;

    private function createService(string $name, string $platform, string $category, bool $active): Service
    {
        return Service::query()->create([
            'name' => $name,
            'platform' => $platform,
            'category' => $category,
            'description' => $name,
            'price_per_1000' => '100.00',
            'minimum_quantity' => 10,
            'maximum_quantity' => 10000,
            'is_active' => $active,
        ]);
    }

    public function test_active_and_inactive_status_filters_return_only_matching_services(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $active = $this->createService('Active Instagram Followers', 'Instagram', 'Followers', true);
        $inactive = $this->createService('Inactive Instagram Likes', 'Instagram', 'Likes', false);

        $this->actingAs($admin)
            ->get(route('admin.services.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($inactive->name)
            ->assertDontSee($active->name)
            ->assertSee('Showing 1 inactive services');

        $this->get(route('admin.services.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name)
            ->assertSee('Showing 1 active services');
    }

    public function test_combined_status_platform_and_category_filters_are_applied_together(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $match = $this->createService('Instagram Followers Inactive', 'Instagram', 'Followers', false);
        $this->createService('Instagram Followers Active', 'Instagram', 'Followers', true);
        $this->createService('TikTok Followers Inactive', 'TikTok', 'Followers', false);
        $this->createService('Instagram Likes Inactive', 'Instagram', 'Likes', false);

        $this->actingAs($admin)
            ->get(route('admin.services.index', [
                'status' => 'inactive',
                'platform' => 'Instagram',
                'category' => 'Followers',
            ]))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee('Instagram Followers Active')
            ->assertDontSee('TikTok Followers Inactive')
            ->assertDontSee('Instagram Likes Inactive')
            ->assertSee('Showing 1 inactive services');
    }

    public function test_status_cards_preserve_search_platform_and_category_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createService('Instagram Followers', 'Instagram', 'Followers', false);

        $this->actingAs($admin)
            ->get(route('admin.services.index', [
                'status' => 'inactive',
                'search' => 'Followers',
                'platform' => 'Instagram',
                'category' => 'Followers',
            ]))
            ->assertOk()
            ->assertSee('status=active', false)
            ->assertSee('status=all', false)
            ->assertSee('search=Followers', false)
            ->assertSee('platform=Instagram', false)
            ->assertSee('category=Followers', false);
    }
}
