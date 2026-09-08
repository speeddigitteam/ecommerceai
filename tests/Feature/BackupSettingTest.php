<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\BackupRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_backup_settings(): void
    {
        $this->get(route('settings.backup.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_backup_settings_page(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('settings.backup.edit'))
            ->assertOk()
            ->assertSee('Backup')
            ->assertSee('Run backup now');
    }

    public function test_admin_can_save_local_backup_settings(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.backup.update'), [
            'disk' => 'local',
            'frequency' => 'daily',
            'path_prefix' => 'nightly-backups',
        ])->assertRedirect(route('settings.backup.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();
        $this->assertSame('local', $settings->backup_disk);
        $this->assertSame('daily', $settings->backup_frequency);
        $this->assertSame('nightly-backups', $settings->backup_path_prefix);
    }

    public function test_backup_frequency_must_be_a_known_option(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.backup.update'), [
            'disk' => 'local',
            'frequency' => 'hourly',
        ])->assertSessionHasErrors(['frequency']);
    }

    public function test_admin_can_save_s3_backup_settings_and_the_secret_is_encrypted(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.backup.update'), [
            'disk' => 's3',
            'frequency' => 'off',
            's3_bucket' => 'my-bucket',
            's3_region' => 'us-east-1',
            's3_key' => 'access-key-id',
            's3_secret' => 'super-secret-value',
        ])->assertRedirect(route('settings.backup.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();
        $this->assertSame('s3', $settings->backup_disk);
        $this->assertSame('my-bucket', $settings->backup_s3_bucket);
        $this->assertSame('super-secret-value', $settings->backup_s3_secret);
        $this->assertNotSame('super-secret-value', DB::table('website_settings')->value('backup_s3_secret'));
    }

    public function test_s3_backup_settings_cannot_be_saved_without_required_fields(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.backup.update'), [
            'disk' => 's3',
            'frequency' => 'off',
            's3_bucket' => '',
            's3_region' => '',
            's3_key' => '',
            's3_secret' => '',
        ])->assertSessionHasErrors(['s3_bucket', 's3_region', 's3_key', 's3_secret']);
    }

    public function test_scheduled_backups_are_registered_for_each_frequency(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('backup-daily')
            ->expectsOutputToContain('backup-weekly')
            ->expectsOutputToContain('backup-monthly');
    }

    public function test_scheduled_backup_runs_when_its_frequency_matches_the_saved_setting(): void
    {
        $settings = WebsiteSetting::factory()->create(['backup_frequency' => 'daily']);
        $this->mock(BackupRunner::class, function ($mock) use ($settings): void {
            $mock->shouldReceive('run')->once()->withArgs(fn (WebsiteSetting $arg): bool => $arg->is($settings));
        });

        $this->artisan('schedule:test', ['--name' => 'backup-daily']);
    }

    public function test_scheduled_backup_is_skipped_when_frequency_does_not_match(): void
    {
        WebsiteSetting::factory()->create(['backup_frequency' => 'daily']);
        $this->mock(BackupRunner::class, function ($mock): void {
            $mock->shouldNotReceive('run');
        });

        $this->artisan('schedule:test', ['--name' => 'backup-weekly']);
    }

    public function test_admin_can_run_a_backup_successfully(): void
    {
        $admin = User::factory()->create();
        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:run', ['--only-db' => true, '--disable-notifications' => true])
            ->andReturn(0);

        $this->actingAs($admin)->post(route('settings.backup.run'))
            ->assertRedirect(route('settings.backup.edit'))
            ->assertSessionHas('status');
    }

    public function test_backup_run_failure_is_reported(): void
    {
        $admin = User::factory()->create();
        Artisan::shouldReceive('call')->once()->andReturn(1);
        Artisan::shouldReceive('output')->once()->andReturn('mysqldump failed');

        $this->actingAs($admin)->post(route('settings.backup.run'))
            ->assertRedirect(route('settings.backup.edit'))
            ->assertSessionHas('error');
    }

    public function test_admin_can_download_and_delete_an_existing_backup(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create(['backup_disk' => 'local', 'backup_path_prefix' => 'test-backups']);
        Storage::fake('local');
        Storage::disk('local')->put('test-backups/sample-backup.zip', 'dummy-content');

        $this->actingAs($admin)
            ->get(route('settings.backup.edit'))
            ->assertOk()
            ->assertSee('sample-backup.zip');

        $this->actingAs($admin)
            ->get(route('settings.backup.download', ['path' => 'test-backups/sample-backup.zip']))
            ->assertOk();

        $this->actingAs($admin)
            ->delete(route('settings.backup.destroy'), ['path' => 'test-backups/sample-backup.zip'])
            ->assertRedirect(route('settings.backup.edit'));

        Storage::disk('local')->assertMissing('test-backups/sample-backup.zip');
    }
}
