<?php

declare(strict_types=1);

use App\Core\Contracts\ColorableEnum;
use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\SendsNotifications;
use App\Core\Contracts\SettingsStore;
use App\Core\Contracts\StatusEnum;
use App\Core\Enums\AuditCategory;
use App\Core\Enums\AuditStatus;
use App\Core\Enums\CsvRowResult;

enum TestStatusEnum: string implements StatusEnum
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return $this->value;
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
            self::Draft => [self::Active],
            self::Active => [self::Archived],
            self::Archived => [],
        };
    }
}

enum TestColorableEnum: string implements ColorableEnum
{
    case Success = 'success';

    public function color(): string
    {
        return 'green';
    }
}

it('FR-C1: Core enums implement LabelEnum and expose a label()', function () {
    foreach ([AuditCategory::class, AuditStatus::class, CsvRowResult::class] as $enum) {
        expect($enum)->toImplement(LabelEnum::class);
        foreach ($enum::cases() as $case) {
            expect($case->label())->toBeString();
        }
    }
});

it('FR-C2: StatusEnum extends LabelEnum with lifecycle methods', function () {
    expect(StatusEnum::class)->toImplement(LabelEnum::class);
    expect(TestStatusEnum::Draft->isTerminal())->toBeFalse();
    expect(TestStatusEnum::Archived->isTerminal())->toBeTrue();
    expect(TestStatusEnum::Draft->canTransitionTo(TestStatusEnum::Active))->toBeTrue();
    expect(TestStatusEnum::Draft->canTransitionTo(TestStatusEnum::Archived))->toBeFalse();
    expect(TestStatusEnum::Archived->validTransitions())->toBe([]);
    expect(TestStatusEnum::Draft->validTransitions())->toBe([TestStatusEnum::Active]);
});

it('FR-C3: ColorableEnum requires a color() string for badges', function () {
    expect(TestColorableEnum::class)->toImplement(ColorableEnum::class);
    expect(TestColorableEnum::Success->color())->toBeString();
});

it('FR-C4: SendsNotifications contract declares execute(userId, type, title, ...)', function () {
    $method = (new ReflectionMethod(SendsNotifications::class, 'execute'));
    $names = array_map(fn (ReflectionParameter $p) => $p->getName(), $method->getParameters());

    expect($method->isPublic())->toBeTrue();
    expect($names)->toBe(['userId', 'type', 'title', 'message', 'data', 'link']);
    expect($method->getReturnType())->not->toBeNull();
});

it('FR-C5: SettingsStore contract declares get(key, default)', function () {
    $method = (new ReflectionMethod(SettingsStore::class, 'get'));

    expect($method->isPublic())->toBeTrue();
    expect($method->getParameters()[0]->getName())->toBe('key');
    expect($method->getParameters()[1]->getName())->toBe('default');
});
