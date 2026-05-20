<?php

declare(strict_types=1);

use App\Domain\Attendance\Enums\AbsenceReasonType;
use App\Domain\Attendance\Enums\AbsenceRequestStatus;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

arch('LabelEnum is an interface')
    ->expect(LabelEnum::class)
    ->toBeInterface();

arch('StatusEnum is an interface')
    ->expect(StatusEnum::class)
    ->toBeInterface();

arch('AccountStatus implements LabelEnum and StatusEnum')
    ->expect(AccountStatus::class)
    ->toImplement(LabelEnum::class)
    ->toImplement(StatusEnum::class);

arch('Role implements LabelEnum')
    ->expect(Role::class)
    ->toImplement(LabelEnum::class);

arch('AttendanceStatus implements LabelEnum and StatusEnum')
    ->expect(AttendanceStatus::class)
    ->toImplement(LabelEnum::class)
    ->toImplement(StatusEnum::class);

arch('AbsenceRequestStatus implements LabelEnum and StatusEnum')
    ->expect(AbsenceRequestStatus::class)
    ->toImplement(LabelEnum::class)
    ->toImplement(StatusEnum::class);

arch('AbsenceReasonType implements LabelEnum')
    ->expect(AbsenceReasonType::class)
    ->toImplement(LabelEnum::class);
