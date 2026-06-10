<?php

declare(strict_types=1);

namespace App\Auth\ApiTokens\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class ApiTokenState extends BaseEntity
{
    public function __construct(
        private ?Carbon $expiresAt,
        private ?Carbon $revokedAt,
        private int $attempts,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            expiresAt: $model->expires_at,
            revokedAt: $model->revoked_at,
            attempts: (int) ($model->attempts ?? 0),
        );
    }

    public function isExpired(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isValid(?Carbon $now = null): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired($now);
    }

    public function hasExceededMaxAttempts(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }
}
