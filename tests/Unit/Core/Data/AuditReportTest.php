<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditReport', function () {
    it('passes when all checks pass', function () {
        $report = new AuditReport(checks: [
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
        ]);

        expect($report->passed())->toBeTrue();
    });

    it('fails when any check fails', function () {
        $report = new AuditReport(checks: [
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::FAIL, 'fail'),
        ]);

        expect($report->passed())->toBeFalse();
    });

    it('passes with only warnings', function () {
        $report = new AuditReport(checks: [
            new AuditCheck(AuditCategory::REQUIREMENTS, 'ext', AuditStatus::WARN, 'warn'),
        ]);

        expect($report->passed())->toBeTrue();
    });

    it('passes with empty checks', function () {
        $report = new AuditReport;

        expect($report->passed())->toBeTrue();
    });

    it('filters checks by category', function () {
        $report = new AuditReport(checks: [
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::PERMISSIONS, 'perm', AuditStatus::FAIL, 'fail'),
            new AuditCheck(AuditCategory::DATABASE, 'conn', AuditStatus::PASS, 'ok'),
        ]);

        $dbChecks = $report->forCategory(AuditCategory::DATABASE);

        expect($dbChecks)->toHaveCount(2)
            ->and($dbChecks[0]->nameKey)->toBe('db');
    });

    it('returns empty array for category with no checks', function () {
        $report = new AuditReport;

        expect($report->forCategory(AuditCategory::TERMINAL))->toBe([]);
    });
});
