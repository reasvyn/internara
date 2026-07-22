<?php

declare(strict_types=1);

use App\User\Models\User;
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
*/

$modules = [
    'Academics', 'Assessment', 'Assignment', 'Auth', 'Certification',
    'Core', 'Document', 'Enrollment', 'Evaluation', 'Incident',
    'Journals', 'Partners', 'Program', 'Reports', 'Settings',
    'Setup', 'SysAdmin', 'User',
];

$extraDirs = ['Providers', 'Stubs', 'Support'];

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
