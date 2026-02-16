<?php

declare(strict_types=1);

namespace Modules\Schedule\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\Contracts\ScheduleService;


beforeEach(function () {
    \Modules\Setting\Facades\Setting::shouldReceive('getValue')
        ->with('active_academic_year', \Mockery::any(), \Mockery::any())
        ->andReturn('2025/2026');
});

test('it can create a schedule event via service', function () {
    $service = app(ScheduleService::class);

    $data = [
        'title' => 'Pembekalan PKL',
        'description' => 'Sesi briefing persiapan magang',
        'start_at' => now()->addDays(1)->toDateTimeString(),
        'type' => ScheduleType::BRIEFING,
        'academic_year' => '2025/2026',
    ];

    $schedule = $service->create($data);

    expect($schedule)
        ->toBeInstanceOf(Schedule::class)
        ->and($schedule->title)
        ->toBe('Pembekalan PKL')
        ->and($schedule->type)
        ->toBe(ScheduleType::BRIEFING);
});

test('it can retrieve student timeline filtered by registration', function () {
    $service = app(ScheduleService::class);
    $studentId = (string) \Illuminate\Support\Str::uuid();

    $internship = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $registration = app(\Modules\Internship\Services\Contracts\RegistrationService::class)
        ->factory()
        ->create([
            'student_id' => $studentId,
            'internship_id' => $internship->id,
        ]);

    Schedule::factory()->create([
        'title' => 'Global Event',
        'internship_id' => null,
        'academic_year' => '2025/2026',
    ]);

    Schedule::factory()->create([
        'title' => 'Specific Event',
        'internship_id' => $internship->id,
        'academic_year' => '2025/2026',
    ]);

    $otherInternship = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    Schedule::factory()->create([
        'title' => 'Other Event',
        'internship_id' => $otherInternship->id,
        'academic_year' => '2025/2026',
    ]);

    $timeline = $service->getStudentTimeline($studentId);

    expect($timeline)
        ->toHaveCount(2)
        ->and($timeline->pluck('title'))
        ->toContain('Global Event', 'Specific Event')
        ->and($timeline->pluck('title'))
        ->not->toContain('Other Event');
});
