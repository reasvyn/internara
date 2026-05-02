<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Groups user accounts created in a single administrative provisioning operation.
 *
 * Examples: "Siswa Kelas 10 Batch 2026", "Guru Semester Ganjil 2026",
 * "Mentor Industri PT Xyz Batch A".
 *
 * Lifecycle: draft → issued → archived
 * - draft:    created, accounts imported, slips not yet distributed
 * - issued:   credential slips distributed, users can start claiming accounts
 * - archived: cycle complete; all (or most) accounts claimed
 */
class OnboardingBatch extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_STUDENT = 'student';

    public const TYPE_TEACHER = 'teacher';

    public const TYPE_MENTOR = 'mentor';

    protected $fillable = ['name', 'type', 'status', 'notes', 'issued_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'onboarding_batch_id');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Number of accounts in this batch that have been claimed.
     */
    public function claimedCount(): int
    {
        return $this->users()
            ->whereHas('accountTokens', fn ($q) => $q->whereNotNull('claimed_at'))
            ->count();
    }

    /**
     * Number of accounts that still have an active (unclaimed) token.
     */
    public function pendingCount(): int
    {
        return $this->users()->count() - $this->claimedCount();
    }

    /**
     * Mark the batch as issued (credential slips distributed).
     */
    public function markIssued(): void
    {
        $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => now(),
        ]);
    }

    /**
     * Archive the batch (cycle complete).
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }
}
