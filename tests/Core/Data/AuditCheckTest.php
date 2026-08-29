<?php

declare(strict_types=1);

use App\Modules\Core\Data\AuditCheck;
use App\Modules\Core\Enums\AuditCategory;
use App\Modules\Core\Enums\AuditStatus;

test('SE5Q9-FR-D1: exposes the defined DTO shape', function () {
    $check = new AuditCheck(
        category: AuditCategory::REQUIREMENTS,
        nameKey: 'setup.wizard.requirements',
        status: AuditStatus::PASS,
        messageKey: 'setup.wizard.check_ok',
        nameParams: ['field' => 'name'],
        messageParams: ['value' => 42],
    );

    $data = $check->toArray();

    expect($data['category'])->toBe(AuditCategory::REQUIREMENTS);
    expect($data['nameKey'])->toBe('setup.wizard.requirements');
    expect($data['status'])->toBe(AuditStatus::PASS);
    expect($data['messageKey'])->toBe('setup.wizard.check_ok');
    expect($data['nameParams'])->toBe(['field' => 'name']);
    expect($data['messageParams'])->toBe(['value' => 42]);
});

test('SE5Q9-FR-D1: can be reconstructed from an array with defaults', function () {
    $check = AuditCheck::fromArray([
        'category' => AuditCategory::DATABASE,
        'nameKey' => 'setup.wizard.database',
        'status' => AuditStatus::WARN,
        'messageKey' => 'setup.wizard.check_warn',
    ]);

    expect($check->category)->toBe(AuditCategory::DATABASE);
    expect($check->nameParams)->toBe([]);
    expect($check->messageParams)->toBe([]);
});
