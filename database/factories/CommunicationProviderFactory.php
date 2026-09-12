<?php

namespace Database\Factories;

use App\Models\CommunicationProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationProvider>
 */
class CommunicationProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel' => 'email',
            'name' => fake()->company().' SMTP',
            'driver' => 'smtp',
            'settings' => ['host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'tls', 'username' => 'mailer@example.com', 'password' => 'secret', 'from_address' => 'hello@example.com', 'from_name' => 'Example Store'],
            'is_active' => false,
        ];
    }
}
