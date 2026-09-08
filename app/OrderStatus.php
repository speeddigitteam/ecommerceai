<?php

namespace App;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case ReadyToShip = 'ready_to_ship';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::ReadyToShip, self::Cancelled],
            self::ReadyToShip => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Completed, self::Returned],
            self::Completed, self::Cancelled, self::Returned => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ReadyToShip => 'Ready to Ship',
            default => ucfirst($this->value),
        };
    }
}
