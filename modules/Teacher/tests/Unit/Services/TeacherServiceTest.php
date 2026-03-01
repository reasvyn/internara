<?php

declare(strict_types=1);

namespace Modules\Teacher\Tests\Unit\Services;

use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Teacher\Services\TeacherService;
use Modules\User\Services\Contracts\UserService;

test('it can create teacher account and profile', function () {
    $user = mock(\Modules\User\Models\User::class);
    $userService = mock(UserService::class);
    $profileService = mock(ProfileService::class);
    $profile = new class extends \Modules\Profile\Models\Profile
    {
        protected $keyType = 'string';
    };

    $service = new TeacherService($user, $userService, $profileService);

    $data = [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'profile' => [
            'registration_number' => 'NIP123',
        ],
    ];

    $createdUser = new class extends \Modules\User\Models\User
    {
        protected $keyType = 'string';
    };
    $createdUser->id = 'user-uuid';
    $userService->shouldReceive('create')->once()->andReturn($createdUser);

    $profile->id = 'profile-uuid';
    $profileService->shouldReceive('getByUserId')->with('user-uuid')->once()->andReturn($profile);
    $profileService
        ->shouldReceive('update')
        ->with('profile-uuid', ['registration_number' => 'NIP123'])
        ->once();

    $result = $service->create($data);
    expect($result)->toBe($createdUser);
});
