<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

/**
 * Represents a single-use activation or credential-reset token for a user.
 *
 * The plaintext code is NEVER stored — only the HMAC-SHA256 hash is persisted.
 * The plaintext is returned once at generation time and must be distributed
 * through institution-controlled channels (printed slips, physical handoff, etc.).
 */
class AccountToken extends Model
{
    use HasUuids;

    /**
     * Token type for first-time account claim.
     */
    public const TYPE_ACTIVATION = 'activation';

    /**
     * Token type for admin-initiated re-entry (e.g. lost activation code).
     */
    public const TYPE_CREDENTIAL_RESET = 'credential_reset';

    /**
     * Token type for email-delivered invitation to privileged accounts (Admin).
     * Unlike activation codes (short, printed), invitation tokens are long
     * hex strings delivered via email link and looked up by HMAC hash directly.
     */
    public const TYPE_INVITATION = 'invitation';

    protected $fillable = [
        'user_id',
        'type',
        'token',
        'expires_at',
        'claimed_at',
        'issued_by',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * Only tokens that have not been claimed and have not expired.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('claimed_at')->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Hash a plaintext activation code for safe storage.
     * Uses HMAC-SHA256 keyed with app.key — useless without the application secret.
     */
    public static function hashCode(string $plainCode): string
    {
        return hash_hmac('sha256', $plainCode, (string) config('app.key'));
    }

    /**
     * Verify a plaintext code against the stored hash in constant time.
     */
    public function verify(string $plainCode): bool
    {
        return hash_equals($this->token, static::hashCode($plainCode));
    }

    /**
     * Whether this token is still usable (not claimed, not expired).
     */
    public function isActive(): bool
    {
        return is_null($this->claimed_at) &&
            (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    /**
     * Mark the token as consumed, recording the claimant's IP.
     */
    public function markClaimed(?string $ipAddress = null): void
    {
        $this->update([
            'claimed_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }
}
