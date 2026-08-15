<?php

declare(strict_types=1);

use App\Core\Models\ActivityLog;
use App\User\Models\User;
use App\User\Profile\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

it('SE5Q9-FR-M7: forUser() filters entries by causer_id', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    activity()->causedBy($user)->log('mine');
    activity()->causedBy($other)->log('theirs');

    $rows = ActivityLog::forUser($user->id)->get();

    expect($rows)->toHaveCount(1);
    expect($rows->first()->description)->toBe('mine');
});

it('SE5Q9-FR-M7: whereSubject() filters by subject type and optional id', function () {
    $profile = Profile::factory()->create();
    activity()->performedOn($profile)->log('changed');

    expect(ActivityLog::whereSubject(Profile::class, $profile->id)->count())->toBe(1);
    expect(ActivityLog::whereSubject(Profile::class)->count())->toBe(1);
});

it('SE5Q9-FR-M7: ofAction() filters by the event column', function () {
    activity()->event('created')->log('a');
    activity()->event('updated')->log('b');

    expect(ActivityLog::ofAction('created')->get())->toHaveCount(1);
    expect(ActivityLog::ofAction('updated')->get())->toHaveCount(1);
});

it('SE5Q9-FR-M7: inLog() filters by one or more log names', function () {
    activity()->useLog('User')->log('a');
    activity()->useLog('Enrollment')->log('b');

    expect(ActivityLog::inLog('User')->count())->toBe(1);
    expect(ActivityLog::inLog('User', 'Enrollment')->count())->toBe(2);
});

it('SE5Q9-FR-M7: recent() limits to the latest N entries', function () {
    activity()->log('a');
    activity()->log('b');
    activity()->log('c');

    expect(ActivityLog::recent(2)->get())->toHaveCount(2);
});

it('SE5Q9-FR-M7: lastDays() filters by created_at within the window', function () {
    activity()->log('fresh');
    DB::table('activity_log')->insert([
        'description' => 'stale',
        'log_name' => 'default',
        'properties' => null,
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    expect(ActivityLog::lastDays(7)->get())->toHaveCount(1);
});

it('SE5Q9-FR-M7: forModule() matches by subject namespace or log name', function () {
    $profile = Profile::factory()->create();
    activity()->performedOn($profile)->log('on-profile');
    activity()->useLog('User')->log('in-log');

    expect(ActivityLog::forModule('User')->count())->toBe(2);
});

it('SE5Q9-FR-M7: groupedByDay() scope returns daily counts', function () {
    activity()->log('a');
    activity()->log('b');

    $grouped = ActivityLog::query()->groupedByDay(30)->get();

    expect($grouped)->toBeCollection();
    expect($grouped->first())->toHaveKeys(['date', 'count']);
    expect((int) $grouped->first()['count'])->toBe(2);
});

it('SE5Q9-FR-M7: getSubjectModelAttribute() exposes the short subject class name', function () {
    $profile = Profile::factory()->create();
    activity()->performedOn($profile)->log('changed');

    expect(ActivityLog::first()->subject_model)->toBe('Profile');
});
