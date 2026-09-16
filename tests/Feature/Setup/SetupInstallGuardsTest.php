<?php

declare(strict_types=1);

use App\Modules\Core\Enums\AuditCategory;
use App\Modules\Core\Enums\AuditStatus;
use App\Modules\Setup\Entities\SetupEntity;
use App\Modules\SysAdmin\Domain\Observability\Services\EnvironmentAuditor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

describe('8NZAU: installation audit and CLI guards', function (): void {
    test('8NZAU-FR-INST-001: audit gates the PHP floor and every required extension', function (): void {
        $report = app(EnvironmentAuditor::class)->audit();

        $php = collect($report->checks)->firstWhere('nameKey', 'php_version');
        expect($php)->not->toBeNull()
            ->and($php->category)->toBe(AuditCategory::REQUIREMENTS)
            ->and($php->status)->toBe(AuditStatus::PASS)
            ->and($php->messageParams['current'])->toBe(PHP_VERSION)
            ->and($php->nameParams['required'])->toBe(config('setup.requirements.php_version'));

        $required = config('setup.requirements.extensions');
        expect($required)->toHaveCount(12);

        foreach ($required as $extension) {
            $check = collect($report->checks)->first(
                fn ($c) => $c->nameKey === 'extension' && ($c->nameParams['extension'] ?? null) === $extension,
            );
            expect($check)->not->toBeNull("missing platform gate for [{$extension}]");
            expect($check->category->isCritical())->toBeTrue();
        }

        try {
            config()->set('setup.requirements.extensions', [...$required, 'definitely_missing_ext_8nzau']);
            $failed = app(EnvironmentAuditor::class)->audit();

            $bogus = collect($failed->checks)->first(
                fn ($c) => ($c->nameParams['extension'] ?? null) === 'definitely_missing_ext_8nzau',
            );
            expect($bogus->status)->toBe(AuditStatus::FAIL);
            expect($failed->passed())->toBeFalse();
        } finally {
            config()->set('setup.requirements.extensions', $required);
        }
    });

    test('8NZAU-FR-INST-002: advisory checks warn without blocking provisioning', function (): void {
        $recommended = config('setup.requirements.recommended_extensions');

        try {
            config()->set('setup.requirements.recommended_extensions', [...$recommended, 'definitely_missing_ext_8nzau']);
            $report = app(EnvironmentAuditor::class)->audit();

            $bogus = collect($report->checks)->first(
                fn ($c) => ($c->nameParams['extension'] ?? null) === 'definitely_missing_ext_8nzau',
            );
            expect($bogus->status)->toBe(AuditStatus::WARN);
            expect($report->passed())->toBeTrue();

            foreach ($report->checks as $check) {
                if (! $check->category->isCritical()) {
                    expect($check->status)->not->toBe(AuditStatus::FAIL);
                }
            }
        } finally {
            config()->set('setup.requirements.recommended_extensions', $recommended);
        }
    });

    test('8NZAU-FR-INST-003: audit gates directory permission and database connectivity', function (): void {
        $report = app(EnvironmentAuditor::class)->audit();

        $permission = $report->forCategory(AuditCategory::PERMISSIONS);
        expect($permission)->not->toBeEmpty();

        $directories = array_map(fn ($c) => $c->nameParams['directory'] ?? null, $permission);
        expect($directories)->toContain('storage')->toContain('bootstrap/cache');

        foreach ($permission as $check) {
            expect($check->status)->toBe(AuditStatus::PASS);
        }

        $database = $report->forCategory(AuditCategory::DATABASE);
        expect($database)->toHaveCount(1);
        expect($database[0]->status)->toBe(AuditStatus::PASS);
    });

    test('8NZAU-UC-INST-002: audit-alone run provisions nothing and mints no token', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $exit = Artisan::call('setup:install', ['--check-only' => true]);

        expect($exit)->toBe(0);
        expect(Artisan::output())->toContain(__('setup.cli.check_only_complete'));

        Cache::flush();
        expect(SetupEntity::get()->isInstalled())->toBeFalse();
        expect(SetupEntity::get()->hasStoredToken())->toBeFalse();
    });

    test('8NZAU-FR-INST-016, 8NZAU-NFR-INST-005: installed systems and production force-runs are refused', function (): void {
        app()->setLocale('en');
        Cache::flush();

        $exit = Artisan::call('setup:install');

        expect($exit)->toBe(1);
        expect(Artisan::output())->toContain(__('setup.cli.already_installed'));

        $originalEnv = app()->environment();
        app()->instance('env', 'production');

        try {
            $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
            Cache::flush();

            $forced = Artisan::call('setup:install', ['--force' => true]);

            expect($forced)->toBe(1);
            expect(Artisan::output())->toContain(__('setup.cli.force_restricted'));
        } finally {
            app()->instance('env', $originalEnv);
        }

        Cache::flush();
        expect(SetupEntity::get()->isInstalled())->toBeFalse();
        expect(SetupEntity::get()->hasStoredToken())->toBeFalse();
    });

    test('8NZAU-FR-INST-016: force on an installed system re-enters the audit without provisioning', function (): void {
        $exit = Artisan::call('setup:install', ['--force' => true, '--check-only' => true]);

        expect($exit)->toBe(0);

        Cache::flush();
        expect(SetupEntity::get()->isInstalled())->toBeTrue();
        expect(SetupEntity::get()->hasStoredToken())->toBeFalse();
    });
});
