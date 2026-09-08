<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadMediaRequest;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileManagerController extends Controller
{
    public function index(Request $request): View
    {
        $path = $this->safeRelativePath($request->string('path')->value());
        $directory = $this->diskPath($path);
        $disk = Storage::disk('public');
        $disk->makeDirectory($directory);
        $search = Str::lower($request->string('search')->trim()->value());

        $folders = collect($disk->directories($directory))
            ->map(fn (string $folder): array => [
                'name' => basename($folder),
                'path' => $this->relativePath($folder),
                'item_count' => count($disk->files($folder)) + count($disk->directories($folder)),
                'modified_at' => $this->latestModifiedAt($folder),
            ])
            ->filter(fn (array $folder): bool => $search === '' || str_contains(Str::lower($folder['name']), $search))
            ->sortBy('name')
            ->values();

        $directoryFiles = $disk->files($directory);
        foreach ($directoryFiles as $directoryFile) {
            if (str_starts_with($disk->mimeType($directoryFile) ?: '', 'image/')) {
                $this->registerImage($directoryFile);
            }
        }
        $mediaAssets = MediaAsset::query()->whereIn('path', $directoryFiles)->get()->keyBy('path');
        $files = collect($directoryFiles)
            ->map(fn (string $file): array => $this->fileData($file, $mediaAssets->get($file)))
            ->filter(fn (array $file): bool => $search === '' || str_contains(Str::lower($file['name']), $search))
            ->sortByDesc('modified_at')
            ->values();

        $allFiles = collect($disk->allFiles('media'));

        return view('file-manager.index', [
            'path' => $path,
            'parentPath' => $path === '' ? null : Str::beforeLast($path, '/'),
            'breadcrumbs' => $this->breadcrumbs($path),
            'folders' => $folders,
            'files' => $files,
            'totalFiles' => $allFiles->count(),
            'totalSize' => $allFiles->sum(fn (string $file): int => $disk->size($file)),
            'totalSizeLabel' => Number::fileSize($allFiles->sum(fn (string $file): int => $disk->size($file))),
        ]);
    }

    public function upload(UploadMediaRequest $request): RedirectResponse
    {
        $path = $this->safeRelativePath((string) $request->validated('path'));
        $directory = $this->diskPath($path);

        foreach ($request->file('files', []) as $file) {
            $storedPath = $file->storeAs($directory, $this->uniqueFileName($directory, $file->getClientOriginalName()), 'public');
            $mimeType = Storage::disk('public')->mimeType($storedPath) ?: $file->getClientMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                MediaAsset::query()->create([
                    'path' => $storedPath,
                    'slug' => $this->uniqueMediaSlug(basename($storedPath)),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $mimeType,
                    'size' => Storage::disk('public')->size($storedPath),
                    'alt_text' => Str::of(pathinfo($storedPath, PATHINFO_FILENAME))->replace('-', ' ')->headline()->value(),
                    'title' => Str::of(pathinfo($storedPath, PATHINFO_FILENAME))->replace('-', ' ')->headline()->value(),
                ]);
            }
        }

        return $this->backToPath($path)->with('status', 'Files uploaded successfully.');
    }

    public function createFolder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'path' => ['nullable', 'string', 'max:1000'],
            'name' => ['required', 'string', 'max:100', 'regex:~^[^\\/:*?"<>|]+$~'],
        ]);
        $path = $this->safeRelativePath($validated['path'] ?? '');
        $folder = $this->diskPath($path).'/'.trim($validated['name']);
        if (Storage::disk('public')->exists($folder)) {
            throw ValidationException::withMessages(['name' => 'A folder with this name already exists.']);
        }
        Storage::disk('public')->makeDirectory($folder);

        return $this->backToPath($path)->with('status', 'Folder created successfully.');
    }

    public function rename(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:1000'],
            'name' => ['required', 'string', 'max:150', 'regex:~^[^\\/:*?"<>|]+$~'],
        ]);
        $relative = $this->safeRelativePath($validated['path']);
        $source = $this->diskPath($relative);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($source), 404);
        $parent = Str::beforeLast($source, '/');
        $name = trim($validated['name']);
        $mediaAsset = MediaAsset::query()->where('path', $source)->first();
        if ($disk->fileExists($source)) {
            $extension = pathinfo($source, PATHINFO_EXTENSION);
            if ($mediaAsset !== null) {
                $name = $this->uniqueMediaSlug(pathinfo($name, PATHINFO_FILENAME).($extension ? '.'.$extension : ''), $mediaAsset->id);
            } else {
                $name = pathinfo($name, PATHINFO_FILENAME).($extension ? '.'.$extension : '');
            }
        }
        $target = $parent.'/'.$name;
        if ($disk->exists($target)) {
            throw ValidationException::withMessages(['name' => 'An item with this name already exists.']);
        }
        $disk->move($source, $target);
        if ($disk->fileExists($target)) {
            $mediaAsset?->update(['path' => $target, 'slug' => basename($target)]);
        } else {
            MediaAsset::query()->where('path', 'like', $source.'/%')->get()->each(function (MediaAsset $asset) use ($source, $target): void {
                $asset->update(['path' => $target.Str::after($asset->path, $source)]);
            });
        }

        return $this->backToPath($this->relativePath($parent))->with('status', 'Item renamed successfully.');
    }

    public function updateImageSeo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:1000'],
            'filename' => ['required', 'string', 'max:100'],
            'alt_text' => ['required', 'string', 'max:125'],
            'title' => ['nullable', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $relative = $this->safeRelativePath($validated['path']);
        $source = $this->diskPath($relative);
        $disk = Storage::disk('public');
        abort_unless($disk->fileExists($source) && str_starts_with($disk->mimeType($source) ?: '', 'image/'), 404);
        $mediaAsset = MediaAsset::query()->where('path', $source)->first();

        $extension = Str::lower(pathinfo($source, PATHINFO_EXTENSION));
        $seoName = Str::of(pathinfo($validated['filename'], PATHINFO_FILENAME))->slug()->limit(80, '')->value();
        if ($seoName === '') {
            throw ValidationException::withMessages(['filename' => 'Enter a descriptive image filename.']);
        }
        $slug = $this->uniqueMediaSlug($seoName.'.'.$extension, $mediaAsset?->id);
        $target = Str::beforeLast($source, '/').'/'.$slug;
        if ($target !== $source) {
            if ($disk->exists($target)) {
                throw ValidationException::withMessages(['filename' => 'An image with this filename already exists.']);
            }
            $disk->move($source, $target);
        }

        MediaAsset::query()->updateOrCreate(['path' => $source], [
            'path' => $target,
            'slug' => $slug,
            'original_name' => $mediaAsset?->original_name ?: basename($source),
            'mime_type' => $disk->mimeType($target),
            'size' => $disk->size($target),
            'alt_text' => $validated['alt_text'],
            'title' => $validated['title'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return $this->backToPath($this->relativePath(Str::beforeLast($target, '/')))->with('status', 'Image SEO information updated successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate(['path' => ['required', 'string', 'max:1000']]);
        $relative = $this->safeRelativePath($validated['path']);
        abort_if($relative === '', 422);
        $target = $this->diskPath($relative);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($target), 404);
        MediaAsset::query()
            ->where('path', $target)
            ->orWhere('path', 'like', $target.'/%')
            ->delete();
        $disk->directoryExists($target) ? $disk->deleteDirectory($target) : $disk->delete($target);

        $parentPath = str_contains($relative, '/') ? Str::beforeLast($relative, '/') : '';

        return $this->backToPath($parentPath)->with('status', 'Item deleted successfully.');
    }

    public function download(Request $request): BinaryFileResponse
    {
        $relative = $this->safeRelativePath($request->string('path')->value());
        $file = $this->diskPath($relative);
        abort_unless(Storage::disk('public')->fileExists($file), 404);

        return response()->download(Storage::disk('public')->path($file), basename($file));
    }

    private function fileData(string $file, ?MediaAsset $mediaAsset): array
    {
        $disk = Storage::disk('public');
        $mimeType = $disk->mimeType($file) ?: 'application/octet-stream';

        return [
            'name' => basename($file),
            'path' => $this->relativePath($file),
            'url' => $mediaAsset ? route('media.show', $mediaAsset, false) : '/storage/'.str_replace('%2F', '/', rawurlencode($file)),
            'mime_type' => $mimeType,
            'is_image' => str_starts_with($mimeType, 'image/'),
            'size' => $disk->size($file),
            'size_label' => Number::fileSize($disk->size($file)),
            'modified_at' => $disk->lastModified($file),
            'extension' => strtoupper(pathinfo($file, PATHINFO_EXTENSION) ?: 'FILE'),
            'seo' => [
                'filename' => pathinfo($file, PATHINFO_FILENAME),
                'alt_text' => $mediaAsset?->alt_text ?? '',
                'title' => $mediaAsset?->title ?? '',
                'caption' => $mediaAsset?->caption ?? '',
                'description' => $mediaAsset?->description ?? '',
                'is_complete' => filled($mediaAsset?->alt_text),
            ],
        ];
    }

    private function safeRelativePath(?string $path): string
    {
        $path = trim(str_replace('\\', '/', (string) $path), '/');
        abort_if(collect(explode('/', $path))->contains(fn (string $segment): bool => $segment === '..' || $segment === '.'), 422, 'Invalid path.');

        return $path;
    }

    private function diskPath(string $path): string
    {
        return 'media'.($path === '' ? '' : '/'.$path);
    }

    private function relativePath(string $diskPath): string
    {
        return trim(Str::after($diskPath, 'media'), '/');
    }

    private function uniqueFileName(string $directory, string $originalName): string
    {
        $disk = Storage::disk('public');
        $base = Str::of(pathinfo($originalName, PATHINFO_FILENAME))->slug()->limit(80, '')->value() ?: 'file';
        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));
        $name = $base.($extension ? '.'.$extension : '');
        $counter = 2;
        while ($disk->exists($directory.'/'.$name)) {
            $name = $base.'-'.$counter.($extension ? '.'.$extension : '');
            $counter++;
        }

        return $name;
    }

    private function uniqueMediaSlug(string $filename, ?int $exceptId = null): string
    {
        $base = Str::of(pathinfo($filename, PATHINFO_FILENAME))->slug()->limit(80, '')->value() ?: 'image';
        $extension = Str::lower(pathinfo($filename, PATHINFO_EXTENSION));
        $slug = $base.($extension ? '.'.$extension : '');
        $counter = 2;

        while (MediaAsset::query()->where('slug', $slug)->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))->exists()) {
            $slug = $base.'-'.$counter.($extension ? '.'.$extension : '');
            $counter++;
        }

        return $slug;
    }

    private function registerImage(string $path): MediaAsset
    {
        $disk = Storage::disk('public');
        $title = Str::of(pathinfo($path, PATHINFO_FILENAME))->replace('-', ' ')->headline()->value();

        return MediaAsset::query()->firstOrCreate(['path' => $path], [
            'slug' => $this->uniqueMediaSlug(basename($path)),
            'original_name' => basename($path),
            'mime_type' => $disk->mimeType($path),
            'size' => $disk->size($path),
            'alt_text' => $title,
            'title' => $title,
        ]);
    }

    private function latestModifiedAt(string $folder): int
    {
        $disk = Storage::disk('public');
        $files = $disk->allFiles($folder);

        return $files === [] ? now()->timestamp : (int) collect($files)->max(fn (string $file): int => $disk->lastModified($file));
    }

    private function breadcrumbs(string $path): array
    {
        $breadcrumbs = [['name' => 'All Files', 'path' => '']];
        $builtPath = '';
        foreach (array_filter(explode('/', $path)) as $segment) {
            $builtPath = trim($builtPath.'/'.$segment, '/');
            $breadcrumbs[] = ['name' => $segment, 'path' => $builtPath];
        }

        return $breadcrumbs;
    }

    private function backToPath(string $path): RedirectResponse
    {
        return to_route('file-manager.index', array_filter(['path' => $path]));
    }
}
