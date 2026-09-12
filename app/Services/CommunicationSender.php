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
    /** @var array<string, string> */
    private const MRAM_ERRORS = [
        '1002' => 'Sender ID or masking was not found.',
        '1003' => 'API key was not found.',
        '1004' => 'The message was detected as spam.',
        '1005' => 'The SMS provider reported an internal error.',
        '1006' => 'The SMS provider reported an internal error.',
        '1007' => 'SMS balance is insufficient.',
        '1008' => 'The message is empty.',
        '1009' => 'Message type is missing.',
        '1010' => 'Invalid username or password.',
        '1011' => 'Invalid user ID.',
        '1012' => 'Invalid mobile number.',
        '1013' => 'SMS API limit exceeded.',
        '1014' => 'No matching SMS template was found.',
        '1015' => 'SMS content validation failed.',
        '1016' => 'This server IP address is not allowed.',
        '1019' => 'SMS purpose is missing.',
    ];

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
        $scheme = ($settings['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp';
        config(['mail.mailers.communication' => ['transport' => 'smtp', 'scheme' => $scheme, 'host' => $settings['host'], 'port' => $settings['port'], 'username' => $settings['username'] ?? null, 'password' => $settings['password'] ?? null, 'timeout' => 30, 'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST)]]);
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
            $payload[$settings['sender_parameter'] ?? 'sender_id'] = $settings['sender_id'];
        }
        if (filled($settings['api_key'] ?? null)) {
            $payload[$settings['api_key_parameter'] ?: 'api_key'] = $settings['api_key'];
        }
        if (filled($settings['message_type'] ?? null)) {
            $payload['type'] = $settings['message_type'] === 'auto'
                ? (preg_match('/[^\x00-\x7F]/u', $body) ? 'unicode' : 'text')
                : $settings['message_type'];
        }
        if (filled($settings['label'] ?? null)) {
            $payload['label'] = $settings['label'];
        }

        $response = Http::asForm()->acceptJson()->timeout(30)->retry(2, 500, throw: false)->post($settings['url'], $payload);
        if ($response->failed()) {
            throw new RuntimeException('SMS provider rejected the request with status '.$response->status().'.');
        }

        $providerBody = trim($response->body());
        if (isset(self::MRAM_ERRORS[$providerBody])) {
            throw new RuntimeException(self::MRAM_ERRORS[$providerBody]);
        }

        return str($response->body())->limit(2000)->toString();
    }

    public function balance(CommunicationProvider $provider): string
    {
        $settings = $provider->settings;
        $balanceUrl = $settings['balance_url'] ?? null;
        if (blank($balanceUrl) || blank($settings['api_key'] ?? null)) {
            throw new RuntimeException('Balance URL or API key is missing.');
        }

        $url = str_replace('{api_key}', rawurlencode($settings['api_key']), $balanceUrl);
        $response = Http::acceptJson()->timeout(30)->retry(2, 500, throw: false)->get($url);
        if ($response->failed()) {
            throw new RuntimeException('Balance service returned status '.$response->status().'.');
        }

        return str($response->body())->trim()->limit(200)->toString();
    }
}
