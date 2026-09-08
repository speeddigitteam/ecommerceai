<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class NewsletterSubscriptionController extends Controller
{
    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        $email = $request->string('email')->trim()->lower()->toString();
        $subscriber = NewsletterSubscriber::query()->firstWhere('email', $email);

        if ($subscriber?->status === 'subscribed') {
            return back()->with('newsletterStatus', 'You are already subscribed to our newsletter.');
        }

        if ($subscriber) {
            $subscriber->update(['status' => 'subscribed', 'subscribed_at' => now(), 'unsubscribed_at' => null]);
        } else {
            NewsletterSubscriber::query()->create([
                'email' => $email,
                'status' => 'subscribed',
                'unsubscribe_token' => Str::random(48),
                'subscribed_at' => now(),
            ]);
        }

        return back()->with('newsletterStatus', 'Thanks for subscribing! Watch your inbox for updates.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::query()->where('unsubscribe_token', $token)->firstOrFail();
        $subscriber->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);

        return redirect()->route('storefront.index')->with('newsletterStatus', 'You have been unsubscribed from our newsletter.');
    }
}
