<?php

declare(strict_types=1);

use App\Certification\Certificate\Livewire\StudentCertificates;
use Livewire\Livewire;

test('component class is instantiable', function () {
    expect(class_exists(StudentCertificates::class))->toBeTrue();
});

test('has boot check and render method', function () {
    $reflection = new ReflectionClass(StudentCertificates::class);
    expect($reflection->hasMethod('boot'))->toBeTrue();
    expect($reflection->hasMethod('render'))->toBeTrue();
});
