<?php

declare(strict_types=1);

use App\Modules\User\Domain\Profile\Data\UpdateProfileData;
use App\Modules\User\Domain\UserManagement\Data\CreateUserData;
use App\Modules\User\Domain\UserManagement\Data\SetUserStatusData;
use App\Modules\User\Domain\UserManagement\Data\UpdateUserData;
use App\Modules\User\Enums\AccountStatus;
use Illuminate\Http\UploadedFile;

describe('95EVB: CreateUserData DTO', function (): void {
    test('95EVB-FR-USER-001: fromArray maps the user payload with profile and notification defaults', function (): void {
        $dto = CreateUserData::fromArray(['user' => ['name' => 'Sinta', 'email' => 'sinta@smk.id']]);

        expect($dto->user)->toBe(['name' => 'Sinta', 'email' => 'sinta@smk.id']);
        expect($dto->profile)->toBe([]);
        expect($dto->roles)->toBe([]);
        expect($dto->sendNotification)->toBeTrue();
    });

    test('95EVB-FR-USER-001: fromArray accepts snake_case keys', function (): void {
        $dto = CreateUserData::fromArray([
            'user' => ['name' => 'Budi'],
            'profile' => ['phone' => '0812'],
            'roles' => ['student'],
            'send_notification' => false,
        ]);

        expect($dto->profile)->toBe(['phone' => '0812']);
        expect($dto->roles)->toBe(['student']);
        expect($dto->sendNotification)->toBeFalse();
    });

    test('95EVB-FR-USER-001: fromArray throws when the user payload is missing', function (): void {
        expect(fn (): CreateUserData => CreateUserData::fromArray(['roles' => ['student']]))
            ->toThrow(InvalidArgumentException::class, 'user');
    });

    test('95EVB-FR-USER-004: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(CreateUserData::from(['user' => ['name' => 'S']])->profile)->toBe([]);

        $source = new class
        {
            public function toArray(): array
            {
                return ['user' => ['name' => 'S2'], 'roles' => ['teacher']];
            }
        };

        expect(CreateUserData::from($source)->roles)->toBe(['teacher']);
        expect(fn (): CreateUserData => CreateUserData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('95EVB-FR-USER-004: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateUserData(user: ['name' => 'S']);

        expect($dto->toArray())->toBe(['user' => ['name' => 'S'], 'profile' => [], 'roles' => [], 'sendNotification' => true]);
        expect($dto->only('user'))->toBe(['user' => ['name' => 'S']]);
        expect(array_key_exists('roles', $dto->except('user')))->toBeTrue();

        $merged = $dto->merge(['roles' => ['student']]);

        expect($merged->roles)->toBe(['student']);
        expect($dto->roles)->toBe([]);
    });
});

describe('95EVB: UpdateUserData DTO', function (): void {
    test('95EVB-FR-USER-007: fromArray maps the target user with null profile and role defaults', function (): void {
        $dto = UpdateUserData::fromArray(['userId' => 'user-1', 'user' => ['name' => 'Sinta Baru']]);

        expect($dto->userId)->toBe('user-1');
        expect($dto->user)->toBe(['name' => 'Sinta Baru']);
        expect($dto->profile)->toBeNull();
        expect($dto->roles)->toBeNull();
    });

    test('95EVB-FR-USER-007: fromArray accepts snake_case keys', function (): void {
        $dto = UpdateUserData::fromArray(['user_id' => 'user-2', 'user' => ['name' => 'B'], 'roles' => ['teacher']]);

        expect($dto->userId)->toBe('user-2');
        expect($dto->roles)->toBe(['teacher']);
    });

    test('95EVB-FR-USER-007: fromArray throws when the user payload is missing', function (): void {
        expect(fn (): UpdateUserData => UpdateUserData::fromArray(['userId' => 'user-1']))
            ->toThrow(InvalidArgumentException::class, 'user');
    });

    test('95EVB-FR-USER-007: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(UpdateUserData::from(['userId' => 'u', 'user' => ['name' => 'N']])->userId)->toBe('u');

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'user' => ['name' => 'N2'], 'profile' => ['phone' => '0812']];
            }
        };

        expect(UpdateUserData::from($source)->profile)->toBe(['phone' => '0812']);
        expect(fn (): UpdateUserData => UpdateUserData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('95EVB-FR-USER-007: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new UpdateUserData(userId: 'u', user: ['name' => 'N']);

        expect($dto->toArray())->toBe(['userId' => 'u', 'user' => ['name' => 'N'], 'profile' => null, 'roles' => null]);
        expect($dto->only('userId', 'user'))->toBe(['userId' => 'u', 'user' => ['name' => 'N']]);
        expect($dto->except('user'))->toBe(['userId' => 'u', 'profile' => null, 'roles' => null]);

        $merged = $dto->merge(['user' => ['name' => 'N-baru']]);

        expect($merged->user)->toBe(['name' => 'N-baru']);
        expect($dto->user)->toBe(['name' => 'N']);
    });
});

