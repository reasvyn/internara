<?php

declare(strict_types=1);

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('models generate UUID v7 for primary key', function () {
    $user = UserFactory::new()->create();

    expect(Str::isUuid($user->id))->toBeTrue();
    expect(Str::isUuid($user->id, version: 7))->toBeTrue();
});

it('UUID v7 is sortable (time-ordered)', function () {
    $uuid1 = (string) Str::uuid7();
    usleep(1000);
    $uuid2 = (string) Str::uuid7();

    expect($uuid1)->toBeUuid();
    expect($uuid2)->toBeUuid();
    expect($uuid2 > $uuid1)->toBeTrue();
});

it('HasUuids trait works on BaseModel descendants', function () {
    $user = UserFactory::new()->create(['id' => null]);

    expect($user->id)->not->toBeNull();
    expect(strlen($user->id))->toBe(36);
    expect($user->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
});
