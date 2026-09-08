<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(): View
    {
        return view('newsletter.index', [
            'subscribers' => NewsletterSubscriber::query()->latest()->paginate(20),
            'subscribedCount' => NewsletterSubscriber::query()->where('status', 'subscribed')->count(),
            'unsubscribedCount' => NewsletterSubscriber::query()->where('status', 'unsubscribed')->count(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $subscribers = NewsletterSubscriber::query()->latest()->get();

        return response()->streamDownload(function () use ($subscribers): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Email', 'Status', 'Subscribed at']);
            foreach ($subscribers as $subscriber) {
                fputcsv($output, [$subscriber->email, $subscriber->status, $subscriber->subscribed_at?->toDateTimeString()]);
            }
            fclose($output);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return to_route('newsletter.index')->with('status', 'Subscriber removed successfully.');
    }
}
