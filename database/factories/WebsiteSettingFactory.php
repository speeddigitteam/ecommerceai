<?php

namespace Database\Factories;

use App\Models\WebsiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebsiteSetting>
 */
class WebsiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_name' => fake()->company(),
            'seo_title' => fake()->sentence(5),
            'meta_description' => fake()->sentence(15),
            'meta_keywords' => implode(', ', fake()->words(5)),
            'logo_path' => null,
            'favicon_path' => null,
            'featured_image_path' => null,
        ];
    }
}
