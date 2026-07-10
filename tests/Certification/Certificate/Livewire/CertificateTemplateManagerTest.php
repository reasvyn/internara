<?php

declare(strict_types=1);

use App\Certification\Certificate\Livewire\CertificateTemplateManager;
use Livewire\Livewire;

test('component class is instantiable', function () {
    expect(class_exists(CertificateTemplateManager::class))->toBeTrue();
});

test('has create and save methods', function () {
    $reflection = new ReflectionClass(CertificateTemplateManager::class);
    expect($reflection->hasMethod('create'))->toBeTrue();
    expect($reflection->hasMethod('saveTemplate'))->toBeTrue();
    expect($reflection->hasMethod('render'))->toBeTrue();
});
