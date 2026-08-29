<?php

declare(strict_types=1);

use App\Modules\User\Models\User;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Directory Registration — Auto-discovered from filesystem
|--------------------------------------------------------------------------
|
| Module test directories are discovered directly from the real directory
| structure (tests/{Module}/), so no manual list is needed. Adding a new
| Module test suite is just creating the directory.
|
| Note: config() is not available at Pest discovery time, so we scan the
| filesystem directly here instead of reading config/module.php.
|
*/

$extraDirs = ['Stubs', 'Support'];

$modules = [];
foreach (scandir(__DIR__) as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }
    $path = __DIR__.'/'.$entry;
    if (! is_dir($path)) {
        continue;
    }
    if (in_array($entry, $extraDirs, true)) {
        continue;
    }
    // Only consider directories that contain at least one PHP file (a test suite)
    if (glob($path.'/*.php') || glob($path.'/*/*.php') || glob($path.'/**/*.php')) {
        $modules[] = $entry;
    } elseif (is_dir($path)) {
        // Empty module directories are also registered (suites get tests as they are rewritten)
        $modules[] = $entry;
    }
}
sort($modules);

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