describe('95EVB: SetUserStatusData DTO', function (): void {
    test('95EVB-FR-USER-020: fromArray maps the target, verdict, and check defaults', function (): void {
        $dto = SetUserStatusData::fromArray(['userId' => 'user-1', 'newStatus' => AccountStatus::SUSPENDED]);

        expect($dto->userId)->toBe('user-1');
        expect($dto->newStatus)->toBe(AccountStatus::SUSPENDED);
        expect($dto->reason)->toBeNull();
        expect($dto->skipAuthCheck)->toBeFalse();
    });

    test('95EVB-FR-USER-020: fromArray accepts snake_case keys with a reason', function (): void {
        $dto = SetUserStatusData::fromArray([
            'user_id' => 'user-2',
            'new_status' => AccountStatus::VERIFIED,
            'reason' => 'Banding diterima.',
            'skip_auth_check' => true,
        ]);

        expect($dto->userId)->toBe('user-2');
        expect($dto->newStatus)->toBe(AccountStatus::VERIFIED);
        expect($dto->reason)->toBe('Banding diterima.');
        expect($dto->skipAuthCheck)->toBeTrue();
    });

    test('95EVB-FR-USER-020: fromArray throws when the verdict is missing', function (): void {
        expect(fn (): SetUserStatusData => SetUserStatusData::fromArray(['userId' => 'user-1']))
            ->toThrow(InvalidArgumentException::class, 'newStatus');
    });

    test('95EVB-FR-USER-020: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['userId' => 'u', 'newStatus' => AccountStatus::ARCHIVED];

        expect(SetUserStatusData::from($payload)->newStatus)->toBe(AccountStatus::ARCHIVED);

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'newStatus' => AccountStatus::INACTIVE];
            }
        };

        expect(SetUserStatusData::from($source)->userId)->toBe('u2');
        expect(fn (): SetUserStatusData => SetUserStatusData::from(null))->toThrow(InvalidArgumentException::class);
    });

    test('95EVB-FR-USER-020: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new SetUserStatusData(userId: 'u', newStatus: AccountStatus::SUSPENDED);

        expect($dto->toArray()['newStatus'])->toBe(AccountStatus::SUSPENDED);
        expect($dto->only('newStatus'))->toBe(['newStatus' => AccountStatus::SUSPENDED]);
        expect(array_key_exists('reason', $dto->except('newStatus')))->toBeTrue();

        $merged = $dto->merge(['newStatus' => AccountStatus::VERIFIED]);

        expect($merged->newStatus)->toBe(AccountStatus::VERIFIED);
        expect($dto->newStatus)->toBe(AccountStatus::SUSPENDED);
    });
});

describe('OCEMS: UpdateProfileData DTO', function (): void {
    test('OCEMS-FR-PROF-002: fromArray maps the user and profile with null identity defaults', function (): void {
        $dto = UpdateProfileData::fromArray(['userId' => 'user-1', 'profile' => ['phone' => '0812']]);

        expect($dto->userId)->toBe('user-1');
        expect($dto->profile)->toBe(['phone' => '0812']);
        expect($dto->name)->toBeNull();
        expect($dto->email)->toBeNull();
        expect($dto->username)->toBeNull();
        expect($dto->avatar)->toBeNull();
    });

    test('OCEMS-FR-PROF-002: fromArray accepts snake_case keys with identity fields', function (): void {
        $dto = UpdateProfileData::fromArray([
            'user_id' => 'user-2',
            'profile' => [],
            'name' => 'Sinta',
            'email' => 'sinta@smk.id',
            'username' => 'sinta01',
        ]);

        expect($dto->userId)->toBe('user-2');
        expect($dto->name)->toBe('Sinta');
        expect($dto->email)->toBe('sinta@smk.id');
        expect($dto->username)->toBe('sinta01');
    });

    test('OCEMS-FR-PROF-001: fromArray throws when the profile payload is missing', function (): void {
        expect(fn (): UpdateProfileData => UpdateProfileData::fromArray(['userId' => 'user-1']))
            ->toThrow(InvalidArgumentException::class, 'profile');
    });

    test('OCEMS-FR-PROF-002: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(UpdateProfileData::from(['userId' => 'u', 'profile' => []])->userId)->toBe('u');

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'profile' => ['address' => 'Jl. Mawar'], 'name' => 'Budi'];
            }
        };

        expect(UpdateProfileData::from($source)->name)->toBe('Budi');
        expect(fn (): UpdateProfileData => UpdateProfileData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('OCEMS-FR-PROF-004: toArray, only, except, and merge shape the payload', function (): void {
        $avatar = UploadedFile::fake()->create('avatar.png', 50, 'image/png');
        $dto = new UpdateProfileData(userId: 'u', profile: [], avatar: $avatar);

        expect($dto->toArray()['avatar'])->toBe($avatar);
        expect($dto->only('userId'))->toBe(['userId' => 'u']);
        expect(array_key_exists('profile', $dto->except('userId')))->toBeTrue();

        $merged = $dto->merge(['name' => 'Sinta']);

        expect($merged->name)->toBe('Sinta');
        expect($dto->name)->toBeNull();
    });
});
