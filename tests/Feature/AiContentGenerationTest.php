<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiContentGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_ai_content_settings_without_replacing_existing_key(): void
    {
        $admin = User::factory()->create();
        $settings = WebsiteSetting::factory()->create(['openai_api_key' => 'sk-existing']);
        $this->actingAs($admin)->get(route('settings.ai-content.edit'))->assertOk()->assertSee('AI content settings');
        $this->actingAs($admin)->put(route('settings.ai-content.update'), ['openai_enabled' => '1', 'openai_api_key' => '', 'openai_model' => 'gpt-5.2', 'openai_max_output_tokens' => 2000, 'openai_default_language' => 'Bangla', 'openai_default_tone' => 'Friendly'])->assertRedirect(route('settings.ai-content.edit'));
        $settings->refresh();
        $this->assertTrue($settings->openai_enabled);
        $this->assertSame('sk-existing', $settings->openai_api_key);
        $this->assertSame('Friendly', $settings->openai_default_tone);
    }

    public function test_admin_can_generate_structured_product_content(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create(['openai_enabled' => true, 'openai_api_key' => 'sk-test', 'openai_model' => 'gpt-5.2', 'openai_max_output_tokens' => 2500]);
        $this->actingAs($admin)->get(route('settings.ai-content.edit'))->assertOk();
        $generated = ['title' => 'Cotton Shirt', 'description' => '<p>Comfortable shirt.</p>', 'excerpt' => 'Soft cotton shirt.', 'seo_title' => 'Cotton Shirt Bangladesh', 'meta_description' => 'Buy a comfortable cotton shirt.', 'tags' => ['shirt', 'cotton']];
        Http::fake(['api.openai.com/*' => Http::response(['output' => [['content' => [['type' => 'output_text', 'text' => json_encode($generated)]]]]])]);
        $this->actingAs($admin)->postJson(route('ai-content.generate'), ['content_type' => 'product', 'topic' => 'Cotton Shirt', 'language' => 'English', 'tone' => 'Friendly', 'length' => 'medium'])->assertOk()->assertJsonPath('content.title', 'Cotton Shirt');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses' && $request['store'] === false);
    }

    public function test_generation_requires_enabled_configured_openai(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create(['openai_enabled' => false]);
        $this->actingAs($admin)->postJson(route('ai-content.generate'), ['content_type' => 'blog', 'topic' => 'Shopping guide', 'language' => 'English', 'tone' => 'Professional', 'length' => 'short'])->assertUnprocessable()->assertJsonValidationErrors('openai');
        Http::assertNothingSent();
        $this->actingAs($admin)->get(route('blog.create'))->assertOk()->assertSee('Configure AI Content');
    }
}
