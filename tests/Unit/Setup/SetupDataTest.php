<?php

declare(strict_types=1);

use App\Modules\Setup\Domain\Installation\Data\SetupTokenData;
use App\Modules\Setup\Domain\SetupWizard\Data\FinalizeSetupData;
use Carbon\Carbon;

describe('8NZAU: SetupTokenData DTO', function (): void {
    test('8NZAU-FR-INST-011: fromArray maps the plaintext token and expiry', function (): void {
        $expiresAt = Carbon::parse('2026-09-11 12:00:00');
        $dto = SetupTokenData::fromArray(['plaintext' => str_repeat('a', 64), 'expiresAt' => $expiresAt]);

        expect($dto->plaintext)->toBe(str_repeat('a', 64));
        expect($dto->expiresAt->equalTo($expiresAt))->toBeTrue();
    });

    test('8NZAU-FR-INST-011: fromArray accepts snake_case keys', function (): void {
        $expiresAt = Carbon::parse('2026-09-11 13:00:00');
        $dto = SetupTokenData::fromArray(['plaintext' => 'tok', 'expires_at' => $expiresAt]);

        expect($dto->plaintext)->toBe('tok');
        expect($dto->expiresAt->equalTo($expiresAt))->toBeTrue();
    });

    test('8NZAU-FR-INST-011: fromArray throws when the expiry is missing', function (): void {
        expect(fn (): SetupTokenData => SetupTokenData::fromArray(['plaintext' => 'tok']))
            ->toThrow(InvalidArgumentException::class, 'expiresAt');
    });

    test('8NZAU-FR-INST-011: from accepts arrays and arrayables, rejects scalars', function (): void {
        $expiresAt = Carbon::parse('2026-09-11 14:00:00');
        $payload = ['plaintext' => 'tok', 'expiresAt' => $expiresAt];

        expect(SetupTokenData::from($payload)->plaintext)->toBe('tok');

        $source = new class
        {
            public function toArray(): array
            {
                return ['plaintext' => 'tok2', 'expiresAt' => Carbon::parse('2026-09-11 15:00:00')];
            }
        };

        expect(SetupTokenData::from($source)->plaintext)->toBe('tok2');
        expect(fn (): SetupTokenData => SetupTokenData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('8NZAU-FR-INST-011: toArray serializes the expiry while only and except shape the payload', function (): void {
        $dto = new SetupTokenData(plaintext: 'tok', expiresAt: Carbon::parse('2026-09-11 12:00:00'));

        expect($dto->toArray()['plaintext'])->toBe('tok');
        expect($dto->toArray()['expiresAt'])->toBe($dto->expiresAt->jsonSerialize());
        expect($dto->only('plaintext'))->toBe(['plaintext' => 'tok']);
        expect(array_key_exists('expiresAt', $dto->except('plaintext')))->toBeTrue();
    });
});

describe('VEJCX: FinalizeSetupData DTO', function (): void {
    test('VEJCX-FR-WIZ-009: fromArray maps school, department, and admin payloads with step defaults', function (): void {
        $dto = FinalizeSetupData::fromArray([
            'schoolData' => ['name' => 'SMK Maju'],
            'departmentData' => ['name' => 'RPL'],
            'adminData' => ['email' => 'admin@smk.id', 'password' => 'Rahasia123'],
        ]);

        expect($dto->schoolData)->toBe(['name' => 'SMK Maju']);
        expect($dto->departmentData)->toBe(['name' => 'RPL']);
        expect($dto->adminData)->toBe(['email' => 'admin@smk.id', 'password' => 'Rahasia123']);
        expect($dto->stepsToComplete)->toBe(['account', 'school', 'department']);
    });

    test('VEJCX-FR-WIZ-009: fromArray accepts snake_case keys', function (): void {
        $dto = FinalizeSetupData::fromArray([
            'school_data' => ['name' => 'SMK Mundur'],
            'department_data' => ['name' => 'TKJ'],
            'admin_data' => ['email' => 'a@smk.id', 'password' => 'pw'],
            'steps_to_complete' => ['account'],
        ]);

        expect($dto->schoolData)->toBe(['name' => 'SMK Mundur']);
        expect($dto->stepsToComplete)->toBe(['account']);
    });

    test('VEJCX-FR-WIZ-015: fromArray throws when the admin payload is missing', function (): void {
        expect(fn (): FinalizeSetupData => FinalizeSetupData::fromArray([
            'schoolData' => ['name' => 'SMK Maju'],
            'departmentData' => ['name' => 'RPL'],
        ]))->toThrow(InvalidArgumentException::class, 'adminData');
    });

    test('VEJCX-FR-WIZ-009: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = [
            'schoolData' => ['name' => 'S'],
            'departmentData' => ['name' => 'D'],
            'adminData' => ['email' => 'e', 'password' => 'p'],
        ];

        expect(FinalizeSetupData::from($payload)->schoolData)->toBe(['name' => 'S']);

        $source = new class
        {
            public function toArray(): array
            {
                return [
                    'schoolData' => ['name' => 'S2'],
                    'departmentData' => ['name' => 'D2'],
                    'adminData' => ['email' => 'e2', 'password' => 'p2'],
                ];
            }
        };

        expect(FinalizeSetupData::from($source)->departmentData)->toBe(['name' => 'D2']);
        expect(fn (): FinalizeSetupData => FinalizeSetupData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('VEJCX-FR-WIZ-009: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new FinalizeSetupData(
            schoolData: ['name' => 'S'],
            departmentData: ['name' => 'D'],
            adminData: ['email' => 'e', 'password' => 'p'],
        );

        expect($dto->toArray()['adminData'])->toBe(['email' => 'e', 'password' => 'p']);
        expect($dto->only('schoolData'))->toBe(['schoolData' => ['name' => 'S']]);
        expect(array_key_exists('adminData', $dto->except('schoolData')))->toBeTrue();

        $merged = $dto->merge(['stepsToComplete' => ['account']]);

        expect($merged->stepsToComplete)->toBe(['account']);
        expect($dto->stepsToComplete)->toBe(['account', 'school', 'department']);
    });
});
