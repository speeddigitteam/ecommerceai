<?php

namespace Database\Factories;

use App\Models\CommunicationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationLog>
 */
class CommunicationLogFactory extends Factory
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
            'recipient' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'status' => 'sent',
            'sent_at' => now(),
        ];
    }
}
