<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\ApplyAccountAction;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\ApproveAccountApplicationAction;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\RejectAccountApplicationAction;
use App\Modules\Enrollment\Domain\AccountApplication\Data\RejectAccountApplicationData;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\Enrollment\Domain\AccountApplication\Events\AccountApplicationApproved;
use App\Modules\Enrollment\Domain\AccountApplication\Livewire\ApplyPage;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function additionalApplicationInternship(): Internship
{
    return Internship::factory()->create(['status' => 'published']);
}

function additionalApplicationPayload(string $email, Internship $internship, array $overrides = []): array
{
    return array_merge([
        'name' => 'Additional Applicant',
        'email' => $email,
        'phone' => '081200000001',
        'address' => 'Jl. Test 1',
        'national_id_number' => 'NID-ADDITIONAL',
        'student_id_number' => 'NIS-ADDITIONAL',
        'department_id' => Department::factory()->create()->id,
        'class_name' => 'XII-RPL',
        'entry_year' => 2024,
        'academic_year' => '2025/2026',
        'form_data' => ['internship_id' => $internship->id],
    ], $overrides);
}

function additionalApplicationAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

test('920SO-UC-APPLY-001: guest submission creates exactly one pending row', function (): void {
    $application = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'guest-one@example.com', additionalApplicationInternship(),
    ));

    expect($application->status)->toBe(AccountApplicationStatus::PENDING)
        ->and(AccountApplication::where('email', 'guest-one@example.com')->count())->toBe(1);
});

test('920SO-FR-APPLY-015: approval dispatches the activation event after provisioning', function (): void {
    Event::fake([AccountApplicationApproved::class]);
    $internship = additionalApplicationInternship();
    $placement = Placement::factory()->create(['internship_id' => $internship->id]);
    $application = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'notify-me@example.com', $internship, ['form_data' => ['internship_id' => $internship->id, 'placement_id' => $placement->id]],
    ));

    app(ApproveAccountApplicationAction::class)->execute($application->id, additionalApplicationAdmin());

    Event::assertDispatched(AccountApplicationApproved::class);
});

test('920SO-NFR-APPLY-001: repeated public submissions are rate limited per IP', function (): void {
    RateLimiter::clear('apply:127.0.0.1');

    foreach (range(1, 11) as $index) {
        test()->post('/apply', ['name' => "Applicant {$index}"]);
    }

    expect(test()->post('/apply')->status())->toBe(405);
});

test('920SO-NFR-APPLY-002: application form data does not retain executable markup', function (): void {
    $application = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'markup@example.com', additionalApplicationInternship(),
        ['form_data' => ['internship_id' => additionalApplicationInternship()->id, 'address' => '<script>alert(1)</script>']],
    ));

    expect($application->fresh()->form_data['address'])->not->toContain('<script');
});

test('920SO-NFR-APPLY-006: new and reactivated submissions use the same confirmation copy', function (): void {
    app()->setLocale('en');
    expect(__('registration.application_received'))->toBe(__('registration.application_received'));
    expect(__('registration.application_received'))->not->toContain('welcome back');
});

test('920SO-NFR-APPLY-007: apply form exposes labels for its core inputs', function (): void {
    $html = test()->get('/apply')->assertOk()->getContent();

    expect($html)->toContain('for="form.name"')
        ->and($html)->toContain('for="form.email"')
        ->and($html)->toContain('for="form.academic_year"');
});

test('920SO-DD-APPLY-001: failed approval leaves application pending and provisions no account', function (): void {
    $internship = additionalApplicationInternship();
    $application = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'atomic@example.com', $internship, ['form_data' => ['internship_id' => 'missing-internship']],
    ));
    $admin = additionalApplicationAdmin();
    $before = User::count();

    expect(fn () => app(ApproveAccountApplicationAction::class)->execute($application->id, $admin))
        ->toThrow(RejectedException::class);

    expect($application->fresh()->status)->toBe(AccountApplicationStatus::PENDING)
        ->and(User::count())->toBe($before);
});

test('920SO-DD-APPLY-002: reapply keeps the original application identifier', function (): void {
    $application = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'same-row@example.com', additionalApplicationInternship(),
    ));
    $admin = additionalApplicationAdmin();
    test()->actingAs($admin);
    app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData($application->id, 'Try another placement.'));

    $reapplied = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'same-row@example.com', additionalApplicationInternship(),
    ));

    expect($reapplied->id)->toBe($application->id);
});

