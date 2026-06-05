<?php

declare(strict_types=1);

use App\Core\Models\BaseModel;

test('BaseModel is abstract', function () {
    $ref = new ReflectionClass(BaseModel::class);
    expect($ref->isAbstract())->toBeTrue();
});

test('BaseModel uses HasUuids', function () {
    $traits = class_uses(BaseModel::class);
    expect($traits)->toContain('Illuminate\Database\Eloquent\Concerns\HasUuids');
});

test('BaseModel disables incrementing', function () {
    $model = new class extends BaseModel {};
    expect($model->getIncrementing())->toBeFalse();
});

test('BaseModel uses string key type', function () {
    $model = new class extends BaseModel {};
    expect($model->getKeyType())->toBe('string');
});
