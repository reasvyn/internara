<?php

declare(strict_types=1);

use App\Auth\AccessTokens\Entities\ActivationToken;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

test('activation token returns constructor values via getters', function () {
    $expiresAt = Carbon::now()->addDays(30);
    $token = new ActivationToken(
        plainText: 'abc123',
        tokenId: 'token-uuid-123',
        expiresAt: $expiresAt,
    );

    expect($token->plainText())->toBe('abc123');
    expect($token->tokenId())->toBe('token-uuid-123');
    expect($token->expiresAt())->toBe($expiresAt);
});

test('activation token from model returns empty plain text', function () {
    $token = ActivationToken::fromModel(new class extends Model
    {
        public string $id = 'model-uuid';

        public $expires_at;
    });

    expect($token->plainText())->toBe('');
    expect($token->tokenId())->toBe('model-uuid');
    expect($token->expiresAt())->toBeInstanceOf(Carbon::class);
});
