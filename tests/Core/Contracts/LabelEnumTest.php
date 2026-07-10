<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;

enum MockLabelEnum: string implements LabelEnum
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}

test('label enum contract can be implemented', function () {
    expect(MockLabelEnum::Active->label())->toBe('Active');
    expect(MockLabelEnum::Inactive->label())->toBe('Inactive');
});

test('label enum is string backed', function () {
    expect(MockLabelEnum::Active->value)->toBe('active');
});
