<?php

declare(strict_types=1);

use App\Core\Contracts\ColorableEnum;

enum MockColorableEnum: string implements ColorableEnum
{
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';

    public function color(): string
    {
        return match ($this) {
            self::Success => 'green',
            self::Warning => 'yellow',
            self::Danger => 'red',
        };
    }
}

test('colorable enum returns color for each case', function () {
    expect(MockColorableEnum::Success->color())->toBe('green');
    expect(MockColorableEnum::Warning->color())->toBe('yellow');
    expect(MockColorableEnum::Danger->color())->toBe('red');
});
