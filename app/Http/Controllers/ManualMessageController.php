<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendManualMessageRequest;
use App\Models\CommunicationLog;
use App\Models\CommunicationProvider;
use App\Models\MessageTemplate;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\User;
use App\Services\CommunicationSender;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ManualMessageController extends Controller
{
    public function create(string $channel): View
    {
        abort_unless(in_array($channel, ['email', 'sms'], true), 404);

        return view('communication.manual', ['channel' => $channel, 'templates' => MessageTemplate::query()->where('channel', $channel)->orderByDesc('is_important')->orderBy('name')->get(), 'logs' => CommunicationLog::query()->where('channel', $channel)->latest()->limit(10)->get()]);
    }

    public function send(SendManualMessageRequest $request, string $channel, CommunicationSender $sender): RedirectResponse
    {
        $provider = CommunicationProvider::query()->where('channel', $channel)->where('is_active', true)->first();
        if (! $provider) {
            throw ValidationException::withMessages(['provider' => 'Activate a '.$channel.' provider before sending.']);
        }
        $recipients = $this->recipients($request, $channel);
        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages(['recipients' => 'Add at least one valid recipient or select a group.']);
        }

        $sent = 0;
        $failed = 0;
        foreach ($recipients->take(100) as $recipient) {
            try {
                $sender->send($provider, $recipient, $request->string('body')->toString(), $request->string('subject')->toString() ?: null, $request->file('attachments', []));
                $sent++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return back()->with($sent ? 'status' : 'error', "{$sent} sent, {$failed} failed.");
    }

    /** @return Collection<int, string> */
    private function recipients(SendManualMessageRequest $request, string $channel): Collection
    {
        $manual = collect(preg_split('/[\s,;]+/', $request->string('recipients')->toString(), -1, PREG_SPLIT_NO_EMPTY));
        $group = match ($request->string('group')->toString()) {
            'customers' => $channel === 'email' ? User::query()->where('role', UserRole::Customer->value)->pluck('email') : Order::query()->whereNotNull('customer_phone')->pluck('customer_phone'),
            'admins' => $channel === 'email' ? User::query()->where('role', UserRole::Admin->value)->pluck('email') : collect(),
            'newsletter' => $channel === 'email' ? NewsletterSubscriber::query()->where('status', 'subscribed')->pluck('email') : collect(),
            default => collect(),
        };

        return $manual->merge($group)->map(fn (string $recipient): string => trim($recipient))->filter(fn (string $recipient): bool => $channel === 'email' ? filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false : preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', $recipient) === 1)->unique()->values();
    }
}
