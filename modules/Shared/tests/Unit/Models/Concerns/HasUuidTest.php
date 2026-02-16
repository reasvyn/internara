<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Models\Concerns\HasUuid;

uses(RefreshDatabase::class);

class UuidTestModel extends Model
{
    use HasUuid;

    protected $table = 'uuid_test_models';

    protected $guarded = [];
}

describe('HasUuid Trait', function () {
    beforeEach(function () {
        Schema::create('uuid_test_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    });

    test('it fulfills [SYRS-NF-504] by generating a valid uuid v4 on creation', function () {
        $model = UuidTestModel::create();

        expect($model->id)
            ->not->toBeNull()
            ->and(\Illuminate\Support\Str::isUuid($model->id))
            ->toBeTrue();
    });

    test('it does not overwrite existing id if provided', function () {
        $customId = \Illuminate\Support\Str::uuid()->toString();
        $model = UuidTestModel::create(['id' => $customId]);

        expect($model->id)->toBe($customId);
    });
});
