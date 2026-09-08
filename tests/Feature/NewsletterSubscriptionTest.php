<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_subscribe_from_the_storefront_footer(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com'])
            ->assertRedirect()
            ->assertSessionHas('newsletterStatus');

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com', 'status' => 'subscribed']);
    }

    public function test_subscribing_twice_does_not_create_a_duplicate_row(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

        $this->assertSame(1, NewsletterSubscriber::query()->where('email', 'reader@example.com')->count());
    }

    public function test_email_is_required_and_must_be_valid(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
    }

    public function test_subscriber_can_unsubscribe_via_token_link(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $this->get(route('newsletter.unsubscribe', $subscriber->unsubscribe_token))->assertRedirect(route('storefront.index'));

        $this->assertDatabaseHas('newsletter_subscribers', ['id' => $subscriber->id, 'status' => 'unsubscribed']);
    }

    public function test_resubscribing_after_unsubscribe_reactivates_the_same_row(): void
    {
        $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'reader@example.com']);

        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

        $this->assertSame(1, NewsletterSubscriber::query()->where('email', 'reader@example.com')->count());
        $this->assertDatabaseHas('newsletter_subscribers', ['id' => $subscriber->id, 'status' => 'subscribed']);
    }

    public function test_guest_cannot_access_admin_newsletter_list(): void
    {
        $this->get(route('newsletter.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_and_remove_subscribers(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $subscriber = NewsletterSubscriber::factory()->create();

        $this->actingAs($admin)->get(route('newsletter.index'))->assertOk()->assertSee($subscriber->email);

        $this->actingAs($admin)->delete(route('newsletter.destroy', $subscriber))->assertRedirect(route('newsletter.index'));
        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $subscriber->id]);
    }
}
