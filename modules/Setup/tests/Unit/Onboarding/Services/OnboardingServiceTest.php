<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Onboarding\Services;

use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Setup\Onboarding\Services\OnboardingService;
use Modules\Student\Services\Contracts\StudentService;
use Modules\Teacher\Services\Contracts\TeacherService;
use Modules\User\Models\User;
use Modules\User\Services\AccountProvisioningService;
use Modules\User\Services\Contracts\UserService;

describe('OnboardingService Unit Test', function () {
    beforeEach(function () {
        $this->userService = $this->mock(UserService::class);
        $this->profileService = $this->mock(ProfileService::class);
        $this->studentService = $this->mock(StudentService::class);
        $this->teacherService = $this->mock(TeacherService::class);
        $this->provisioningService = $this->mock(AccountProvisioningService::class);

        $this->service = new OnboardingService(
            $this->userService,
            $this->profileService,
            $this->studentService,
            $this->teacherService,
            $this->provisioningService,
        );
    });

    test('it generates correct template for students', function () {
        $template = $this->service->getTemplate('student');

        expect($template)->toContain(
            'name,email,username,phone,address,department_id,national_identifier,registration_number',
        );
    });

    test('it returns error if file not found', function () {
        $results = $this->service->importFromCsv('non_existent.csv', 'student');

        expect($results['success'])->toBe(0)->and($results['errors'])->not->toBeEmpty();
    });

    test('it processes valid csv row', function () {
        $userMock = \Mockery::mock(User::class);
        $userMock->shouldReceive('getAttribute')->with('id')->andReturn('user-uuid');
        $this->provisioningService->shouldReceive('createWithRoles')->once()->andReturn($userMock);
        $this->provisioningService->shouldReceive('provision')->once();
        $this->studentService->shouldReceive('create')->once();

        // Create temporary CSV with correct headers matching service logic
        $csvPath = tempnam(sys_get_temp_dir(), 'test_') . '.csv';
        $headers =
            'name,email,username,phone,address,department_id,national_identifier,registration_number';
        $row = 'John Doe,john@example.com,jdoe,0812,Jl. Merdeka,dept-1,12345,67890';
        file_put_contents($csvPath, $headers . "\n" . $row);

        $results = $this->service->importFromCsv($csvPath, 'student');

        if ($results['success'] === 0) {
            dd($results['errors']);
        }
        expect($results['success'])->toBe(1);
        unlink($csvPath);
    });
});
