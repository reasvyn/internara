<?php

declare(strict_types=1);

use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\ColorableEnum;
use App\Modules\Core\Contracts\LabelEnum;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Core\Contracts\SettingsStore;
use App\Modules\Core\Contracts\StatusEnum;
use App\Modules\Core\Enums\AuditCategory;
use App\Modules\Core\Enums\AuditStatus;
use App\Modules\Core\Enums\CsvRowResult;

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

test('SE5Q9-FR-C1: Core enums implement LabelEnum and expose a label()', function () {
    foreach ([AuditCategory::class, AuditStatus::class, CsvRowResult::class] as $enum) {
        expect($enum)->toImplement(LabelEnum::class);
        foreach ($enum::cases() as $case) {
            expect($case->label())->toBeString();
        }
    }
});

test('SE5Q9-FR-C2: StatusEnum extends LabelEnum with lifecycle methods', function () {
    expect(StatusEnum::class)->toImplement(LabelEnum::class);
    expect(TestStatusEnum::Draft->isTerminal())->toBeFalse();
    expect(TestStatusEnum::Archived->isTerminal())->toBeTrue();
    expect(TestStatusEnum::Draft->canTransitionTo(TestStatusEnum::Active))->toBeTrue();
    expect(TestStatusEnum::Draft->canTransitionTo(TestStatusEnum::Archived))->toBeFalse();
    expect(TestStatusEnum::Archived->validTransitions())->toBe([]);
    expect(TestStatusEnum::Draft->validTransitions())->toBe([TestStatusEnum::Active]);
});

test('SE5Q9-FR-C3: ColorableEnum requires a color() string for badges', function () {
    expect(TestColorableEnum::class)->toImplement(ColorableEnum::class);
    expect(TestColorableEnum::Success->color())->toBeString();
});

test('SE5Q9-FR-C4: SendsNotifications contract declares execute(NotificationData)', function () {
    $method = (new ReflectionMethod(SendsNotifications::class, 'execute'));
    $params = $method->getParameters();

    expect($method->isPublic())->toBeTrue();
    expect($params)->toHaveCount(1);
    expect($params[0]->getName())->toBe('data');
    expect((string) $params[0]->getType())->toBe(NotificationData::class);
    expect($method->getReturnType())->not->toBeNull();
});

test('SE5Q9-FR-C5: SettingsStore contract declares get(key, default)', function () {
    $method = (new ReflectionMethod(SettingsStore::class, 'get'));

    expect($method->isPublic())->toBeTrue();
    expect($method->getParameters()[0]->getName())->toBe('key');
    expect($method->getParameters()[1]->getName())->toBe('default');
});
