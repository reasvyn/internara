<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasUuid;

class UuidModelStub extends Model
{
    use HasUuid;

    protected $guarded = [];
}

describe('HasUuid Trait', function () {
    test('it fulfills [SYRS-NF-504] by generating a UUID on creation', function () {
        $model = new UuidModelStub;
        $model->save();

        expect($model->id)
            ->not->toBeNull()
            ->and(strlen($model->id))
            ->toBe(36)
            ->and($model->getIncrementing())
            ->toBeFalse()
            ->and($model->getKeyType())
            ->toBe('string');
    });

    test('it does not overwrite existing id if provided', function () {
        $customId = 'custom-id';
        $model = new UuidModelStub(['id' => $customId]);
        $model->save();

        expect($model->id)->toBe($customId);
    });
});
