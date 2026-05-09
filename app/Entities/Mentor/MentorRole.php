<?php

declare(strict_types=1);

namespace App\Entities\Mentor;

use App\Entities\BaseEntity;
use App\Enums\Auth\Role;
use App\Models\Mentor;
use Illuminate\Database\Eloquent\Model;

final readonly class MentorRole extends BaseEntity
{
    public function __construct(
        private string $type,
    ) {}

    public static function fromModel(Model $model): static
    {
        assert($model instanceof Mentor);

        return new self(
            type: $model->type,
        );
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isSchoolTeacher(): bool
    {
        return $this->type === Mentor::TYPE_SCHOOL_TEACHER;
    }

    public function isIndustrySupervisor(): bool
    {
        return $this->type === Mentor::TYPE_INDUSTRY_SUPERVISOR;
    }

    public function role(): Role
    {
        return match ($this->type) {
            Mentor::TYPE_SCHOOL_TEACHER => Role::TEACHER,
            Mentor::TYPE_INDUSTRY_SUPERVISOR => Role::SUPERVISOR,
        };
    }

    public function canVerifyAttendance(): bool
    {
        return $this->isSchoolTeacher();
    }

    public function canVerifyLogbook(): bool
    {
        return $this->isSchoolTeacher();
    }

    public function canGradeSubmission(): bool
    {
        return $this->isSchoolTeacher();
    }

    public function canFinalizeAssessment(): bool
    {
        return $this->isSchoolTeacher();
    }

    public function canVerifySupervisionLog(): bool
    {
        return $this->isSchoolTeacher();
    }

    public function canCreateSupervisionLog(): bool
    {
        return true;
    }

    public function canCreateSchedule(): bool
    {
        return $this->isSchoolTeacher();
    }
}
