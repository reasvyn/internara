<?php

declare(strict_types=1);

use App\Modules\Core\Actions\BaseAction;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Data\BaseData;
use App\Modules\Core\Entities\BaseEntity;
use App\Modules\Core\Models\BaseAuthenticatable;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Core\Policies\BasePolicy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class EntityInnerDouble extends BaseEntity
{
    public function __construct(
        public string $code,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(code: (string) ($model->getAttributes()['code'] ?? ''));
    }
}

final readonly class EntityRichDouble extends BaseEntity
{
    public function __construct(
        public string $name,
        public Carbon $at,
        public EntityInnerDouble $inner,
    ) {}

    public static function fromModel(Model $model): static
    {
        $attributes = $model->getAttributes();

        return new self(
            name: (string) ($attributes['name'] ?? ''),
            at: Carbon::parse($attributes['at'] ?? 'now'),
            inner: new EntityInnerDouble(code: (string) ($attributes['code'] ?? '')),
        );
    }
}

final readonly class EntityTestDouble extends BaseEntity
{
    public function __construct(
        public string $id,
        public string $name,
        public float $score = 0.0,
    ) {}

    public static function fromModel(Model $model): static
    {
        $attributes = $model->getAttributes();

        return new self(
            id: (string) ($attributes['id'] ?? ''),
            name: (string) ($attributes['name'] ?? ''),
            score: (float) ($attributes['score'] ?? 0.0),
        );
    }
}

final readonly class EntityWithRulesDouble extends BaseEntity
{
    public function __construct(
        public string $name,
        public int $quota = 0,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            name: (string) ($model->getAttributes()['name'] ?? ''),
            quota: (int) ($model->getAttributes()['quota'] ?? 0),
        );
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'quota' => ['required', 'integer', 'min:1'],
        ];
    }
}

final class AnonymousModelDouble extends Model
{
    protected $guarded = [];
}

describe('SE5Q9: base entity', function (): void {
    test('SE5Q9-FR-BASE-011: fromArray hydrates constructor params with defaults', function (): void {
        $entity = EntityTestDouble::fromArray(['id' => '1', 'name' => 'Placement']);

        expect($entity->id)->toBe('1');
        expect($entity->name)->toBe('Placement');
        expect($entity->score)->toBe(0.0);
    });

    test('SE5Q9-FR-BASE-011: fromArray rejects a missing required param', function (): void {
        expect(fn (): EntityTestDouble => EntityTestDouble::fromArray(['id' => '1']))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('QLHDO-FR-GLB-016: fromModel bridges persistence without persisting', function (): void {
        $model = new AnonymousModelDouble(['id' => '7', 'name' => 'Bridged', 'score' => 4.5]);

        $entity = EntityTestDouble::fromModel($model);

        expect($entity->id)->toBe('7');
        expect($entity->name)->toBe('Bridged');
        expect($entity->score)->toBe(4.5);
        expect($model->exists)->toBeFalse();
    });

    test('SE5Q9-FR-BASE-011: toArray and jsonSerialize expose plain state', function (): void {
        $entity = new EntityTestDouble(id: '1', name: 'Placement', score: 9.5);

        expect($entity->toArray())->toBe(['id' => '1', 'name' => 'Placement', 'score' => 9.5]);
        expect($entity->jsonSerialize())->toBe($entity->toArray());
        expect(json_encode($entity))->toBeJson();
    });

    test('SE5Q9-FR-BASE-011: equals compares by value, with() returns a modified copy', function (): void {
        $a = new EntityTestDouble(id: '1', name: 'Placement');
        $b = new EntityTestDouble(id: '1', name: 'Placement');
        $c = $a->with('name', 'Renamed');

        expect($a->equals($b))->toBeTrue();
        expect($a->equals($c))->toBeFalse();
        expect($c->name)->toBe('Renamed');
        expect($a->name)->toBe('Placement');
    });

    test('SE5Q9-FR-BASE-011: with() preserves Carbon and nested-entity value types', function (): void {
        $at = Carbon::parse('2026-01-15 08:00:00');
        $entity = new EntityRichDouble(name: 'Placement', at: $at, inner: new EntityInnerDouble(code: 'A1'));

        $copy = $entity->with('name', 'Renamed');

        expect($copy->name)->toBe('Renamed');
        expect($copy->at)->toBeInstanceOf(Carbon::class);
        expect($copy->at->equalTo($at))->toBeTrue();
        expect($copy->inner)->toBeInstanceOf(EntityInnerDouble::class);
        expect($copy->inner->code)->toBe('A1');
        expect($entity->name)->toBe('Placement');
    });

    test('SE5Q9-FR-BASE-042: entities may expose static rules returning validation arrays shared by form objects', function (): void {
        $rules = EntityWithRulesDouble::rules();

        expect($rules)->toHaveKeys(['name', 'quota'])
            ->and($rules['name'])->toContain('required')
            ->and($rules['quota'])->toContain('min:1');
    });

    test('SE5Q9-NFR-BASE-002: all base classes are abstract and cannot be directly instantiated', function (): void {
        $bases = [
            BaseEntity::class,
            BaseAction::class,
            BaseCommandAction::class,
            BaseReadAction::class,
            BaseProcessAction::class,
            BaseModel::class,
            BaseAuthenticatable::class,
            BaseData::class,
            BasePolicy::class,
        ];

        foreach ($bases as $base) {
            $ref = new ReflectionClass($base);
            expect($ref->isAbstract())->toBeTrue();
            expect(fn () => new $base)->toThrow(Error::class);
        }
    });
});
