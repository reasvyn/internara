<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Account\Data\ActivateAccountData;
use App\Modules\Auth\Domain\Login\Data\LoginData;

describe('YB7RG: LoginData DTO', function (): void {
    test('YB7RG-FR-AUTH-001: fromArray maps the identifier with remember defaulting false', function (): void {
        $dto = LoginData::fromArray(['identifier' => 'sinta@smk.id', 'password' => 'Rahasia123']);

        expect($dto->identifier)->toBe('sinta@smk.id');
        expect($dto->password)->toBe('Rahasia123');
        expect($dto->remember)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-001: fromArray accepts a username identifier', function (): void {
        $dto = LoginData::fromArray(['identifier' => 'sinta01', 'password' => 'Rahasia123', 'remember' => true]);

        expect($dto->identifier)->toBe('sinta01');
        expect($dto->remember)->toBeTrue();
    });

    test('YB7RG-FR-AUTH-022: fromArray throws when the password is missing', function (): void {
        expect(fn (): LoginData => LoginData::fromArray(['identifier' => 'sinta@smk.id']))
            ->toThrow(InvalidArgumentException::class, 'password');
    });

    test('YB7RG-FR-AUTH-021: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['identifier' => 'sinta', 'password' => 'pw'];

        expect(LoginData::from($payload)->identifier)->toBe('sinta');

        $source = new class
        {
            public function toArray(): array
            {
                return ['identifier' => 'budi', 'password' => 'pw2', 'remember' => true];
            }
        };

        expect(LoginData::from($source)->remember)->toBeTrue();
        expect(fn (): LoginData => LoginData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('YB7RG-FR-AUTH-001: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new LoginData(identifier: 'sinta', password: 'pw');

        expect($dto->toArray())->toBe(['identifier' => 'sinta', 'password' => 'pw', 'remember' => false]);
        expect($dto->only('identifier'))->toBe(['identifier' => 'sinta']);
        expect($dto->except('password'))->toBe(['identifier' => 'sinta', 'remember' => false]);

        $merged = $dto->merge(['remember' => true]);

        expect($merged->remember)->toBeTrue();
        expect($dto->remember)->toBeFalse();
    });
});

describe('YB7RG: ActivateAccountData DTO', function (): void {
    test('YB7RG-FR-AUTH-029: fromArray maps the user, code, and password', function (): void {
        $dto = ActivateAccountData::fromArray([
            'userId' => 'user-1',
            'code' => 'ABC123',
            'password' => 'Baru12345',
        ]);

        expect($dto->userId)->toBe('user-1');
        expect($dto->code)->toBe('ABC123');
        expect($dto->password)->toBe('Baru12345');
    });

    test('YB7RG-FR-AUTH-029: fromArray accepts snake_case keys', function (): void {
        $dto = ActivateAccountData::fromArray(['user_id' => 'user-2', 'code' => 'XYZ', 'password' => 'pw']);

        expect($dto->userId)->toBe('user-2');
    });

    test('YB7RG-FR-AUTH-033: fromArray throws when the activation code is missing', function (): void {
        expect(fn (): ActivateAccountData => ActivateAccountData::fromArray(['userId' => 'u', 'password' => 'pw']))
            ->toThrow(InvalidArgumentException::class, 'code');
    });

    test('YB7RG-FR-AUTH-029: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['userId' => 'u', 'code' => 'C', 'password' => 'pw'];

        expect(ActivateAccountData::from($payload)->code)->toBe('C');

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'code' => 'C2', 'password' => 'pw2'];
            }
        };

        expect(ActivateAccountData::from($source)->userId)->toBe('u2');
        expect(fn (): ActivateAccountData => ActivateAccountData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('YB7RG-FR-AUTH-031: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new ActivateAccountData(userId: 'u', code: 'C', password: 'pw');

        expect($dto->toArray())->toBe(['userId' => 'u', 'code' => 'C', 'password' => 'pw']);
        expect($dto->only('code'))->toBe(['code' => 'C']);
        expect($dto->except('password'))->toBe(['userId' => 'u', 'code' => 'C']);

        $merged = $dto->merge(['password' => 'pw-baru']);

        expect($merged->password)->toBe('pw-baru');
        expect($dto->password)->toBe('pw');
    });
});
