<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Domain\UserManagement\Livewire\AdminManager;
use App\Modules\User\Domain\UserManagement\Livewire\StudentManager;
use App\Modules\User\Domain\UserManagement\Livewire\SupervisorManager;
use App\Modules\User\Domain\UserManagement\Livewire\TeacherManager;
use App\Modules\User\Domain\UserManagement\Livewire\UserManager;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('95EVB: user Livewire managers', function (): void {
    test('95EVB-FR-USER-033: every role manager inherits the shared record machinery', function (): void {
        foreach ([UserManager::class, StudentManager::class, TeacherManager::class, SupervisorManager::class, AdminManager::class] as $manager) {
            expect(new $manager)->toBeInstanceOf(BaseRecordManager::class);
        }

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create(['name' => 'Manager Machinery Student']);
        $student->assignRole('student');

        Livewire::test(UserManager::class)->assertSee('Manager Machinery Student');
    });

    test('95EVB-FR-USER-034: search reaches name, email, username, and profile phone', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $alpha = User::factory()->create(['name' => 'Alpha Searchable', 'email' => 'alpha.searchable@example.com', 'username' => 'alphasearchable']);
        $beta = User::factory()->create(['name' => 'Beta Searchable', 'email' => 'beta.searchable@example.com', 'username' => 'betasearchable']);
        $phoned = User::factory()->create(['name' => 'Phoned Searchable', 'email' => 'phoned.searchable@example.com', 'username' => 'phonedsearchable']);
        Profile::factory()->create(['user_id' => $phoned->id, 'phone' => '0815999888']);

        Livewire::test(UserManager::class)
            ->set('search', 'Alpha Searchable')
            ->assertSee('Alpha Searchable')
            ->assertDontSee('Beta Searchable');

        Livewire::test(UserManager::class)
            ->set('search', 'betasearchable')
            ->assertSee('Beta Searchable')
            ->assertDontSee('Alpha Searchable');

        Livewire::test(UserManager::class)
            ->set('search', '0815999888')
            ->assertSee('Phoned Searchable')
            ->assertDontSee('Beta Searchable');

        expect($alpha->fresh())->not->toBeNull();
        expect($beta->fresh())->not->toBeNull();
    });

    test('95EVB-FR-USER-035: filters slice by role, status, and creation window', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $teacher = User::factory()->create(['name' => 'Filter Teacher', 'status' => 'verified']);
        $teacher->assignRole('teacher');
        $suspended = User::factory()->create(['name' => 'Filter Suspended', 'status' => 'suspended']);
        $suspended->assignRole('student');

        Livewire::test(UserManager::class)
            ->set('filters', ['role' => 'teacher'])
            ->assertSee('Filter Teacher')
            ->assertDontSee('Filter Suspended');

        Livewire::test(UserManager::class)
            ->set('filters', ['status' => 'suspended'])
            ->assertSee('Filter Suspended')
            ->assertDontSee('Filter Teacher');

        Livewire::test(UserManager::class)
            ->set('filters', ['created_from' => now()->addDay()->toDateString()])
            ->assertDontSee('Filter Teacher')
            ->assertDontSee('Filter Suspended');
    });

    test('95EVB-FR-USER-036: the listing columns show what decisions need', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $indexes = collect(Livewire::test(UserManager::class)->instance()->headers())->pluck('index')->all();

        foreach (['name', 'email', 'profile.phone', 'roles_list', 'status', 'actions'] as $index) {
            expect($indexes)->toContain($index);
        }
    });

    test('95EVB-FR-USER-037: listings eager-load roles and profile', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        foreach (range(1, 4) as $i) {
            $user = User::factory()->create(['name' => "Flat Roster {$i}"]);
            $user->assignRole('student');
            Profile::factory()->create(['user_id' => $user->id]);
        }

        $instance = Livewire::test(UserManager::class)->instance();
        $method = new ReflectionMethod($instance, 'query');
        $eagerLoads = array_keys($method->invoke($instance)->getEagerLoads());

        expect($eagerLoads)->toContain('roles')->and($eagerLoads)->toContain('profile');
    });

    test('95EVB-FR-USER-038: admin roles hide from the general desk and its listing (also 95EVB-FR-USER-039)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $roleNames = Livewire::test(UserManager::class)->instance()->roles()->pluck('name')->all();

        expect($roleNames)->not->toContain('superadmin')
            ->and($roleNames)->not->toContain('admin')
            ->and($roleNames)->toContain('student');

        $hidden = User::factory()->create(['name' => 'Hidden Desk Admin']);
        $hidden->assignRole('admin');
        $visible = User::factory()->create(['name' => 'Visible Desk Student']);
        $visible->assignRole('student');

        Livewire::test(UserManager::class)
            ->assertSee('Visible Desk Student')
            ->assertDontSee('Hidden Desk Admin');

        Livewire::test(AdminManager::class)->assertSee('Hidden Desk Admin');
    });

    test('95EVB-FR-USER-040: dangerous states hide from the quick status dropdown', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $ids = collect(Livewire::test(UserManager::class)->instance()->statusOptions())->pluck('id')->all();

        expect($ids)->not->toContain(AccountStatus::PROTECTED->value)
            ->and($ids)->not->toContain(AccountStatus::ARCHIVED->value);

        foreach ([AccountStatus::ACTIVATED, AccountStatus::VERIFIED, AccountStatus::SUSPENDED] as $status) {
            expect($ids)->toContain($status->value);
        }
    });

    test('95EVB-FR-USER-041: the student desk adds its department lens', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $rpl = Department::factory()->create(['name' => 'RPL Lens']);
        $tkj = Department::factory()->create(['name' => 'TKJ Lens']);

        $inRpl = User::factory()->create(['name' => 'RPL Lens Student']);
        $inRpl->assignRole('student');
        Profile::factory()->create(['user_id' => $inRpl->id, 'department_id' => $rpl->id]);

        $inTkj = User::factory()->create(['name' => 'TKJ Lens Student']);
        $inTkj->assignRole('student');
        Profile::factory()->create(['user_id' => $inTkj->id, 'department_id' => $tkj->id]);

        $indexes = collect(Livewire::test(StudentManager::class)->instance()->headers())->pluck('index')->all();
        expect($indexes)->toContain('profile.department.name');

        Livewire::test(StudentManager::class)
            ->set('filters', ['department_id' => $rpl->id])
            ->assertSee('RPL Lens Student')
            ->assertDontSee('TKJ Lens Student');
    });

    test('95EVB-FR-USER-042: the teacher desk shows the registration-number column', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $teacher = User::factory()->create(['name' => 'NIP Teacher']);
        $teacher->assignRole('teacher');
        Profile::factory()->create(['user_id' => $teacher->id, 'id_number' => 'NIP-424242']);

        $indexes = collect(Livewire::test(TeacherManager::class)->instance()->headers())->pluck('index')->all();
        expect($indexes)->toContain('profile.id_number');

        Livewire::test(TeacherManager::class)->assertSee('NIP-424242');
    });

    test('95EVB-FR-USER-043: the supervisor desk shows the company column', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $company = Company::factory()->create(['name' => 'PT Maju Jaya Test']);
        $supervisor = User::factory()->create(['name' => 'Company Supervisor']);
        $supervisor->assignRole('supervisor');
        Profile::factory()->create(['user_id' => $supervisor->id, 'company_id' => $company->id]);

        $indexes = collect(Livewire::test(SupervisorManager::class)->instance()->headers())->pluck('index')->all();
        expect($indexes)->toContain('profile.company_id');

        $companies = Livewire::test(SupervisorManager::class)->instance()->companies();
        expect(collect($companies)->pluck('name')->all())->toContain('PT Maju Jaya Test');

        Livewire::test(SupervisorManager::class)->assertSee('Company Supervisor');
    });

    test('95EVB-FR-USER-044: the admin desk opens for admins and refuses everyone else (also 95EVB-FR-USER-045)', function (): void {
        $managers = [UserManager::class, StudentManager::class, TeacherManager::class, SupervisorManager::class, AdminManager::class];

        $admin = User::factory()->create(['name' => 'Second Door Admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        expect(Gate::allows('viewAny', User::class))->toBeTrue();
        expect(Gate::allows('viewAdmin', User::class))->toBeTrue();

        foreach ($managers as $manager) {
            expect(Livewire::test($manager)->instance())->toBeInstanceOf($manager);
        }
        Livewire::test(AdminManager::class)->assertSee('Second Door Admin');

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        expect(Gate::allows('viewAny', User::class))->toBeFalse();
        expect(Gate::allows('viewAdmin', User::class))->toBeFalse();

        foreach ($managers as $manager) {
            expect(Livewire::test($manager)->instance())->toBeNull();
        }
    });
});
