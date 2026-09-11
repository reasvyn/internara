<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccessTokens\Entities\ActivationToken;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class ActivationTokenModelDouble extends Model
{
    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';
}

describe('YB7RG: activation token', function (): void {
    test('YB7RG-FR-AUTH-040: fromArray hydrates plaintext, id, and expiry', function (): void {
        $expiresAt = Carbon::now()->addDays(30);

        $token = ActivationToken::fromArray([
            'plainText' => 'secret-code',
            'tokenId' => 'token-1',
            'expiresAt' => $expiresAt,
        ]);

        expect($token->plainText())->toBe('secret-code');
        expect($token->tokenId())->toBe('token-1');
        expect($token->expiresAt()->equalTo($expiresAt))->toBeTrue();
    });

    test('YB7RG-FR-AUTH-040: fromArray rejects a missing plaintext', function (): void {
        expect(fn (): ActivationToken => ActivationToken::fromArray([
            'tokenId' => 'token-1',
            'expiresAt' => Carbon::now()->addDay(),
        ]))->toThrow(InvalidArgumentException::class, 'plainText');
    });

    test('YB7RG-FR-AUTH-040: fromModel blanks the plaintext and bridges id plus expiry', function (): void {
        $expiresAt = Carbon::now()->addDays(30);
        $model = new ActivationTokenModelDouble(['id' => 'token-9', 'expires_at' => $expiresAt]);

        $token = ActivationToken::fromModel($model);

        expect($token->plainText())->toBe('');
        expect($token->tokenId())->toBe('token-9');
        expect($token->expiresAt()->equalTo($expiresAt))->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-035: fromModel falls back to a thirty-day expiry when none is stored', function (): void {
        $model = new ActivationTokenModelDouble(['id' => 'token-9']);

        $token = ActivationToken::fromModel($model);

        expect($token->expiresAt())->toBeInstanceOf(Carbon::class);
        expect($token->expiresAt()->isFuture())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-040: toArray and equals expose the snapshot by value', function (): void {
        $expiresAt = Carbon::parse('2026-09-01 00:00:00');
        $token = ActivationToken::fromArray(['plainText' => 'abc', 'tokenId' => 't-1', 'expiresAt' => $expiresAt]);

        expect($token->toArray()['plainText'])->toBe('abc');
        expect($token->toArray()['tokenId'])->toBe('t-1');
        expect($token->equals(ActivationToken::fromArray([
            'plainText' => 'abc',
            'tokenId' => 't-1',
            'expiresAt' => Carbon::parse('2026-09-01 00:00:00'),
        ])))->toBeTrue();
        expect($token->equals(ActivationToken::fromArray([
            'plainText' => 'other',
            'tokenId' => 't-1',
            'expiresAt' => Carbon::parse('2026-09-01 00:00:00'),
        ])))->toBeFalse();
    });
});
