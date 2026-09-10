<?php

namespace App\Models;

use App\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'profile_image_path', 'role', 'wholesale_status', 'business_name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isApprovedWholesaler(): bool
    {
        return $this->role === UserRole::Customer && $this->wholesale_status === 'approved';
    }

    public function homeRoute(): string
    {
        return $this->isAdmin() ? 'dashboard' : 'customer.dashboard';
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function reviewableOrders(): Builder
    {
        return Order::query()
            ->where('status', 'completed')
            ->where(function (Builder $query): void {
                $query->where('user_id', $this->id)
                    ->orWhere(function (Builder $legacyOrderQuery): void {
                        $legacyOrderQuery->whereNull('user_id')->where('customer_email', $this->email);
                    });
            });
    }

    public function completedOrderForProduct(Product $product): ?Order
    {
        return $this->reviewableOrders()
            ->whereHas('items', fn (Builder $query) => $query->where('product_id', $product->id))
            ->latest()
            ->first();
    }
}
