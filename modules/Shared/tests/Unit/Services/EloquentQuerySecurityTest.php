<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Modules\Exception\RecordNotFoundException;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Shared\Services\EloquentQuery;
use Modules\Shared\Services\Contracts\EloquentQuery as EloquentQueryContract;

class SecurityModelStub extends Model
{
    use HasUuid;
    protected $table = 'security_model_stubs';
    protected $guarded = [];
}

class SecurityServiceStub extends EloquentQuery implements EloquentQueryContract
{
    protected string $moduleName = 'Shared';

    public function __construct()
    {
        $this->setModel(new SecurityModelStub);
    }
}

describe('EloquentQuery S1 & S2 Compliance', function () {
    beforeEach(function () {
        Schema::create('security_model_stubs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->timestamps();
        });
        $this->service = new SecurityServiceStub;
    });

    afterEach(function () {
        Schema::dropIfExists('security_model_stubs');
    });

    test('it throws RecordNotFoundException instead of Laravel native exception', function () {
        // Assert that calling fail-safe methods throws our domain exception
        $this->service->findOrFail('non-existent-uuid');
    })->throws(RecordNotFoundException::class);

    test('it enforces Gate::authorize on create operations', function () {
        // We define a gate for the stub that always denies
        Gate::define('create', function ($user, $model) {
            return $model instanceof SecurityModelStub ? false : true;
        });

        $this->service->create(['name' => 'Should Fail']);
    })->throws(\Illuminate\Auth\Access\AuthorizationException::class);

    test('it enforces Gate::authorize on update operations', function () {
        $record = SecurityModelStub::create(['name' => 'Original']);
        
        Gate::define('update', function ($user, $model) {
            return $model instanceof SecurityModelStub ? false : true;
        });

        $this->service->update($record->id, ['name' => 'Hacked']);
    })->throws(\Illuminate\Auth\Access\AuthorizationException::class);
});
