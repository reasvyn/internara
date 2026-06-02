<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Core\Models\ActivityLog;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    activity()->causedBy($this->user)->log('First activity');
    activity()->causedBy($this->user)->log('Second activity');
    activity()->causedBy($this->otherUser)->log('Other activity');
});

describe('ActivityLog', function () {
    it('extends Spatie Activity model', function () {
        expect(new ActivityLog)->toBeInstanceOf(Activity::class);
    });

    it('filters by user', function () {
        $logs = ActivityLog::forUser($this->user->id)->get();

        expect($logs)->toHaveCount(2);
    });

    it('filters by subject type', function () {
        activity()->performedOn($this->user)->log('Subject activity');

        $logs = ActivityLog::whereSubject(get_class($this->user))->get();

        expect($logs)->toHaveCount(1);
    });

    it('filters by subject type and id', function () {
        activity()->performedOn($this->user)->log('Subject with id');

        $logs = ActivityLog::whereSubject(get_class($this->user), $this->user->id)->get();

        expect($logs)->toHaveCount(1);
    });

    it('filters by action event', function () {
        activity()->event('created')->log('Created resource');

        $logs = ActivityLog::ofAction('created')->get();

        expect($logs)->toHaveCount(1);
    });

    it('filters by log name', function () {
        activity()->useLog('custom')->log('Custom log entry');

        $logs = ActivityLog::inLog('custom')->get();

        expect($logs)->toHaveCount(1);
    });

    it('returns recent logs', function () {
        $logs = ActivityLog::recent(2)->get();

        expect($logs)->toHaveCount(2);
    });

    it('filters by last days', function () {
        $logs = ActivityLog::lastDays(30)->get();

        expect($logs)->toHaveCount(3);
    });

    it('groups by day', function () {
        $log = new ActivityLog;
        $grouped = $log->getGroupedByDay(30);

        expect($grouped)->toBeInstanceOf(Collection::class)
            ->and($grouped->count())->toBeGreaterThanOrEqual(1);
    });

    it('returns subject model short name', function () {
        $activity = new ActivityLog;
        $activity->subject_type = get_class($this->user);

        expect($activity->subject_model)->toBe('User');
    });

    it('returns null when subject_type is not set', function () {
        $activity = new ActivityLog;

        expect($activity->subject_model)->toBeNull();
    });

    it('filters by module pattern on subject_type', function () {
        activity()->performedOn($this->user)->log('Module activity');

        $logs = ActivityLog::forModule('User')->get();

        expect($logs->count())->toBeGreaterThanOrEqual(1);
    });
});
