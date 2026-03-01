<?php

declare(strict_types=1);

namespace Modules\Guidance\Tests\Feature\Gating;


use Modules\Journal\Services\Contracts\JournalService;
use Modules\Guidance\Services\Contracts\HandbookService;
use Modules\User\Services\Contracts\UserService;



test('blocked access audit: student cannot create journal without acknowledging handbooks', function () {
    $student = app(UserService::class)->factory()->create();
    $student->assignRole('student');
    $this->actingAs($student);
    
    // Ensure guidance is enabled and student has not acknowledged
    setting(['feature_guidance_enabled' => true]);
    
    // We expect 403 Forbidden from JournalService
    // Note: This requires Journal module to be present and following the contract
    expect(fn() => app(JournalService::class)->create([
        'registration_id' => \Str::uuid()->toString(), // Dummy UUID for isolation test
        'work_topic' => 'Test',
        'activity_description' => 'Test',
    ]))->toThrow(\Modules\Exception\AppException::class, 'guidance::messages.must_complete_guidance');
});

test('bypass audit: gating is ignored if feature is disabled', function () {
    $student = app(UserService::class)->factory()->create();
    $student->assignRole('student');
    $this->actingAs($student);
    
    setting(['feature_guidance_enabled' => false]);
    
    // Create a dummy registration first to satisfy SLRI
    $reg = \Modules\Internship\Models\InternshipRegistration::factory()->create(['student_id' => $student->id]);

    // Now it should pass the guidance check (but might fail on other journal rules)
    // We mock the competency sync to isolate this test
    $competencyService = $this->mock(\Modules\Assessment\Services\Contracts\CompetencyService::class);
    $competencyService->shouldIgnoreMissing();

    $result = app(JournalService::class)->create([
        'registration_id' => $reg->id,
        'work_topic' => 'Test topic',
        'activity_description' => 'Test description',
        'date' => now()->toDateString(),
    ]);

    expect($result)->toBeInstanceOf(\Modules\Journal\Models\JournalEntry::class);
});
