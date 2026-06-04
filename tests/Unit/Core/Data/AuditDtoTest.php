<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Data\BaseData;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

test('AuditCheck is a BaseData DTO', function () {
    $check = new AuditCheck(
        category: AuditCategory::DATABASE,
        nameKey: 'audit.db.connection',
        status: AuditStatus::PASS,
        messageKey: 'audit.db.connection_ok',
    );

    expect($check)->toBeInstanceOf(BaseData::class);
    expect($check->category)->toBe(AuditCategory::DATABASE);
    expect($check->status)->toBe(AuditStatus::PASS);
});

test('AuditCheck toArray returns all properties', function () {
    $check = new AuditCheck(
        category: AuditCategory::DATABASE,
        nameKey: 'audit.db.connection',
        status: AuditStatus::FAIL,
        messageKey: 'audit.db.connection_fail',
        nameParams: ['driver' => 'mysql'],
        messageParams: ['host' => 'localhost'],
    );

    $array = $check->toArray();
    expect($array['category'])->toBe(AuditCategory::DATABASE);
    expect($array['status'])->toBe(AuditStatus::FAIL);
    expect($array['nameParams'])->toBe(['driver' => 'mysql']);
});

test('AuditReport aggregates checks and determines pass/fail', function () {
    $checks = [
        new AuditCheck(AuditCategory::DATABASE, 'db.conn', AuditStatus::PASS, 'ok'),
        new AuditCheck(AuditCategory::PERMISSIONS, 'perm.storage', AuditStatus::PASS, 'ok'),
    ];
    $report = new AuditReport($checks);

    expect($report->passed())->toBeTrue();
});

test('AuditReport fails when any check fails', function () {
    $checks = [
        new AuditCheck(AuditCategory::DATABASE, 'db.conn', AuditStatus::PASS, 'ok'),
        new AuditCheck(AuditCategory::PERMISSIONS, 'perm.storage', AuditStatus::FAIL, 'fail'),
    ];
    $report = new AuditReport($checks);

    expect($report->passed())->toBeFalse();
});

test('AuditReport forCategory filters checks by category', function () {
    $checks = [
        new AuditCheck(AuditCategory::REQUIREMENTS, 'req.php', AuditStatus::PASS, 'ok'),
        new AuditCheck(AuditCategory::DATABASE, 'db.conn', AuditStatus::FAIL, 'fail'),
        new AuditCheck(AuditCategory::PERMISSIONS, 'perm.storage', AuditStatus::PASS, 'ok'),
    ];
    $report = new AuditReport($checks);

    $reqChecks = $report->forCategory(AuditCategory::REQUIREMENTS);
    expect($reqChecks)->toHaveCount(1);
    expect($reqChecks[0]->category)->toBe(AuditCategory::REQUIREMENTS);

    $dbChecks = $report->forCategory(AuditCategory::DATABASE);
    expect($dbChecks)->toHaveCount(1);
    expect($dbChecks[0]->status)->toBe(AuditStatus::FAIL);
});
