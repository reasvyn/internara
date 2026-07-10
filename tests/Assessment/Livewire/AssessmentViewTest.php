<?php

declare(strict_types=1);

use App\Assessment\Livewire\AssessmentView;
use Livewire\Livewire;

test('component class is instantiable', function () {
    expect(class_exists(AssessmentView::class))->toBeTrue();
});
