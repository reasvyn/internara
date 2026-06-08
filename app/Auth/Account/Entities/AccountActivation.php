<?php

declare(strict_types=1);

namespace App\Auth\Account\Entities;

use App\Auth\ApiTokens\Models\ApiToken;
use App\Core\Entities\BaseEntity;
use App\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class AccountActivation extends BaseEntity
{
    public function __construct(
        private bool $isActivated,
        private ?Carbon $tokenExpiresAt,
        private bool $tokenIsValid,
        private int $attempts,
    ) {}

    public static function fromModel(Model $model): static
    {
        $token = ApiToken::where('user_id', $model->id)
            ->where('token_type', 'activation')
            ->whereNull('revoked_at')
            ->first();

        return new self(
            isActivated: $token === null,
            tokenExpiresAt: $token?->expires_at,
            tokenIsValid: $token?->expires_at === null || ($token->expires_at !== null && $token->expires_at->isFuture()),
            attempts: $token?->attempts ?? 0,
        );
    }

    public static function forUser(User $user): self
    {
        return self::fromModel($user);
    }

    public function requiresActivation(): bool
    {
        return ! $this->isActivated;
    }

    public function isTokenValid(): bool
    {
        return $this->tokenIsValid;
    }

    public function tokenExpiresAt(): ?Carbon
    {
        return $this->tokenExpiresAt;
    }

    public function isTokenExpired(): bool
    {
        return ! $this->tokenIsValid && ! $this->isActivated;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function hasExceededMaxAttempts(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }
}