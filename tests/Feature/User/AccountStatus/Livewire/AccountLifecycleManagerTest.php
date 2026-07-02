<?php

declare(strict_types=1);

use App\User\AccountStatus\Livewire\AccountLifecycleManager;
use Livewire\Livewire;

test('component class is instantiable', function () {
    expect(class_exists(AccountLifecycleManager::class))->toBeTrue();
});

test('has lifecycle methods', function () {
    $reflection = new ReflectionClass(AccountLifecycleManager::class);
    expect($reflection->hasMethod('askLock'))->toBeTrue();
    expect($reflection->hasMethod('askUnlock'))->toBeTrue();
    expect($reflection->hasMethod('confirmAction'))->toBeTrue();
    expect($reflection->hasMethod('detectClones'))->toBeTrue();
    expect($reflection->hasMethod('render'))->toBeTrue();
});
