<?php

declare(strict_types=1);

use App\Modules\Setup\Domain\SetupWizard\Actions\SetupSchoolAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

function vejcxSchoolData(array $overrides = []): array
{
    return array_merge([
        'name' => 'SMK Negeri 1 Bandung',
        'institutional_code' => '20212345',
        'email' => 'info@smkn1bdg.sch.id',
        'address' => 'Jl. Soekarno Hatta No. 1',
        'phone' => '+62225812345',
        'website' => 'https://smkn1bdg.sch.id',
        'principal_name' => 'Drs. Bambang, M.Pd.',
    ], $overrides);
}

describe('VEJCX: SetupSchoolAction', function (): void {
    test('VEJCX-FR-W7: name is required', function (): void {
        $data = vejcxSchoolData(['name' => '']);

        expect(fn () => app(SetupSchoolAction::class)->execute($data))
            ->toThrow(ValidationException::class);
    });

    test('VEJCX-FR-W7: institutional_code is required', function (): void {
        $data = vejcxSchoolData(['institutional_code' => '']);

        expect(fn () => app(SetupSchoolAction::class)->execute($data))
            ->toThrow(ValidationException::class);
    });

    test('VEJCX-FR-W8: website must be a valid URL when provided', function (): void {
        $data = vejcxSchoolData(['website' => 'not-a-url']);

        expect(fn () => app(SetupSchoolAction::class)->execute($data))
            ->toThrow(ValidationException::class);
    });

    test('VEJCX-FR-F2: stores the school profile under the school.* keys', function (): void {
        $data = vejcxSchoolData();

        app(SetupSchoolAction::class)->execute($data);

        expect(setting('school.name'))->toBe('SMK Negeri 1 Bandung');
        expect(setting('school.institutional_code'))->toBe('20212345');
        expect(setting('school.email'))->toBe('info@smkn1bdg.sch.id');
        expect(setting('school.address'))->toBe('Jl. Soekarno Hatta No. 1');
        expect(setting('school.phone'))->toBe('+62225812345');
        expect(setting('school.website'))->toBe('https://smkn1bdg.sch.id');
        expect(setting('school.principal_name'))->toBe('Drs. Bambang, M.Pd.');
    });
});
