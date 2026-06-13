<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Actions\DeleteInternshipAction;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes internship with no placements or registrations', function () {
    $internship = Internship::factory()->create();
    $action = app(DeleteInternshipAction::class);

    $action->execute($internship);

    $this->assertDatabaseMissing('internships', ['id' => $internship->id]);
});

test('rejects deletion when internship has placements', function () {
    $internship = Internship::factory()->create();
    $placement = Placement::factory()
        ->create(['internship_id' => $internship->id]);
    $action = app(DeleteInternshipAction::class);

    expect(fn () => $action->execute($internship))->toThrow(RejectedException::class);

    $this->assertDatabaseHas('internships', ['id' => $internship->id]);
});

test('rejects deletion when internship has registrations', function () {
    $internship = Internship::factory()->create();
    Registration::factory()
        ->create(['internship_id' => $internship->id]);
    $action = app(DeleteInternshipAction::class);

    expect(fn () => $action->execute($internship))->toThrow(RejectedException::class);

    $this->assertDatabaseHas('internships', ['id' => $internship->id]);
});
