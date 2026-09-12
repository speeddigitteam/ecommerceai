<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommunicationProviderRequest;
use App\Models\CommunicationProvider;
use App\Services\CommunicationSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CommunicationProviderController extends Controller
{
    public function index(string $channel): View
    {
        $this->ensureChannel($channel);

        return view('communication.providers', ['channel' => $channel, 'providers' => CommunicationProvider::query()->where('channel', $channel)->latest()->paginate(15)]);
    }

    public function store(StoreCommunicationProviderRequest $request, string $channel): RedirectResponse
    {
        $this->ensureChannel($channel);
        CommunicationProvider::query()->create(['channel' => $channel, 'name' => $request->string('name'), 'driver' => $request->string('driver'), 'settings' => $this->settings($request, $channel)]);

        return back()->with('status', ucfirst($channel).' provider created successfully.');
    }

    public function update(StoreCommunicationProviderRequest $request, string $channel, CommunicationProvider $communicationProvider): RedirectResponse
    {
        $this->ensureProvider($channel, $communicationProvider);
        $settings = $this->settings($request, $channel, $communicationProvider->settings);
        $communicationProvider->update(['name' => $request->string('name'), 'driver' => $request->string('driver'), 'settings' => $settings]);

        return back()->with('status', ucfirst($channel).' provider updated successfully.');
    }

    public function toggle(string $channel, CommunicationProvider $communicationProvider): RedirectResponse
    {
        $this->ensureProvider($channel, $communicationProvider);
        if (! $communicationProvider->is_active) {
            $credential = $channel === 'email' ? ($communicationProvider->settings['host'] ?? null) : ($communicationProvider->settings['api_key'] ?? null);
            throw_if(blank($credential), ValidationException::withMessages(['provider' => 'Configure the provider credentials before activation.']));
        }
        DB::transaction(function () use ($channel, $communicationProvider): void {
            if (! $communicationProvider->is_active) {
                CommunicationProvider::query()->where('channel', $channel)->update(['is_active' => false]);
            }
            $communicationProvider->update(['is_active' => ! $communicationProvider->is_active]);
        });

        return back()->with('status', $communicationProvider->name.' '.($communicationProvider->is_active ? 'activated' : 'deactivated').' successfully.');
    }

    public function test(Request $request, string $channel, CommunicationProvider $communicationProvider, CommunicationSender $sender): RedirectResponse
    {
        $this->ensureProvider($channel, $communicationProvider);
        $validated = $request->validate(['test_recipient' => ['required', $channel === 'email' ? 'email' : 'regex:/^(?:\+?88)?01[3-9]\d{8}$/']]);
        try {
            $sender->send($communicationProvider, $validated['test_recipient'], $channel === 'email' ? '<p>This is a test email from your website.</p>' : 'This is a test SMS from your website.', 'Provider connection test');
        } catch (Throwable $exception) {
            $providerReason = $exception->getPrevious()?->getMessage();
            $fallback = $channel === 'email'
                ? 'Check the SMTP username, Gmail App Password, sender email, host, port and encryption.'
                : 'Check the SMS API URL, key and parameter names.';

            return back()->with('error', 'Connection test failed: '.str($providerReason ?: $fallback)->limit(300));
        }

        return back()->with('status', 'Test message sent successfully.');
    }

    public function balance(string $channel, CommunicationProvider $communicationProvider, CommunicationSender $sender): RedirectResponse
    {
        $this->ensureProvider($channel, $communicationProvider);
        abort_unless($channel === 'sms', 404);

        try {
            return back()->with('status', 'SMS balance: '.$sender->balance($communicationProvider));
        } catch (Throwable) {
            return back()->with('error', 'Balance check failed. Check the API key and balance URL.');
        }
    }

    public function destroy(string $channel, CommunicationProvider $communicationProvider): RedirectResponse
    {
        $this->ensureProvider($channel, $communicationProvider);
        $communicationProvider->delete();

        return back()->with('status', ucfirst($channel).' provider deleted successfully.');
    }

    /** @param array<string, mixed> $existing */
    private function settings(StoreCommunicationProviderRequest $request, string $channel, array $existing = []): array
    {
        $keys = $channel === 'email' ? ['host', 'port', 'encryption', 'username', 'from_address', 'from_name'] : ['url', 'sender_id', 'to_parameter', 'message_parameter', 'api_key_parameter', 'sender_parameter', 'message_type', 'label', 'balance_url'];
        $settings = collect($request->validated())->only($keys)->all();
        $secret = $channel === 'email' ? 'password' : 'api_key';
        $settings[$secret] = $request->filled($secret) ? $request->string($secret)->toString() : ($existing[$secret] ?? null);

        return $settings;
    }

    private function ensureChannel(string $channel): void
    {
        abort_unless(in_array($channel, ['email', 'sms'], true), 404);
    }

    private function ensureProvider(string $channel, CommunicationProvider $provider): void
    {
        $this->ensureChannel($channel);
        abort_unless($provider->channel === $channel, 404);
    }
}
