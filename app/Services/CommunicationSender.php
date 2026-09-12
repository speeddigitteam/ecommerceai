<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\CommunicationProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class CommunicationSender
{
    /** @param list<UploadedFile> $attachments */
    public function send(CommunicationProvider $provider, string $recipient, string $body, ?string $subject = null, array $attachments = []): CommunicationLog
    {
        $log = CommunicationLog::query()->create(['communication_provider_id' => $provider->id, 'channel' => $provider->channel, 'recipient' => $recipient, 'subject' => $subject, 'body' => $body, 'status' => 'pending']);

        try {
            $response = $provider->channel === 'email' ? $this->sendEmail($provider, $recipient, $body, $subject, $attachments) : $this->sendSms($provider, $recipient, $body);
            $log->update(['status' => 'sent', 'provider_response' => $response, 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $log->update(['status' => 'failed', 'error_message' => str($exception->getMessage())->limit(2000)]);
            throw new RuntimeException('Message could not be sent. Check the active provider settings.', previous: $exception);
        }

        return $log->refresh();
    }

    /** @param list<UploadedFile> $attachments */
    private function sendEmail(CommunicationProvider $provider, string $recipient, string $body, ?string $subject, array $attachments): string
    {
        $settings = $provider->settings;
        config(['mail.mailers.communication' => ['transport' => 'smtp', 'host' => $settings['host'], 'port' => $settings['port'], 'encryption' => ($settings['encryption'] ?? 'none') === 'none' ? null : $settings['encryption'], 'username' => $settings['username'] ?? null, 'password' => $settings['password'] ?? null, 'timeout' => 30]]);
        Mail::purge('communication');
        Mail::mailer('communication')->html($body, function (Message $message) use ($settings, $recipient, $subject, $attachments): void {
            $message->from($settings['from_address'], $settings['from_name'])->to($recipient)->subject($subject ?: 'Message');
            foreach ($attachments as $attachment) {
                $message->attach($attachment->getRealPath(), ['as' => $attachment->getClientOriginalName(), 'mime' => $attachment->getMimeType()]);
            }
        });

        return 'Accepted by SMTP transport.';
    }

    private function sendSms(CommunicationProvider $provider, string $recipient, string $body): string
    {
        $settings = $provider->settings;
        $payload = [$settings['to_parameter'] => $recipient, $settings['message_parameter'] => $body];
        if (filled($settings['sender_id'] ?? null)) {
            $payload['sender_id'] = $settings['sender_id'];
        }
        if (filled($settings['api_key'] ?? null)) {
            $payload[$settings['api_key_parameter'] ?: 'api_key'] = $settings['api_key'];
        }

        $response = Http::asForm()->acceptJson()->timeout(30)->retry(2, 500, throw: false)->post($settings['url'], $payload);
        if ($response->failed()) {
            throw new RuntimeException('SMS provider rejected the request with status '.$response->status().'.');
        }

        return str($response->body())->limit(2000)->toString();
    }
}
