<?php

declare(strict_types=1);

use App\User\Models\User;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Directory Registration
|--------------------------------------------------------------------------
|
| Module test directories. Must be kept in sync with config/module.php.
| config() is not available at Pest discovery time, so this list is
| maintained manually. When adding a new module, update BOTH files.
|
| All modules are registered in build order (docs/specs/index.md §Build
| Order): Core, Setup, Settings, Academics, Auth, User, SysAdmin, Partners,
| Program, Enrollment, Journals, Incident, Assessment, Evaluation,
| Assignment, Document, Certification, Reports. Empty directories are
| registered too — suites get their spec-driven tests as each module is
| rewritten.
|
*/

$modules = [
    'Core',
    'Setup',
    'Settings',
    'Academics',
    'Auth',
    'User',
    'SysAdmin',
    'Partners',
    'Program',
    'Enrollment',
    'Journals',
    'Incident',
    'Assessment',
    'Evaluation',
    'Assignment',
    'Document',
    'Certification',
    'Reports',
];

$extraDirs = ['Stubs', 'Support'];

$dirs = array_merge(
    array_map(fn (string $m) => __DIR__.'/'.$m, $modules),
    array_map(fn (string $d) => __DIR__.'/'.$d, $extraDirs),
);

pest()
    ->extend(TestCase::class)
    ->in(...$dirs);

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function actingAsSuperAdmin(): TestCase
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return test()->actingAs($user);
}

function actingAsAdmin(): TestCase
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return test()->actingAs($user);
}

function actingAsStudent(): TestCase
{
    $user = User::factory()->create();
    $user->assignRole('student');

    return test()->actingAs($user);
}

function captureLogs(): Collection
{
    $captured = collect();

    Log::listen(function (MessageLogged $message) use ($captured) {
        $captured->push($message);
    });

    return $captured;
}
