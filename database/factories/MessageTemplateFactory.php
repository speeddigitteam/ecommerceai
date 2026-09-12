<?php

namespace Database\Factories;

use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
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
            'name' => fake()->unique()->words(3, true),
            'subject' => fake()->sentence(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'is_important' => false,
        ];
    }
}
