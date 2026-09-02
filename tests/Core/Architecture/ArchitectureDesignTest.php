<?php

declare(strict_types=1);

use App\Modules\Core\Actions\BaseAction;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Core\Actions\BaseReadAction;
use Illuminate\Support\Facades\File;

test('D2FT3-FR-ARC1: every directory in app/Modules is a registered module', function () {
    $registered = config('module.list');

    $moduleDirs = array_map('basename', File::directories(config('module.paths.base')));

    foreach ($moduleDirs as $dir) {
        expect($registered)->toContain($dir);
    }
});

test('D2FT3-FR-ARC2: every registered module owns a colocated app/Modules/{Module} directory', function () {
    foreach (config('module.list') as $module) {
        expect(File::isDirectory(config('module.paths.base').DIRECTORY_SEPARATOR.$module))->toBeTrue();
    }
});

test('D2FT3-FR-ARC5/FR-ARC31: module registry auto-discovers app/Modules directories in deterministic order', function () {
    $list = config('module.list');

    $discovered = array_map('basename', File::directories(config('module.paths.base')));
    sort($discovered);

    expect($list)->toBe($discovered)
        ->and($list)->not->toBeEmpty()
        ->and(in_array('Core', $list, true))->toBeTrue();
});

test('D2FT3-FR-ARC11: Command and Process actions extend BaseAction; Read action is standalone', function () {
    expect(is_subclass_of(BaseCommandAction::class, BaseAction::class))->toBeTrue()
        ->and(is_subclass_of(BaseProcessAction::class, BaseAction::class))->toBeTrue()
        ->and(is_subclass_of(BaseReadAction::class, BaseAction::class))->toBeFalse();

    $reflection = new ReflectionClass(BaseAction::class);
    expect($reflection->isAbstract())->toBeTrue();
});
