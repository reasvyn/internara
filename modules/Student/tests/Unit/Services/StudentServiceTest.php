<?php

declare(strict_types=1);

namespace Modules\Student\Tests\Unit\Services;

use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Student\Models\Student;
use Modules\Student\Services\StudentService;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

test('it can create student account and profile', function () {
    $user = mock(User::class);
    $userService = mock(UserService::class);
    $profileService = mock(ProfileService::class);
    $profile = mock(\Modules\Profile\Models\Profile::class);

    $service = new StudentService($user, $userService, $profileService);

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'profile' => [
            'national_identifier' => '12345',
        ],
    ];

    $createdUser = mock(User::class);
    $createdUser->id = 'user-uuid';
    $userService->shouldReceive('create')->once()->andReturn($createdUser);

    $profileService->shouldReceive('getByUserId')->with('user-uuid')->once()->andReturn($profile);
    $profile->id = 'profile-uuid';
    $profileService->shouldReceive('update')->with('profile-uuid', ['national_identifier' => '12345'])->once();

    $result = $service->create($data);
    expect($result)->toBe($createdUser);
});
