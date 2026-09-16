<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Enrollment\Domain\AccountApplication\Livewire\ApplyPage;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function aaPageInternship(string $status = 'published'): Internship
{
    return Internship::factory()->create(['status' => $status]);
}

function aaPagePlacement(Internship $internship, int $quota = 5, int $filled = 0): Placement
{
    return Placement::factory()->create([
        'internship_id' => $internship->id,
        'quota' => $quota,
        'filled_quota' => $filled,
    ]);
}

/** @return array<string, mixed> */
function aaValidForm(Department $dept, Internship $internship, ?Placement $placement): array
{
    return [
        'form.name' => 'Page Kid',
        'form.email' => 'page-kid-'.uniqid().'@example.com',
        'form.department_id' => $dept->id,
        'form.internship_id' => $internship->id,
        'form.placement_id' => $placement?->id,
        'form.academic_year' => '2025/2026',
    ];
}

// Form-rule tests run as an authenticated student: the page's submit path
// authorizes `create` before validating, and that gate currently requires a
// user (guest submit is denied — reported as a finding), while the rules
// themselves are identical for every caller.
function aaApplyAsStudent(): Testable
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return Livewire::actingAs($student)->test(ApplyPage::class);
}

describe('920SO: apply page catalogue', function (): void {
    test('920SO-FR-APPLY-021: only open programs are choosable', function (): void {
        $published = aaPageInternship('published');
        $active = aaPageInternship('active');
        $draft = aaPageInternship('draft');
        $archived = aaPageInternship('archived');

        Livewire::test(ApplyPage::class)
            ->assertSee($published->name)
            ->assertSee($active->name)
            ->assertDontSee($draft->name)
            ->assertDontSee($archived->name);
    });

    test('920SO-FR-APPLY-022: only open slots of the chosen internship show', function (): void {
        $internship = aaPageInternship();
        $other = aaPageInternship();
        $open = aaPagePlacement($internship);
        $full = aaPagePlacement($internship, 4, 4);
        $elsewhere = aaPagePlacement($other);

        Livewire::test(ApplyPage::class)
            ->set('form.internship_id', $internship->id)
            ->assertSee($open->name)
            ->assertDontSee($full->name)
            ->assertDontSee($elsewhere->name);
    });

    test('920SO-FR-APPLY-023: one form serves placement and proposed-company modes', function (): void {
        app()->setLocale('en');
        $internship = aaPageInternship();
        $placement = aaPagePlacement($internship);

        Livewire::test(ApplyPage::class)
            ->assertSee(__('registration.account_application.choose_placement'))
            ->assertSee(__('registration.account_application.propose_company'))
            ->set('form.internship_id', $internship->id)
            ->assertSee($placement->name)
            ->set('form.use_placement', false)
            ->assertSee(__('registration.account_application.proposed_company'));
    });

    test('920SO-FR-APPLY-024: toggling modes wipes the other mode payload', function (): void {
        $internship = aaPageInternship();
        $placement = aaPagePlacement($internship);

        Livewire::test(ApplyPage::class)
            ->set('form.placement_id', $placement->id)
            ->set('form.use_placement', false)
            ->assertSet('form.placement_id', '')
            ->assertSet('form.proposed_company_name', '')
            ->assertSet('form.proposed_company_address', '')
            ->set('form.proposed_company_name', 'PT Stale')
            ->set('form.proposed_company_address', 'Jl. Stale 9')
            ->set('form.use_placement', true)
            ->assertSet('form.proposed_company_name', '')
            ->assertSet('form.proposed_company_address', '')
            ->assertSet('form.placement_id', '');
    });
});

