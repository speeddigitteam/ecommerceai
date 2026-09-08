<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHeroSettingRequest;
use App\Models\Product;
use App\Models\WebsiteSetting;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HeroSettingController extends Controller
{
    public function __construct(private readonly MediaLibrary $mediaLibrary) {}

    public function edit(): View
    {
        return view('settings.hero', [
            'settings' => $this->settings(),
            'products' => Product::query()->where('status', 'published')->where('visibility', 'public')->whereNotNull('price')->orderBy('title')->get(),
        ]);
    }

    public function update(UpdateHeroSettingRequest $request): RedirectResponse
    {
        $settings = $this->settings();
        $validated = $request->safe()->only(['hero_title', 'hero_subtitle', 'hero_product_id']);
        $sliderPaths = $settings->hero_slider_paths ?? [];
        $pathsToRemove = array_intersect($sliderPaths, $request->validated('remove_slider_images', []));
        $sliderPaths = array_values(array_diff($sliderPaths, $pathsToRemove));

        if (count($sliderPaths) + count($request->file('slider_images', [])) > 8) {
            return back()->withErrors(['slider_images' => 'The hero slider may contain up to 8 images.'])->withInput();
        }

        $this->mediaLibrary->delete($pathsToRemove);

        foreach ($request->file('slider_images', []) as $image) {
            $sliderPaths[] = $this->mediaLibrary->storeImage($image, 'website/hero', 'Hero slider');
        }

        $sideImagePaths = [
            'hero_side_image_one_path' => $settings->hero_side_image_one_path,
            'hero_side_image_two_path' => $settings->hero_side_image_two_path,
        ];

        foreach ([
            'one' => 'hero_side_image_one_path',
            'two' => 'hero_side_image_two_path',
        ] as $slot => $pathAttribute) {
            $uploadedImage = $request->file('hero_side_image_'.$slot);
            $shouldRemoveImage = $request->boolean('remove_hero_side_image_'.$slot);

            if (($uploadedImage || $shouldRemoveImage) && $sideImagePaths[$pathAttribute]) {
                $this->mediaLibrary->delete($sideImagePaths[$pathAttribute]);
                $sideImagePaths[$pathAttribute] = null;
            }

            if ($uploadedImage) {
                $sideImagePaths[$pathAttribute] = $this->mediaLibrary->storeImage($uploadedImage, 'website/hero/side', 'Hero side image');
            }
        }

        $settings->update([
            ...$validated,
            'hero_slider_paths' => $sliderPaths,
            ...$sideImagePaths,
        ]);

        return to_route('settings.hero.edit')->with('status', 'Hero settings updated successfully.');
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
    }
}
