<?php

declare(strict_types=1);

namespace App\Entities\AccountRecoveryCode;

use App\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class RecoveryCodeState extends BaseEntity
{
    public function __construct(
        private ?Carbon $usedAt,
        private ?Carbon $expiresAt,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            usedAt: $model->used_at,
            expiresAt: $model->expires_at,
        );
    }

    public function isValid(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->usedAt === null
            && $this->expiresAt !== null
            && $now->lessThan($this->expiresAt);
    }
}
