<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Exceptions\RejectedException;
use App\Program\Internship\Actions\UpdateInternshipAction;
use App\Program\Internship\Enums\InternshipStatus;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates internship name', function () {
    $internship = Internship::factory()->create();
    $action = app(UpdateInternshipAction::class);

    $updated = $action->execute($internship, ['name' => 'Updated PKL']);

    expect($updated->name)->toBe('Updated PKL');
    $this->assertDatabaseHas('internships', [
        'id' => $internship->id,
        'name' => 'Updated PKL',
    ]);
});

test('updates internship status with valid transition', function () {
    $internship = Internship::factory()->create(['status' => InternshipStatus::DRAFT]);
    $action = app(UpdateInternshipAction::class);

    $updated = $action->execute($internship, ['status' => InternshipStatus::PUBLISHED->value]);

    expect($updated->status)->toBe(InternshipStatus::PUBLISHED);
});

test('rejects invalid status transition', function () {
    $internship = Internship::factory()->create(['status' => InternshipStatus::COMPLETED]);
    $action = app(UpdateInternshipAction::class);

    expect(fn () => $action->execute($internship, ['status' => InternshipStatus::DRAFT->value]))
        ->toThrow(RejectedException::class);
});

test('updates multiple fields at once', function () {
    $year = AcademicYear::factory()->create();
    $internship = Internship::factory()->create();
    $action = app(UpdateInternshipAction::class);

    $updated = $action->execute($internship, [
        'name' => 'PKL 2026',
        'academic_year_id' => $year->id,
        'description' => 'Updated description',
    ]);

    expect($updated->name)->toBe('PKL 2026');
    expect((string) $updated->academic_year_id)->toBe((string) $year->id);
    expect($updated->description)->toBe('Updated description');
});
