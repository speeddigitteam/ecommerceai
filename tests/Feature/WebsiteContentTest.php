<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_website_content(): void
    {
        $this->get(route('settings.content.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_edit_and_update_safe_rich_content(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('settings.content.edit'))->assertOk()->assertSee('Homepage content');
        $this->actingAs($admin)->put(route('settings.content.update'), [
            'footer_content' => '<h2>About our store</h2><p onclick="alert(1)">Quality products.</p><script>alert(1)</script>',
        ])->assertRedirect(route('settings.content.edit'));

        $content = WebsiteSetting::query()->firstOrFail()->footer_content;
        $this->assertStringContainsString('<h2>About our store</h2>', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('<script', $content);
    }

    public function test_saved_content_is_rendered_above_the_storefront_footer(): void
    {
        WebsiteSetting::factory()->create(['footer_content' => '<h2>Why shop with us</h2><p>Fast delivery.</p>']);

        $response = $this->get(route('storefront.index'))->assertOk();
        $response->assertSee('<h2>Why shop with us</h2>', false);
        $this->assertLessThan(strpos($response->getContent(), '<footer>'), strpos($response->getContent(), 'Why shop with us'));
        $this->get(route('storefront.shop'))->assertOk()->assertDontSee('Why shop with us');
    }
}
