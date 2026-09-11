<?php

declare(strict_types=1);

use App\Modules\User\Enums\BloodType;

describe('95EVB: BloodType enum', function (): void {
    test('95EVB-FR-USER-028: cases carry the specified backing values', function (): void {
        expect(BloodType::A->value)->toBe('a');
        expect(BloodType::from('a'))->toBe(BloodType::A);
        expect(BloodType::B->value)->toBe('b');
        expect(BloodType::from('b'))->toBe(BloodType::B);
        expect(BloodType::AB->value)->toBe('ab');
        expect(BloodType::from('ab'))->toBe(BloodType::AB);
        expect(BloodType::O->value)->toBe('o');
        expect(BloodType::from('o'))->toBe(BloodType::O);
        expect(BloodType::cases())->toHaveCount(4);
        expect(BloodType::tryFrom('no-such-value'))->toBeNull();
    });
    test('95EVB-FR-USER-028: the label is the stored value itself', function (): void {
        app()->setLocale('en');
        expect(BloodType::A->label())->toBe('a');
        expect(BloodType::AB->label())->toBe('ab');

        app()->setLocale('id');
        expect(BloodType::B->label())->toBe('b');
        expect(BloodType::O->label())->toBe('o');
    });
});
