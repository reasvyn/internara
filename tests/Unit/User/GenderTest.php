<?php

declare(strict_types=1);

use App\Modules\User\Enums\Gender;

describe('95EVB: Gender enum', function (): void {
    test('95EVB-FR-USER-028: cases carry the specified backing values', function (): void {
        expect(Gender::MALE->value)->toBe('male');
        expect(Gender::from('male'))->toBe(Gender::MALE);
        expect(Gender::FEMALE->value)->toBe('female');
        expect(Gender::from('female'))->toBe(Gender::FEMALE);
        expect(Gender::cases())->toHaveCount(2);
        expect(Gender::tryFrom('no-such-value'))->toBeNull();
    });
    test('95EVB-FR-USER-028: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(Gender::MALE->label())->toBe('Male');
        expect(Gender::FEMALE->label())->toBe('Female');
    });

    test('95EVB-FR-USER-028: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(Gender::MALE->label())->toBe('Laki-laki');
        expect(Gender::FEMALE->label())->toBe('Perempuan');
    });
});
