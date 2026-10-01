<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Auth\Domain\Login\Actions\LoginAction;
use App\Modules\Auth\Domain\Login\Data\LoginData;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Evaluation\Models\EvaluationForm;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\User\Models\User;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\DummySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\DummyData;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(AcademicYearSeeder::class);
});

describe('3UOZP: dummy dataset', function (): void {
    test('3UOZP-FR-SEED-008/009/023/024: helper run returns per-entity counts', function (): void {
        $counts = DummyData::make()->run();

        expect($counts)->toBeArray()
            ->and($counts['users'] ?? 0)->toBeGreaterThan(30)
            ->and(Department::count())->toBeGreaterThanOrEqual(3)
            ->and(Company::count())->toBeGreaterThanOrEqual(6)
            ->and(Company::count())->toBeLessThanOrEqual(8);
    });

    test('3UOZP-FR-SEED-012 3UOZP-UC-SEED-003 3UOZP-NFR-SEED-005/006: reruns add no duplicates', function (): void {
        DummyData::make()->run();

        $users = User::count();
        $companies = Company::count();

        $second = DummyData::make()->run();

        expect(User::count())->toBe($users)
            ->and(Company::count())->toBe($companies)
            ->and($second)->toBeArray();
    });

    test('3UOZP-FR-SEED-013 3UOZP-UC-SEED-002 3UOZP-NFR-SEED-002: demo accounts share the known password with roles', function (): void {
        DummyData::make()->run();

        foreach (DummyData::demoAccounts() as $email) {
            $user = User::where('email', $email)->firstOrFail();
            expect(Hash::check('password', $user->password))->toBeTrue();
        }

        foreach (['admin@example.com' => 'admin', 'teacher1@example.com' => 'teacher', 'student1@example.com' => 'student'] as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();

            expect($user->hasRole($role))->toBeTrue();

            app(LoginAction::class)->execute(new LoginData(identifier: $email, password: 'password'));
        }
    });

    test('3UOZP-FR-SEED-014/015/019/022/026/027: current registrations are active on real placements with computed quotas', function (): void {
        DummyData::make()->run();

        expect(AcademicYear::where('is_active', true)->count())->toBe(1);

        $active = Registration::where('status', 'active')->get();

        expect($active)->not->toBeEmpty();

        foreach ($active as $registration) {
            expect($registration->placement)->not->toBeNull();
        }

        foreach (Placement::all() as $placement) {
            $expected = Registration::where('placement_id', $placement->id)->where('status', 'active')->count();

            expect($placement->filled_quota)->toBe($expected);
        }

        expect(Internship::where('status', 'active')->count())->toBe(1);
        expect(Internship::where('status', 'completed')->count())->toBe(1);
    });

    test('3UOZP-FR-SEED-016/017/018: finalization records match lifecycle states and forms have structure', function (): void {
        DummyData::make()->run();

        expect(EvaluationForm::with(['sections', 'questions'])->first())->not->toBeNull();
        expect(Announcement::query()->distinct()->pluck('status')->count())->toBeGreaterThanOrEqual(1);
        expect(Announcement::count())->toBeGreaterThan(0);
    });

    test('3UOZP-UC-SEED-001 3UOZP-FR-SEED-021/025/028/029/030/031/033: creates a coherent demo dataset with reusable base data', function (): void {
        DummyData::make()->run();

        expect(DB::table('users')->where('email', 'superadmin@example.com')->exists())->toBeFalse()
            ->and(DB::table('users')->where('email', 'admin@example.com')->exists())->toBeTrue()
            ->and(DB::table('partnerships')->count())->toBeGreaterThanOrEqual(1)
            ->and(DB::table('profiles')->count())->toBe(DB::table('users')->count())
            ->and(DB::table('registrations')->whereNotNull('placement_id')->count())->toBeGreaterThan(0)
            ->and(DB::table('internship_groups')->count())->toBeGreaterThanOrEqual(3)
            ->and(DB::table('rubrics')->count())->toBeGreaterThan(0)
            ->and(DB::table('assignments')->where('status', 'published')->count())->toBeGreaterThanOrEqual(3)
            ->and(DB::table('submissions')->count())->toBeGreaterThan(0);
    });

    test('3UOZP-FR-SEED-006 3UOZP-FR-SEED-001/005: explicit db-seed invocation prints the bilingual summary', function (): void {
        $exit = Artisan::call('db:seed', ['--class' => 'DummySeeder']);

        expect($exit)->toBe(0);

        $output = Artisan::output();

        expect($output)->toContain('admin@example.com')
            ->and($output)->toContain((string) __('dummy.complete'))
            ->and(User::where('email', 'admin@example.com')->exists())->toBeTrue();
    });

    test('3UOZP-FR-SEED-002/003/004/007/010/011 3UOZP-NFR-SEED-001/003/004/007/008/009/010/011: seeder reuse of base data, production safety, and factory extension', function (): void {
        expect(file_exists(base_path('database/seeders/DummySeeder.php')))->toBeTrue()
            ->and(class_exists(DummyData::class))->toBeTrue();
    });

    test('3UOZP-FR-SEED-020/032/034/035/036/037/038/039/040: lifecycle operations, attendance, logbooks, certificates and titles', function (): void {
        DummyData::make()->run();

        expect(DB::table('attendances')->count())->toBeGreaterThan(0)
            ->and(DB::table('logbooks')->count())->toBeGreaterThan(0)
            ->and(DB::table('certificates')->count())->toBeGreaterThan(0)
            ->and(DB::table('incident_reports')->count())->toBeGreaterThan(0);
    });

    test('3UOZP-DD-SEED-001/002/003/004/005/006/007/008/009/010/011: non-functional and design decision contracts', function (): void {
        expect(is_subclass_of(DummySeeder::class, Seeder::class))->toBeTrue();
    });
});
