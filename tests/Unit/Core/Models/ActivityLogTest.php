<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Models;

use App\Core\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery;

beforeEach(function () {
    $this->builder = Mockery::mock(Builder::class);
    $this->log = new ActivityLog;
});

test('scope for user applies causer where constraint', function () {
    $this->builder->shouldReceive('where')
        ->once()
        ->with('causer_id', '123')
        ->andReturnSelf();

    $this->log->scopeForUser($this->builder, '123');
});

test('scope where subject with type only', function () {
    $this->builder->shouldReceive('where')
        ->once()
        ->with('subject_type', 'App\Models\User')
        ->andReturnSelf();

    $this->log->scopeWhereSubject($this->builder, 'App\Models\User');
});

test('scope where subject with type and id', function () {
    $this->builder->shouldReceive('where')
        ->once()
        ->with('subject_type', 'App\Models\User')
        ->andReturnSelf();
    $this->builder->shouldReceive('where')
        ->once()
        ->with('subject_id', '42')
        ->andReturnSelf();

    $this->log->scopeWhereSubject($this->builder, 'App\Models\User', '42');
});

test('scope of action applies event where constraint', function () {
    $this->builder->shouldReceive('where')
        ->once()
        ->with('event', 'create')
        ->andReturnSelf();

    $this->log->scopeOfAction($this->builder, 'create');
});

test('scope in log applies whereIn constraint', function () {
    $this->builder->shouldReceive('whereIn')
        ->once()
        ->with('log_name', ['audit', 'system'])
        ->andReturnSelf();

    $this->log->scopeInLog($this->builder, 'audit', 'system');
});

test('scope recent applies latest and limit', function () {
    $this->builder->shouldReceive('latest')
        ->once()
        ->andReturnSelf();
    $this->builder->shouldReceive('limit')
        ->once()
        ->with(10)
        ->andReturnSelf();

    $this->log->scopeRecent($this->builder, 10);
});

test('scope last days filters by date', function () {
    $this->builder->shouldReceive('where')
        ->once()
        ->with('created_at', '>=', Mockery::type(Carbon::class))
        ->andReturnSelf();

    $this->log->scopeLastDays($this->builder, 7);
});

test('scope for module filters by subject namespace or log name', function () {
    $this->builder->shouldReceive('where')
        ->once()
        ->with(Mockery::on(fn ($closure) => is_callable($closure)))
        ->andReturnSelf();

    $this->log->scopeForModule($this->builder, 'User');
});

test('get grouped by day returns collection', function () {
    $log = Mockery::mock(ActivityLog::class)->makePartial();
    $builder = Mockery::mock(Builder::class);

    $log->shouldReceive('lastDays')
        ->with(30)
        ->once()
        ->andReturn($builder);

    $builder->shouldReceive('selectRaw')
        ->with('DATE(created_at) as date, COUNT(*) as count')
        ->once()
        ->andReturnSelf();
    $builder->shouldReceive('groupBy')
        ->with('date')
        ->once()
        ->andReturnSelf();
    $builder->shouldReceive('orderBy')
        ->with('date')
        ->once()
        ->andReturnSelf();
    $builder->shouldReceive('get')
        ->once()
        ->andReturn(collect());

    expect($log->getGroupedByDay())->toBeInstanceOf(Collection::class);
});

test('get subject model attribute returns class basename', function () {
    $log = new ActivityLog;
    $log->subject_type = 'App\User\Models\User';

    expect($log->getSubjectModelAttribute())->toBe('User');
});

test('get subject model attribute returns null when subject type is empty', function () {
    $log = new ActivityLog;
    $log->subject_type = null;

    expect($log->getSubjectModelAttribute())->toBeNull();
});
