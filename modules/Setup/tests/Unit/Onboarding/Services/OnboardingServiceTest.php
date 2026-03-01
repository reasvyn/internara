<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Onboarding\Services;

use Modules\Setup\Onboarding\Services\OnboardingService;
use Modules\User\Services\Contracts\UserService;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Student\Services\Contracts\StudentService;
use Modules\Teacher\Services\Contracts\TeacherService;

describe('OnboardingService Unit Test', function () {
    beforeEach(function () {
        $this->userService = $this->mock(UserService::class);
        $this->profileService = $this->mock(ProfileService::class);
        $this->studentService = $this->mock(StudentService::class);
        $this->teacherService = $this->mock(TeacherService::class);
        
        $this->service = new OnboardingService(
            $this->userService,
            $this->profileService,
            $this->studentService,
            $this->teacherService
        );
    });

    test('it generates correct template for students', function () {
        $template = $this->service->generateTemplate('student');

        expect($template)->toContain('name,email,national_identifier,phone');
    });

    test('it returns error if file not found', function () {
        $results = $this->service->importFromCsv('non_existent.csv', 'student');

        expect($results['success'])->toBe(0)
            ->and($results['failure'])->toBe(1);
    });

    test('it processes valid csv row', function () {
        $this->userService->shouldReceive('create')->once()->andReturn($this->mock(\Modules\User\Models\User::class));

        // Create temporary CSV
        $csvPath = tempnam(sys_get_temp_dir(), 'test_') . '.csv';
        file_put_contents($csvPath, "name,email,national_identifier,phone\nJohn Doe,john@example.com,12345,0812");

        $results = $this->service->importFromCsv($csvPath, 'student');

        expect($results['success'])->toBe(1);
        unlink($csvPath);
    });
});
