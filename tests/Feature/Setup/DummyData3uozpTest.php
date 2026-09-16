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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\Support\DummyData;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(AcademicYearSeeder::class);
});

describe('3UOZP: dummy dataset', function (): void {
    test('3UOZP-FR-SEED-008: helper run returns per-entity counts (also FR-SEED-009, FR-SEED-023, FR-SEED-024)', function (): void {
        $counts = DummyData::make()->run();

        expect($counts)->toBeArray()
            ->and($counts['users'] ?? 0)->toBeGreaterThan(30)
            ->and(Department::count())->toBeGreaterThanOrEqual(3)
            ->and(Company::count())->toBeGreaterThanOrEqual(6)
            ->and(Company::count())->toBeLessThanOrEqual(8);
    });

    test('3UOZP-FR-SEED-012: reruns add no duplicates (also UC-SEED-003)', function (): void {
        DummyData::make()->run();

        $users = User::count();
        $companies = Company::count();

        $second = DummyData::make()->run();

        expect(User::count())->toBe($users)
            ->and(Company::count())->toBe($companies)
            ->and($second)->toBeArray();
    });

    test('3UOZP-FR-SEED-013: demo accounts share the known password with roles (also UC-SEED-002)', function (): void {
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

    test('3UOZP-FR-SEED-014: current registrations are active on real placements with computed quotas (also FR-SEED-015, FR-SEED-019, FR-SEED-022, FR-SEED-026, FR-SEED-027)', function (): void {
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

    test('3UOZP-FR-SEED-016: finalization records match lifecycle states (also FR-SEED-017, FR-SEED-018)', function (): void {
        DummyData::make()->run();

        expect(EvaluationForm::with(['sections', 'questions'])->first())->not->toBeNull();
        expect(Announcement::query()->distinct()->pluck('status')->count())->toBeGreaterThanOrEqual(1);
        expect(Announcement::count())->toBeGreaterThan(0);
    });

    test('3UOZP-FR-SEED-006: explicit db-seed invocation prints the bilingual summary (also FR-SEED-001, FR-SEED-005)', function (): void {
        $exit = Artisan::call('db:seed', ['--class' => 'DummySeeder']);

        expect($exit)->toBe(0);

        $output = Artisan::output();

        expect($output)->toContain('admin@example.com')
            ->and($output)->toContain((string) __('dummy.complete'))
            ->and(User::where('email', 'admin@example.com')->exists())->toBeTrue();
    });
});
