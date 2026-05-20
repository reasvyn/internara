<?php

declare(strict_types=1);

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditCheck', function () {
    it('creates with required properties', function () {
        $check = new AuditCheck(
            category: AuditCategory::Requirements,
            nameKey: 'php_version',
            status: AuditStatus::Pass,
            messageKey: 'php_version_pass',
        );

        expect($check->category)->toBe(AuditCategory::Requirements)
            ->and($check->status)->toBe(AuditStatus::Pass)
            ->and($check->nameParams)->toBe([]);
    });

    it('extends Data base class for toArray support', function () {
        $check = new AuditCheck(
            category: AuditCategory::Database,
            nameKey: 'db_check',
            status: AuditStatus::Fail,
            messageKey: 'db_fail',
            nameParams: ['driver' => 'sqlite'],
            messageParams: ['error' => 'connection refused'],
        );

        $array = $check->toArray();

        expect($array)->toHaveKeys(['category', 'nameKey', 'status', 'messageKey', 'nameParams', 'messageParams'])
            ->and($array['status'])->toBeInstanceOf(AuditStatus::class);
    });
});
