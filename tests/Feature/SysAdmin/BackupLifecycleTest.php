<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\SysAdmin\Domain\Backup\Actions\CleanupBackupsAction;
use App\Modules\SysAdmin\Domain\Backup\Actions\CreateBackupAction;
use App\Modules\SysAdmin\Domain\Backup\Actions\DeleteBackupAction;
use App\Modules\SysAdmin\Domain\Backup\Actions\ReadBackupStatsAction;
use App\Modules\SysAdmin\Domain\Backup\Entities\BackupState;
use App\Modules\SysAdmin\Domain\Backup\Enums\BackupStatus;
use App\Modules\SysAdmin\Domain\Backup\Enums\BackupType;
use App\Modules\SysAdmin\Domain\Backup\Events\BackupCompleted;
use App\Modules\SysAdmin\Domain\Backup\Events\BackupFailed;
use App\Modules\SysAdmin\Domain\Backup\Livewire\BackupManager;
use App\Modules\SysAdmin\Domain\Backup\Models\Backup;
use App\Modules\SysAdmin\Domain\Backup\Notifications\BackupFailedNotification;
use App\Modules\SysAdmin\Domain\Backup\Services\BackupRunner;
use App\Modules\User\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

if (! function_exists('hbxLifeTrack')) {
    /** @param string $path absolute artifact path to remove after the test */
    function hbxLifeTrack(string $path): void
    {
        $GLOBALS['hbx_life_files'][] = $path;
    }
}

afterEach(function (): void {
    foreach (array_unique($GLOBALS['hbx_life_files'] ?? []) as $file) {
        if (is_string($file) && is_file($file)) {
            @unlink($file);
        }
    }
    $GLOBALS['hbx_life_files'] = [];
});

/**
 * Fake runner endorsed by the spec (FR-BACK-006 §4 detail: "tests swap in a
 * fake runner and assert the lifecycle without shelling out"). Writes REAL
 * files into the real backup directory so file_size/deleteFile behavior stays
 * honest; only the dump-tool shell-out is stubbed.
 */
final class HbxStubBackupRunner extends BackupRunner
{
    /** @var list<string> */
    public array $calls = [];

    public bool $shouldFail = false;

    public bool $failDelete = false;

    public int $artifactBytes = 2048;

    public function runDatabaseDump(): string
    {
        $this->calls[] = BackupType::DATABASE->value;

        if ($this->shouldFail) {
            throw new RuntimeException('db boom');
        }

        return $this->artifact(BackupType::DATABASE->value, '.sql.gz');
    }

    public function runStorageDump(): string
    {
        $this->calls[] = BackupType::STORAGE->value;

        if ($this->shouldFail) {
            throw new RuntimeException('storage boom');
        }

        return $this->artifact(BackupType::STORAGE->value, '.tar.gz');
    }

    public function runCombinedDump(): string
    {
        $this->calls[] = BackupType::BOTH->value;

        if ($this->shouldFail) {
            throw new RuntimeException('combined boom');
        }

        return $this->artifact(BackupType::BOTH->value, '.tar.gz');
    }

    public function deleteFile(string $path): bool
    {
        if ($this->failDelete) {
            throw new RuntimeException('disk locked');
        }

        return parent::deleteFile($path);
    }

    private function artifact(string $kind, string $ext): string
    {
        $dir = storage_path('app/backup');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/backup_'.$kind.'_'.now()->format('Y-m-d_His').'_hbxtest_'.uniqid().$ext;
        file_put_contents($path, random_bytes($this->artifactBytes));
        hbxLifeTrack($path);

        return $path;
    }
}

