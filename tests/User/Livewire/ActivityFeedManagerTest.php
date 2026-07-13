<?php

declare(strict_types=1);

use App\User\Livewire\ActivityFeedManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    test()->actingAs($user);
});

test('renders for authenticated user', function () {
    Livewire::test(ActivityFeedManager::class)
        ->assertSuccessful();
});

test('displays activity feed with entries', function () {
    $user = auth()->user();

    activity()->by($user)->log('submitted a logbook entry');
    activity()->by($user)->log('clocked in');

    Livewire::test(ActivityFeedManager::class)
        ->assertSee('submitted a logbook entry')
        ->assertSee('clocked in');
});

test('handles empty activity feed', function () {
    Livewire::test(ActivityFeedManager::class)
        ->assertSee('No activity found.');
});

test('scopes activities to authenticated user', function () {
    $userA = auth()->user();
    $userB = User::factory()->create();

    activity()->by($userA)->log('user a event');
    activity()->by($userB)->log('user b event');

    Livewire::test(ActivityFeedManager::class)
        ->assertSee('user a event')
        ->assertDontSee('user b event');
});
