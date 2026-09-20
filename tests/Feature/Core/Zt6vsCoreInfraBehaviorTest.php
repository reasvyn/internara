<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Actions\LoginAction;
use App\Modules\Auth\Domain\Login\Data\LoginData;
use App\Modules\Auth\Domain\Password\Actions\ConfirmPasswordAction;
use App\Modules\Document\Jobs\GenerateDocumentJob;
use App\Modules\Enrollment\Domain\Registration\Events\StudentRegistered;
use App\Modules\Enrollment\Domain\Registration\Listeners\ClearDashboardOnRegistration;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Setting\Actions\SetSettingAction;
use App\Modules\Setting\Actions\TestMailSettingsAction;
use App\Modules\Setting\Data\SettingData;
use App\Modules\Setting\Services\Settings;
use App\Modules\User\Domain\Notify\TestMailNotification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command;
use Tests\Support\Zt6vsFailingJob;
use Tests\Support\Zt6vsPingJob;

uses(LazilyRefreshDatabase::class);

describe('ZT6VS: core infrastructure runtime behavior', function (): void {
    test('ZT6VS-FR-CORE-001: sqlite connection serves queries with zero external services', function (): void {
        $pdo = DB::connection()->getPdo();

        expect($pdo)->toBeInstanceOf(PDO::class)
            ->and(DB::connection()->getDriverName())->toBe('sqlite');
    });

    test('ZT6VS-UC-CORE-001: fresh install works end to end on the sync queue without daemons', function (): void {
        Zt6vsPingJob::$handled = false;

        try {
            dispatch(new Zt6vsPingJob);

            expect(Zt6vsPingJob::$handled)->toBeTrue()
                ->and(config('queue.default'))->toBe('sync');
        } finally {
            Zt6vsPingJob::$handled = false;
        }
    });

    test('ZT6VS-FR-CORE-004: created models receive ordered UUID v7 primary keys', function (): void {
        $first = User::factory()->create();
        $second = User::factory()->create();

        foreach ([$first->id, $second->id] as $id) {
            expect($id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
        }

        expect($first->id)->not->toBe($second->id)
            ->and(strcmp($first->id, $second->id))->toBeLessThan(0);
    });

    test('ZT6VS-FR-CORE-007: high-traffic tables carry composite indexes', function (): void {
        $columnsOf = fn (string $table): array => collect(Schema::getIndexes($table))
            ->map(fn ($index) => $index['columns'])
            ->all();

        expect($columnsOf('registrations'))->toContain(['student_id', 'status'])
            ->and($columnsOf('backups'))->toContain(['status', 'created_at'])
            ->and($columnsOf('placements'))->toContain(['company_id', 'internship_id']);
    });

    test('ZT6VS-FR-CORE-036: tier-0 no-regret guarantees hold at any scale', function (): void {
        $user = User::factory()->create();

        expect($user->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');

        $registry = require config_path('cache-keys.php');

        expect(count($registry))->toBeGreaterThanOrEqual(25);

        $registrationIndexes = collect(Schema::getIndexes('registrations'))
            ->map(fn ($index) => $index['columns'])
            ->all();

        expect($registrationIndexes)->toContain(['student_id', 'status']);
    });

    test('ZT6VS-UC-CORE-002: setting update invalidates the registry cache and renders fresh', function (): void {
        $action = app(SetSettingAction::class);
        $action->execute(new SettingData(key: 'zt6vs.probe_flag', value: 'v1', group: 'zt6vs'));

        Settings::all();
        expect(Cache::get(config('cache-keys.settings_all')))->not->toBeNull();

        $action->execute(new SettingData(key: 'zt6vs.probe_flag', value: 'v2', group: 'zt6vs'));

        expect(Cache::get(config('cache-keys.settings_all')))->toBeNull()
            ->and(Settings::all()->get('zt6vs.probe_flag'))->toBe('v2')
            ->and(setting('zt6vs.probe_flag'))->toBe('v2');
    });

    test('ZT6VS-FR-CORE-013: domain listener clears the dashboard stat key on registration events', function (): void {
        $listener = app(ClearDashboardOnRegistration::class);
        $key = config('cache-keys.admin_dashboard_stats');

        Cache::put($key, ['stale' => true], 600);

        $listener->handle(new StudentRegistered(
            registration: Registration::factory()->make(),
        ));

        expect(Cache::get($key))->toBeNull();
    });

    test('ZT6VS-UC-CORE-003: deployment cache warming exists behind the command and exits clean', function (): void {
        $before = glob(base_path('bootstrap/cache/*.php')) ?: [];

        try {
            $exit = Artisan::call('system:cache-warm');

            expect($exit)->toBe(Command::SUCCESS);
        } finally {
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('event:clear');

            foreach ((glob(base_path('bootstrap/cache/*.php')) ?: []) as $file) {
                if (! in_array($file, $before, true)) {
                    @unlink($file);
                }
            }
        }
    });

    test('ZT6VS-FR-CORE-014: framework cache bake steps run inside the warming command', function (): void {
        $before = glob(base_path('bootstrap/cache/*.php')) ?: [];

        try {
            expect(Artisan::call('system:cache-warm'))->toBe(Command::SUCCESS);

            // The nested config/view/event bake steps leave their manifests behind,
            // proving the deploy-time bake ran instead of being skipped.
            $manifests = glob(base_path('bootstrap/cache/*.php')) ?: [];

            expect($manifests)->not->toBeEmpty();
        } finally {
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('event:clear');

            foreach ((glob(base_path('bootstrap/cache/*.php')) ?: []) as $file) {
                if (! in_array($file, $before, true)) {
                    @unlink($file);
                }
            }
        }
    });

    test('ZT6VS-FR-CORE-019: session id rotates on login and logout', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $beforeLogin = session()->getId();

        app(LoginAction::class)->execute(new LoginData(identifier: $user->email, password: 'secret-123'));

        expect(session()->getId())->not->toBe($beforeLogin);

        $beforeLogout = session()->getId();
        $tokenBefore = session()->token();

        $this->actingAs($user)->post(route('logout'), ['_token' => $tokenBefore])->assertRedirect(route('login'));

        expect(session()->getId())->not->toBe($beforeLogout)
            ->and(session()->token())->not->toBe($tokenBefore);

        $this->assertGuest();
    });

    test('ZT6VS-FR-CORE-021: session carries auth state and confirmation timestamps', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();

        app(LoginAction::class)->execute(new LoginData(identifier: $user->email, password: 'secret-123'));

        $loginKeys = array_values(array_filter(
            array_keys(session()->all()),
            fn ($key) => str_starts_with((string) $key, 'login_'),
        ));

        expect($loginKeys)->not->toBeEmpty();

        $response = app(ConfirmPasswordAction::class)->execute($user, 'secret-123');

        expect($response->success)->toBeTrue()
            ->and(session('auth.password_confirmed_at'))->toBeInt()
            ->and(session('auth.password_confirmed_at'))->toBeLessThanOrEqual(time());
    });

    test('ZT6VS-FR-CORE-024: queue tables ship with migrations for the database driver', function (): void {
        expect(Schema::hasTable('jobs'))->toBeTrue()
            ->and(Schema::hasTable('failed_jobs'))->toBeTrue()
            ->and(Schema::hasTable('job_batches'))->toBeTrue();

        $columns = collect(Schema::getColumns('failed_jobs'))->map(fn ($column) => $column['name'])->all();

        expect($columns)->toContain('exception', 'payload', 'queue');
    });

    test('ZT6VS-FR-CORE-025: failed database jobs keep the full exception trace', function (): void {
        config(['queue.default' => 'database']);

        dispatch(new Zt6vsFailingJob);

        Artisan::call('queue:work', ['--once' => true]);

        $this->assertDatabaseHas('failed_jobs', ['queue' => 'default']);

        $row = DB::table('failed_jobs')->where('queue', 'default')->orderByDesc('failed_at')->first();

        expect($row->exception)->toContain('zt6vs probe failure');
    });

    test('ZT6VS-FR-CORE-026: document jobs dispatch onto the documents pipeline', function (): void {
        Queue::fake();

        GenerateDocumentJob::dispatch('0196test-document-id');

        Queue::assertPushedOn('documents', GenerateDocumentJob::class);

        expect((new GenerateDocumentJob('0196test-document-id'))->queue)->toBe('documents');
    });

    test('ZT6VS-FR-CORE-029: smtp probe succeeds with reachable setting and restores runtime config', function (): void {
        Notification::fake();
        config(['mail.mailers.smtp.host' => 'sentinel-host']);

        $result = app(TestMailSettingsAction::class)->execute('probe@sekolah.id', [
            'host' => 'smtp.sekolah.id',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'admin',
            'password' => 's3cret',
            'from_address' => 'noreply@sekolah.id',
            'from_name' => 'SMK',
        ]);

        expect($result)->toBeTrue()
            ->and(config('mail.mailers.smtp.host'))->toBe('sentinel-host');

        Notification::assertSentTimes(TestMailNotification::class, 1);
    });

    test('ZT6VS-FR-CORE-029: smtp probe failure returns false instead of throwing', function (): void {
        config(['mail.default' => 'zt6vs-broken-mailer']);

        $result = app(TestMailSettingsAction::class)->execute('probe@sekolah.id', ['host' => 'smtp.sekolah.id']);

        expect($result)->toBeFalse();
    });

    test('ZT6VS-FR-CORE-032: public disk writes land under the linked storage path', function (): void {
        $path = 'zt6vs-probe.txt';

        try {
            Storage::disk('public')->put($path, 'probe-content');

            expect(file_exists(storage_path('app/public/'.$path)))->toBeTrue()
                ->and(Storage::disk('public')->get($path))->toBe('probe-content')
                ->and(Storage::url($path))->toContain('/storage/');
        } finally {
            Storage::disk('public')->delete($path);
        }
    });

    test('ZT6VS-UC-CORE-005, ZT6VS-FR-CORE-030: user uploads flow through the media library on a configured disk', function (): void {
        $user = User::factory()->create();
        $media = null;

        try {
            $media = $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 400, 400))->toMediaCollection('avatar');

            $expectedDisk = config('media-library.disk_name');

            $this->assertDatabaseHas('media', [
                'model_type' => User::class,
                'model_id' => $user->id,
                'collection_name' => 'avatar',
                'disk' => $expectedDisk,
            ]);

            expect(file_exists($media->getPath()))->toBeTrue()
                ->and(file_exists($media->getPath('thumb')))->toBeTrue();
        } finally {
            $media?->delete();
        }
    });

    test('ZT6VS-FR-CORE-034, ZT6VS-NFR-CORE-006: raw disk writes never carry user uploads — the library owns the path', function (): void {
        $user = User::factory()->create();
        $media = null;

        try {
            $media = $user->addMedia(UploadedFile::fake()->image('evidence.jpg', 400, 400))->toMediaCollection('avatar');

            expect($media->getPath())->toStartWith(Storage::disk($media->disk)->path(''))
                ->and($media->disk)->toBe(config('media-library.disk_name'));
        } finally {
            $media?->delete();
        }
    });

    test('ZT6VS-FR-CORE-035, ZT6VS-NFR-CORE-006: avatar collection declares its conversions on the owning model', function (): void {
        $user = User::factory()->create();
        $media = null;

        try {
            $media = $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 400, 400))->toMediaCollection('avatar');

            expect($media->disk)->toBe(config('media-library.disk_name'))
                ->and($media->hasGeneratedConversion('thumb'))->toBeTrue()
                ->and(file_exists($media->getPath('thumb')))->toBeTrue();
        } finally {
            $media?->delete();
        }
    });

    test('ZT6VS-NFR-CORE-004: cache miss falls through to fresh database data', function (): void {
        $warm = Settings::all();

        expect($warm->isNotEmpty())->toBeTrue();

        Cache::flush();

        $fresh = Settings::all();

        expect($fresh->isNotEmpty())->toBeTrue()
            ->and($fresh->toArray())->toBe($warm->toArray())
            ->and(setting('setup.is_installed', skipCache: true))->not->toBeNull();
    });

    test('ZT6VS-UC-CORE-006: setting reads survive a cold cache without user-facing errors', function (): void {
        Cache::flush();

        expect(Settings::all()->isNotEmpty())->toBeTrue()
            ->and(setting('setup.is_installed'))->not->toBeNull();
    });

    test('ZT6VS-FR-CORE-043: health surface report service status and the up endpoint answers', function (): void {
        Artisan::call('system:health', ['--json' => true]);

        $rows = json_decode(Artisan::output(), true);

        expect($rows)->toBeArray()
            ->and(count($rows))->toBeGreaterThanOrEqual(10);

        $appKey = collect($rows)->first(fn ($row) => str_contains((string) ($row[2] ?? ''), 'Application key'));

        expect($appKey)->not->toBeNull()
            ->and($appKey[1])->toBe('OK');

        $this->get('/up')->assertOk();
    });

    test('ZT6VS-FR-CORE-041: live application key is a non-empty base64 value', function (): void {
        expect((string) config('app.key'))->toStartWith('base64:')
            ->and(strlen((string) config('app.key')))->toBeGreaterThan(16);
    });
});
