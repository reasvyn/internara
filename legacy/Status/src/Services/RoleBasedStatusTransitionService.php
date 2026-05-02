<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;

/**
 * RoleBasedStatusTransitionService
 *
 * Simple, practical role-based status transition manager.
 * No over-engineering - just handles 5 roles with clear, intuitive flows.
 *
 * **Role Workflows (Indonesian style - praktis & instan):**
 *
 * STUDENT (Pelajar):
 *   PENDING → ACTIVATED (auto atau self-claim)
 *   ACTIVATED → VERIFIED (teacher confirm) / RESTRICTED / SUSPENDED
 *
 * TEACHER (Pengajar):
 *   PENDING → ACTIVATED (auto)
 *   ACTIVATED → VERIFIED (admin confirm) / RESTRICTED / SUSPENDED
 *   Can verify students, suspend/restrict students
 *
 * MENTOR (Pembimbing):
 *   PENDING → ACTIVATED (auto)
 *   ACTIVATED → VERIFIED (admin confirm) / RESTRICTED / SUSPENDED
 *   Can restrict/suspend students only
 *
 * ADMIN (Administrator):
 *   PENDING → ACTIVATED (auto)
 *   ACTIVATED → VERIFIED (superadmin confirm)
 *   Can manage all, except SuperAdmin
 *
 * SUPER_ADMIN (Super Administrator):
 *   PROTECTED (immutable, created as-is)
 *   Cannot be downgraded, modified, or deleted
 */
class RoleBasedStatusTransitionService
{
    private AccountAuditLogger $auditLogger;

