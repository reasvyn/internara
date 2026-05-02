<?php

declare(strict_types=1);

namespace Tests\Arch\Layers;

/**
 * S2 - Sustain: Layer Separation Tests
 * Ensures that layers don't violate the dependency rules defined in docs/architecture.md
 */
describe('Layer Separation Rules', function () {

    test('controllers should not directly use models for data manipulation')
        ->expect('App\Http\Controllers')
        ->not->toUse(['App\Models\Internship', 'App\Models\InternshipPlacement', 'App\Models\AttendanceLog', 'App\Models\School', 'App\Models\Department', 'App\Models\InternshipCompany'])
        ->ignoring(['App\Http\Controllers\Controller', 'App\Http\Controllers\MentorController', 'App\Http\Controllers\AccountLifecycleController', 'App\Http\Controllers\TeacherController', 'App\Http\Controllers\InternshipController']);

    test('controllers should not use repositories for writes')
        ->expect('App\Http\Controllers')
        ->not->toUse('App\Repositories')
        ->ignoring([
            'App\Http\Controllers\Controller',
            'App\Http\Controllers\InternshipController',
        ]);

    test('actions should not use controllers or livewire')
        ->expect('App\Actions')
        ->not->toUse(['App\Http\Controllers', 'App\Livewire']);

    test('models should not depend on actions')
        ->expect('App\Models')
        ->not->toUse('App\Actions');

    test('models should not use http-specific code')
        ->expect('App\Models')
        ->not->toUse(['App\Http', 'request', 'redirect']);

    test('repositories should not use actions')
        ->expect('App\Repositories')
        ->not->toUse('App\Actions');

    test('repositories should only return eloquent objects')
        ->expect('App\Repositories')
        ->not->toUse(['array', 'json_decode', 'json_encode']);

    test('listeners should not use controllers or livewire')
        ->expect('App\Listeners')
        ->not->toUse(['App\Http\Controllers', 'App\Livewire']);

    test('services should not contain business rules')
        ->expect('App\Services')
        ->not->toUse('App\Models');
});
