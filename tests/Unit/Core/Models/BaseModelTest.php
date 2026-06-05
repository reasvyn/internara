<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Models;

use App\Core\Models\BaseModel;

class MockModel extends BaseModel
{
    // Concrete model for testing
}

test('base model has string non-incrementing keys', function () {
    $model = new MockModel;

    expect($model->getIncrementing())->toBeFalse();
    expect($model->getKeyType())->toBe('string');
});
