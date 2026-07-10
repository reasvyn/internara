<?php

declare(strict_types=1);

namespace Tests\Core\Models;

use App\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

class MockModel extends BaseModel
{
    protected $table = 'mock_models';

    public function getTable(): string
    {
        return $this->table;
    }
}

class TestBaseModel extends BaseModel
{
    use HasFactory;

    protected $table = 'test_base_models';

    #[Illuminate\Database\Eloquent\Attributes\Fillable(['name', 'email', 'data'])]
    protected $fillable = ['name', 'email', 'data'];

    protected $casts = [
        'data' => 'json',
    ];
}

beforeEach(function () {
    if (! Schema::hasTable('test_base_models')) {
        Schema::create('test_base_models', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }
});

afterEach(function () {
    Schema::dropIfExists('test_base_models');
});

describe('base model traits', function () {
    it('has string non-incrementing keys', function () {
        $model = new MockModel;

        expect($model->getIncrementing())->toBeFalse();
        expect($model->getKeyType())->toBe('string');
    });

    it('auto generates uuid on create', function () {
        $model = new MockModel;

        $model->{$model->getKeyName()} = $model->newUniqueId();

        expect($model->getKey())->toBeString();
        expect(Str::isUuid($model->getKey()))->toBeTrue();
    });

    it('uses has uuids trait', function () {
        $traits = class_uses_recursive(MockModel::class);

        expect($traits)->toContain(HasUuids::class);
    });

    it('generates unique ids across multiple instances', function () {
        $id1 = new MockModel()->newUniqueId();
        $id2 = new MockModel()->newUniqueId();

        expect($id1)->not->toBe($id2);
    });
});

describe('database operations', function () {
    it('can be created with uuid primary key', function () {
        $model = TestBaseModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
        ]);

        expect($model->id)->toBeString();
        expect(Str::isUuid($model->id))->toBeTrue();
    });

    it('generates uuid automatically on create', function () {
        $model = new TestBaseModel([
            'name' => 'Auto UUID',
            'email' => 'auto@example.com',
        ]);
        $model->save();

        expect($model->id)->toBeString();
        expect(Str::isUuid($model->id))->toBeTrue();
    });

    it('can find model by uuid', function () {
        $created = TestBaseModel::create([
            'name' => 'Find Me',
            'email' => 'find@example.com',
        ]);

        $found = TestBaseModel::find($created->id);

        expect($found)->not->toBeNull();
        expect($found->name)->toBe('Find Me');
        expect($found->email)->toBe('find@example.com');
    });

    it('can update model', function () {
        $model = TestBaseModel::create([
            'name' => 'Original',
            'email' => 'update@example.com',
        ]);

        $model->name = 'Updated';
        $model->save();

        $refreshed = TestBaseModel::find($model->id);
        expect($refreshed->name)->toBe('Updated');
    });

    it('can delete model', function () {
        $model = TestBaseModel::create([
            'name' => 'To Delete',
            'email' => 'delete@example.com',
        ]);

        $id = $model->id;
        $model->delete();

        expect(TestBaseModel::find($id))->toBeNull();
    });

    it('handles json casting', function () {
        $model = TestBaseModel::create([
            'name' => 'JSON Test',
            'email' => 'json@example.com',
            'data' => ['key' => 'value', 'number' => 42],
        ]);

        $refreshed = TestBaseModel::find($model->id);

        expect($refreshed->data)->toBeArray();
        expect($refreshed->data['key'])->toBe('value');
        expect($refreshed->data['number'])->toBe(42);
    });

    it('generates unique uuids for multiple records', function () {
        $models = [];
        for ($i = 0; $i < 10; $i++) {
            $models[] = TestBaseModel::create([
                'name' => "Model $i",
                'email' => "model$i@example.com",
            ]);
        }

        $ids = array_map(fn ($m) => $m->id, $models);
        expect($ids)->toHaveCount(10);
        expect(array_unique($ids))->toHaveCount(10);
    });

    it('supports mass assignment with fillable', function () {
        $model = TestBaseModel::create([
            'name' => 'Mass Assignment',
            'email' => 'mass@example.com',
        ]);

        expect($model->name)->toBe('Mass Assignment');
        expect($model->email)->toBe('mass@example.com');
    });

    it('uses uuid as route key', function () {
        $model = TestBaseModel::create([
            'name' => 'Route Key',
            'email' => 'route@example.com',
        ]);

        expect($model->getRouteKey())->toBe($model->id);
        expect(Str::isUuid($model->getRouteKey()))->toBeTrue();
    });

    it('has timestamps', function () {
        $model = TestBaseModel::create([
            'name' => 'Timestamps',
            'email' => 'time@example.com',
        ]);

        expect($model->created_at)->not->toBeNull();
        expect($model->updated_at)->not->toBeNull();
    });
});

describe('query scopes', function () {
    it('can query with where clauses', function () {
        TestBaseModel::create(['name' => 'A', 'email' => 'a@test.com']);
        TestBaseModel::create(['name' => 'B', 'email' => 'b@test.com']);

        $results = TestBaseModel::where('name', 'A')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->name)->toBe('A');
    });

    it('supports ordering', function () {
        TestBaseModel::create(['name' => 'Zebra', 'email' => 'z@test.com']);
        TestBaseModel::create(['name' => 'Apple', 'email' => 'a@test.com']);

        $results = TestBaseModel::orderBy('name')->get();

        expect($results[0]->name)->toBe('Apple');
        expect($results[1]->name)->toBe('Zebra');
    });
});
