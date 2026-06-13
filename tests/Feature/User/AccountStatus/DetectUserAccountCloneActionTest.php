<?php

declare(strict_types=1);

use App\User\AccountStatus\Actions\DetectUserAccountCloneAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('detects users with duplicate email', function () {
    User::factory()->create(['email' => 'duplicate@test.com']);
    User::factory()->create(['email' => 'duplicate@test.com']);

    $action = app(DetectUserAccountCloneAction::class);
    $result = $action->execute();

    expect($result)->toHaveCount(1);
    expect($result->first())->toMatchArray([
        'type' => 'duplicate_email',
        'identifier' => 'duplicate@test.com',
    ]);
    expect($result->first()['user_ids'])->toHaveCount(2);
});

test('returns empty collection when no duplicates exist', function () {
    User::factory()->create(['email' => 'one@test.com']);
    User::factory()->create(['email' => 'two@test.com']);

    $action = app(DetectUserAccountCloneAction::class);
    $result = $action->execute();

    expect($result)->toHaveCount(0);
});

test('detects multiple duplicate email groups', function () {
    User::factory()->create(['email' => 'dup1@test.com']);
    User::factory()->create(['email' => 'dup1@test.com']);
    User::factory()->create(['email' => 'dup2@test.com']);
    User::factory()->create(['email' => 'dup2@test.com']);

    $action = app(DetectUserAccountCloneAction::class);
    $result = $action->execute();

    expect($result)->toHaveCount(2);
});

test('handles three-way duplicate emails', function () {
    User::factory()->create(['email' => 'triple@test.com']);
    User::factory()->create(['email' => 'triple@test.com']);
    User::factory()->create(['email' => 'triple@test.com']);

    $action = app(DetectUserAccountCloneAction::class);
    $result = $action->execute();

    expect($result)->toHaveCount(1);
    expect($result->first()['user_ids'])->toHaveCount(3);
});
