<?php

declare(strict_types=1);

use App\Domain\School\Models\Department;
use App\Domain\User\Actions\CreateUserAction;
use App\Domain\User\Actions\DeleteUserAction;
use App\Domain\User\Actions\UpdateUserAction;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Enums\Auth\Role as RoleEnum;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Create roles if they don't exist
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    // Create a super admin user for testing (matches the 'super_admin' role used in routes)
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(RoleEnum::SUPER_ADMIN);
});

// Admin Manager Tests
describe('Admin Manager', function () {
    it('can list admins', function () {
        actingAs($this->superAdmin)->get('/admin/users/admins')->assertOk();
    });

    it('can create admin with valid data', function () {
        $data = [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'username' => 'u12345678',
            'roles' => [RoleEnum::ADMIN->value],
        ];

        $action = app(CreateUserAction::class);
        $profileData = [];

        $user = $action->execute($data, $profileData, [RoleEnum::ADMIN->value]);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->hasRole(RoleEnum::ADMIN))
            ->toBeTrue()
            ->and($user->email)
            ->toBe('newadmin@example.com');
    });

    it('can update admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);

        $action = app(UpdateUserAction::class);
        $updateData = [
            'name' => 'Updated Admin Name',
            'email' => $admin->email,
            'username' => $admin->username,
        ];

        $action->execute($admin, $updateData, []);

        expect($admin->fresh()->name)->toBe('Updated Admin Name');
    });

    it('can delete admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);

        $action = app(DeleteUserAction::class);
        $action->execute($admin);

        expect(User::find($admin->id))->toBeNull();
    });
});

// Student Manager Tests
describe('Student Manager', function () {
    it('can list students', function () {
        actingAs($this->superAdmin)->get('/admin/users/students')->assertOk();
    });

    it('can create student with NISN', function () {
        $department = Department::factory()->create();

        $data = [
            'name' => 'New Student',
            'email' => 'student@example.com',
            'username' => 'u87654321',
            'national_identifier' => '1234567890', // NISN
            'registration_number' => 'REG001',
            'department_id' => $department->id,
        ];

        $action = app(CreateUserAction::class);
        $profileData = [
            'national_identifier' => '1234567890',
            'registration_number' => 'REG001',
            'department_id' => $department->id,
        ];

        $user = $action->execute($data, $profileData, [RoleEnum::STUDENT->value]);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->hasRole(RoleEnum::STUDENT))
            ->toBeTrue()
            ->and($user->profile->national_identifier)
            ->toBe('1234567890');
    });

    it('can update student profile', function () {
        $student = User::factory()->create();
        $student->assignRole(RoleEnum::STUDENT);
        $student->profile()->save(Profile::factory()->make());

        $action = app(UpdateUserAction::class);
        $userData = [
            'name' => 'Updated Student Name',
            'email' => $student->email,
            'username' => $student->username,
        ];
        $profileData = [
            'national_identifier' => '9876543210',
            'registration_number' => 'REG002',
        ];

        $action->execute($student, $userData, $profileData, []);

        expect($student->fresh()->name)
            ->toBe('Updated Student Name')
            ->and($student->profile->fresh()->national_identifier)
            ->toBe('9876543210');
    });
});

// Teacher Manager Tests
describe('Teacher Manager', function () {
    it('can list teachers', function () {
        actingAs($this->superAdmin)->get('/admin/users/teachers')->assertOk();
    });

    it('can create teacher with NIP', function () {
        $data = [
            'name' => 'New Teacher',
            'email' => 'teacher@example.com',
            'username' => 'u11223344',
            'registration_number' => 'NIP001', // NIP
        ];

        $action = app(CreateUserAction::class);
        $profileData = [
            'registration_number' => 'NIP001',
        ];

        $user = $action->execute($data, $profileData, [RoleEnum::TEACHER->value]);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->hasRole(RoleEnum::TEACHER))
            ->toBeTrue()
            ->and($user->profile->registration_number)
            ->toBe('NIP001');
    });
});

// Mentor Manager Tests
describe('Mentor Manager', function () {
    it('can list mentors', function () {
        actingAs($this->superAdmin)->get('/admin/users/mentors')->assertOk();
    });

    it('can create mentor with phone', function () {
        $data = [
            'name' => 'New Mentor',
            'email' => 'mentor@example.com',
            'username' => 'u99887766',
            'phone' => '08123456789',
        ];

        $action = app(CreateUserAction::class);
        $profileData = [
            'phone' => '08123456789',
        ];

        $user = $action->execute($data, $profileData, [RoleEnum::SUPERVISOR->value]);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->hasRole(RoleEnum::SUPERVISOR))
            ->toBeTrue()
            ->and($user->profile->phone)
            ->toBe('08123456789');
    });
});

// RBAC Tests
describe('Role-Based Access Control', function () {
    it('prevents student from accessing admin pages', function () {
        $student = User::factory()->create();
        $student->assignRole(RoleEnum::STUDENT);

        actingAs($student)->get('/admin/users/admins')->assertForbidden();
    });

    it('prevents teacher from accessing admin pages', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole(RoleEnum::TEACHER);

        actingAs($teacher)->get('/admin/users/students')->assertForbidden();
    });

    it('allows super admin to access all pages', function () {
        // First verify the super admin has the super_admin role
        expect($this->superAdmin->hasRole('super_admin'))->toBeTrue();

        actingAs($this->superAdmin)->get('/admin/users/admins')->assertOk();

        actingAs($this->superAdmin)->get('/admin/users/students')->assertOk();

        actingAs($this->superAdmin)->get('/admin/users/teachers')->assertOk();

        actingAs($this->superAdmin)->get('/admin/users/mentors')->assertOk();
    });
});
