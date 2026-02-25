<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shared\Services\EloquentQuery;

class QueryModelStub extends Model
{
    protected $fillable = ['name'];
}

class QueryServiceStub extends EloquentQuery
{
    public function __construct(QueryModelStub $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name']);
        $this->setSortable(['name', 'created_at']);
    }
}

uses(RefreshDatabase::class);

describe('EloquentQuery Base Service', function () {
    beforeEach(function () {
        $this->model = new QueryModelStub;
        // Create table for stub
        \Illuminate\Support\Facades\Schema::create('query_model_stubs', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->service = new QueryServiceStub($this->model);
    });

    test('it can create a record', function () {
        $record = $this->service->create(['name' => 'Test Record']);
        expect($record->name)->toBe('Test Record');
        $this->assertDatabaseHas('query_model_stubs', ['name' => 'Test Record']);
    });

    test('it can update a record', function () {
        $record = $this->service->create(['name' => 'Original']);
        $updated = $this->service->update($record->id, ['name' => 'Updated']);

        expect($updated->name)->toBe('Updated');
    });

    test('it can find a record by id', function () {
        $record = $this->service->create(['name' => 'Find Me']);
        $found = $this->service->find($record->id);

        expect($found->id)->toBe($record->id);
    });

    test('it can paginate records with search', function () {
        $this->service->create(['name' => 'Alpha']);
        $this->service->create(['name' => 'Beta']);

        $results = $this->service->paginate(['search' => 'Alpha']);
        expect($results->total())->toBe(1);
    });
});
