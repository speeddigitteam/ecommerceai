<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug().'.jpg';

        return [
            'path' => 'media/'.$slug,
            'slug' => $slug,
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1000, 1000000),
            'alt_text' => fake()->sentence(5),
            'title' => fake()->sentence(4),
            'caption' => fake()->optional()->sentence(),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
