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
| Currently only the Core module suite exists (spec-driven rewrite).
| As each module's spec-driven suite is rewritten, re-register it here
| and in phpunit.xml.
|
*/

$modules = [
    'Core',
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
