<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Onboarding\Services;

use Illuminate\Support\Facades\DB;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Setup\Onboarding\Services\OnboardingService;
use Modules\Student\Services\Contracts\StudentService;
use Modules\Teacher\Services\Contracts\TeacherService;
use Modules\User\Services\Contracts\UserService;

beforeEach(function () {
    app()->setLocale('en');
});

test('it generates correct template for students', function () {
    $service = new OnboardingService(
        mock(UserService::class),
        mock(ProfileService::class),
        mock(StudentService::class),
        mock(TeacherService::class),
    );

    $template = $service->getTemplate('student');

    expect($template)->toContain(
        'name,email,username,password,phone,address,department_id,national_identifier,registration_number',
    );
});

test('it returns error if file not found', function () {
    $service = new OnboardingService(
        mock(UserService::class),
        mock(ProfileService::class),
        mock(StudentService::class),
        mock(TeacherService::class),
    );

    $results = $service->importFromCsv('non_existent.csv', 'student');

    expect($results['errors'])->not->toBeEmpty();
});

test('it processes valid csv row', function () {
    $userService = mock(UserService::class);
    $profileService = mock(ProfileService::class);
    $studentService = mock(StudentService::class);
    $teacherService = mock(TeacherService::class);

    $service = new OnboardingService(
        $userService,
        $profileService,
        $studentService,
        $teacherService,
    );

    $csvContent =
        "name,email,national_identifier,department_id\nJohn Doe,john@example.com,12345,dept-id";
    $filePath = tempnam(sys_get_temp_dir(), 'test_').'.csv';
    file_put_contents($filePath, $csvContent);

    $userMock = mock(\Modules\User\Models\User::class);
    $userMock->shouldReceive('getAttribute')->with('profile')->andReturn(null);
    $studentService->shouldReceive('create')->once()->andReturn($userMock);

    // We mock DB::transaction
    DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

    $results = $service->importFromCsv($filePath, 'student');

    expect($results['success'])->toBe(1);

    unlink($filePath);
});
