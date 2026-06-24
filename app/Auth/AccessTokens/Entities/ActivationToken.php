<?php

declare(strict_types=1);

namespace App\Auth\AccessTokens\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class ActivationToken extends BaseEntity
{
    public function __construct(
        private string $plainText,
        private string $tokenId,
        private Carbon $expiresAt,
    ) {}

    public function plainText(): string
    {
        return $this->plainText;
    }

    public function tokenId(): string
    {
        return $this->tokenId;
    }

    public function expiresAt(): Carbon
    {
        return $this->expiresAt;
    }

    public static function fromModel(Model $model): static
    {
        return new self(
            plainText: '',
            tokenId: $model->id,
            expiresAt: $model->expires_at ?? now()->addDays(30),
        );
    }
}
