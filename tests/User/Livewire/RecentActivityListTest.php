<?php

declare(strict_types=1);

use App\User\Livewire\RecentActivityList;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    test()->actingAs($user);
});

test('renders for authenticated user', function () {
    Livewire::test(RecentActivityList::class)
        ->assertSuccessful();
});

test('displays recent activities', function () {
    $user = auth()->user();

    activity()->by($user)->log('created_a_logbook_entry');
    activity()->by($user)->log('updated_profile');

    Livewire::test(RecentActivityList::class)
        ->assertSee('Created A Logbook Entry')
        ->assertSee('Updated Profile');
});

test('handles empty activity log', function () {
    Livewire::test(RecentActivityList::class)
        ->assertSee('No recent activity found.');
});

test('limits to 10 activities', function () {
    $user = auth()->user();

    foreach (range(1, 15) as $i) {
        activity()->by($user)->log("event {$i}");
    }

    $component = Livewire::test(RecentActivityList::class);
    $activities = $component->get('activities');
    expect($activities)->toHaveCount(10);
});

test('scopes activities to authenticated user', function () {
    $userA = auth()->user();
    $userB = User::factory()->create();

    activity()->by($userA)->log('user a event');
    activity()->by($userB)->log('user b event');

    $component = Livewire::test(RecentActivityList::class);
    $activities = $component->get('activities');
    expect($activities)->toHaveCount(1);
    expect($activities->first()->description)->toBe('user a event');
});

test('returns activities ordered by latest first', function () {
    $user = auth()->user();

    activity()->by($user)->log('first event');
    $this->travel(1)->second();
    activity()->by($user)->log('second event');

    $component = Livewire::test(RecentActivityList::class);
    $activities = $component->get('activities');
    expect($activities->first()->description)->toBe('second event');
});
