<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditReport', function () {
    it('passes when no checks fail', function () {
        $report = new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php', AuditStatus::PASS, 'ok'),
            new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
        ]);

        expect($report->passed())->toBeTrue();
    });

    it('fails when any check fails', function () {
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

        expect($report->forCategory(AuditCategory::DATABASE))->toHaveCount(1)
            ->and($report->forCategory(AuditCategory::REQUIREMENTS))->toHaveCount(1);
    });

    it('passes with empty checks', function () {
        expect((new AuditReport([]))->passed())->toBeTrue();
    });
});
