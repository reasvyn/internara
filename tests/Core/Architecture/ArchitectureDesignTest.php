<?php

declare(strict_types=1);

use App\Modules\Core\Actions\BaseAction;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Core\Actions\BaseReadAction;
use Illuminate\Support\Facades\File;

const D2FT3_EXPECTED_MODULE_ORDER = [
    'Core',
    'Setup',
    'Settings',
    'Auth',
    'User',
    'SysAdmin',
    'Academics',
    'Partners',
    'Program',
    'Enrollment',
    'Journals',
    'Assignment',
    'Reports',
    'Assessment',
    'Evaluation',
    'Certification',
    'Incident',
    'Document',
];

const D2FT3_NON_MODULE_APP_DIRS = ['Jobs', 'Providers'];

test('D2FT3-FR-ARC1: every business directory in app/ is a registered module', function () {
    $registered = config('module.list');

    $topLevel = array_values(array_filter(
        array_map('basename', File::directories(app_path())),
        fn (string $dir) => ! in_array($dir, D2FT3_NON_MODULE_APP_DIRS, true),
    ));

    foreach ($topLevel as $dir) {
        expect($registered)->toContain($dir);
    }
});

test('D2FT3-FR-ARC2: every registered module owns a colocated app/{Module} directory', function () {
    foreach (config('module.list') as $module) {
        expect(File::isDirectory(app_path($module)))->toBeTrue();
    }
});

test('D2FT3-FR-ARC5/FR-ARC31: module registry follows the canonical dependency order with Core first', function () {
    $list = config('module.list');

    expect($list)->toBe(D2FT3_EXPECTED_MODULE_ORDER)
        ->and($list[0])->toBe('Core');
});

test('D2FT3-FR-ARC11: Command and Process actions extend BaseAction; Read action is standalone', function () {
    expect(is_subclass_of(BaseCommandAction::class, BaseAction::class))->toBeTrue()
        ->and(is_subclass_of(BaseProcessAction::class, BaseAction::class))->toBeTrue()
        ->and(is_subclass_of(BaseReadAction::class, BaseAction::class))->toBeFalse();

    $reflection = new ReflectionClass(BaseAction::class);
    expect($reflection->isAbstract())->toBeTrue();
});
