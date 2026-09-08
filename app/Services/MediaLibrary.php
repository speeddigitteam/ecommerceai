<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MediaLibrary
{
    public function storeImage(UploadedFile $file, string $directory, ?string $title = null): string
    {
        $disk = Storage::disk('public');
        $directory = 'media/'.trim($directory, '/');
        $baseName = Str::of(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))->slug()->limit(80, '')->value() ?: 'image';
        $extension = Str::lower($file->getClientOriginalExtension());
        $fileName = $baseName.'.'.$extension;
        $counter = 2;
        while ($disk->exists($directory.'/'.$fileName)) {
            $fileName = $baseName.'-'.$counter.'.'.$extension;
            $counter++;
        }
        $path = $file->storeAs($directory, $fileName, 'public');
        $imageTitle = $title ?: Str::headline($baseName);
        try {
            MediaAsset::query()->create(['path' => $path, 'slug' => $this->uniqueSlug($fileName), 'original_name' => $file->getClientOriginalName(), 'mime_type' => $disk->mimeType($path) ?: $file->getClientMimeType(), 'size' => $disk->size($path), 'alt_text' => $imageTitle, 'title' => $imageTitle]);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }

        return $path;
    }

    /** @param string|list<string|null>|null $paths */
    public function delete(string|array|null $paths): void
    {
        $paths = array_values(array_filter((array) $paths));
        MediaAsset::query()->whereIn('path', $paths)->delete();
        Storage::disk('public')->delete($paths);
    }

    private function uniqueSlug(string $fileName): string
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
