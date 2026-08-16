<?php

declare(strict_types=1);

use App\Core\Data\AuditCheck;
use App\Core\Data\AuditReport;
use App\Core\Enums\AuditCategory;
use App\Core\Enums\AuditStatus;

function auditCheck(AuditCategory $category, AuditStatus $status): AuditCheck
{
    return new AuditCheck(
        category: $category,
        nameKey: 'setup.wizard.check',
        status: $status,
        messageKey: 'setup.wizard.check_message',
    );
}

test('SE5Q9-FR-D2: passed() is true when no check has failed', function () {
    $report = new AuditReport([
        auditCheck(AuditCategory::REQUIREMENTS, AuditStatus::PASS),
        auditCheck(AuditCategory::DATABASE, AuditStatus::WARN),
    ]);

    expect($report->passed())->toBeTrue();
});

test('SE5Q9-FR-D2: passed() is false when a check has failed', function () {
    $report = new AuditReport([
        auditCheck(AuditCategory::REQUIREMENTS, AuditStatus::PASS),
        auditCheck(AuditCategory::DATABASE, AuditStatus::FAIL),
    ]);

    expect($report->passed())->toBeFalse();
});

test('SE5Q9-FR-D2: forCategory() filters checks by category', function () {
    $report = new AuditReport([
        auditCheck(AuditCategory::REQUIREMENTS, AuditStatus::PASS),
        auditCheck(AuditCategory::DATABASE, AuditStatus::FAIL),
        auditCheck(AuditCategory::DATABASE, AuditStatus::WARN),
    ]);

    $database = $report->forCategory(AuditCategory::DATABASE);

    expect($database)->toHaveCount(2);
    expect($report->forCategory(AuditCategory::TERMINAL))->toBe([]);
});