    public function __construct(AccountAuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Get all valid status transitions for a user's role
     */
    public function getValidTransitionsForRole(User $user): array
    {
        $currentStatus = $user->latestStatus()?->name;
        if (! $currentStatus) {
            $currentStatus = Status::PENDING->value;
        }

        $enum = Status::from($currentStatus);
        $roleBaseTransitions = $this->getRoleBasedTransitionRules($user);

        // Filter to only role-appropriate transitions
        $validTransitions = [];
        foreach ($enum->validTransitions() as $nextStatus) {
            if ($this->isTransitionAllowedForRole($user, $enum, $nextStatus)) {
                $validTransitions[] = $nextStatus;
            }
        }

        return $validTransitions;
    }

    /**
     * Check if a specific role can perform a transition
     */
    public function canTransition(
        User $user,
        Status $fromStatus,
        Status $toStatus,
        ?User $triggeredBy = null,
    ): bool {
        $triggeredBy = $triggeredBy ?? auth()->user();

        // 1. Check valid transition exists in state machine
        if (! $fromStatus->canTransitionTo($toStatus)) {
            return false;
        }

        // 2. Check role-based rules
        if (! $this->isTransitionAllowedForRole($user, $fromStatus, $toStatus)) {
            return false;
        }

        // 3. Check permission to perform transition
        if (! $this->hasPermissionToTransition($user, $fromStatus, $toStatus, $triggeredBy)) {
            return false;
        }

        return true;
    }

    /**
     * Perform a status transition with validation
     */
    public function transition(
        User $user,
        Status $toStatus,
        string $reason = '',
        ?User $triggeredBy = null,
    ): bool {
        $triggeredBy = $triggeredBy ?? auth()->user();
        $fromStatus = Status::from($user->latestStatus()?->name ?? Status::PENDING->value);

        // Validate transition
        if (! $this->canTransition($user, $fromStatus, $toStatus, $triggeredBy)) {
            throw new \Exception(
                "Transisi tidak diizinkan: {$fromStatus->value} → {$toStatus->value} untuk role {$user->getHighestRole()}",
            );
        }

        // Perform transition using spatie
        try {
            $user->setStatus($toStatus->value, $reason ?: "Diubah dari {$fromStatus->label()}");

            // Log to audit
            $this->auditLogger->log(
                user: $user,
                event: 'status_transitioned',
                metadata: [
                    'from_status' => $fromStatus->value,
                    'to_status' => $toStatus->value,
                    'reason' => $reason,
                    'triggered_by' => $triggeredBy->email,
                    'user_role' => $user->getHighestRole(),
                ],
            );

            Log::info(
                "Status transition: {$user->email} ({$user->getHighestRole()}) {$fromStatus->value} → {$toStatus->value}",
            );

            return true;
        } catch (\Exception $e) {
            Log::error("Transition failed for {$user->email}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Get all transition rules for a role
     */
    private function getRoleBasedTransitionRules(User $user): array
    {
        $role = $user->getHighestRole();

        return match ($role) {
            'student' => [
                Status::PENDING => [Status::ACTIVATED, Status::SUSPENDED],
                Status::ACTIVATED => [Status::VERIFIED, Status::RESTRICTED, Status::SUSPENDED],
                Status::VERIFIED => [Status::RESTRICTED, Status::SUSPENDED, Status::INACTIVE],
                Status::RESTRICTED => [Status::VERIFIED, Status::SUSPENDED],
                Status::SUSPENDED => [Status::RESTRICTED, Status::INACTIVE],
                Status::INACTIVE => [Status::ARCHIVED],
            ],
            'teacher' => [
                Status::PENDING => [Status::ACTIVATED],
                Status::ACTIVATED => [Status::VERIFIED],
                Status::VERIFIED => [Status::RESTRICTED, Status::SUSPENDED, Status::INACTIVE],
                Status::RESTRICTED => [Status::VERIFIED, Status::SUSPENDED],
                Status::SUSPENDED => [Status::INACTIVE],
                Status::INACTIVE => [Status::ARCHIVED],
            ],
            'mentor' => [
                Status::PENDING => [Status::ACTIVATED],
                Status::ACTIVATED => [Status::VERIFIED],
                Status::VERIFIED => [Status::RESTRICTED, Status::SUSPENDED, Status::INACTIVE],
                Status::RESTRICTED => [Status::VERIFIED, Status::SUSPENDED],
                Status::SUSPENDED => [Status::INACTIVE],
                Status::INACTIVE => [Status::ARCHIVED],
            ],
            'admin' => [
                Status::PENDING => [Status::ACTIVATED],
                Status::ACTIVATED => [Status::VERIFIED],
                Status::VERIFIED => [Status::RESTRICTED, Status::SUSPENDED, Status::INACTIVE],
                Status::RESTRICTED => [Status::VERIFIED, Status::SUSPENDED],
                Status::SUSPENDED => [Status::INACTIVE],
                Status::INACTIVE => [Status::ARCHIVED],
            ],
            'super_admin' => [
                // Super Admin cannot be transitioned (PROTECTED is immutable)
            ],
            default => [],
        };
    }

    /**
     * Check if role-specific transition is allowed
     */
    private function isTransitionAllowedForRole(
        User $user,
        Status $fromStatus,
        Status $toStatus,
    ): bool {
        $roleRules = $this->getRoleBasedTransitionRules($user);
        $allowedTransitions = $roleRules[$fromStatus->value] ?? [];

        return in_array($toStatus, $allowedTransitions);
    }

    /**
     * Check if triggering user has permission to perform this transition
     */
    private function hasPermissionToTransition(
        User $user,
        Status $fromStatus,
        Status $toStatus,
        User $triggeredBy,
    ): bool {
        $userRole = $user->getHighestRole();
        $triggeredByRole = $triggeredBy->getHighestRole();

        // SuperAdmin can do anything
        if ($triggeredByRole === 'super_admin') {
            // But cannot change another SuperAdmin
            if ($userRole === 'super_admin' && $user->id !== $triggeredBy->id) {
                return false;
            }

            return true;
        }

        // Admin can transition users below them
        if ($triggeredByRole === 'admin') {
            $canManage = in_array($userRole, ['student', 'teacher', 'mentor']);

            return $canManage && $toStatus !== Status::PROTECTED;
        }

        // Teacher can verify/manage students
        if ($triggeredByRole === 'teacher') {
            return $userRole === 'student' && $toStatus === Status::VERIFIED;
        }

        // Mentor can restrict/suspend students only
        if ($triggeredByRole === 'mentor') {
            return $userRole === 'student' &&
                in_array($toStatus, [Status::RESTRICTED, Status::SUSPENDED]);
        }

        // Users can activate/verify themselves
        if ($triggeredBy->id === $user->id) {
            return in_array($toStatus, [Status::ACTIVATED, Status::VERIFIED]);
        }

        return false;
    }

    /**
     * Get status change reason for human-readable messages
     */
    public function getTransitionLabel(Status $from, Status $to, User $user): string
    {
        $role = $user->getHighestRole();

        return match (true) {
            $from === Status::PENDING && $to === Status::ACTIVATED => 'Akun diaktifkan',
            $from === Status::ACTIVATED && $to === Status::VERIFIED => "Akun diverifikasi oleh {$role}",
            $from === Status::VERIFIED && $to === Status::RESTRICTED => 'Akun dibatasi (investigasi)',
            $from === Status::VERIFIED && $to === Status::SUSPENDED => 'Akun ditangguhkan (pelanggaran)',
            in_array($to, [Status::RESTRICTED, Status::SUSPENDED]) => "Akun {$to->label()}",
            $from === Status::INACTIVE && $to === Status::ARCHIVED => 'Akun diarsipkan (GDPR pending)',
            default => "Status diubah: {$from->label()} → {$to->label()}",
        };
    }

    /**
     * Get all users waiting for specific approval by role
     */
    public function getPendingApprovals(User $approvingUser): LengthAwarePaginator
    {
        $role = $approvingUser->getHighestRole();

        $query = User::whereHas('statuses', function ($q) {
            $q->where('name', Status::ACTIVATED->value);
        });

        // Filter by who can approve what
        if ($role === 'super_admin') {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin']));
        } elseif ($role === 'admin') {
            $query->whereHas(
                'roles',
                fn ($q) => $q->whereIn('name', ['student', 'teacher', 'mentor']),
            );
        } elseif ($role === 'teacher') {
            $query->whereHas('roles', fn ($q) => $q->where('name', 'student'));
        } else {
            $query->whereRaw('1 = 0'); // Empty for other roles
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }
}