describe('920SO: apply form validation', function (): void {
    test('920SO-FR-APPLY-020: the page validates through the bound form object', function (): void {
        aaApplyAsStudent()
            ->call('submit')
            ->assertHasErrors([
                'form.name',
                'form.email',
                'form.internship_id',
                'form.academic_year',
                'form.placement_id',
            ]);

        expect(AccountApplication::count())->toBe(0);
    });

    test('920SO-FR-APPLY-025: closed programs and bad emails fail base validation', function (): void {
        $dept = Department::factory()->create();
        $closed = aaPageInternship('draft');
        $open = aaPageInternship();
        $placement = aaPagePlacement($open);

        aaApplyAsStudent()
            ->set('form.name', 'Validation Kid')
            ->set('form.email', 'not-an-email')
            ->set('form.department_id', $dept->id)
            ->set('form.internship_id', $open->id)
            ->set('form.placement_id', $placement->id)
            ->set('form.academic_year', '2025/2026')
            ->call('submit')
            ->assertHasErrors(['form.email' => 'email']);

        aaApplyAsStudent()
            ->set('form.name', 'Validation Kid')
            ->set('form.email', 'closed-prog@example.com')
            ->set('form.department_id', $dept->id)
            ->set('form.internship_id', $closed->id)
            ->set('form.academic_year', '2025/2026')
            ->call('submit')
            ->assertHasErrors(['form.internship_id']);

        expect(AccountApplication::where('email', 'closed-prog@example.com')->count())->toBe(0);
    });

    test('920SO-FR-APPLY-008: emails colliding with either table are refused by the form', function (): void {
        $dept = Department::factory()->create();
        $internship = aaPageInternship();
        $placement = aaPagePlacement($internship);

        $taken = User::factory()->create(['email' => 'taken-user@example.com']);

        aaApplyAsStudent()
            ->set('form.name', 'Collision Kid')
            ->set('form.email', $taken->email)
            ->set('form.department_id', $dept->id)
            ->set('form.internship_id', $internship->id)
            ->set('form.placement_id', $placement->id)
            ->set('form.academic_year', '2025/2026')
            ->call('submit')
            ->assertHasErrors(['form.email' => 'unique']);

        AccountApplication::factory()->create([
            'email' => 'taken-application@example.com',
            'form_data' => ['internship_id' => $internship->id],
        ]);

        aaApplyAsStudent()
            ->set('form.name', 'Collision Kid')
            ->set('form.email', 'taken-application@example.com')
            ->set('form.department_id', $dept->id)
            ->set('form.internship_id', $internship->id)
            ->set('form.placement_id', $placement->id)
            ->set('form.academic_year', '2025/2026')
            ->call('submit')
            ->assertHasErrors(['form.email' => 'unique']);
    });

    test('920SO-FR-APPLY-026: placement mode demands a placement choice', function (): void {
        $dept = Department::factory()->create();
        $internship = aaPageInternship();

        aaApplyAsStudent()
            ->set('form.name', 'Mode Kid')
            ->set('form.email', 'mode-placement@example.com')
            ->set('form.department_id', $dept->id)
            ->set('form.internship_id', $internship->id)
            ->set('form.academic_year', '2025/2026')
            ->set('form.use_placement', true)
            ->call('submit')
            ->assertHasErrors(['form.placement_id']);

        expect(AccountApplication::where('email', 'mode-placement@example.com')->count())->toBe(0);
    });
});

describe('920SO: apply flow localization', function (): void {
    test('920SO-NFR-APPLY-008: apply flow resolves translations in both locales', function (): void {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            test()->get('/apply')
                ->assertOk()
                ->assertSee(__('registration.account_application.title'))
                ->assertDontSee('registration.account_application.title');
        }
    });

    test('920SO-NFR-APPLY-008: every key used on the apply page exists in both locales', function (): void {
        $blade = file_get_contents(
            base_path('resources/views/enrollment/account-application/apply-page.blade.php')
        );

        preg_match_all("/__\(\s*'([a-z0-9_.]+)'/", (string) $blade, $matches);
        $keys = array_values(array_unique($matches[1]));

        expect($keys)->not->toBeEmpty();

        foreach ($keys as $key) {
            expect(Lang::has($key, 'en'))->toBeTrue("missing en key: {$key}")
                ->and(Lang::has($key, 'id'))->toBeTrue("missing id key: {$key}");
        }
    });
});
