<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWebsiteSettingRequest;
use App\Models\MediaAsset;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebsiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.website', [
            'settings' => WebsiteSetting::query()->firstOrCreate([], [
                'site_name' => config('app.name', 'Shopwise'),
                'seo_title' => config('app.name', 'Shopwise'),
            ]),
        ]);
    }

    public function update(UpdateWebsiteSettingRequest $request): RedirectResponse
    {
        $settings = WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
        $validated = $request->safe()->except(['logo', 'favicon', 'featured_image']);

        foreach (['logo', 'favicon', 'featured_image'] as $image) {
            if (! $request->hasFile($image)) {
                continue;
            }

            $pathAttribute = $image.'_path';
            $previousPath = $settings->{$pathAttribute};
            $validated[$pathAttribute] = $this->storeBrandImage($request->file($image), $image, $previousPath);
            if ($previousPath) {
                Storage::disk('public')->delete($previousPath);
                MediaAsset::query()->where('path', $previousPath)->delete();
            }
        }

        $settings->update($validated);

        return to_route('settings.website.edit')->with('status', 'Website settings updated successfully.');
    }

    private function storeBrandImage(UploadedFile $file, string $imageType, ?string $previousPath = null): string
    {
        $disk = Storage::disk('public');
        $directory = 'media/website';
        $extension = Str::lower($file->getClientOriginalExtension());
        $baseName = Str::slug($imageType) ?: 'brand-image';
        $counter = 1;

        if ($previousPath) {
            $previousBaseName = pathinfo($previousPath, PATHINFO_FILENAME);

            if ($previousBaseName === $baseName) {
                $counter = 2;
            } elseif (preg_match('/^'.preg_quote($baseName, '/').'-(\d+)$/', $previousBaseName, $matches)) {
                $counter = ((int) $matches[1]) + 1;
            }
        }

        $fileName = $counter === 1 ? $baseName.'.'.$extension : $baseName.'-'.$counter.'.'.$extension;

        while ($disk->exists($directory.'/'.$fileName) && $directory.'/'.$fileName !== $previousPath) {
            $counter++;
            $fileName = $baseName.'-'.$counter.'.'.$extension;
        }

        $path = $file->storeAs($directory, $fileName, 'public');
        $title = Str::headline($imageType);

        MediaAsset::query()->create([
            'path' => $path,
            'slug' => $this->uniqueMediaSlug($fileName),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $disk->mimeType($path) ?: $file->getClientMimeType(),
            'size' => $disk->size($path),
            'alt_text' => $title,
            'title' => $title,
        ]);

        return $path;
    }

    private function uniqueMediaSlug(string $fileName): string
    {
        $baseName = Str::of(pathinfo($fileName, PATHINFO_FILENAME))->slug()->limit(80, '')->value() ?: 'image';
        $extension = Str::lower(pathinfo($fileName, PATHINFO_EXTENSION));
        $slug = $baseName.'.'.$extension;
        $counter = 2;

        while (MediaAsset::query()->where('slug', $slug)->exists()) {
            $slug = $baseName.'-'.$counter.'.'.$extension;
            $counter++;
        }

        return $slug;
    }
}
