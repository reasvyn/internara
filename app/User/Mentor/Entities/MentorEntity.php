<?php

declare(strict_types=1);

namespace App\User\Mentor\Entities;

use App\Core\Entities\BaseEntity;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final readonly class MentorEntity extends BaseEntity
{
    public function __construct(
        private string $registrationId,
        private Collection $mentors,
    ) {}

    public static function fromModel(Model $model): static
    {
        $mentors = $model->relationLoaded('mentors')
            ? $model->mentors
            : $model->mentors()->get();

        return new self(
            registrationId: $model->id,
            mentors: $mentors,
        );
    }

    // ---- Role Queries ----

    public function isTeacher(User $user): bool
    {
        return $this->mentors->contains(
            fn (User $m) => $m->id === $user->id && ($m->pivot->role ?? null) === 'teacher',
        );
    }

    public function isSupervisor(User $user): bool
    {
        return $this->mentors->contains(
            fn (User $m) => $m->id === $user->id && ($m->pivot->role ?? null) === 'supervisor',
        );
    }

    public function isMentor(User $user): bool
    {
        return $this->mentors->contains($user->id);
    }

    // ---- Cross-Role Proxy ----

    public function canProxyAsSupervisor(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('teacher') && $this->isTeacher($user)) {
            return true;
        }

        return false;
    }

    public function canProxyAsTeacher(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    // ---- Domain Capabilities (proxy-aware) ----

    public function canVerifyLogbook(User $user): bool
    {
        if ($this->isSupervisor($user)) {
            return true;
        }

        return $this->canProxyAsSupervisor($user);
    }

    public function canScoreCompetency(User $user, string $evaluatorRole): bool
    {
        if ($evaluatorRole === 'teacher' && $this->isTeacher($user)) {
            return true;
        }

        if ($evaluatorRole === 'supervisor' && $this->isSupervisor($user)) {
            return true;
        }

        if ($evaluatorRole === 'supervisor' && $this->canProxyAsSupervisor($user)) {
            return true;
        }

        if ($this->canProxyAsTeacher($user)) {
            return true;
        }

        return false;
    }

    public function canReviewSupervisionLog(User $user): bool
    {
        if ($this->isSupervisor($user)) {
            return true;
        }

        return $this->canProxyAsSupervisor($user);
    }

    public function canGradeSubmission(User $user): bool
    {
        if ($this->isTeacher($user)) {
            return true;
        }

        return $this->canProxyAsTeacher($user);
    }

    public function canVerifyAttendance(User $user): bool
    {
        if ($this->isTeacher($user)) {
            return true;
        }

        return $this->canProxyAsTeacher($user);
    }

    // ---- Accessors ----

    public function registrationId(): string
    {
        return $this->registrationId;
    }

    public function mentors(): Collection
    {
        return $this->mentors;
    }
}
