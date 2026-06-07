<?php

declare(strict_types=1);

use App\Core\Data\BaseData;
use App\Data\AuditCheck;
use App\Enums\AuditCategory;
use App\Enums\AuditStatus;

test('audit check can be created', function () {
    $check = new AuditCheck(
        category: AuditCategory::DATABASE,
        nameKey: 'setup.checks.database_connection',
        status: AuditStatus::PASS,
        messageKey: 'setup.checks.database_ok',
    );

    expect($check->category)->toBe(AuditCategory::DATABASE);
    expect($check->nameKey)->toBe('setup.checks.database_connection');
    expect($check->status)->toBe(AuditStatus::PASS);
    expect($check->messageKey)->toBe('setup.checks.database_ok');
    expect($check->nameParams)->toBe([]);
    expect($check->messageParams)->toBe([]);
});

test('audit check accepts optional params', function () {
    $check = new AuditCheck(
        category: AuditCategory::REQUIREMENTS,
        nameKey: 'setup.checks.php_version',
        status: AuditStatus::FAIL,
        messageKey: 'setup.checks.php_version_fail',
        nameParams: ['required' => '8.4', 'current' => '8.3'],
        messageParams: ['version' => '8.3'],
    );

    expect($check->nameParams)->toBe(['required' => '8.4', 'current' => '8.3']);
    expect($check->messageParams)->toBe(['version' => '8.3']);
});

test('audit check extends base data', function () {
    $check = new AuditCheck(
        category: AuditCategory::PERMISSIONS,
        nameKey: 'setup.checks.storage_writable',
        status: AuditStatus::PASS,
        messageKey: 'setup.checks.storage_ok',
    );

    expect($check)->toBeInstanceOf(BaseData::class);
});

test('audit check serializes to array', function () {
    $check = new AuditCheck(
        category: AuditCategory::TERMINAL,
        nameKey: 'setup.checks.terminal_available',
        status: AuditStatus::WARN,
        messageKey: 'setup.checks.terminal_unavailable',
    );

    $array = $check->toArray();

    expect($array['category'])->toBe(AuditCategory::TERMINAL);
    expect($array['nameKey'])->toBe('setup.checks.terminal_available');
    expect($array['status'])->toBe(AuditStatus::WARN);
    expect($array['messageKey'])->toBe('setup.checks.terminal_unavailable');
    expect($array['nameParams'])->toBe([]);
    expect($array['messageParams'])->toBe([]);
});

test('audit check can be hydrated from array', function () {
    $check = AuditCheck::fromArray([
        'category' => AuditCategory::RECOMMENDATIONS,
        'nameKey' => 'setup.checks.https',
        'status' => AuditStatus::PASS,
        'messageKey' => 'setup.checks.https_ok',
    ]);

    expect($check->category)->toBe(AuditCategory::RECOMMENDATIONS);
    expect($check->status)->toBe(AuditStatus::PASS);
});

test('audit check is a readonly class', function () {
    $ref = new ReflectionClass(AuditCheck::class);

    expect($ref->isReadOnly())->toBeTrue();
});