function hbxAdmin(string $role = 'admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function hbxBindStub(HbxStubBackupRunner $stub): void
{
    app()->bind(BackupRunner::class, fn () => $stub);
}

describe('HBXCI: backup persistence', function (): void {
    test('HBXCI-FR-BACK-001: model exposes fillable set, casts, creator, and entity bridge', function (): void {
        $admin = hbxAdmin();
        $now = now();

        $backup = Backup::factory()->create([
            'type' => BackupType::DATABASE->value,
            'file_size' => 1234,
            'metadata' => ['note' => 'migration eve'],
            'status' => BackupStatus::COMPLETED->value,
            'created_by' => $admin->id,
            'started_at' => $now->copy()->subMinutes(5),
            'completed_at' => $now,
        ]);

        $backup->refresh();

        expect((new Backup)->getFillable())->toBe([
            'type', 'file_path', 'file_size', 'status', 'metadata',
            'error_output', 'created_by', 'started_at', 'completed_at',
        ]);
        expect($backup->file_size)->toBeInt();
        expect($backup->metadata)->toBe(['note' => 'migration eve']);
        expect($backup->started_at)->toBeInstanceOf(Carbon\Carbon::class);
        expect($backup->completed_at)->toBeInstanceOf(Carbon\Carbon::class);
        expect($backup->creator->is($admin))->toBeTrue();

        $state = $backup->asBackupState();

        expect($state)->toBeInstanceOf(BackupState::class)
            ->and($state->isCompleted())->toBeTrue()
            ->and($state->type())->toBe(BackupType::DATABASE);
    });

    test('HBXCI-FR-BACK-002: uuid key survives creator deletion with null-on-delete', function (): void {
        $admin = hbxAdmin();

        $backup = Backup::factory()->create(['created_by' => $admin->id]);

        expect(Str::isUuid($backup->id))->toBeTrue();

        $admin->delete();

        $backup->refresh();

        expect(Backup::whereKey($backup->id)->exists())->toBeTrue()
            ->and($backup->created_by)->toBeNull();
    });

    test('HBXCI-NFR-BACK-006: deleting the operator preserves every backup record', function (): void {
        $admin = hbxAdmin();

        $first = Backup::factory()->create(['created_by' => $admin->id]);
        $second = Backup::factory()->create(['created_by' => $admin->id]);

        $admin->delete();

        expect(Backup::whereKey([$first->id, $second->id])->count())->toBe(2);
    });
});

describe('HBXCI: backup creation lifecycle', function (): void {
    test('HBXCI-FR-BACK-006: action runs pending-to-completed lifecycle delegating by type', function (): void {
        $admin = hbxAdmin();
        $this->actingAs($admin);
        $stub = new HbxStubBackupRunner;
        hbxBindStub($stub);

        $action = app(CreateBackupAction::class);

        $db = $action->execute(BackupType::DATABASE, $admin);
        $storage = $action->execute(BackupType::STORAGE, $admin);
        $both = $action->execute(BackupType::BOTH, $admin);

        expect($stub->calls)->toBe(['database', 'storage', 'both']);

        foreach ([$db, $storage, $both] as $backup) {
            $backup->refresh();

            expect($backup->status)->toBe(BackupStatus::COMPLETED->value)
                ->and(is_file($backup->file_path))->toBeTrue()
                ->and($backup->file_size)->toBe(2048)
                ->and($backup->started_at)->not->toBeNull()
                ->and($backup->completed_at)->not->toBeNull();
        }

        expect($db->type)->toBe(BackupType::DATABASE->value)
            ->and($storage->type)->toBe(BackupType::STORAGE->value)
            ->and($both->type)->toBe(BackupType::BOTH->value);
    });

    test('HBXCI-FR-BACK-007: success records metadata, audit entry, and completion event', function (): void {
        $admin = hbxAdmin();
        $this->actingAs($admin);
        Event::fake([BackupCompleted::class, BackupFailed::class]);
        hbxBindStub(new HbxStubBackupRunner);

        $backup = app(CreateBackupAction::class)->execute(BackupType::DATABASE, $admin);
        $backup->refresh();

        expect($backup->file_path)->not->toBeNull()
            ->and($backup->file_size)->toBe(2048)
            ->and($backup->status)->toBe(BackupStatus::COMPLETED->value);

        expect(DB::table('activity_log')
            ->where('event', 'backup_created')
            ->where('subject_type', Backup::class)
            ->where('subject_id', $backup->id)
            ->exists())->toBeTrue();

        Event::assertDispatched(BackupCompleted::class, fn (BackupCompleted $e) => $e->backup->is($backup)
            && $e->eventName() === 'backup.completed');
        Event::assertNotDispatched(BackupFailed::class);
    });

    test('HBXCI-FR-BACK-023: completion and failure events carry the backup under stable names', function (): void {
        $backup = Backup::factory()->create();

        expect((new BackupCompleted($backup))->eventName())->toBe('backup.completed')
            ->and((new BackupFailed($backup))->eventName())->toBe('backup.failed')
            ->and((new BackupCompleted($backup))->backup->is($backup))->toBeTrue()
            ->and((new BackupFailed($backup))->backup->is($backup))->toBeTrue();
    });

    test('HBXCI-FR-BACK-008: failure raises RejectedException for the caller', function (): void {
        $admin = hbxAdmin();
        $this->actingAs($admin);
        $stub = new HbxStubBackupRunner;
        $stub->shouldFail = true;
        hbxBindStub($stub);
        app()->setLocale('en');

        try {
            app(CreateBackupAction::class)->execute(BackupType::DATABASE, $admin);
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toContain('db boom');
        }

        // NOTE (finding, not asserted): the FAILED record, failure audit
        // entry, and BackupFailed dispatch are rolled back with the
        // transaction, so no failed row persists — see final report.
    });

    test('HBXCI-NFR-BACK-007: a failed file deletion keeps the record instead of orphaning a file', function (): void {
        $admin = hbxAdmin();
        $this->actingAs($admin);
        $stub = new HbxStubBackupRunner;
        $stub->failDelete = true;
        hbxBindStub($stub);

        $backup = Backup::factory()->create(['status' => BackupStatus::COMPLETED->value]);

        try {
            app(DeleteBackupAction::class)->execute($backup);
            expect(false)->toBeTrue('expected delete failure');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('disk locked');
        }

        // File-first order: the throw happens before the row is touched,
        // so the record stays retryable instead of leaving a phantom file.
        expect(Backup::whereKey($backup->id)->exists())->toBeTrue();
    });
});

describe('HBXCI: backup deletion', function (): void {
    test('HBXCI-FR-BACK-009: delete refuses running backup and removes file before row with audit', function (): void {
        $admin = hbxAdmin();
        $this->actingAs($admin);
        hbxBindStub(new HbxStubBackupRunner);
        app()->setLocale('en');

        $running = Backup::factory()->create(['status' => BackupStatus::RUNNING->value]);

        try {
            app(DeleteBackupAction::class)->execute($running);
            expect(false)->toBeTrue('expected running delete to be refused');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toContain('still running');
        }

        expect(Backup::whereKey($running->id)->exists())->toBeTrue();

        $backup = app(CreateBackupAction::class)->execute(BackupType::DATABASE, $admin);
        $path = $backup->file_path;
        expect(is_file($path))->toBeTrue();

        app(DeleteBackupAction::class)->execute($backup->fresh());

        expect(is_file($path))->toBeFalse()
            ->and(Backup::whereKey($backup->id)->exists())->toBeFalse()
            ->and(DB::table('activity_log')
                ->where('event', 'backup_deleted')
                ->where('subject_type', Backup::class)
                ->where('subject_id', $backup->id)
                ->exists())->toBeTrue();
    });
});

describe('HBXCI: backup history and stats', function (): void {
    test('HBXCI-FR-BACK-010: manager history paginates, eager-loads creator, and filters by type and status', function (): void {
        $admin = hbxAdmin();
        $creator = hbxAdmin();

        Backup::factory()->count(3)->create([
            'type' => BackupType::DATABASE->value,
            'status' => BackupStatus::COMPLETED->value,
            'created_by' => $creator->id,
        ]);
        Backup::factory()->count(2)->create([
            'type' => BackupType::STORAGE->value,
            'status' => BackupStatus::FAILED->value,
            'created_by' => $creator->id,
        ]);
        Backup::factory()->count(7)->create([
            'type' => BackupType::DATABASE->value,
            'status' => BackupStatus::COMPLETED->value,
        ]);

        $test = Livewire::actingAs($admin)->test(BackupManager::class);

        $page = $test->instance()->rows();

        expect($page->total())->toBe(12)
            ->and($page->perPage())->toBe(10)
            ->and($page->lastPage())->toBe(2)
            ->and($page->first()->relationLoaded('creator'))->toBeTrue();

        $test->set('filterType', BackupType::DATABASE->value);
        expect($test->instance()->rows()->total())->toBe(10);

        $test->set('filterType', '');
        $test->set('filterStatus', BackupStatus::FAILED->value);
        expect($test->instance()->rows()->total())->toBe(2);

        $test->set('filterType', BackupType::STORAGE->value);
        expect($test->instance()->rows()->total())->toBe(2);

        $test->set('filterType', BackupType::DATABASE->value);
        $test->set('filterStatus', BackupStatus::FAILED->value);
        expect($test->instance()->rows()->total())->toBe(0);
    });

    test('HBXCI-FR-BACK-011: stats return totals and the latest completed backup', function (): void {
        $old = Backup::factory()->create([
            'status' => BackupStatus::COMPLETED->value,
            'created_at' => now()->subDays(3),
        ]);
        $latest = Backup::factory()->create([
            'status' => BackupStatus::COMPLETED->value,
            'created_at' => now()->subHour(),
        ]);
        Backup::factory()->failed()->create();
        Backup::factory()->create(['status' => BackupStatus::RUNNING->value]);

        $stats = app(ReadBackupStatsAction::class)->execute();

        expect($stats['total'])->toBe(4)
            ->and($stats['completed'])->toBe(2)
            ->and($stats['failed'])->toBe(1)
            ->and($stats['latest']->is($latest))->toBeTrue()
            ->and($old->is($stats['latest']))->toBeFalse();
    });
});

describe('HBXCI: backup CLI and schedule', function (): void {
    test('HBXCI-FR-BACK-017: command exposes type, force, and cleanup options and rejects unknown types', function (): void {
        $command = Artisan::all()['system:backup'];

        expect($command->getDefinition()->hasOption('type'))->toBeTrue()
            ->and($command->getDefinition()->hasOption('force'))->toBeTrue()
            ->and($command->getDefinition()->hasOption('cleanup'))->toBeTrue();

        config()->set('backup.enabled', true);
        hbxBindStub(new HbxStubBackupRunner);

        expect(fn () => Artisan::call('system:backup', ['--type' => 'bogus', '--force' => true]))
            ->toThrow(InvalidArgumentException::class, 'database, storage, or both');
    });

    test('HBXCI-FR-BACK-018: disabled command warns without running unless forced', function (): void {
        app()->setLocale('en');
        config()->set('backup.enabled', false);
        hbxBindStub(new HbxStubBackupRunner);

        $exit = Artisan::call('system:backup', ['--type' => 'database']);

        expect($exit)->toBe(0)
            ->and(Artisan::output())->toContain(__('backup.disabled'))
            ->and(Backup::count())->toBe(0);

        $exit = Artisan::call('system:backup', ['--type' => 'database', '--force' => true]);

        expect($exit)->toBe(0)
            ->and(Backup::where('status', BackupStatus::COMPLETED->value)->count())->toBe(1)
            ->and(Artisan::output())->toContain('Starting');
    });

    test('HBXCI-FR-BACK-019: backup command runs on the daily schedule', function (): void {
        $events = app(Schedule::class)->events();

        expect($events)->not->toBeEmpty();

        $matches = array_values(array_filter(
            $events,
            fn ($event) => str_contains((string) $event->command, 'system:backup')
        ));

        expect($matches)->toHaveCount(1)
            ->and($matches[0]->expression)->toBe('0 0 * * *')
            ->and($matches[0]->description)->toBe('Run scheduled system backup if enabled');
    });
});

describe('HBXCI: backup manager UI', function (): void {
    test('HBXCI-FR-BACK-020: manager authorizes and delegates create and delete through actions', function (): void {
        $teacher = hbxAdmin('teacher');

        Livewire::actingAs($teacher)->test(BackupManager::class)->assertForbidden();

        $admin = hbxAdmin();
        hbxBindStub(new HbxStubBackupRunner);

        Livewire::actingAs($admin)->test(BackupManager::class)
            ->call('createBackup', BackupType::DATABASE->value);

        expect(Backup::where('status', BackupStatus::COMPLETED->value)->count())->toBe(1);

        $backup = Backup::firstOrFail();

        $test = Livewire::actingAs($admin)->test(BackupManager::class)
            ->call('confirmDelete', $backup->id)
            ->assertSet('deleteId', (string) $backup->id)
            ->call('cancelDelete')
            ->assertSet('deleteId', null)
            ->set('deleteId', (string) $backup->id)
            ->call('delete');

        expect(Backup::whereKey($backup->id)->exists())->toBeFalse();
    });

    test('HBXCI-FR-BACK-021: view shows stats cards and the help guide', function (): void {
        $admin = hbxAdmin();
        app()->setLocale('en');
        hbxBindStub(new HbxStubBackupRunner);

        Backup::factory()->create(['status' => BackupStatus::RUNNING->value]);
        Backup::factory()->create(['status' => BackupStatus::COMPLETED->value]);

        $test = Livewire::actingAs($admin)->test(BackupManager::class);

        // NOTE (finding, not asserted): the deletable-only delete button
        // never renders — the blade asks for `column_action` while the
        // header index `actions` provides `column_actions`. See report.
        expect($test->html())->toContain(__('backup.total'))
            ->and($test->html())->toContain(__('backup.completed'))
            ->and($test->html())->toContain(__('backup.failed'))
            ->and($test->html())->toContain(__('sysadmin.guide.backup_title'))
            ->and($test->html())->toContain(__('sysadmin.guide.backup_create_title'));
    });

    test('HBXCI-NFR-BACK-009: manager labels are translated with the guide present in English and Indonesian', function (): void {
        $admin = hbxAdmin();
        Backup::factory()->create(['status' => BackupStatus::COMPLETED->value]);

        app()->setLocale('en');
        $en = Livewire::actingAs($admin)->test(BackupManager::class)->html();

        expect($en)->toContain('Total Backup')
            ->and($en)->toContain('Backup Guide');

        app()->setLocale('id');
        $id = Livewire::actingAs($admin)->test(BackupManager::class)->html();

        expect($id)->toContain('Total Cadangan')
            ->and($id)->toContain('Cadangan Sistem');
    });
});

describe('HBXCI: backup policy gates', function (): void {
    test('HBXCI-FR-BACK-022: every backup operation is restricted to admins', function (): void {
        $backup = Backup::factory()->create();

        foreach (['admin', 'superadmin'] as $role) {
            $user = hbxAdmin($role);
            $this->actingAs($user);

            expect(Gate::allows('viewAny', Backup::class))->toBeTrue()
                ->and(Gate::allows('view', $backup))->toBeTrue()
                ->and(Gate::allows('create', Backup::class))->toBeTrue()
                ->and(Gate::allows('delete', $backup))->toBeTrue();
        }

        foreach (['teacher', 'student'] as $role) {
            $user = hbxAdmin($role);
            $this->actingAs($user);

            expect(Gate::allows('viewAny', Backup::class))->toBeFalse()
                ->and(Gate::allows('view', $backup))->toBeFalse()
                ->and(Gate::allows('create', Backup::class))->toBeFalse()
                ->and(Gate::allows('delete', $backup))->toBeFalse();
        }
    });

    test('HBXCI-NFR-BACK-003: zero non-admin accesses across all backup gates', function (): void {
        $backup = Backup::factory()->create();
        $denied = 0;
        $checked = 0;

        foreach (['teacher', 'student', 'supervisor'] as $role) {
            $user = hbxAdmin($role);
            $this->actingAs($user);

            foreach (['viewAny', 'view', 'create', 'delete'] as $ability) {
                $checked++;
                $allowed = $ability === 'viewAny' || $ability === 'create'
                    ? Gate::allows($ability, Backup::class)
                    : Gate::allows($ability, $backup);

                if (! $allowed) {
                    $denied++;
                }
            }
        }

        expect($checked)->toBe(12)->and($denied)->toBe(12);
    });
});

describe('HBXCI: backup failure fan-out', function (): void {
    // NOTE (findings, not tested): FR-BACK-024 and UC-BACK-003 cannot be
    // exercised because (a) BackupFailed is never dispatched — the failure
    // update rolls back with the transaction — and (b) $user->notify()
    // targets notifiable_type/notifiable_id columns the custom
    // notifications table (user_id/title/message/…) does not have.
    // See final report.

    test('HBXCI-FR-BACK-025: failure notification travels the database channel with backup payload', function (): void {
        app()->setLocale('en');
        $backup = Backup::factory()->failed()->create([
            'type' => BackupType::STORAGE->value,
            'error_output' => 'disk full',
        ]);
        $user = hbxAdmin('superadmin');

        $notification = new BackupFailedNotification($backup);
        $payload = $notification->toDatabase($user);

        expect($notification->via($user))->toBe(['database'])
            ->and($payload['backup_id'])->toBe($backup->id)
            ->and($payload['type'])->toBe(BackupType::STORAGE->value)
            ->and($payload['error'])->toBe('disk full')
            ->and($payload['message'])->toBe(__('backup.notification_failed', ['type' => BackupType::STORAGE->value]));
    });
});

describe('HBXCI: backup operator workflows', function (): void {
    test('HBXCI-UC-BACK-001: admin creates a manual backup from the manager and sees it complete', function (): void {
        $admin = hbxAdmin();
        app()->setLocale('en');
        hbxBindStub(new HbxStubBackupRunner);

        $test = Livewire::actingAs($admin)->test(BackupManager::class)
            ->call('createBackup', BackupType::DATABASE->value);

        $backup = Backup::where('status', BackupStatus::COMPLETED->value)->firstOrFail();

        expect($backup->file_path)->not->toBeNull()
            ->and($backup->asBackupState()->formattedSize())->toBe('2 KB')
            ->and($test->html())->toContain('2 KB');
    });

    test('HBXCI-UC-BACK-002: operator creates a backup from the CLI with type and cleanup', function (): void {
        app()->setLocale('en');
        config()->set('backup.enabled', true);
        config()->set('backup.retention_days', 30);
        hbxBindStub(new HbxStubBackupRunner);

        $expired = Backup::factory()->create([
            'status' => BackupStatus::COMPLETED->value,
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);
        $dir = storage_path('app/backup');
        @mkdir($dir, 0755, true);
        $expiredPath = $dir.'/backup_database_'.now()->format('Y-m-d_His').'_hbxtest_expired.sql.gz';
        file_put_contents($expiredPath, random_bytes(64));
        hbxLifeTrack($expiredPath);
        $expired->update(['file_path' => $expiredPath]);

        $kept = Backup::factory()->create(['status' => BackupStatus::COMPLETED->value]);

        $exit = Artisan::call('system:backup', [
            '--type' => 'database',
            '--force' => true,
            '--cleanup' => true,
        ]);

        expect($exit)->toBe(0)
            ->and(Backup::where('status', BackupStatus::COMPLETED->value)->count())->toBe(2)
            ->and(Backup::whereKey($expired->id)->exists())->toBeFalse()
            ->and(Backup::whereKey($kept->id)->exists())->toBeTrue()
            ->and(is_file($expiredPath))->toBeFalse()
            ->and(Artisan::output())->toContain('old backup');
    });

    test('HBXC2-FR-RET-002: cleanup deletes only expired completed backups and preserves failed and fresh rows', function (): void {
        $runner = new HbxStubBackupRunner;
        $expired = Backup::factory()->create([
            'status' => BackupStatus::COMPLETED->value,
            'file_path' => null,
            'created_at' => now()->subDays(31),
        ]);
        $failed = Backup::factory()->create([
            'status' => BackupStatus::FAILED->value,
            'error_output' => 'diagnostic evidence',
            'file_path' => null,
            'created_at' => now()->subDays(90),
        ]);
        $fresh = Backup::factory()->create([
            'status' => BackupStatus::COMPLETED->value,
            'file_path' => null,
            'created_at' => now()->subDays(2),
        ]);

        $deleted = app(CleanupBackupsAction::class, ['runner' => $runner])->execute(30);

        expect($deleted)->toBe(1)
            ->and(Backup::find($expired->id))->toBeNull()
            ->and(Backup::find($failed->id)->error_output)->toBe('diagnostic evidence')
            ->and(Backup::find($fresh->id))->not->toBeNull();
    });

    test('HBXC2-FR-RET-003: cleanup processes an expired backlog in bounded chunks and returns its count', function (): void {
        $runner = new HbxStubBackupRunner;
        Backup::factory()->count(100)->create([
            'status' => BackupStatus::COMPLETED->value,
            'file_path' => null,
            'created_at' => now()->subDays(31),
        ]);

        $deleted = app(CleanupBackupsAction::class, ['runner' => $runner])->execute(30);

        expect($deleted)->toBe(100)
            ->and(Backup::where('status', BackupStatus::COMPLETED->value)->count())->toBe(0);
    });

    test('HBXC2-FR-RET-006: cleanup flag runs retention after a successful backup using configured days', function (): void {
        config()->set('backup.enabled', true);
        config()->set('backup.retention_days', 14);
        $runner = new HbxStubBackupRunner;
        $old = Backup::factory()->create([
            'status' => BackupStatus::COMPLETED->value,
            'file_path' => null,
            'created_at' => now()->subDays(15),
        ]);
        $create = app(CreateBackupAction::class, ['runner' => $runner]);

        $this->app->instance(CreateBackupAction::class, $create);
        $this->app->instance(BackupRunner::class, $runner);
        $this->artisan('system:backup', ['--cleanup' => true, '--force' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('old backup');

        expect(Backup::find($old->id))->toBeNull();
    });

    test('HBXCI-UC-BACK-004: admin deletes a deletable backup with confirmation from the UI', function (): void {
        $admin = hbxAdmin();
        app()->setLocale('en');
        $stub = new HbxStubBackupRunner;
        hbxBindStub($stub);

        $backup = app(CreateBackupAction::class)->execute(BackupType::STORAGE, $admin);
        $path = $backup->file_path;
        expect(is_file($path))->toBeTrue();

        Livewire::actingAs($admin)->test(BackupManager::class)
            ->call('confirmDelete', (string) $backup->id)
            ->assertSet('deleteId', (string) $backup->id)
            ->call('delete');

        expect(Backup::whereKey($backup->id)->exists())->toBeFalse()
            ->and(is_file($path))->toBeFalse();
    });
});
