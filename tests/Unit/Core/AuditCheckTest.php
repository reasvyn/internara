<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditCheck', function () {
    it('creates with required properties', function () {
        $check = new AuditCheck(
            category: AuditCategory::REQUIREMENTS,
            nameKey: 'php_version',
            status: AuditStatus::PASS,
            messageKey: 'php_version_pass',
        );

        expect($check->category)->toBe(AuditCategory::REQUIREMENTS)
            ->and($check->status)->toBe(AuditStatus::PASS)
            ->and($check->nameParams)->toBe([]);
    });

    it('extends Data base class for toArray support', function () {
        $check = new AuditCheck(
            category: AuditCategory::DATABASE,
            nameKey: 'db_check',
            status: AuditStatus::FAIL,
            messageKey: 'db_fail',
            nameParams: ['driver' => 'sqlite'],
            messageParams: ['error' => 'connection refused'],
        );

        $array = $check->toArray();

        expect($array)->toHaveKeys(['category', 'nameKey', 'status', 'messageKey', 'nameParams', 'messageParams'])
            ->and($array['status'])->toBeInstanceOf(AuditStatus::class);
    });
});
