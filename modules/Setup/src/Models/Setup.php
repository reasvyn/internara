<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

/**
 * Setup Model - Manages system installation state
 *
 * [S1 - Secure] Encrypted token storage, UUID primary key
 * [S2 - Sustain] Clear intent, self-documenting
 * [S3 - Scalable] Independent entity, proper relationships
 */
class Setup extends Model
{
    use HasUUid;
    use SoftDeletes;

    protected $fillable = [
        'version',
        'is_installed',
        'setup_token_encrypted',
        'token_expires_at',
        'completed_steps',
        'admin_id',
        'school_id',
        'department_id',
        'internship_id',
    ];

    protected $casts = [
        'is_installed' => 'boolean',
        'completed_steps' => 'array',
        'token_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * [S1 - Secure] Store encrypted token
     */
    public function setToken(string $plainToken): void
    {
        $this->setup_token_encrypted = encrypt($plainToken);
    }

    /**
     * [S1 - Secure] Retrieve and decrypt token
     */
    public function getToken(): ?string
    {
        if (empty($this->setup_token_encrypted)) {
            return null;
        }

        try {
            return decrypt($this->setup_token_encrypted);
        } catch (\Exception $e) {
            \Log::warning('Failed to decrypt setup token', [
                'setup_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * [S1 - Secure] Check if token matches (timing-safe)
     */
    public function tokenMatches(string $providedToken): bool
    {
        $storedToken = $this->getToken();

        if ($storedToken === null) {
            return false;
        }

        return hash_equals($storedToken, $providedToken);
    }

    /**
     * [S1 - Secure] Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if ($this->token_expires_at === null) {
            return true;
        }

        return now()->greaterThan($this->token_expires_at);
    }

    /**
     * Mark a step as completed
     */
    public function completeStep(string $step): void
    {
        $steps = $this->completed_steps ?? [];
        $steps[$step] = true;
        $this->completed_steps = $steps;
    }

    /**
     * Check if step is completed
     */
    public function isStepCompleted(string $step): bool
    {
        $steps = $this->completed_steps ?? [];

        return $steps[$step] ?? false;
    }

    /**
     * [S1 - Secure] Finalize setup (atomic operation)
     */
    public function finalize(string $adminId): void
    {
        $this->is_installed = true;
        $this->admin_id = $adminId;
        $this->setup_token_encrypted = null;
        $this->token_expires_at = null;
        $this->save();
    }

    /**
     * Relationships (S3 - Scalable: UUID references, no FK constraints)
     */
    public function admin()
    {
        return $this->belongsTo(\Modules\User\Models\User::class, 'admin_id');
    }

    public function school()
    {
        return $this->belongsTo(\Modules\School\Models\School::class, 'school_id');
    }

    public function department()
    {
        return $this->belongsTo(\Modules\Department\Models\Department::class, 'department_id');
    }

    public function internship()
    {
        return $this->belongsTo(\Modules\Internship\Models\Internship::class, 'internship_id');
    }
}
