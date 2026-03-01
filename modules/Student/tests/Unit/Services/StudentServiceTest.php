<?php

declare(strict_types=1);

namespace Modules\Student\Tests\Unit\Services;

use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Student\Services\StudentService;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

test('it can create student account and profile', function () {
    $user = mock(User::class);
    $userService = mock(UserService::class);
    $profileService = mock(ProfileService::class);
    $profile = new class extends \Modules\Profile\Models\Profile
    {
        protected $keyType = 'string';
    };

    $service = new StudentService($user, $userService, $profileService);

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'profile' => [
            'national_identifier' => '12345',
        ],
    ];

    $createdUser = new class extends User
    {
        protected $keyType = 'string';
    };
    $createdUser->id = 'user-uuid';
    $userService->shouldReceive('create')->once()->andReturn($createdUser);

    $profile->id = 'profile-uuid';
    $profileService->shouldReceive('getByUserId')->with('user-uuid')->once()->andReturn($profile);
    $profileService
        ->shouldReceive('update')
        ->with('profile-uuid', ['national_identifier' => '12345'])
        ->once();

    $result = $service->create($data);
    expect($result)->toBe($createdUser);
});
