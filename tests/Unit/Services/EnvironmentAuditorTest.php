<?php

declare(strict_types=1);

use App\Data\Audit\AuditCheck;
use App\Data\Audit\AuditReport;
use App\Enums\Setup\AuditCategory;
use App\Enums\Shared\AuditStatus;
use App\Services\Setup\EnvironmentAuditor;

it('audit returns an AuditReport', function () {
    $report = (new EnvironmentAuditor)->audit();

    expect($report)->toBeInstanceOf(AuditReport::class);
});

it('audit contains audit checks', function () {
    $report = (new EnvironmentAuditor)->audit();

    expect($report->checks)->not->toBeEmpty();
});

it('audit checks have the correct structure', function () {
    $report = (new EnvironmentAuditor)->audit();

    foreach ($report->checks as $check) {
        expect($check)->toBeInstanceOf(AuditCheck::class)
            ->and($check->category)->toBeInstanceOf(AuditCategory::class)
            ->and($check->status)->toBeInstanceOf(AuditStatus::class)
            ->and($check->nameKey)->toBeString()->not->toBeEmpty()
            ->and($check->messageKey)->toBeString()->not->toBeEmpty();
    }
});

it('audit contains checks from all categories', function () {
    $report = (new EnvironmentAuditor)->audit();
    $categories = array_map(fn ($c) => $c->category->value, $report->checks);

    expect($categories)->toContain(AuditCategory::Requirements->value)
        ->toContain(AuditCategory::Permissions->value)
        ->toContain(AuditCategory::Database->value)
        ->toContain(AuditCategory::Terminal->value);
});

it('php version check passes in current environment', function () {
    $report = (new EnvironmentAuditor)->audit();

    $phpChecks = $report->forCategory(AuditCategory::Requirements);
    $phpVersion = array_values(
        array_filter($phpChecks, fn ($c) => $c->nameKey === 'php_version'),
    );

    expect($phpVersion)->not->toBeEmpty();
    expect($phpVersion[0]->status)->toBe(AuditStatus::Pass);
});

it('pdo extension check passes', function () {
    $report = (new EnvironmentAuditor)->audit();

    $reqChecks = $report->forCategory(AuditCategory::Requirements);
    $pdoCheck = array_values(
        array_filter($reqChecks, fn ($c) => $c->nameParams === ['extension' => 'pdo']),
    );

    expect($pdoCheck)->not->toBeEmpty();
    expect($pdoCheck[0]->status)->toBe(AuditStatus::Pass);
});

it('database connection check passes in test environment', function () {
    $report = (new EnvironmentAuditor)->audit();

    $dbChecks = $report->forCategory(AuditCategory::Database);

    expect($dbChecks)->not->toBeEmpty();
    expect($dbChecks[0]->status)->toBe(AuditStatus::Pass);
});

it('permissions check passes in test environment', function () {
    $report = (new EnvironmentAuditor)->audit();

    $permChecks = $report->forCategory(AuditCategory::Permissions);

    expect($permChecks)->not->toBeEmpty();
    foreach ($permChecks as $check) {
        expect($check->status)->toBe(AuditStatus::Pass);
    }
});

it('passed returns true when all checks pass', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::Requirements, 'test', AuditStatus::Pass, 'pass_msg'),
        new AuditCheck(AuditCategory::Database, 'test', AuditStatus::Pass, 'pass_msg'),
    ]);

    expect($report->passed())->toBeTrue();
});

it('passed returns false when any check fails', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::Requirements, 'test', AuditStatus::Pass, 'pass_msg'),
        new AuditCheck(AuditCategory::Database, 'test', AuditStatus::Fail, 'fail_msg'),
    ]);

    expect($report->passed())->toBeFalse();
});

it('passed returns true with only warnings', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::Terminal, 'test', AuditStatus::Warn, 'warn_msg'),
    ]);

    expect($report->passed())->toBeTrue();
});

it('forCategory filters checks by category', function () {
    $report = new AuditReport([
        new AuditCheck(AuditCategory::Requirements, 'php', AuditStatus::Pass, 'ok'),
        new AuditCheck(AuditCategory::Database, 'db', AuditStatus::Pass, 'ok'),
        new AuditCheck(AuditCategory::Requirements, 'ext', AuditStatus::Pass, 'ok'),
    ]);

    $requirements = $report->forCategory(AuditCategory::Requirements);

    expect($requirements)->toHaveCount(2);
});

it('recommended extensions use warn status when missing', function () {
    $report = (new EnvironmentAuditor)->audit();

    $recChecks = $report->forCategory(AuditCategory::Recommendations);

    expect($recChecks)->not->toBeEmpty();
    foreach ($recChecks as $check) {
        expect($check->status)->toBeIn([AuditStatus::Pass, AuditStatus::Warn]);
    }
});
