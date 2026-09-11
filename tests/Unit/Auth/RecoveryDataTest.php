<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccountRecovery\Data\RecoveryCodeData;
use App\Modules\Auth\Domain\AccountRecovery\Data\RedeemRecoverySlipData;

describe('SHQ1J: RecoveryCodeData DTO', function (): void {
    test('SHQ1J-FR-SLIP-005: fromArray maps plaintext and hash with open expiry default', function (): void {
        $dto = RecoveryCodeData::fromArray(['plainText' => 'ABCD1234EFGH', 'hashedToken' => 'hash-1']);

        expect($dto->plainText)->toBe('ABCD1234EFGH');
        expect($dto->hashedToken)->toBe('hash-1');
        expect($dto->expiresAt)->toBeNull();
    });

    test('SHQ1J-FR-SLIP-005: fromArray accepts snake_case keys', function (): void {
        $dto = RecoveryCodeData::fromArray([
            'plain_text' => 'ZZZZ9999YYYY',
            'hashed_token' => 'hash-2',
            'expires_at' => '2027-01-01',
        ]);

        expect($dto->plainText)->toBe('ZZZZ9999YYYY');
        expect($dto->hashedToken)->toBe('hash-2');
        expect($dto->expiresAt)->toBe('2027-01-01');
    });

    test('SHQ1J-FR-SLIP-005: fromArray throws when the hash is missing', function (): void {
        expect(fn (): RecoveryCodeData => RecoveryCodeData::fromArray(['plainText' => 'ABCD1234EFGH']))
            ->toThrow(InvalidArgumentException::class, 'hashedToken');
    });

    test('SHQ1J-FR-SLIP-005: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['plainText' => 'P', 'hashedToken' => 'h'];

        expect(RecoveryCodeData::from($payload)->plainText)->toBe('P');

        $source = new class
        {
            public function toArray(): array
            {
                return ['plainText' => 'P2', 'hashedToken' => 'h2'];
            }
        };

        expect(RecoveryCodeData::from($source)->hashedToken)->toBe('h2');
        expect(fn (): RecoveryCodeData => RecoveryCodeData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('SHQ1J-FR-SLIP-005: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new RecoveryCodeData(plainText: 'P', hashedToken: 'h');

        expect($dto->toArray())->toBe(['plainText' => 'P', 'hashedToken' => 'h', 'expiresAt' => null]);
        expect($dto->only('plainText'))->toBe(['plainText' => 'P']);
        expect($dto->except('expiresAt'))->toBe(['plainText' => 'P', 'hashedToken' => 'h']);

        $merged = $dto->merge(['expiresAt' => '2027-06-01']);

        expect($merged->expiresAt)->toBe('2027-06-01');
        expect($dto->expiresAt)->toBeNull();
    });
});

describe('SHQ1J: RedeemRecoverySlipData DTO', function (): void {
    test('SHQ1J-FR-SLIP-007: fromArray maps username, code, and the new password', function (): void {
        $dto = RedeemRecoverySlipData::fromArray([
            'username' => 'sinta01',
            'code' => 'ABCD1234EFGH',
            'newPassword' => 'Baru12345',
        ]);

        expect($dto->username)->toBe('sinta01');
        expect($dto->code)->toBe('ABCD1234EFGH');
        expect($dto->newPassword)->toBe('Baru12345');
    });

    test('SHQ1J-FR-SLIP-007: fromArray accepts snake_case keys', function (): void {
        $dto = RedeemRecoverySlipData::fromArray([
            'username' => 'budi',
            'code' => 'C',
            'new_password' => 'pw-baru',
        ]);

        expect($dto->newPassword)->toBe('pw-baru');
    });

    test('SHQ1J-FR-SLIP-010: fromArray throws when the code is missing', function (): void {
        expect(fn (): RedeemRecoverySlipData => RedeemRecoverySlipData::fromArray([
            'username' => 'sinta01',
            'newPassword' => 'pw',
        ]))->toThrow(InvalidArgumentException::class, 'code');
    });

    test('SHQ1J-FR-SLIP-007: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['username' => 'u', 'code' => 'c', 'newPassword' => 'pw'];

        expect(RedeemRecoverySlipData::from($payload)->username)->toBe('u');

        $source = new class
        {
            public function toArray(): array
            {
                return ['username' => 'u2', 'code' => 'c2', 'newPassword' => 'pw2'];
            }
        };

        expect(RedeemRecoverySlipData::from($source)->code)->toBe('c2');
        expect(fn (): RedeemRecoverySlipData => RedeemRecoverySlipData::from(null))->toThrow(InvalidArgumentException::class);
    });

    test('SHQ1J-FR-SLIP-009: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new RedeemRecoverySlipData(username: 'u', code: 'c', newPassword: 'pw');

        expect($dto->toArray())->toBe(['username' => 'u', 'code' => 'c', 'newPassword' => 'pw']);
        expect($dto->only('username', 'code'))->toBe(['username' => 'u', 'code' => 'c']);
        expect($dto->except('newPassword'))->toBe(['username' => 'u', 'code' => 'c']);

        $merged = $dto->merge(['code' => 'c-baru']);

        expect($merged->code)->toBe('c-baru');
        expect($dto->code)->toBe('c');
    });
});
