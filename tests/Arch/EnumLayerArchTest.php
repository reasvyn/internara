<?php

declare(strict_types=1);

use App\Enums\Auth\AccountStatus;
use App\Enums\Setup\AuditCategory;
use App\Enums\Shared\AuditStatus;

arch('all enums are string-backed')
    ->expect('App\Enums')
    ->toBeEnums()
    ->toBeStringBackedEnums();

arch('enums implement LabelEnum interface')
    ->expect('App\Enums')
    ->toBeEnums()
    ->toImplement('App\Contracts\Shared\LabelEnum')
    ->ignoring([
        AccountStatus::class,
        AuditStatus::class,
        AuditCategory::class,
    ]);

arch('AccountStatus implements both LabelEnum and ColorableEnum')
    ->expect(AccountStatus::class)
    ->toImplement([
        'App\Contracts\Shared\LabelEnum',
        'App\Contracts\Shared\ColorableEnum',
    ]);
