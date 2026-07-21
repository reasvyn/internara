<?php

declare(strict_types=1);
use App\User\Models\User;
use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->in(
        __DIR__.'/Academics',
        __DIR__.'/Assessment',
        __DIR__.'/Assignment',
        __DIR__.'/Auth',
        __DIR__.'/Certification',
        __DIR__.'/Core',
        __DIR__.'/Document',
        __DIR__.'/Enrollment',
        __DIR__.'/Evaluation',
        __DIR__.'/Incident',
        __DIR__.'/Journals',
        __DIR__.'/Partners',
        __DIR__.'/Program',
        __DIR__.'/Providers',
        __DIR__.'/Reports',
        __DIR__.'/Settings',
        __DIR__.'/Setup',
        __DIR__.'/SysAdmin',
        __DIR__.'/User',
    );

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
