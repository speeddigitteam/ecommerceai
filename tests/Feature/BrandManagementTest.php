<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_both_brand_descriptions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('brands.store'), [
            'name' => 'Acme',
            'slug' => 'acme',
            'description' => '<p>Top brand description.</p>',
            'extra_description' => '<p>Bottom brand description.</p>',
        ])->assertRedirect(route('brands.index'));

        $brand = Brand::query()->firstOrFail();
        $this->assertSame('<p>Top brand description.</p>', $brand->description);
        $this->assertSame('<p>Bottom brand description.</p>', $brand->extra_description);

        $this->actingAs($admin)->put(route('brands.update', $brand), [
            'name' => 'Acme',
            'slug' => 'acme',
            'description' => '<p>Updated top description.</p>',
            'extra_description' => '<p>Updated bottom description.</p>',
        ])->assertRedirect(route('brands.index'));

        $brand->refresh();
        $this->assertSame('<p>Updated top description.</p>', $brand->description);
        $this->assertSame('<p>Updated bottom description.</p>', $brand->extra_description);
    }
}
