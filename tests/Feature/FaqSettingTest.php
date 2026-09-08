<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_faq_settings(): void
    {
        $this->get(route('settings.faq.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_faq_settings_with_default_questions(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('settings.faq.edit'))
            ->assertOk()
            ->assertSee('Homepage FAQs')
            ->assertSee('Add FAQ')
            ->assertSee('Do you deliver all over Bangladesh?')
            ->assertSee('FAQ Settings');
    }

    public function test_admin_can_save_ordered_faqs_that_render_on_the_storefront_and_in_schema(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.faq.update'), [
            'faqs' => [
                ['question' => 'First custom question?', 'answer' => 'First custom answer.'],
                ['question' => 'Second custom question?', 'answer' => 'Second custom answer.'],
            ],
        ])->assertRedirect(route('settings.faq.edit'));

        $this->assertSame([
            ['question' => 'First custom question?', 'answer' => 'First custom answer.'],
            ['question' => 'Second custom question?', 'answer' => 'Second custom answer.'],
        ], WebsiteSetting::query()->firstOrFail()->homepage_faqs);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSeeInOrder(['First custom question?', 'Second custom question?'])
            ->assertSee('First custom answer.')
            ->assertSee('FAQPage')
            ->assertDontSee('Do you deliver all over Bangladesh?');
    }

    public function test_admin_can_save_an_empty_faq_list_and_the_storefront_shows_an_empty_state(): void
    {
        $settings = WebsiteSetting::factory()->create(['homepage_faqs' => [
            ['question' => 'Old question?', 'answer' => 'Old answer.'],
        ]]);
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.faq.update'), [])
            ->assertRedirect(route('settings.faq.edit'));

        $this->assertSame([], $settings->fresh()->homepage_faqs);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('No FAQs available.')
            ->assertDontSee('Old question?')
            ->assertDontSee('FAQPage');
    }

    public function test_each_faq_requires_a_question_and_answer(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.faq.update'), [
            'faqs' => [['question' => '', 'answer' => '']],
        ])->assertSessionHasErrors(['faqs.0.question', 'faqs.0.answer']);
    }
}
