<?php

declare(strict_types=1);

namespace App\Auth\Account\Entities;

use App\Core\Entities\BaseEntity;
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
        $token = $model->relationLoaded('activationToken') && $model->activationToken
            ? $model->activationToken
            : null;

        return new self(
            isActivated: $token === null,
            tokenExpiresAt: $token?->expires_at,
            tokenIsValid: $token === null || ($token->expires_at !== null && $token->expires_at->isFuture()),
            attempts: $token?->attempts ?? 0,
        );
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
