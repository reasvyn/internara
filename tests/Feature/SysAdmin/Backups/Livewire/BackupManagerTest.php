<?php

declare(strict_types=1);

use App\SysAdmin\Backups\Livewire\BackupManager;
use Livewire\Livewire;

test('component class is instantiable', function () {
    expect(class_exists(BackupManager::class))->toBeTrue();
});

test('has CRUD methods', function () {
    $reflection = new ReflectionClass(BackupManager::class);
    expect($reflection->hasMethod('createBackup'))->toBeTrue();
    expect($reflection->hasMethod('confirmDelete'))->toBeTrue();
    expect($reflection->hasMethod('delete'))->toBeTrue();
    expect($reflection->hasMethod('render'))->toBeTrue();
});
