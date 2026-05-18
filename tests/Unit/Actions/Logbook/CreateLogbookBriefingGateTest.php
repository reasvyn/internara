<?php

declare(strict_types=1);

use App\Actions\Logbook\CreateLogbookAction;
use App\Models\Briefing;
use App\Models\BriefingAttendance;
use App\Models\Internship;
use App\Models\Mentee;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStudentWithActiveRegistration(): array
{
    $user = User::factory()->create();
    $internship = Internship::factory()->create();
    $mentee = Mentee::factory()->create(['user_id' => $user->id]);
    $registration = Registration::factory()->create([
        'mentee_id' => $mentee->id,
        'internship_id' => $internship->id,
    ]);
    $registration->setStatus('active');

    return ['user' => $user, 'internship' => $internship, 'mentee' => $mentee];
}

it('blocks logbook creation when mandatory briefing not attended', function () {
    $data = createStudentWithActiveRegistration();

    Briefing::create([
        'title' => 'Mandatory Briefing',
        'date' => now()->subDay(),
        'is_mandatory' => true,
        'internship_id' => $data['internship']->id,
        'created_by' => User::factory()->create()->id,
    ]);

    app(CreateLogbookAction::class)->execute($data['user']->id, [
        'date' => now()->format('Y-m-d'),
        'content' => 'Test entry',
    ]);
})->throws(RuntimeException::class, 'You must attend the mandatory briefing');

it('allows logbook creation when mandatory briefing attended', function () {
    $data = createStudentWithActiveRegistration();

    $briefing = Briefing::create([
        'title' => 'Mandatory Briefing',
        'date' => now()->subDay(),
        'is_mandatory' => true,
        'internship_id' => $data['internship']->id,
        'created_by' => User::factory()->create()->id,
    ]);

    BriefingAttendance::create([
        'briefing_id' => $briefing->id,
        'user_id' => $data['user']->id,
        'attended' => true,
    ]);

    $entry = app(CreateLogbookAction::class)->execute($data['user']->id, [
        'date' => now()->format('Y-m-d'),
        'content' => 'Test entry',
    ]);

    expect($entry)->not->toBeNull();
});

it('allows logbook creation when no mandatory briefing exists', function () {
    $data = createStudentWithActiveRegistration();

    $entry = app(CreateLogbookAction::class)->execute($data['user']->id, [
        'date' => now()->format('Y-m-d'),
        'content' => 'Test entry without briefing',
    ]);

    expect($entry)->not->toBeNull();
});
