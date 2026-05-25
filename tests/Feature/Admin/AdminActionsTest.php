<?php

declare(strict_types=1);

use App\Domain\Admin\Actions\ArchiveStudentAccountsAction;
use App\Domain\Admin\Actions\CreateUserAction;
use App\Domain\Admin\Actions\DeleteUserAction;
use App\Domain\Admin\Actions\GetAdminDashboardStatsAction;
use App\Domain\Admin\Actions\ReadRecoveryKeyAction;
use App\Domain\Admin\Actions\SaveRecoveryKeyAction;
use App\Domain\Admin\Actions\SendAnnouncementAction;
use App\Domain\Admin\Actions\ToggleUserStatusAction;
use App\Domain\Admin\Actions\UpdateUserAction;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Actions\SendNotificationAction;
use App\Domain\User\Models\Notification as AdminNotification;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('AdminDomainActions', function () {
    describe('CreateUserAction', function () {
        it('creates a user with basic data', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'username' => 'johndoe',
            ];

            $user = app(CreateUserAction::class)->execute($userData);

            expect($user)->toBeInstanceOf(User::class)
                ->and($user->name)->toBe('John Doe')
                ->and($user->email)->toBe('john@example.com')
                ->and($user->username)->toBe('johndoe');
        });

        it('creates a user with profile and roles', function () {
            $userData = [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'username' => 'janedoe',
            ];

            $user = app(CreateUserAction::class)->execute(
                $userData,
                ['phone' => '08123456789', 'address' => '123 Main St'],
                [Role::STUDENT->value],
            );

            expect($user->profile)->not->toBeNull()
                ->and($user->profile->phone)->toBe('08123456789')
                ->and($user->hasRole(Role::STUDENT->value))->toBeTrue();
        });

        it('validates required fields', function () {
            app(CreateUserAction::class)->execute(['name' => 'No Email']);
        })->throws(ValidationException::class);

        it('uses generated username when not provided', function () {
            $user = app(CreateUserAction::class)->execute([
                'name' => 'Auto User',
                'email' => 'auto@example.com',
            ]);

            expect($user->username)->not->toBeNull()
                ->and($user->email)->toBe('auto@example.com');
        });
    });

    describe('UpdateUserAction', function () {
        it('updates user name and email', function () {
            $user = User::factory()->create();

            $result = app(UpdateUserAction::class)->execute($user, [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

            expect($result->name)->toBe('Updated Name')
                ->and($result->email)->toBe('updated@example.com');
        });

        it('updates profile data', function () {
            $user = User::factory()->create();

            $result = app(UpdateUserAction::class)->execute($user, [], ['phone' => '08987654321']);

            expect($result->profile)->not->toBeNull()
                ->and($result->profile->phone)->toBe('08987654321');
        });

        it('syncs roles when provided', function () {
            $user = User::factory()->create();

            $result = app(UpdateUserAction::class)->execute($user, [], null, [Role::TEACHER->value]);

            expect($result->hasRole(Role::TEACHER->value))->toBeTrue();
        });
    });

    describe('DeleteUserAction', function () {
        it('prevents self-deletion', function () {
            $user = User::factory()->create();
            $user->assignRole(Role::ADMIN->value);

            $this->actingAs($user);

            app(DeleteUserAction::class)->execute($user);
        })->throws(RuntimeException::class, 'cannot delete your own account');

        it('deletes another user', function () {
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $target = User::factory()->create();

            $this->actingAs($admin);

            app(DeleteUserAction::class)->execute($target);

            expect(User::find($target->id))->toBeNull();
        });
    });

    describe('ToggleUserStatusAction', function () {
        it('toggles user status from verified to suspended', function () {
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $target = User::factory()->create();
            $target->setStatus(AccountStatus::VERIFIED->value);

            $this->actingAs($admin);

            $result = app(ToggleUserStatusAction::class)->execute($target);

            expect($result->latestStatus()?->name)->toBe(AccountStatus::SUSPENDED->value);
        });

        it('prevents self-toggle', function () {
            $user = User::factory()->create();
            $user->assignRole(Role::SUPER_ADMIN->value);

            $this->actingAs($user);

            app(ToggleUserStatusAction::class)->execute($user);
        })->throws(RuntimeException::class, 'Cannot change your own status');
    });

    describe('GetAdminDashboardStatsAction', function () {
        it('returns dashboard stats with correct keys', function () {
            User::factory()->count(3)->create()->each(fn ($u) => $u->assignRole(Role::STUDENT->value));
            User::factory()->count(2)->create()->each(fn ($u) => $u->assignRole(Role::TEACHER->value));

            $stats = app(GetAdminDashboardStatsAction::class)->execute();

            expect($stats)->toHaveKeys(['totalStudents', 'totalTeachers', 'totalDepartments', 'activeInternships'])
                ->and($stats['totalStudents'])->toBe(3)
                ->and($stats['totalTeachers'])->toBe(2);
        });
    });

    describe('SendNotificationAction', function () {
        it('creates a notification for a user', function () {
            $user = User::factory()->create();

            $notification = app(SendNotificationAction::class)->execute(
                $user->id,
                'info',
                'Test Title',
                'Test Message',
            );

            expect($notification)->toBeInstanceOf(AdminNotification::class)
                ->and($notification->user_id)->toBe($user->id)
                ->and($notification->type)->toBe('info')
                ->and($notification->title)->toBe('Test Title')
                ->and($notification->message)->toBe('Test Message')
                ->and($notification->is_read)->toBeFalse();
        });

        it('throws for non-existent user', function () {
            app(SendNotificationAction::class)->execute('non-existent-id', 'info', 'Title');
        })->throws(ModelNotFoundException::class);
    });

    describe('SendAnnouncementAction', function () {
        it('creates an announcement', function () {
            Notification::fake();
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $this->actingAs($admin);

            $announcement = app(SendAnnouncementAction::class)->execute([
                'title' => 'Test Announcement',
                'message' => 'This is a test announcement.',
                'type' => 'info',
            ]);

            expect($announcement->title)->toBe('Test Announcement')
                ->and($announcement->message)->toBe('This is a test announcement.')
                ->and($announcement->type)->toBe('info')
                ->and($announcement->created_by)->toBe($admin->id);
        });

        it('validates announcement data', function () {
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $this->actingAs($admin);

            app(SendAnnouncementAction::class)->execute(['title' => '']);
        })->throws(ValidationException::class);
    });

    describe('ArchiveStudentAccountsAction', function () {
        it('archives users matching query', function () {
            User::factory()->count(3)->create();

            $query = User::query();

            $count = app(ArchiveStudentAccountsAction::class)->execute($query);

            expect($count)->toBe(3);

            User::all()->each(function ($user) {
                expect($user->latestStatus()?->name)->toBe(AccountStatus::ARCHIVED->value);
            });
        });

        it('returns zero for empty query', function () {
            $query = User::query()->whereRaw('1 = 0');

            $count = app(ArchiveStudentAccountsAction::class)->execute($query);

            expect($count)->toBe(0);
        });
    });

    describe('SaveRecoveryKeyAction', function () {
        it('saves a recovery key file', function () {
            $key = 'test-recovery-key-12345';

            $path = app(SaveRecoveryKeyAction::class)->execute($key);

            expect(File::exists($path))->toBeTrue()
                ->and(File::get($path))->toContain($key);

            File::delete($path);
        });
    });

    describe('ReadRecoveryKeyAction', function () {
        it('returns null when file does not exist', function () {
            $path = storage_path('app/private/.recovery-key');
            $backup = null;

            if (File::exists($path)) {
                $backup = File::get($path);
                File::delete($path);
            }

            $result = app(ReadRecoveryKeyAction::class)->execute();

            expect($result)->toBeNull();

            if ($backup !== null) {
                File::put($path, $backup);
            }
        });

        it('reads a saved recovery key', function () {
            $key = 'saved-recovery-key-abc';
            app(SaveRecoveryKeyAction::class)->execute($key);

            $result = app(ReadRecoveryKeyAction::class)->execute();

            expect($result)->toBe($key);
        });
    });
});
