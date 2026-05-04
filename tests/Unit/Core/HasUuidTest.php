<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

test('has uuid trait disables incrementing', function () {
    $model = new class extends Model
    {
        use HasUuid;

        protected $table = 'settings';

        protected $guarded = [];
    };

    expect($model->getIncrementing())->toBeFalse();
});

test('has uuid trait sets key type to string', function () {
    $model = new class extends Model
    {
        use HasUuid;

        protected $table = 'settings';

        protected $guarded = [];
    };

    expect($model->getKeyType())->toBe('string');
});
