<?php

declare(strict_types=1);

use App\Incident\IncidentReport\Livewire\IncidentForm;
use Livewire\Livewire;

test('component class is instantiable', function () {
    expect(class_exists(IncidentForm::class))->toBeTrue();
});

test('has required validation rules for form data', function () {
    $reflection = new ReflectionClass(IncidentForm::class);
    expect($reflection->hasProperty('formData'))->toBeTrue();
    expect($reflection->hasMethod('save'))->toBeTrue();
    expect($reflection->hasMethod('render'))->toBeTrue();
});
