<?php

declare(strict_types=1);

namespace App\Auth\ApiTokens\Entities;

use App\Auth\ApiTokens\Models\ApiToken;
use App\Core\Entities\BaseEntity;
use App\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

final readonly class ActivationToken extends BaseEntity
{
    public function __construct(
        public string $plainText,
        public string $tokenId,
        public Carbon $expiresAt,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            plainText: '',
            tokenId: $model->id,
            expiresAt: $model->expires_at ?? now()->addDays(30),
        );
    }

    public static function generate(User $user, array $options = []): self
    {
        $raw = bin2hex(random_bytes(32));
        $ttlDays = $options['ttl_days'] ?? 30;

        $token = ApiToken::create([
            'user_id' => $user->id,
            'token' => Hash::make($raw),
            'token_type' => 'activation',
            'name' => $options['name'] ?? 'Account Activation',
            'expires_at' => now()->addDays($ttlDays),
            'attempts' => 0,
        ]);

        return new self(
            plainText: $raw,
            tokenId: $token->id,
            expiresAt: now()->addDays($ttlDays),
        );
    }
}
