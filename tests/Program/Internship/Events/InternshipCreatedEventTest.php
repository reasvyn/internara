<?php

declare(strict_types=1);

use App\Program\Internship\Events\InternshipCreated;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('holds internship reference', function () {
    $internship = Internship::factory()->make();
    $event = new InternshipCreated($internship);

    expect($event->internship)->toBe($internship);
});

test('created by defaults to null', function () {
    $internship = Internship::factory()->make();
    $event = new InternshipCreated($internship);

    expect($event->createdBy)->toBeNull();
});

test('holds created by user', function () {
    $internship = Internship::factory()->make();
    $user = new User;
    $event = new InternshipCreated($internship, $user);

    expect($event->createdBy)->toBe($user);
});

test('returns event name', function () {
    $internship = Internship::factory()->make();
    $event = new InternshipCreated($internship);

    expect($event->eventName())->toBe('internship.created');
});
