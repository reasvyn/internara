<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Password\Data\ResetPasswordData;

describe('D9TKW: ResetPasswordData DTO', function (): void {
    test('D9TKW-FR-PWRST-009: fromArray maps email, token, and the password pair', function (): void {
        $dto = ResetPasswordData::fromArray([
            'email' => 'sinta@smk.id',
            'token' => 'tok-1',
            'password' => 'Baru12345',
            'passwordConfirmation' => 'Baru12345',
        ]);

        expect($dto->email)->toBe('sinta@smk.id');
        expect($dto->token)->toBe('tok-1');
        expect($dto->password)->toBe('Baru12345');
        expect($dto->passwordConfirmation)->toBe('Baru12345');
    });

    test('D9TKW-FR-PWRST-009: fromArray accepts snake_case keys', function (): void {
        $dto = ResetPasswordData::fromArray([
            'email' => 'budi@smk.id',
            'token' => 'tok-2',
            'password' => 'pw',
            'password_confirmation' => 'pw',
        ]);

        expect($dto->passwordConfirmation)->toBe('pw');
    });

    test('D9TKW-FR-PWRST-007: fromArray throws when the confirmation is missing', function (): void {
        expect(fn (): ResetPasswordData => ResetPasswordData::fromArray([
            'email' => 'sinta@smk.id',
            'token' => 'tok-1',
            'password' => 'Baru12345',
        ]))->toThrow(InvalidArgumentException::class, 'passwordConfirmation');
    });

    test('D9TKW-FR-PWRST-009: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['email' => 'e', 'token' => 't', 'password' => 'p', 'passwordConfirmation' => 'p'];

        expect(ResetPasswordData::from($payload)->token)->toBe('t');

        $source = new class
        {
            public function toArray(): array
            {
                return ['email' => 'e2', 'token' => 't2', 'password' => 'p2', 'passwordConfirmation' => 'p2'];
            }
        };

        expect(ResetPasswordData::from($source)->email)->toBe('e2');
        expect(fn (): ResetPasswordData => ResetPasswordData::from(7))->toThrow(InvalidArgumentException::class);
    });

    test('D9TKW-FR-PWRST-009: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new ResetPasswordData(email: 'e', token: 't', password: 'p', passwordConfirmation: 'p');

        expect($dto->toArray())->toBe(['email' => 'e', 'token' => 't', 'password' => 'p', 'passwordConfirmation' => 'p']);
        expect($dto->only('email', 'token'))->toBe(['email' => 'e', 'token' => 't']);
        expect($dto->except('password', 'passwordConfirmation'))->toBe(['email' => 'e', 'token' => 't']);

        $merged = $dto->merge(['token' => 't-baru']);

        expect($merged->token)->toBe('t-baru');
        expect($dto->token)->toBe('t');
    });
});
