<?php

declare(strict_types=1);

use App\User\Actions\ReadActivityLogAction;
use App\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('returns paginated activity logs for user', function () {
    $user = User::factory()->create();

    activity()->by($user)->log('test event');

    $action = app(ReadActivityLogAction::class);
    $result = $action->execute($user->id);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(1);
});

test('returns empty paginator when user has no activity', function () {
    $user = User::factory()->create();

    $action = app(ReadActivityLogAction::class);
    $result = $action->execute($user->id);

    expect($result->total())->toBe(0);
});

test('logs are ordered by latest first', function () {
    $user = User::factory()->create();

    activity()->by($user)->log('first event');
    $this->travel(1)->second();
    activity()->by($user)->log('second event');

    $action = app(ReadActivityLogAction::class);
    $result = $action->execute($user->id);

    expect($result->first()->description)->toBe('second event');
});

test('respects per page parameter', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        activity()->by($user)->log("event {$i}");
    }

    $action = app(ReadActivityLogAction::class);
    $result = $action->execute($user->id, perPage: 2);

    expect($result->perPage())->toBe(2);
    expect($result->total())->toBe(5);
});

test('only returns logs for specified user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    activity()->by($userA)->log('user a event');
    activity()->by($userB)->log('user b event');

    $action = app(ReadActivityLogAction::class);
    $result = $action->execute($userA->id);

    expect($result->total())->toBe(1);
    expect($result->first()->description)->toBe('user a event');
});
