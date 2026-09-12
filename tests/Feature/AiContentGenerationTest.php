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
        $settings = WebsiteSetting::factory()->create([
            'openai_api_key' => 'sk-existing',
            'anthropic_enabled' => true,
            'anthropic_api_key' => 'sk-ant-existing',
        ]);
        $this->actingAs($admin)->get(route('settings.ai-content.edit'))->assertOk()->assertSee('AI content settings')->assertSee('API key configured');
        $this->actingAs($admin)->put(route('settings.ai-content.update'), ['openai_enabled' => '1', 'openai_api_key' => '', 'openai_model' => 'gpt-5.2', 'openai_max_output_tokens' => 2000, 'openai_default_language' => 'Bangla', 'openai_default_tone' => 'Friendly'])->assertRedirect(route('settings.ai-content.edit'));
        $settings->refresh();
        $this->assertTrue($settings->openai_enabled);
        $this->assertFalse($settings->anthropic_enabled);
        $this->assertSame('sk-existing', $settings->openai_api_key);
        $this->assertSame('Friendly', $settings->openai_default_tone);
    }

    public function test_admin_cannot_enable_ai_content_without_an_api_key(): void
    {
        $admin = User::factory()->create();
        $settings = WebsiteSetting::factory()->create([
            'openai_enabled' => false,
            'openai_api_key' => null,
        ]);

        $this->actingAs($admin)->get(route('settings.ai-content.edit'))
            ->assertOk()
            ->assertSee('API key not configured');

        $this->actingAs($admin)->put(route('settings.ai-content.update'), [
            'openai_enabled' => '1',
            'openai_api_key' => '',
            'openai_model' => 'gpt-5.2',
            'openai_max_output_tokens' => 2000,
            'openai_default_language' => 'Bangla',
            'openai_default_tone' => 'Friendly',
        ])->assertSessionHasErrors('openai_api_key');

        $this->assertFalse($settings->fresh()->openai_enabled);
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

    public function test_admin_can_configure_and_activate_claude_from_provider_table(): void
    {
        $admin = User::factory()->create();
        $settings = WebsiteSetting::factory()->create([
            'openai_enabled' => true,
            'openai_api_key' => 'sk-openai-existing',
            'openai_model' => 'gpt-5.2',
        ]);

        $this->actingAs($admin)->get(route('settings.ai-apis.index'))
            ->assertOk()
            ->assertSee('AI API providers')
            ->assertSee('OpenAI')
            ->assertSee('Claude')
            ->assertSee('Deactivate')
            ->assertSee('Activate');

        $this->actingAs($admin)->put(route('settings.ai-apis.update', 'anthropic'), [
            'api_key' => 'sk-ant-live-example',
            'model' => 'claude-sonnet-4-5',
            'max_output_tokens' => 3000,
            'default_language' => 'English',
            'default_tone' => 'Professional',
        ])->assertRedirect(route('settings.ai-apis.index'));

        $settings->refresh();
        $this->assertSame('sk-ant-live-example', $settings->anthropic_api_key);
        $this->assertNotSame('sk-ant-live-example', $settings->getRawOriginal('anthropic_api_key'));
        $this->assertSame('claude-sonnet-4-5', $settings->anthropic_model);
        $this->assertSame(3000, $settings->anthropic_max_output_tokens);

        $this->actingAs($admin)->patch(route('settings.ai-apis.toggle', 'anthropic'), ['active' => true])
            ->assertRedirect(route('settings.ai-apis.index'));

        $settings->refresh();
        $this->assertTrue($settings->anthropic_enabled);
        $this->assertFalse($settings->openai_enabled);
    }

    public function test_provider_cannot_be_activated_without_its_api_key(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create(['anthropic_api_key' => null]);

        $this->actingAs($admin)->patch(route('settings.ai-apis.toggle', 'anthropic'), ['active' => true])
            ->assertSessionHasErrors('provider');

        $this->assertDatabaseHas('website_settings', ['anthropic_enabled' => false]);
    }

    public function test_admin_can_generate_content_with_active_claude_provider(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create([
            'openai_enabled' => false,
            'anthropic_enabled' => true,
            'anthropic_api_key' => 'sk-ant-test',
            'anthropic_model' => 'claude-sonnet-4-5',
            'anthropic_max_output_tokens' => 2500,
        ]);
        $generated = ['title' => 'Cotton Shirt', 'description' => '<p>Comfortable shirt.</p>', 'excerpt' => 'Soft cotton shirt.', 'seo_title' => 'Cotton Shirt Bangladesh', 'meta_description' => 'Buy a comfortable cotton shirt.', 'tags' => ['shirt', 'cotton']];
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => json_encode($generated)]]])]);

        $this->actingAs($admin)->postJson(route('ai-content.generate'), [
            'content_type' => 'product',
            'topic' => 'Cotton Shirt',
            'language' => 'English',
            'tone' => 'Friendly',
            'length' => 'medium',
        ])->assertOk()->assertJsonPath('content.title', 'Cotton Shirt');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.anthropic.com/v1/messages'
            && $request->hasHeader('x-api-key', 'sk-ant-test')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && $request['messages'][0]['content'] === "Type: product\nTopic: Cotton Shirt\nLanguage: English\nTone: Friendly\nLength: medium\nKeywords: \nFacts/context: ");
    }

    public function test_generation_requires_enabled_configured_openai(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create(['openai_enabled' => false]);
        $this->actingAs($admin)->postJson(route('ai-content.generate'), ['content_type' => 'blog', 'topic' => 'Shopping guide', 'language' => 'English', 'tone' => 'Professional', 'length' => 'short'])->assertUnprocessable()->assertJsonValidationErrors('ai');
        Http::assertNothingSent();
        $this->actingAs($admin)->get(route('blog.create'))->assertOk()->assertSee('Configure AI APIs');
    }
}
