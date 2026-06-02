<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditCheck', function () {
    it('creates with all properties', function () {
        $check = new AuditCheck(
            category: AuditCategory::REQUIREMENTS,
            nameKey: 'php_version',
            status: AuditStatus::PASS,
            messageKey: 'php_version_ok',
        );

        expect($check->category)->toBe(AuditCategory::REQUIREMENTS)
            ->and($check->nameKey)->toBe('php_version')
            ->and($check->status)->toBe(AuditStatus::PASS)
            ->and($check->messageKey)->toBe('php_version_ok');
    });

    it('creates with optional params', function () {
        $check = new AuditCheck(
            category: AuditCategory::DATABASE,
            nameKey: 'db_connection',
            status: AuditStatus::FAIL,
            messageKey: 'db_fail',
            nameParams: ['driver' => 'sqlite'],
            messageParams: ['error' => 'timeout'],
        );

        expect($check->nameParams)->toBe(['driver' => 'sqlite'])
            ->and($check->messageParams)->toBe(['error' => 'timeout']);
    });

    it('is readonly', function () {
        $ref = new ReflectionClass(AuditCheck::class);

        expect($ref->isReadOnly())->toBeTrue();
    });

    it('converts to array', function () {
        $check = new AuditCheck(
            category: AuditCategory::PERMISSIONS,
            nameKey: 'file_perms',
            status: AuditStatus::WARN,
            messageKey: 'perms_warn',
        );

        $arr = $check->toArray();

        expect($arr['category'])->toBe(AuditCategory::PERMISSIONS)
            ->and($arr['nameKey'])->toBe('file_perms')
            ->and($arr['status'])->toBe(AuditStatus::WARN);
    });
});
