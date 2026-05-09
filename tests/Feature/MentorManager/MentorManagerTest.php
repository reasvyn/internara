<?php

declare(strict_types=1);

use App\Livewire\User\Admin\MentorManager;
use App\Models\Mentor;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Super Admin']);
    $this->admin->assignRole('super_admin');

    $this->actingAs($this->admin);
});

describe('access control', function () {

    it('allows super_admin to access', function () {
        Livewire::test(MentorManager::class)
            ->assertSuccessful();
    });

    it('allows admin to access', function () {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(MentorManager::class)
            ->assertSuccessful();
    });

    it('blocks teacher from accessing', function () {
        $user = User::factory()->create()->assignRole('teacher');
        $this->actingAs($user);

        Livewire::test(MentorManager::class)
            ->assertForbidden();
    });

    it('blocks student from accessing', function () {
        $user = User::factory()->create()->assignRole('student');
        $this->actingAs($user);

        Livewire::test(MentorManager::class)
            ->assertForbidden();
    });

});

describe('rendering', function () {

    it('renders the mentor manager page', function () {
        Livewire::test(MentorManager::class)
            ->assertSuccessful()
            ->assertSet('search', '');
    });

    it('displays mentors in the table', function () {
        Mentor::factory()->schoolTeacher()->create([
            'user_id' => User::factory()->create(['name' => 'Alice Mentor'])->assignRole('teacher')->id,
        ]);
        Mentor::factory()->industrySupervisor()->create([
            'user_id' => User::factory()->create(['name' => 'Bob Supervisor'])->assignRole('supervisor')->id,
        ]);

        Livewire::test(MentorManager::class)
            ->assertSee('Alice Mentor')
            ->assertSee('Bob Supervisor');
    });

});

describe('search', function () {

    it('filters mentors by name', function () {
        Mentor::factory()->schoolTeacher()->create([
            'user_id' => User::factory()->create(['name' => 'Unique Mentor'])->id,
        ]);
        Mentor::factory()->schoolTeacher()->create([
            'user_id' => User::factory()->create(['name' => 'Other Mentor'])->id,
        ]);

        Livewire::test(MentorManager::class)
            ->set('search', 'Unique')
            ->assertSee('Unique Mentor')
            ->assertDontSee('Other Mentor');
    });

    it('filters mentors by email', function () {
        Mentor::factory()->schoolTeacher()->create([
            'user_id' => User::factory()->create(['email' => 'unique@example.com'])->id,
        ]);
        Mentor::factory()->schoolTeacher()->create([
            'user_id' => User::factory()->create(['email' => 'other@example.com'])->id,
        ]);

        Livewire::test(MentorManager::class)
            ->set('search', 'unique@')
            ->assertSee('unique@example.com')
            ->assertDontSee('other@example.com');
    });

});

describe('create', function () {

    it('opens the create modal', function () {
        Livewire::test(MentorManager::class)
            ->call('create')
            ->assertSet('userModal', true)
            ->assertSet('userData.id', null);
    });

    it('creates a new school teacher mentor', function () {
        Livewire::test(MentorManager::class)
            ->call('create')
            ->set('userData.name', 'New Teacher Mentor')
            ->set('userData.email', 'teachermentor@example.com')
            ->set('userData.type', Mentor::TYPE_SCHOOL_TEACHER)
            ->call('save')
            ->assertHasNoErrors();

        $mentor = Mentor::whereHas('user', fn ($q) => $q->where('email', 'teachermentor@example.com'))->first();

        expect($mentor)->not->toBeNull();
        expect($mentor->type)->toBe(Mentor::TYPE_SCHOOL_TEACHER);
        expect($mentor->is_active)->toBeTrue();
        expect($mentor->user->name)->toBe('New Teacher Mentor');
        expect($mentor->user->hasRole('teacher'))->toBeTrue();

        assertDatabaseHas('mentors', ['id' => $mentor->id, 'type' => Mentor::TYPE_SCHOOL_TEACHER]);
    });

    it('creates a new industry supervisor mentor', function () {
        Livewire::test(MentorManager::class)
            ->call('create')
            ->set('userData.name', 'New Industry Mentor')
            ->set('userData.email', 'industrymentor@example.com')
            ->set('userData.type', Mentor::TYPE_INDUSTRY_SUPERVISOR)
            ->call('save')
            ->assertHasNoErrors();

        $mentor = Mentor::whereHas('user', fn ($q) => $q->where('email', 'industrymentor@example.com'))->first();

        expect($mentor)->not->toBeNull();
        expect($mentor->type)->toBe(Mentor::TYPE_INDUSTRY_SUPERVISOR);
        expect($mentor->user->hasRole('supervisor'))->toBeTrue();
    });

    it('validates required fields on create', function () {
        Livewire::test(MentorManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'userData.name' => 'required',
                'userData.email' => 'required',
            ]);
    });

    it('validates email uniqueness on create', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(MentorManager::class)
            ->call('create')
            ->set('userData.name', 'Test')
            ->set('userData.email', 'existing@example.com')
            ->call('save')
            ->assertHasErrors(['userData.email' => 'unique']);
    });

});

describe('edit', function () {

    it('opens the edit modal with mentor data', function () {
        $mentor = Mentor::factory()->schoolTeacher()->create();
        $mentor->user->assignRole('teacher');

        Livewire::test(MentorManager::class)
            ->call('edit', $mentor->id)
            ->assertSet('userModal', true)
            ->assertSet('userData.id', $mentor->id)
            ->assertSet('userData.name', $mentor->user->name)
            ->assertSet('userData.type', Mentor::TYPE_SCHOOL_TEACHER);
    });

    it('updates mentor data', function () {
        $mentor = Mentor::factory()->schoolTeacher()->create();
        $mentor->user->assignRole('teacher');

        Livewire::test(MentorManager::class)
            ->call('edit', $mentor->id)
            ->set('userData.type', Mentor::TYPE_INDUSTRY_SUPERVISOR)
            ->set('userData.is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $mentor->fresh();

        expect($fresh->type)->toBe(Mentor::TYPE_INDUSTRY_SUPERVISOR);
        expect($fresh->is_active)->toBeFalse();
    });

});

describe('delete', function () {

    it('deletes a mentor and cascades to user', function () {
        $mentor = Mentor::factory()->schoolTeacher()->create();
        $mentor->user->assignRole('teacher');
        $userId = $mentor->user_id;

        Livewire::test(MentorManager::class)
            ->call('delete', $mentor->id);

        expect(Mentor::find($mentor->id))->toBeNull();
        expect(User::find($userId))->toBeNull();
        assertDatabaseMissing('mentors', ['id' => $mentor->id]);
    });

});

describe('bulk delete', function () {

    it('deletes selected mentors', function () {
        $mentor1 = Mentor::factory()->schoolTeacher()->create();
        $mentor1->user->assignRole('teacher');
        $mentor2 = Mentor::factory()->schoolTeacher()->create();
        $mentor2->user->assignRole('teacher');

        Livewire::test(MentorManager::class)
            ->set('selectedIds', [$mentor1->id, $mentor2->id])
            ->call('deleteSelected');

        expect(Mentor::find($mentor1->id))->toBeNull();
        expect(Mentor::find($mentor2->id))->toBeNull();
        assertDatabaseMissing('mentors', ['id' => $mentor1->id]);
        assertDatabaseMissing('mentors', ['id' => $mentor2->id]);
    });

});
