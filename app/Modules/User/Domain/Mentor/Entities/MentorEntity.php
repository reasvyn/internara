<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Mentor\Entities;

use App\Modules\Core\Entities\BaseEntity;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final readonly class MentorEntity extends BaseEntity
{
    public function __construct(
        private string $registrationId,
        private Collection $mentors,
        private ?Carbon $supervisorLastActiveAt = null,
        private int $inactivityHours = 48,
    ) {}

    public static function fromModel(Model $model): static
    {
        $mentors = $model->relationLoaded('mentors')
            ? $model->mentors
            : $model->mentors()->get();

        $supervisorLastActiveAt = $model->getAttribute('supervisor_last_active_at');
        if (is_string($supervisorLastActiveAt)) {
            $supervisorLastActiveAt = Carbon::parse($supervisorLastActiveAt);
        }

        $inactivityHours = $model->getAttribute('proxy_inactivity_hours');

        return new self(
            registrationId: $model->id,
            mentors: $mentors,
            supervisorLastActiveAt: $supervisorLastActiveAt instanceof Carbon ? $supervisorLastActiveAt : null,
            inactivityHours: $inactivityHours !== null ? (int) $inactivityHours : 48,
        );
    }

    public function withSupervisorActivity(?Carbon $lastActiveAt, ?int $inactivityHours = null): static
    {
        $updated = $this->with('supervisorLastActiveAt', $lastActiveAt);

        if ($inactivityHours !== null) {
            $updated = $updated->with('inactivityHours', $inactivityHours);
        }

        return $updated;
    }

    // ---- Role Queries ----

    public function isTeacher(User $user): bool
    {
        return $this->mentors->contains(
            fn (User $m) => $m->id === $user->id && $m->hasRole('teacher'),
        );
    }

    public function isSupervisor(User $user): bool
    {
        return $this->mentors->contains(
            fn (User $m) => $m->id === $user->id && $m->hasRole('supervisor'),
        );
    }

    public function isMentor(User $user): bool
    {
        return $this->mentors->contains(fn (User $m) => $m->id === $user->id);
    }

    // ---- Cross-Role Proxy ----

    public function canProxyAsSupervisor(User $user, ?Carbon $now = null): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if (! ($user->hasRole('teacher') && $this->isTeacher($user))) {
            return false;
        }

        if ($this->supervisorLastActiveAt !== null) {
            $currentTime = $now ?? Carbon::now();
            $threshold = $currentTime->copy()->subHours($this->inactivityHours);

            if ($this->supervisorLastActiveAt->isAfter($threshold)) {
                return false;
            }
        }

        return true;
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

    /**
     * Resolve proxy metadata for activity logging (T4B26-FR-CRP6, FR-RBAC-021).
     *
     * @return array{proxy_role?: string, proxy_reason?: string}
     */
    public function resolveProxyMetadata(User $actor, string $targetRole): array
    {
        if ($targetRole === 'supervisor') {
            if ($this->isSupervisor($actor) && $actor->hasRole('supervisor')) {
                return [];
            }

            if ($actor->hasRole('teacher') && $this->isTeacher($actor)) {
                return [
                    'proxy_role' => 'supervisor',
                    'proxy_reason' => 'mentor-proxy-for-supervisor',
                ];
            }

            if ($actor->hasRole('admin') || $actor->hasRole('super_admin')) {
                return [
                    'proxy_role' => 'supervisor',
                    'proxy_reason' => 'admin-proxy-for-supervisor',
                ];
            }
        }

        if ($targetRole === 'teacher') {
            if ($this->isTeacher($actor) && $actor->hasRole('teacher')) {
                return [];
            }

            if ($actor->hasRole('admin') || $actor->hasRole('super_admin')) {
                return [
                    'proxy_role' => 'teacher',
                    'proxy_reason' => 'admin-proxy-for-teacher',
                ];
            }
        }

        return [];
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

    public function supervisorLastActiveAt(): ?Carbon
    {
        return $this->supervisorLastActiveAt;
    }

    public function inactivityHours(): int
    {
        return $this->inactivityHours;
    }
}
