<?php

namespace Tests\Unit\Http\Controllers\Admin;

use App\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('admin-logs')]
class ReadOnlyLogViewerControllerTest extends TestCase
{
    private string $logPath;
    private string $logDirectory;
    private User $programmer;

    public function setUp(): void
    {
        parent::setUp();
        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole($role);
        $this->logDirectory = storage_path('framework/testing-log-viewer');
        File::ensureDirectoryExists($this->logDirectory);
        config(['logviewer.storage_path' => $this->logDirectory]);
        $this->logPath = $this->logDirectory.'/admin-viewer-test.log';
        File::put($this->logPath, "[2026-09-04 12:00:00] testing.INFO: Deterministic admin log entry\n");
    }

    public function tearDown(): void
    {
        File::deleteDirectory($this->logDirectory);
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function privileged_user_can_list_and_view_configured_log_files(): void
    {
        $file = urlencode(Crypt::encryptString(basename($this->logPath)));
        $this->actingAs($this->programmer)->get('/admin/logs')->assertOk()->assertSee('admin-viewer-test.log');
        $this->actingAs($this->programmer)->get('/admin/logs?l='.$file)->assertOk()->assertSee('Deterministic admin log entry');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function privileged_user_can_download_a_configured_log_file(): void
    {
        $file = urlencode(Crypt::encryptString(basename($this->logPath)));
        $this->actingAs($this->programmer)->get('/admin/logs?dl='.$file)
            ->assertOk()->assertDownload('admin-viewer-test.log');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function restricted_and_guest_users_cannot_access_logs(): void
    {
        $viewer = factory(User::class)->create();
        $this->actingAs($viewer)->get('/admin/logs')->assertForbidden();
        $this->get('/admin/logs')->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function destructive_package_operations_are_disabled(): void
    {
        foreach (['clean', 'del', 'delall'] as $operation) {
            $this->actingAs($this->programmer)->get('/admin/logs?'.$operation.'=invalid')->assertStatus(405);
        }
        $this->assertFileExists($this->logPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function log_entries_are_paginated_in_newest_first_order(): void
    {
        $entries = collect(range(1, 101))->map(
            fn ($number) => sprintf('[2026-09-04 12:%02d:00] testing.INFO: Log entry %03d', $number % 60, $number)
        )->join("\n")."\n";
        File::put($this->logPath, $entries);
        $file = urlencode(Crypt::encryptString(basename($this->logPath)));

        $firstPage = $this->actingAs($this->programmer)->get('/admin/logs?l='.$file)->assertOk();
        $firstPage->assertSee('Log entry 101')->assertDontSee('Log entry 001');
        $firstPage->assertSee('?l='.$file.'&amp;page=2', false);

        $this->actingAs($this->programmer)->get('/admin/logs?l='.$file.'&page=2')
            ->assertOk()->assertSee('Log entry 001')->assertDontSee('Log entry 101');
    }
}
