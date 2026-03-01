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
        $template = $this->service->getTemplate('student');

        expect($template)->toContain('name,email,username,password,phone,address,department_id,national_identifier,registration_number');
    });

    test('it returns error if file not found', function () {
        $results = $this->service->importFromCsv('non_existent.csv', 'student');

        expect($results['success'])->toBe(0)
            ->and($results['errors'])->not->toBeEmpty();
    });

    test('it processes valid csv row', function () {
        $this->studentService->shouldReceive('create')->once();

        // Create temporary CSV with correct headers matching service logic
        $csvPath = tempnam(sys_get_temp_dir(), 'test_') . '.csv';
        $headers = 'name,email,username,password,phone,address,department_id,national_identifier,registration_number';
        $row = 'John Doe,john@example.com,jdoe,secret,0812,Jl. Merdeka,dept-1,12345,67890';
        file_put_contents($csvPath, $headers . "\n" . $row);

        $results = $this->service->importFromCsv($csvPath, 'student');

        expect($results['success'])->toBe(1);
        unlink($csvPath);
    });
});
