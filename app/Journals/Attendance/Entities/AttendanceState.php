<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Entities;

use App\Core\Entities\BaseEntity;
use App\Journals\Attendance\Enums\AttendanceStatus as AttendanceStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class AttendanceState extends BaseEntity
{
    public function __construct(private ?AttendanceStatusEnum $status, private ?Carbon $clockOut) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
            clockOut: $model->clock_out ? Carbon::parse($model->clock_out) : null,
        );
    }

    public function hasClockOut(): bool
    {
        return $this->clockOut !== null;
    }

    public function isExcused(): bool
    {
        return $this->status?->isExcused() ?? false;
    }
}
