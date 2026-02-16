<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Unit\Models;

use Modules\Media\Models\Media;

test('it can set module attribute', function () {
    $media = new Media;
    $media->withModule('Shared');

    expect($media->module)->toBe('Shared');
});

test('it has correct fillable attributes', function () {
    $media = new Media;
    expect($media->getFillable())
        ->toContain('module')
        ->and($media->getFillable())
        ->toContain('model_id');
});
