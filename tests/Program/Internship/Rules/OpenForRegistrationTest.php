<?php

declare(strict_types=1);

use App\Program\Internship\Enums\InternshipStatus;
use App\Program\Internship\Models\Internship;
use App\Program\Internship\Rules\OpenForRegistration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('passes for published internship with open registration window', function () {
    $internship = Internship::factory()->create([
        'status' => InternshipStatus::PUBLISHED,
    ]);
    $rule = new OpenForRegistration;
    $passed = true;

    $rule->validate('internship_id', $internship->id, function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});

test('passes for active internship with open registration window', function () {
    $internship = Internship::factory()->create([
        'status' => InternshipStatus::ACTIVE,
    ]);
    $rule = new OpenForRegistration;
    $passed = true;

    $rule->validate('internship_id', $internship->id, function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});

test('fails for draft internship', function () {
    $internship = Internship::factory()->create(['status' => InternshipStatus::DRAFT]);
    $rule = new OpenForRegistration;
    $failed = false;

    $rule->validate('internship_id', $internship->id, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('fails for non-existent internship', function () {
    $rule = new OpenForRegistration;
    $failed = false;

    $rule->validate('internship_id', '00000000-0000-0000-0000-000000000000', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('fails for completed internship', function () {
    $internship = Internship::factory()->create(['status' => InternshipStatus::COMPLETED]);
    $rule = new OpenForRegistration;
    $failed = false;

    $rule->validate('internship_id', $internship->id, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('fails for cancelled internship', function () {
    $internship = Internship::factory()->create(['status' => InternshipStatus::CANCELLED]);
    $rule = new OpenForRegistration;
    $failed = false;

    $rule->validate('internship_id', $internship->id, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});
