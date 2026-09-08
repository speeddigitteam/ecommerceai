<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Throwable;

class BackupRunner
{
    public function backupFolder(WebsiteSetting $settings): string
    {
        return $settings->backup_path_prefix ?: config('backup.backup.name');
    }

    public function resolveDisk(WebsiteSetting $settings): string
    {
        Config::set('backup.backup.name', $this->backupFolder($settings));

        if ($settings->backup_disk === 's3'
            && filled($settings->backup_s3_bucket)
            && filled($settings->backup_s3_key)
            && filled($settings->backup_s3_secret)
        ) {
            Config::set('filesystems.disks.s3', [
                'driver' => 's3',
                'key' => $settings->backup_s3_key,
                'secret' => $settings->backup_s3_secret,
                'region' => $settings->backup_s3_region ?: 'us-east-1',
                'bucket' => $settings->backup_s3_bucket,
                'endpoint' => $settings->backup_s3_endpoint ?: null,
                'use_path_style_endpoint' => $settings->backup_s3_use_path_style_endpoint,
                'throw' => false,
            ]);
            Config::set('backup.backup.destination.disks', ['s3']);

            return 's3';
        }

        Config::set('backup.backup.destination.disks', ['local']);

        return 'local';
    }

    /** @return array{success: bool, disk: string, message: string} */
    public function run(WebsiteSetting $settings): array
    {
        $disk = $this->resolveDisk($settings);

        try {
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);
        } catch (Throwable $e) {
            return ['success' => false, 'disk' => $disk, 'message' => 'Backup failed: '.$e->getMessage()];
        }

        if ($exitCode !== 0) {
            return ['success' => false, 'disk' => $disk, 'message' => 'Backup failed. '.trim(Artisan::output())];
        }

        $settings->forceFill(['backup_last_run_at' => now()])->save();

        return ['success' => true, 'disk' => $disk, 'message' => 'Database backup created successfully on the "'.$disk.'" disk.'];
    }
}
