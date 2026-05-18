<?php

declare(strict_types=1);

use App\Actions\Attendance\ClockInAction;
use App\Models\Briefing;
use App\Models\BriefingAttendance;
use App\Models\Internship;
use App\Models\Mentee;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createUserWithActiveRegistration(): User
{
    $user = User::factory()->create();
    $internship = Internship::factory()->create();
    $mentee = Mentee::factory()->create(['user_id' => $user->id]);
    $registration = Registration::factory()->create([
        'mentee_id' => $mentee->id,
        'internship_id' => $internship->id,
    ]);
    $registration->setStatus('active');

    return $user;
}

it('blocks clock-in when mandatory briefing not attended', function () {
    $user = createUserWithActiveRegistration();

    Briefing::create([
        'title' => 'Mandatory Briefing',
        'date' => now()->subDay(),
        'is_mandatory' => true,
        'internship_id' => $user->registrations()->first()->internship_id,
        'created_by' => User::factory()->create()->id,
    ]);

    app(ClockInAction::class)->execute($user, []);
})->throws(RuntimeException::class, 'You must attend the mandatory briefing');

it('allows clock-in when mandatory briefing attended', function () {
    $user = createUserWithActiveRegistration();
    $internshipId = $user->registrations()->first()->internship_id;

    $briefing = Briefing::create([
        'title' => 'Mandatory Briefing',
        'date' => now()->subDay(),
        'is_mandatory' => true,
        'internship_id' => $internshipId,
        'created_by' => User::factory()->create()->id,
    ]);

    BriefingAttendance::create([
        'briefing_id' => $briefing->id,
        'user_id' => $user->id,
        'attended' => true,
    ]);

    $attendance = app(ClockInAction::class)->execute($user, []);

    expect($attendance)->not->toBeNull();
});
