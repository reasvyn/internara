<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Setup\Services\EnvironmentAuditor;

beforeEach(function () {
    $this->auditor = app(EnvironmentAuditor::class);
});

describe('EnvironmentAuditor', function () {
    it('returns an AuditReport', function () {
        $report = $this->auditor->audit();

        expect($report)->toBeInstanceOf(AuditReport::class);
    });

    it('checks PHP version', function () {
        $report = $this->auditor->audit();
        $checks = $report->forCategory(AuditCategory::REQUIREMENTS);

        $phpCheck = collect($checks)->first(fn ($c) => $c->nameKey === 'php_version');

        expect($phpCheck)->not->toBeNull()
            ->and($phpCheck->status)->toBe(AuditStatus::PASS);
    });

    it('checks required extensions', function () {
        $report = $this->auditor->audit();
        $checks = $report->forCategory(AuditCategory::REQUIREMENTS);

        $extChecks = collect($checks)->filter(fn ($c) => $c->nameKey === 'extension');

        expect($extChecks)->not->toBeEmpty()
            ->and($extChecks->every(fn ($c) => $c->status === AuditStatus::PASS))->toBeTrue();
    });

    it('checks storage and cache permissions', function () {
        $report = $this->auditor->audit();
        $checks = $report->forCategory(AuditCategory::PERMISSIONS);

        expect($checks)->toHaveCount(2);

        foreach ($checks as $check) {
            expect($check->status)->toBe(AuditStatus::PASS);
        }
    });

    it('checks database connection', function () {
        $report = $this->auditor->audit();
        $checks = $report->forCategory(AuditCategory::DATABASE);

        expect($checks)->toHaveCount(1)
            ->and($checks[0]->status)->toBe(AuditStatus::PASS);
    });

    it('detects fail when database credentials are forge defaults', function () {
        config(['database.connections.sqlite.username' => 'forge']);

        $report = $this->auditor->audit();
        $checks = $report->forCategory(AuditCategory::DATABASE);

        expect($checks[0]->status)->toBe(AuditStatus::FAIL);
    });

    it('reports terminal support checks', function () {
        $report = $this->auditor->audit();
        $checks = $report->forCategory(AuditCategory::TERMINAL);

        expect($checks)->toHaveCount(2);
    });

    it('warns when frontend assets are not built', function () {
        $manifestPath = public_path('build/manifest.json');
        $originalExists = file_exists($manifestPath);

        if ($originalExists) {
            rename($manifestPath, $manifestPath.'.bak');
        }

        $report = $this->auditor->audit();
        $checks = $report->forCategory(AuditCategory::RECOMMENDATIONS);

        $assetCheck = collect($checks)->first(fn ($c) => $c->nameKey === 'frontend_assets');

        expect($assetCheck->status)->toBe(AuditStatus::WARN);

        if ($originalExists) {
            rename($manifestPath.'.bak', $manifestPath);
        }
    });

    it('report passes when no critical checks fail', function () {
        $report = $this->auditor->audit();

        expect($report->passed())->toBeTrue();
    });

    it('report fails when a critical check fails', function () {
        config(['database.connections.sqlite.username' => 'forge']);

        $report = $this->auditor->audit();

        expect($report->passed())->toBeFalse();
    });
});
