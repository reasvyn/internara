<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditReport', function () {
    it('reports passed when no failing checks', function () {
        $report = new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
        ]);

        expect($report->passed())->toBeTrue();
    });

    it('reports failed when any check fails', function () {
        $report = new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::FAIL, 'fail'),
        ]);

        expect($report->passed())->toBeFalse();
    });

    it('filters by category', function () {
        $report = new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::TERMINAL, 'term', AuditStatus::PASS, 'ok'),
        ]);

        $dbChecks = $report->forCategory(AuditCategory::DATABASE);

        expect($dbChecks)->toHaveCount(1)
            ->and($dbChecks[0]->nameKey)->toBe('db');
    });

    it('passes with empty checks', function () {
        $report = new AuditReport([]);

        expect($report->passed())->toBeTrue();
    });
});
