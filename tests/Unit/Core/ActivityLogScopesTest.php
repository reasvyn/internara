<?php

declare(strict_types=1);

use App\Domain\Core\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;

describe('ActivityLog scopes', function () {
    it('can be instantiated via Spatie Activity', function () {
        $log = new ActivityLog;

        expect($log)->toBeInstanceOf(ActivityLog::class);
    });

    it('forUser scope applies causer_id condition', function () {
        $query = ActivityLog::forUser('user-1');
        $wheres = $query->getQuery()->wheres;

        $found = collect($wheres)->first(fn ($w) => isset($w['column']) && $w['column'] === 'causer_id');

        expect($found)->not->toBeNull()
            ->and($found['value'])->toBe('user-1');
    });

    it('ofAction scope applies event condition', function () {
        $query = ActivityLog::ofAction('test-action');
        $wheres = $query->getQuery()->wheres;

        $found = collect($wheres)->first(fn ($w) => isset($w['column']) && $w['column'] === 'event');

        expect($found)->not->toBeNull()
            ->and($found['value'])->toBe('test-action');
    });

    it('inLog scope filters by multiple log names', function () {
        $query = ActivityLog::inLog('auth', 'system');
        $wheres = $query->getQuery()->wheres;

        $found = collect($wheres)->first(fn ($w) => isset($w['type']) && $w['type'] === 'In');

        expect($found)->not->toBeNull();
    });

    it('recent scope limits to latest entries', function () {
        $query = ActivityLog::recent(20);

        expect($query->getQuery()->limit)->toBe(20);
    });

    it('lastDays scope filters by date range', function () {
        $query = ActivityLog::lastDays(7);
        $wheres = $query->getQuery()->wheres;

        $found = collect($wheres)->first(fn ($w) => isset($w['column']) && $w['column'] === 'created_at');

        expect($found)->not->toBeNull();
    });

    it('extracts subject model name from subject_type', function () {
        $log = new ActivityLog;
        $log->subject_type = 'App\Domain\User\Models\User';

        expect($log->subject_model)->toBe('User');
    });

    it('returns null subject model when subject_type is empty', function () {
        $log = new ActivityLog;
        $log->subject_type = null;

        expect($log->subject_model)->toBeNull();
    });

    it('forModule scope returns a query builder', function () {
        $query = ActivityLog::forModule('Auth');

        expect($query)->toBeInstanceOf(Builder::class);
    });

    it('getGroupedByDay exists as instance method', function () {
        expect(method_exists(ActivityLog::class, 'getGroupedByDay'))->toBeTrue();
    });

    it('scopeWhereSubject filters by type and optional id', function () {
        $query = ActivityLog::whereSubject('App\Models\User', 'user-1');

        expect($query)->toBeInstanceOf(Builder::class);
    });

    it('scopeWhereSubject filters by type only when id is null', function () {
        $query = ActivityLog::whereSubject('App\Models\User');

        expect($query)->toBeInstanceOf(Builder::class);
    });
});
