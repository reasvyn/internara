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
| Tests are organized by type (Arch, Unit, Feature, Browser) with
| per-module subdirectories. Each type is registered as a Pest suite.
| Adding a new module is just creating the directory under the type.
|
| Structure:
|   tests/Arch/{Module}/*.php    — Architecture / contract tests
|   tests/Unit/{Module}/*.php    — Unit tests (Entity, DTO, Enum, Model)
|   tests/Feature/{Module}/*.php — Feature tests (Action, Livewire, Policy)
|   tests/Browser/{Module}/*.php — Browser / E2E tests
|   tests/Support/               — Shared helpers (NOT a test suite)
|
*/

$testTypes = ['Arch', 'Unit', 'Feature', 'Browser'];

$dirs = [];

foreach ($testTypes as $type) {
    $typePath = __DIR__.'/'.$type;
    if (! is_dir($typePath)) {
        continue;
    }
    foreach (scandir($typePath) as $module) {
        if ($module === '.' || $module === '..') {
            continue;
        }
        $modulePath = $typePath.'/'.$module;
        if (is_dir($modulePath)) {
            $dirs[] = $modulePath;
        }
    }
}

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
