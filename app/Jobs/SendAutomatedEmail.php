<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\AutomatedEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAutomatedEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $templateKey, public int $orderId) {}

    public function handle(AutomatedEmailService $service): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order) {
            $service->sendForOrder($this->templateKey, $order);
        }
    }
}
