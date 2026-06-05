<?php

declare(strict_types=1);
use App\User\Models\User;
use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->in(__DIR__.'/Feature', __DIR__.'/Unit');

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
