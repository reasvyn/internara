<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Services\EloquentQuery;

uses(RefreshDatabase::class);

class QueryTestModel extends Model
{
    protected $table = 'query_test_models';

    protected $fillable = ['name', 'category'];
}

class QueryTestService extends EloquentQuery
{
    public function __construct(QueryTestModel $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name']);
        $this->setSortable(['name', 'created_at']);
    }
}

describe('EloquentQuery Base Service', function () {
    beforeEach(function () {
        Schema::create('query_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->timestamps();
        });

        $this->service = new QueryTestService(new QueryTestModel);
    });

    test('it can create a record', function () {
        $record = $this->service->create(['name' => 'Test Item', 'category' => 'A']);

        expect($record->name)->toBe('Test Item')->and($record->category)->toBe('A');
    });

    test('it can update a record', function () {
        $record = QueryTestModel::create(['name' => 'Original']);
        $updated = $this->service->update($record->id, ['name' => 'Updated']);

        expect($updated->name)->toBe('Updated');
    });

    test('it can find a record by id', function () {
        $record = QueryTestModel::create(['name' => 'Find Me']);
        $found = $this->service->find($record->id);

        expect($found->name)->toBe('Find Me');
    });

    test('it can paginate records with search and sort', function () {
        QueryTestModel::create(['name' => 'Alpha']);
        QueryTestModel::create(['name' => 'Beta']);
        QueryTestModel::create(['name' => 'Gamma']);

        $results = $this->service->paginate([
            'search' => 'Al',
            'sort_by' => 'name',
            'sort_dir' => 'desc',
        ]);

        expect($results->total())
            ->toBe(1)
            ->and($results->first()->name)
            ->toBe('Alpha');
    });
});
