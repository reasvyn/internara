<?php

declare(strict_types=1);

use App\Domain\Core\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ActivityLog scope query filters work as expected', function () {
    // Create direct entries in activity_log table
    ActivityLog::create([
        'log_name' => 'setup',
        'description' => 'System setup completed',
        'event' => 'installed',
        'subject_type' => 'App\Domain\Setup\Models\Setup',
        'subject_id' => '123e4567-e89b-12d3-a456-426614174000',
        'causer_type' => 'App\Domain\User\Models\User',
        'causer_id' => '11111111-1111-1111-1111-111111111111',
        'created_at' => now(),
    ]);

    ActivityLog::create([
        'log_name' => 'admin',
        'description' => 'User updated settings',
        'event' => 'updated',
        'subject_type' => 'App\Domain\SysAdmin\Models\Setting',
        'subject_id' => '22222222-2222-2222-2222-222222222222',
        'causer_type' => 'App\Domain\User\Models\User',
        'causer_id' => '22222222-2222-2222-2222-222222222222',
        'created_at' => now()->subDays(5),
    ]);

    // ForUser scope
    $forUser1 = ActivityLog::forUser('11111111-1111-1111-1111-111111111111')->get();
    expect($forUser1)->toHaveCount(1);
    expect($forUser1->first()->description)->toBe('System setup completed');

    // WhereSubject scope
    $whereSub = ActivityLog::whereSubject('App\Domain\Setup\Models\Setup', '123e4567-e89b-12d3-a456-426614174000')->get();
    expect($whereSub)->toHaveCount(1);

    // OfAction scope
    $ofAction = ActivityLog::ofAction('updated')->get();
    expect($ofAction)->toHaveCount(1);

    // InLog scope
    $inLog = ActivityLog::inLog('setup', 'admin')->get();
    expect($inLog)->toHaveCount(2);

    // Recent scope
    $recent = ActivityLog::recent(1)->get();
    expect($recent)->toHaveCount(1);

    // LastDays scope
    $lastDays = ActivityLog::lastDays(3)->get();
    expect($lastDays)->toHaveCount(1); // the other was 5 days ago

    // ForModule scope
    $forModuleSetup = ActivityLog::forModule('Setup')->get();
    expect($forModuleSetup)->toHaveCount(1);
    expect($forModuleSetup->first()->log_name)->toBe('setup');

    $forModuleAdmin = ActivityLog::forModule('Admin')->get();
    expect($forModuleAdmin)->toHaveCount(1);

    // getGroupedByDay
    $grouped = (new ActivityLog)->getGroupedByDay(10);
    expect($grouped)->not->toBeEmpty();

    // getSubjectModelAttribute
    $log = ActivityLog::where('subject_type', 'App\Domain\Setup\Models\Setup')->first();
    expect($log->subject_model)->toBe('Setup');

    $logNoSubject = new ActivityLog;
    expect($logNoSubject->subject_model)->toBeNull();
});
