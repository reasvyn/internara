<?php

declare(strict_types=1);

namespace App\Guidance\Mentor\Entities;

use App\Core\Entities\BaseEntity;
use App\User\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final readonly class MentorRole extends BaseEntity
{
    public const string TYPE_SCHOOL_TEACHER = 'school_teacher';

    public const string TYPE_INDUSTRY_SUPERVISOR = 'industry_supervisor';

    public function __construct(
        private string $type,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            type: $model->getAttribute('type') ?? '',
        );
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isSchoolTeacher(): bool
    {
        return $this->type === self::TYPE_SCHOOL_TEACHER;
    }

    public function isIndustrySupervisor(): bool
    {
        return $this->type === self::TYPE_INDUSTRY_SUPERVISOR;
    }

    public function role(): Role
    {
        return match ($this->type) {
            self::TYPE_SCHOOL_TEACHER => Role::TEACHER,
            self::TYPE_INDUSTRY_SUPERVISOR => Role::SUPERVISOR,
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
