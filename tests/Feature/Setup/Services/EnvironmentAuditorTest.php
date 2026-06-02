<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Services;

use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Setup\Services\EnvironmentAuditor;

describe('EnvironmentAuditor', function () {
    it('returns an audit report', function () {
        $auditor = new EnvironmentAuditor;
        $report = $auditor->audit();

        expect($report->checks)->not->toBeEmpty();
    });

    it('checks PHP version requirement', function () {
        $auditor = new EnvironmentAuditor;
        $report = $auditor->audit();

        $phpCheck = collect($report->checks)->firstWhere('nameKey', 'php_version');
        expect($phpCheck)->not->toBeNull()
            ->and($phpCheck->status)->toBe(AuditStatus::PASS);
    });

    it('checks critical extensions', function () {
        $auditor = new EnvironmentAuditor;
        $report = $auditor->audit();

        $extChecks = collect($report->checks)->filter(
            fn ($c) => $c->category === AuditCategory::REQUIREMENTS && str_contains($c->nameKey, 'extension')
        );
        expect($extChecks)->not->toBeEmpty();
    });

    it('checks permissions for storage and bootstrap/cache', function () {
        $auditor = new EnvironmentAuditor;
        $report = $auditor->audit();

        $permChecks = collect($report->checks)->filter(
            fn ($c) => $c->category === AuditCategory::PERMISSIONS
        );
        expect($permChecks)->toHaveCount(2);
    });

    it('checks database connection', function () {
        $auditor = new EnvironmentAuditor;
        $report = $auditor->audit();

        $dbCheck = collect($report->checks)->firstWhere('nameKey', 'db_connection');
        expect($dbCheck)->not->toBeNull();
    });
});
