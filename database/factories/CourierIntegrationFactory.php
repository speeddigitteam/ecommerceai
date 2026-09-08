<?php

namespace Database\Factories;

use App\Models\CourierIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourierIntegration>
 */
class CourierIntegrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'steadfast',
            'name' => 'Steadfast Courier',
            'api_key' => 'test-api-key',
            'secret_key' => 'test-secret-key',
            'base_url' => 'https://portal.packzy.com/api/v1',
            'is_active' => true,
        ];
    }
}
