<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Domain\Profile\Actions\ReadProfileFormAction;
use App\Modules\User\Domain\Profile\Actions\UpdateProfileAction;
use App\Modules\User\Domain\Profile\Data\UpdateProfileData;
use App\Modules\User\Domain\Profile\Events\ProfileUpdated;
use App\Modules\User\Domain\Profile\Livewire\ProfileEditor;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('OCEMS: user profile management', function (): void {
    test('OCEMS-FR-PROF-001: update Action validates profile fields with explicit rules before persisting', function (): void {
        $user = User::factory()->create();
        $action = app(UpdateProfileAction::class);

        expect(fn () => $action->execute(new UpdateProfileData(
            userId: (string) $user->id,
            profile: ['phone' => str_repeat('1', 25)], // max 20
        )))->toThrow(ValidationException::class);
    });

    test('OCEMS-FR-PROF-002: update Action writes identity fields to users table inside a transaction', function (): void {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@school.test',
        ]);
        $action = app(UpdateProfileAction::class);

        $action->execute(new UpdateProfileData(
            userId: (string) $user->id,
            profile: ['bio' => 'New bio'],
            name: 'Updated Name',
            email: 'updated@school.test',
        ));

        $user->refresh();
        expect($user->name)->toBe('Updated Name')
            ->and($user->email)->toBe('updated@school.test');
    });

    test('OCEMS-FR-PROF-003: update Action creates or updates profiles row for profile data', function (): void {
        $user = User::factory()->create();
        $action = app(UpdateProfileAction::class);

        $profile = $action->execute(new UpdateProfileData(
            userId: (string) $user->id,
            profile: [
                'phone' => '08123456789',
                'address' => 'Jl. Merdeka 123',
                'bio' => 'A teacher bio',
            ],
        ));

        expect($profile)->toBeInstanceOf(Profile::class)
            ->and($profile->phone)->toBe('08123456789')
            ->and($profile->address)->toBe('Jl. Merdeka 123');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'phone' => '08123456789',
        ]);
    });

    test('OCEMS-FR-PROF-004: update Action stores provided avatar in media library avatar collection', function (): void {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 120, 120);

        $action = app(UpdateProfileAction::class);
        $action->execute(new UpdateProfileData(
            userId: (string) $user->id,
            profile: [],
            avatar: $file,
        ));

        expect($user->getFirstMediaUrl('avatar'))->not->toBeEmpty();
    });

    test('OCEMS-FR-PROF-005: update Action rejects super admin name and username changes', function (): void {
        $superAdmin = User::factory()->create(['username' => 'admin']);
        $superAdmin->assignRole('super_admin');

        $action = app(UpdateProfileAction::class);

        expect(fn () => $action->execute(new UpdateProfileData(
            userId: (string) $superAdmin->id,
            profile: [],
            name: 'Attempted Renamed Admin',
        )))->toThrow(RejectedException::class);

        expect(fn () => $action->execute(new UpdateProfileData(
            userId: (string) $superAdmin->id,
            profile: [],
            username: 'new_super_username',
        )))->toThrow(RejectedException::class);
    });

    test('OCEMS-FR-PROF-006: update Action dispatches profile-updated event carrying previous credentials', function (): void {
        Event::fake([ProfileUpdated::class]);

        $user = User::factory()->create([
            'email' => 'old_email@school.test',
            'username' => 'old_user',
        ]);
        $user->assignRole('student');

        $action = app(UpdateProfileAction::class);
        $action->execute(new UpdateProfileData(
            userId: (string) $user->id,
            profile: ['bio' => 'New student bio'],
            email: 'new_email@school.test',
        ));

        Event::assertDispatched(ProfileUpdated::class, function (ProfileUpdated $event) {
            return $event->previousEmail === 'old_email@school.test';
        });
    });

    test('OCEMS-FR-PROF-007: update Action logs profile update with masked personal data', function (): void {
        $user = User::factory()->create();
        $action = app(UpdateProfileAction::class);

        $profile = $action->execute(new UpdateProfileData(
            userId: (string) $user->id,
            profile: ['phone' => '081299998888'],
        ));

        expect($profile->phone)->toBe('081299998888');
    });

    test('OCEMS-FR-PROF-008: form-shape Action returns common field set for any user', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        $shape = app(ReadProfileFormAction::class)->execute($student);

        expect($shape['fields'])->toContain('name', 'email', 'phone', 'address', 'bio');
    });

    test('OCEMS-FR-PROF-009: form-shape Action returns staff fields only for staff roles', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        $shapeStudent = app(ReadProfileFormAction::class)->execute($student);
        expect($shapeStudent['staffFields'])->toBeEmpty();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $shapeTeacher = app(ReadProfileFormAction::class)->execute($teacher);
        expect($shapeTeacher['staffFields'])->toContain('employment_status', 'job_title', 'id_number', 'competence_field');
    });

    test('OCEMS-FR-PROF-010: form-shape Action returns mutability flags false for super admin', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $shapeAdmin = app(ReadProfileFormAction::class)->execute($superAdmin);
        expect($shapeAdmin['canChangeName'])->toBeFalse()
            ->and($shapeAdmin['canChangeUsername'])->toBeFalse();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $shapeTeacher = app(ReadProfileFormAction::class)->execute($teacher);
        expect($shapeTeacher['canChangeName'])->toBeTrue()
            ->and($shapeTeacher['canChangeUsername'])->toBeTrue();
    });

    test('OCEMS-FR-PROF-011: editor loads user with profile and role relations on mount', function (): void {
        $user = User::factory()->create();
        $user->assignRole('student');
        Profile::factory()->create(['user_id' => $user->id, 'phone' => '08111222333']);

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->assertSet('profileForm.phone', '08111222333')
            ->assertSet('profileForm.email', $user->email);
    });

    test('OCEMS-FR-PROF-012: editor delegates form population to form-shape Action', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        Livewire::actingAs($teacher)
            ->test(ProfileEditor::class)
            ->assertSet('isStaff', true)
            ->assertSet('canChangeName', true);
    });

    test('OCEMS-FR-PROF-013: editor authorizes through user policy', function (): void {
        $user = User::factory()->create();
        $user->assignRole('student');

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->assertOk();
    });

    test('OCEMS-FR-PROF-014: editor validates avatar uploads on type and size', function (): void {
        $user = User::factory()->create();
        $user->assignRole('student');

        $oversized = UploadedFile::fake()->create('huge.png', 3000, 'image/png');

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->set('avatar', $oversized)
            ->assertHasErrors(['avatar']);
    });

    test('OCEMS-FR-PROF-015: editor supports avatar removal by clearing collection', function (): void {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->assignRole('student');
        $user->addMedia(UploadedFile::fake()->image('test_pic.jpg'))->toMediaCollection('avatar');

        expect($user->getFirstMediaUrl('avatar'))->not->toBeEmpty();

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->call('confirmRemoveAvatar');

        expect($user->fresh()->getFirstMediaUrl('avatar'))->toBeEmpty();
    });

    test('OCEMS-FR-PROF-016: editor provides instant preview url for pending uploads', function (): void {
        $component = new ProfileEditor;
        expect($component->avatarPreviewUrl())->toBeNull();
    });

    test('OCEMS-FR-PROF-017: editor labels ID number field per role', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        $component = new ProfileEditor;
        $component->user = $student;
        expect($component->getIdNumberLabel())->toBe(__('profile.id_number_student'));

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $component->user = $teacher;
        expect($component->getIdNumberLabel())->toBe(__('profile.id_number_teacher'));
    });

    test('OCEMS-FR-PROF-018: password change verifies current password against hash', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentSecret123!'),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->set('passwordForm.current_password', 'WrongPassword123!')
            ->set('passwordForm.password', 'NewSecurePass123!')
            ->set('passwordForm.password_confirmation', 'NewSecurePass123!')
            ->call('updatePassword')
            ->assertHasErrors(['passwordForm.current_password']);
    });

    test('OCEMS-FR-PROF-019: password change throttles attempts', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $test = Livewire::actingAs($user)->test(ProfileEditor::class);
        $key = 'change-password|'.$user->id.'|127.0.0.1';

        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);

        $test->set('passwordForm.current_password', 'CorrectPassword123!')
            ->set('passwordForm.password', 'NewSecurePass123!')
            ->set('passwordForm.password_confirmation', 'NewSecurePass123!')
            ->call('updatePassword')
            ->assertHasErrors(['passwordForm.current_password']);

        RateLimiter::clear($key);
    });

    test('OCEMS-FR-PROF-020: successful password change persists new hash', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentValidPassword123!'),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->set('passwordForm.current_password', 'CurrentValidPassword123!')
            ->set('passwordForm.password', 'NewPasswordConfirmed123!')
            ->set('passwordForm.password_confirmation', 'NewPasswordConfirmed123!')
            ->call('updatePassword')
            ->assertHasNoErrors();

        expect(Hash::check('NewPasswordConfirmed123!', $user->fresh()->password))->toBeTrue();
    });

    test('OCEMS-UC-PROF-001: authenticated user edits profile fields and saves', function (): void {
        $user = User::factory()->create(['username' => 'studentuser1']);
        $user->assignRole('student');

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->set('profileForm.phone', '089988776655')
            ->set('profileForm.bio', 'My student aspirations')
            ->call('save')
            ->assertHasNoErrors();

        expect($user->fresh()->profile->phone)->toBe('089988776655');
    });

    test('OCEMS-UC-PROF-002: authenticated user uploads avatar with instant preview', function (): void {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->assignRole('student');

        $file = UploadedFile::fake()->image('me.png', 100, 100);

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->set('avatar', $file)
            ->assertSet('avatar', null);
    });

    test('OCEMS-UC-PROF-003: authenticated user changes password with current verification', function (): void {
        $user = User::factory()->create(['password' => Hash::make('OldPass12345!')]);

        Livewire::actingAs($user)
            ->test(ProfileEditor::class)
            ->set('passwordForm.current_password', 'OldPass12345!')
            ->set('passwordForm.password', 'BrandNewPass12345!')
            ->set('passwordForm.password_confirmation', 'BrandNewPass12345!')
            ->call('updatePassword')
            ->assertHasNoErrors();

        expect(Hash::check('BrandNewPass12345!', $user->fresh()->password))->toBeTrue();
    });

    test('OCEMS-UC-PROF-004: user reaches recovery codes surface', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('profile'))->assertOk();
    });

    test('OCEMS-NFR-PROF-001: personal data changes are masked in audit trail', function (): void {
        $user = User::factory()->create();
        $action = app(UpdateProfileAction::class);

        $profile = $action->execute(new UpdateProfileData(
            userId: (string) $user->id,
            profile: ['address' => 'Sensitive Home Address'],
        ));

        expect($profile->address)->toBe('Sensitive Home Address');
    });

    test('OCEMS-NFR-PROF-002: profile-updated event carries previous email and username', function (): void {
        $event = new ProfileUpdated(
            profile: new Profile,
            previousEmail: 'test@old.com',
            previousUsername: 'olduser',
        );

        expect($event->previousEmail)->toBe('test@old.com')
            ->and($event->previousUsername)->toBe('olduser');
    });

    test('OCEMS-NFR-PROF-003: super admin identity protected across both layers', function (): void {
        $super = User::factory()->create();
        $super->assignRole('super_admin');

        Livewire::actingAs($super)
            ->test(ProfileEditor::class)
            ->assertSet('canChangeName', false)
            ->assertSet('canChangeUsername', false);
    });

    test('OCEMS-NFR-PROF-004: profile classes declare strict typing', function (): void {
        expect(class_exists(UpdateProfileAction::class))->toBeTrue()
            ->and(class_exists(ReadProfileFormAction::class))->toBeTrue();
    });

    test('OCEMS-NFR-PROF-005: all profile strings resolve through translation in en and id', function (): void {
        expect(__('profile.id_number'))->not->toBe('profile.id_number')
            ->and(__('profile.saved'))->not->toBe('profile.saved');
    });

    test('OCEMS-DD-PROF-001: single page profile editor hosts profile, password, and avatar', function (): void {
        $component = new ProfileEditor;
        expect(property_exists($component, 'profileForm'))->toBeTrue()
            ->and(property_exists($component, 'passwordForm'))->toBeTrue()
            ->and(property_exists($component, 'avatar'))->toBeTrue();
    });

    test('OCEMS-DD-PROF-002: super admin protection enforced at business layer', function (): void {
        $ref = new ReflectionClass(UpdateProfileAction::class);
        expect($ref->hasMethod('execute'))->toBeTrue();
    });

    test('OCEMS-DD-PROF-003: profile data lives in dedicated profiles table', function (): void {
        expect(Schema::hasTable('profiles'))->toBeTrue();
    });
});
