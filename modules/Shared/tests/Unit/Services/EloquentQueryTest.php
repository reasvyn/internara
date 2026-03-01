<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Shared\Services\Contracts\EloquentQuery as EloquentQueryContract;
use Modules\Shared\Services\EloquentQuery;

uses(RefreshDatabase::class);

class QueryModelStub extends Model
{
    use HasUuid;

    protected $table = 'query_model_stubs';

    protected $guarded = [];
}

class QueryServiceStub extends EloquentQuery implements EloquentQueryContract
{
    public function __construct()
    {
        $this->setModel(new QueryModelStub);
    }
}

describe('EloquentQuery Base Service', function () {
    beforeEach(function () {
        Schema::create('query_model_stubs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->timestamps();
        });
        $this->service = new QueryServiceStub;

        // S1 Security Alignment: Mock user with super-admin role to bypass all permission checks
        $user = \Mockery::mock(\Modules\User\Models\User::class)->makePartial();
        $user->shouldReceive('hasRole')->andReturn(true);
        $this->actingAs($user);

        \Illuminate\Support\Facades\Gate::define('create', fn () => true);
        \Illuminate\Support\Facades\Gate::define('update', fn () => true);
        \Illuminate\Support\Facades\Gate::define('delete', fn () => true);
    });

    afterEach(function () {
        Schema::dropIfExists('query_model_stubs');
    });

    test('test can create a record atomically', function () {
        $data = ['name' => 'Genesis Test'];
        $record = $this->service->create($data);

        expect($record)
            ->toBeInstanceOf(QueryModelStub::class)
            ->and($record->name)
            ->toBe('Genesis Test');
    });

    test('test can find a record by identity', function () {
        $created = QueryModelStub::create(['name' => 'Target']);
        $found = $this->service->find((string) $created->id);

        expect($found->id)->toBe($created->id);
    });

    test('test can update an existing record', function () {
        $record = QueryModelStub::create(['name' => 'Old Name']);
        $this->service->update((string) $record->id, ['name' => 'New Name']);

        expect($record->fresh()->name)->toBe('New Name');
    });

    test('test can paginate results efficiently', function () {
        QueryModelStub::create(['name' => 'A']);
        QueryModelStub::create(['name' => 'B']);

        $results = $this->service->paginate([], 1);

        expect($results->count())->toBe(1)->and($results->total())->toBe(2);
    });
});
