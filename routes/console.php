<?php

use App\Models\WebsiteSetting;
use App\Services\BackupRunner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$runScheduledBackupIfDue = function (string $frequency): void {
    $settings = WebsiteSetting::query()->first();

    if ($settings && $settings->backup_frequency === $frequency) {
        app(BackupRunner::class)->run($settings);
    }
};

Schedule::call(fn () => $runScheduledBackupIfDue('daily'))->daily()->at('02:00')->name('backup-daily');
Schedule::call(fn () => $runScheduledBackupIfDue('weekly'))->weekly()->sundays()->at('02:00')->name('backup-weekly');
Schedule::call(fn () => $runScheduledBackupIfDue('monthly'))->monthly()->at('02:00')->name('backup-monthly');
