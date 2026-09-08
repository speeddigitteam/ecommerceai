<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBackupSettingRequest;
use App\Models\WebsiteSetting;
use App\Services\BackupRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupSettingController extends Controller
{
    public function __construct(private readonly BackupRunner $backupRunner) {}

    public function edit(): View
    {
        $settings = $this->settings();
        $disk = $this->backupRunner->resolveDisk($settings);
        $backups = collect();
        $listError = null;

        try {
            $backups = collect(Storage::disk($disk)->allFiles($this->backupRunner->backupFolder($settings)))
                ->filter(fn (string $file): bool => str_ends_with($file, '.zip'))
                ->map(fn (string $file): array => [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => Storage::disk($disk)->size($file),
                    'modified_at' => Carbon::createFromTimestamp(Storage::disk($disk)->lastModified($file)),
                ])
                ->sortByDesc('modified_at')
                ->values();
        } catch (Throwable $e) {
            $listError = 'Could not list backups from the "'.$disk.'" disk: '.$e->getMessage();
        }

        return view('settings.backup', [
            'settings' => $settings,
            'backups' => $backups,
            'activeDisk' => $disk,
            'listError' => $listError,
        ]);
    }

    public function update(UpdateBackupSettingRequest $request): RedirectResponse
    {
        $settings = $this->settings();
        $disk = $request->string('disk')->toString();
        $bucket = $request->string('s3_bucket')->trim()->toString();
        $key = $request->string('s3_key')->trim()->toString();
        $secret = $request->string('s3_secret')->trim()->toString();
        $region = $request->string('s3_region')->trim()->toString();

        $configurationErrors = [];

        if ($disk === 's3') {
            if ($bucket === '') {
                $configurationErrors['s3_bucket'] = 'The S3 bucket is required when Amazon S3 is selected.';
            }

            if ($key === '') {
                $configurationErrors['s3_key'] = 'The S3 access key is required when Amazon S3 is selected.';
            }

            if ($region === '') {
                $configurationErrors['s3_region'] = 'The S3 region is required when Amazon S3 is selected.';
            }

            if ($secret === '' && blank($settings->backup_s3_secret)) {
                $configurationErrors['s3_secret'] = 'The S3 secret key is required when Amazon S3 is selected.';
            }
        }

        if ($configurationErrors !== []) {
            throw ValidationException::withMessages($configurationErrors);
        }

        $settings->backup_disk = $disk;
        $settings->backup_path_prefix = $request->string('path_prefix')->trim()->toString() ?: null;
        $settings->backup_frequency = $request->string('frequency')->toString();
        $settings->backup_s3_key = $key !== '' ? $key : null;
        $settings->backup_s3_region = $region !== '' ? $region : null;
        $settings->backup_s3_bucket = $bucket !== '' ? $bucket : null;
        $settings->backup_s3_endpoint = $request->string('s3_endpoint')->trim()->toString() ?: null;
        $settings->backup_s3_use_path_style_endpoint = $request->boolean('s3_use_path_style_endpoint');

        if ($secret !== '') {
            $settings->backup_s3_secret = $secret;
        }

        $settings->save();

        return to_route('settings.backup.edit')->with('status', 'Backup settings updated successfully.');
    }

    public function run(): RedirectResponse
    {
        $result = $this->backupRunner->run($this->settings());

        return to_route('settings.backup.edit')->with($result['success'] ? 'status' : 'error', $result['message']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $settings = $this->settings();
        $disk = $this->backupRunner->resolveDisk($settings);
        $path = $request->string('path')->toString();

        abort_if($path === '' || str_contains($path, '..'), 422);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        Storage::disk($disk)->delete($path);

        return to_route('settings.backup.edit')->with('status', 'Backup deleted.');
    }

    public function download(Request $request): StreamedResponse
    {
        $settings = $this->settings();
        $disk = $this->backupRunner->resolveDisk($settings);
        $path = $request->string('path')->toString();

        abort_if($path === '' || str_contains($path, '..'), 422);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path);
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
    }
}