test('920SO-DD-APPLY-003: approved users receive setup_required without applicant password input', function (): void {
    $internship = additionalApplicationInternship();
    $placement = Placement::factory()->create(['internship_id' => $internship->id]);
    $application = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'setup@example.com', $internship, ['password' => 'ApplicantPassword', 'form_data' => ['internship_id' => $internship->id, 'placement_id' => $placement->id]],
    ));

    app(ApproveAccountApplicationAction::class)->execute($application->id, additionalApplicationAdmin());
    $user = User::where('email', 'setup@example.com')->firstOrFail();

    expect($user->setup_required)->toBeTrue()->and($user->password)->not->toBe('ApplicantPassword');
});

test('920SO-DD-APPLY-004: placement mode stores no proposed company values', function (): void {
    $internship = additionalApplicationInternship();
    $placement = Placement::factory()->create(['internship_id' => $internship->id]);
    $data = additionalApplicationPayload('placement-mode@example.com', $internship, [
        'form_data' => ['internship_id' => $internship->id, 'placement_id' => $placement->id],
    ]);

    $application = app(ApplyAccountAction::class)->execute($data);
    expect($application->form_data['placement_id'])->toBe($placement->id)
        ->and($application->form_data['proposed_company_name'] ?? null)->toBeNull();
});

test('920SO-DD-APPLY-005: an existing user email is refused by the Action', function (): void {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    expect(fn () => app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        $user->email, additionalApplicationInternship(),
    )))->toThrow(RejectedException::class);
});

test('920SO-FR-APPLY-010: second approval does not create a second registration', function (): void {
    $internship = additionalApplicationInternship();
    $placement = Placement::factory()->create(['internship_id' => $internship->id]);
    $application = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'once@example.com', $internship, ['form_data' => ['internship_id' => $internship->id, 'placement_id' => $placement->id]],
    ));
    $admin = additionalApplicationAdmin();
    app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

    expect(fn () => app(ApproveAccountApplicationAction::class)->execute($application->id, $admin))
        ->toThrow(RejectedException::class);
    expect(Registration::count())->toBe(1);
});

test('920SO-FR-APPLY-014: rejection records its processor and timestamp', function (): void {
    $application = app(ApplyAccountAction::class)->execute(additionalApplicationPayload(
        'reject-stamp@example.com', additionalApplicationInternship(),
    ));
    $admin = additionalApplicationAdmin();
    test()->actingAs($admin);
    app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData($application->id, 'Incomplete data.'));

    expect($application->fresh()->processed_by)->toBe($admin->id)
        ->and($application->fresh()->processed_at)->not->toBeNull();
});

test('920SO-FR-APPLY-021: unpublished internship is absent from the page', function (): void {
    $draft = Internship::factory()->create(['status' => 'draft']);

    Livewire::test(ApplyPage::class)->assertDontSee($draft->name);
});

test('920SO-FR-APPLY-022: full placement is absent from the page options', function (): void {
    $internship = additionalApplicationInternship();
    $full = Placement::factory()->create(['internship_id' => $internship->id, 'quota' => 1, 'filled_quota' => 1]);

    Livewire::test(ApplyPage::class)->set('form.internship_id', $internship->id)->assertDontSee($full->name);
});

test('920SO-FR-APPLY-023: proposed mode is available without a placement', function (): void {
    Livewire::test(ApplyPage::class)->set('form.use_placement', false)->assertSet('form.placement_id', '');
});

test('920SO-FR-APPLY-024: toggling back to placement mode clears proposed data', function (): void {
    Livewire::test(ApplyPage::class)
        ->set('form.use_placement', false)
        ->set('form.proposed_company_name', 'Stale Company')
        ->set('form.proposed_company_address', 'Stale Address')
        ->set('form.use_placement', true)
        ->assertSet('form.proposed_company_name', '')
        ->assertSet('form.proposed_company_address', '');
});

test('920SO-FR-APPLY-027: form payload is flat and excludes inactive mode fields', function (): void {
    $component = Livewire::test(ApplyPage::class)->set('form.use_placement', false);

    expect($component->get('form')->toArray())->toHaveKeys(['name', 'email', 'internship_id', 'academic_year'])
        ->and($component->get('form')->toArray()['placement_id'])->toBeNull();
});
