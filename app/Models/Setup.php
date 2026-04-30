<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Setup extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'version',
        'is_installed',
        'setup_token',
        'token_expires_at',
        'completed_steps',
        'admin_id',
        'school_id',
        'department_id',
        'internship_id',
    ];

    protected $casts = [
        'is_installed' => 'boolean',
        'token_expires_at' => 'datetime',
        'completed_steps' => 'array',
    ];

    /**
     * Set an encrypted setup token with expiry.
     */
    public function setToken(string $token, int $ttlHours = 24): void
    {
        $this->setup_token = Crypt::encryptString($token);
        $this->token_expires_at = now()->addHours($ttlHours);
        $this->save();
    }

    /**
     * Get the plaintext setup token.
     */
    public function getToken(): ?string
    {
        if ($this->setup_token === null) {
            return null;
        }

        return Crypt::decryptString($this->setup_token);
    }

    /**
     * Validate a token against the stored one using timing-safe comparison.
     */
    public function tokenMatches(string $token): bool
    {
        $stored = $this->getToken();

        if ($stored === null) {
            return false;
        }

        return hash_equals($stored, $token);
    }

    /**
     * Check if the setup token has expired.
     */
    public function isTokenExpired(): bool
    {
        if ($this->token_expires_at === null) {
            return true;
        }

        return now()->greaterThan($this->token_expires_at);
    }

    /**
     * Mark a setup step as completed.
     */
    public function completeStep(string $step): void
    {
        $steps = $this->completed_steps ?? [];

        if (! in_array($step, $steps)) {
            $steps[] = $step;
            $this->completed_steps = $steps;
            $this->save();
        }
    }

    /**
     * Check if a setup step has been completed.
     */
    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->completed_steps ?? []);
    }

    /**
     * Finalize the installation: mark as installed, clear tokens.
     */
    public function finalize(): void
    {
        $this->is_installed = true;
        $this->setup_token = null;
        $this->token_expires_at = null;
        $this->save();
    }

    /**
     * Get the admin user who completed setup.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the school created during setup.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Get the department created during setup.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the internship created during setup.
     */
    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class, 'internship_id');
    }
}
