<?php

namespace App\Services;

use App\Models\CommunicationProvider;
use App\Models\MessageTemplate;
use App\Models\Order;
use App\Models\WebsiteSetting;

class AutomatedEmailService
{
    public function sendForOrder(string $templateKey, Order $order): void
    {
        if (blank($order->customer_email)) {
            return;
        }

        $template = MessageTemplate::query()->where('channel', 'email')->where('key', $templateKey)->where('is_active', true)->first();
        $provider = CommunicationProvider::query()->where('channel', 'email')->where('is_active', true)->first();
        if (! $template || ! $provider) {
            return;
        }

        $settings = WebsiteSetting::query()->first();
        $variables = [
            '{{customer_name}}' => $order->customer_name,
            '{{order_number}}' => $order->order_number,
            '{{order_total}}' => '৳'.number_format((float) $order->total, 2),
            '{{order_status}}' => str($order->status)->replace('_', ' ')->title()->toString(),
            '{{tracking_url}}' => route('storefront.orders.track'),
            '{{store_name}}' => $settings?->site_name ?? config('app.name'),
            '{{store_phone}}' => $settings?->business_phone ?? '',
        ];
        $subject = strtr((string) $template->subject, $variables);
        $body = strtr($template->body, $variables);
        if (filled($template->button_text) && filled($template->button_url)) {
            $body .= '<p><a href="'.e(strtr($template->button_url, $variables)).'" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#4f46e5;color:#fff;text-decoration:none">'.e($template->button_text).'</a></p>';
        }

        app(CommunicationSender::class)->send($provider, $order->customer_email, $body, $subject);
    }
}
