<?php

declare(strict_types=1);

use App\Core\Data\AuditCheck;
use App\Core\Data\AuditReport;
use App\Core\Data\BaseData;
use App\Core\Enums\AuditCategory;
use App\Core\Enums\AuditStatus;

test('audit report passes when all checks pass', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
        new AuditCheck(AuditCategory::PERMISSIONS, 'perm', AuditStatus::PASS, 'ok'),
    ]);

    expect($report->passed())->toBeTrue();
});

test('audit report fails when any check fails', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
        new AuditCheck(AuditCategory::PERMISSIONS, 'perm', AuditStatus::FAIL, 'failed'),
        new AuditCheck(AuditCategory::REQUIREMENTS, 'req', AuditStatus::PASS, 'ok'),
    ]);

    expect($report->passed())->toBeFalse();
});

test('audit report passes with warnings only', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::TERMINAL, 'term', AuditStatus::WARN, 'warn'),
        new AuditCheck(AuditCategory::RECOMMENDATIONS, 'rec', AuditStatus::WARN, 'warn'),
    ]);

    expect($report->passed())->toBeTrue();
});

test('audit report passes with empty checks', function () {
    $report = new AuditReport;

    expect($report->passed())->toBeTrue();
});

test('audit report filters by category', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
        new AuditCheck(AuditCategory::PERMISSIONS, 'perm', AuditStatus::FAIL, 'failed'),
        new AuditCheck(AuditCategory::DATABASE, 'db2', AuditStatus::WARN, 'warn'),
    ]);

    $dbChecks = $report->forCategory(AuditCategory::DATABASE);

    expect($dbChecks)->toHaveCount(2);
    expect($dbChecks[0]->nameKey)->toBe('db');
    expect($dbChecks[1]->nameKey)->toBe('db2');
});

test('audit report for category returns empty when none match', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
    ]);

    expect($report->forCategory(AuditCategory::REQUIREMENTS))->toBe([]);
});

test('audit report serializes to array', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::DATABASE, 'db', AuditStatus::PASS, 'ok'),
    ]);

    $array = $report->toArray();

    expect($array)->toHaveKey('checks');
    expect($array['checks'])->toHaveCount(1);
    expect($array['checks'][0])->toBeArray();
    expect($array['checks'][0]['nameKey'])->toBe('db');
});

test('audit report extends base data', function () {
    expect(new AuditReport)->toBeInstanceOf(BaseData::class);
});

test('audit report is a readonly class', function () {
    $ref = new ReflectionClass(AuditReport::class);

    expect($ref->isReadOnly())->toBeTrue();
});
