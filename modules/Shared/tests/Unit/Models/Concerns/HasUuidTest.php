<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Models\Concerns\HasUuid;

class UuidModelStub extends Model
{
    use HasUuid;

    protected $table = 'uuid_model_stubs';
    protected $guarded = [];
}

describe('HasUuid Trait', function () {
    beforeEach(function () {
        Schema::create('uuid_model_stubs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
        });
    });

    afterEach(function () {
        Schema::dropIfExists('uuid_model_stubs');
    });

    test('test fulfills identity protection by generating uuid on creation', function () {
        $model = new UuidModelStub;
        $model->save();

        expect($model->id)
            ->not->toBeNull()
            ->and(strlen($model->id))->toBe(36)
            ->and($model->getIncrementing())->toBeFalse()
            ->and($model->getKeyType())->toBe('string');
    });

    test('test preserves manually provided uuid', function () {
        $customId = '550e8400-e29b-41d4-a716-446655440000';
        $model = new UuidModelStub(['id' => $customId]);
        $model->save();

        expect($model->id)->toBe($customId);
    });
});
