<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditReport', function () {
    it('reports passed when no failing checks', function () {
        $report = new AuditReport([
            new AuditCheck(AuditCategory::Requirements, 'php', AuditStatus::Pass, 'ok'),
            new AuditCheck(AuditCategory::Database, 'db', AuditStatus::Pass, 'ok'),
        ]);

        expect($report->passed())->toBeTrue();
    });

    it('reports failed when any check fails', function () {
        $report = new AuditReport([
            new AuditCheck(AuditCategory::Requirements, 'php', AuditStatus::Pass, 'ok'),
            new AuditCheck(AuditCategory::Database, 'db', AuditStatus::Fail, 'fail'),
        ]);

        expect($report->passed())->toBeFalse();
    });

    it('filters by category', function () {
        $report = new AuditReport([
            new AuditCheck(AuditCategory::Requirements, 'php', AuditStatus::Pass, 'ok'),
            new AuditCheck(AuditCategory::Database, 'db', AuditStatus::Pass, 'ok'),
            new AuditCheck(AuditCategory::Terminal, 'term', AuditStatus::Pass, 'ok'),
        ]);

        $dbChecks = $report->forCategory(AuditCategory::Database);

        expect($dbChecks)->toHaveCount(1)
            ->and($dbChecks[0]->nameKey)->toBe('db');
    });

    it('passes with empty checks', function () {
        $report = new AuditReport([]);

        expect($report->passed())->toBeTrue();
    });
});
