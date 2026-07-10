<?php

declare(strict_types=1);

use App\Core\Contracts\StatusEnum;

enum MockStatusEnum: string implements StatusEnum
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Archived;
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        return in_array($target, $this->validTransitions(), true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published],
            self::Published => [self::Archived],
            self::Archived => [],
        };
    }
}

test('status enum has label', function () {
    expect(MockStatusEnum::Draft->label())->toBe('Draft');
});

test('terminal status returns true for isTerminal', function () {
    expect(MockStatusEnum::Archived->isTerminal())->toBeTrue();
});

test('non terminal status returns false for isTerminal', function () {
    expect(MockStatusEnum::Draft->isTerminal())->toBeFalse();
});

test('valid transition returns true', function () {
    expect(MockStatusEnum::Draft->canTransitionTo(MockStatusEnum::Published))->toBeTrue();
});

test('invalid transition returns false', function () {
    expect(MockStatusEnum::Draft->canTransitionTo(MockStatusEnum::Archived))->toBeFalse();
});

test('terminal status has no valid transitions', function () {
    expect(MockStatusEnum::Archived->validTransitions())->toBe([]);
});
