<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Modules\Assessment\Actions\GenerateAssessmentMagicLinkAction;
use App\Modules\Assessment\Actions\SubmitPartnerAssessmentAction;
use App\Modules\Assessment\Data\GenerateMagicLinkData;
use App\Modules\Assessment\Data\SubmitPartnerAssessmentData;
use App\Modules\Assessment\Livewire\PartnerAssessmentPage;
use App\Modules\Assessment\Models\AssessmentMagicLink;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('MGCLK: Industry Supervisor Magic Link Assessment', function (): void {
    test('MGCLK-FR-001: GenerateAssessmentMagicLinkAction creates token with custom validity', function (): void {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $action = app(GenerateAssessmentMagicLinkAction::class);
        $link = $action->execute(new GenerateMagicLinkData(
            registrationId: (string) $registration->id,
            creatorId: (string) $teacher->id,
            validityDays: 14,
        ));

        expect($link)->toBeInstanceOf(AssessmentMagicLink::class)
            ->and($link->token)->toHaveLength(64)
            ->and($link->registration_id)->toBe((string) $registration->id)
            ->and($link->isExpired())->toBeFalse()
            ->and($link->isSubmitted())->toBeFalse();
    });

    test('MGCLK-FR-002: Default validity is 30 days', function (): void {
        $student = User::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $action = app(GenerateAssessmentMagicLinkAction::class);
        $link = $action->execute(new GenerateMagicLinkData(
            registrationId: (string) $registration->id,
        ));

        expect((int) round(now()->diffInDays($link->expires_at, false)))->toBe(30);
    });

    test('MGCLK-FR-003: Public PartnerAssessmentPage mounts for guest without authentication', function (): void {
        $student = User::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $action = app(GenerateAssessmentMagicLinkAction::class);
        $link = $action->execute(new GenerateMagicLinkData(
            registrationId: (string) $registration->id,
        ));

        $test = Livewire::test(PartnerAssessmentPage::class, ['token' => $link->token]);
        $test->assertOk()
            ->assertSet('isNotFound', false)
            ->assertSet('isExpired', false)
            ->assertSet('isSubmitted', false);
    });

    test('MGCLK-FR-004: PartnerAssessmentPage shows expired notice when token expired', function (): void {
        $student = User::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $link = AssessmentMagicLink::create([
            'registration_id' => (string) $registration->id,
            'token' => str_repeat('a', 64),
            'expires_at' => now()->subDay(),
        ]);

        $test = Livewire::test(PartnerAssessmentPage::class, ['token' => $link->token]);
        $test->assertOk()
            ->assertSet('isExpired', true);
    });

    test('MGCLK-FR-005: PartnerAssessmentPage shows not found for unknown token', function (): void {
        $test = Livewire::test(PartnerAssessmentPage::class, ['token' => 'invalid-token-123']);
        $test->assertOk()
            ->assertSet('isNotFound', true);
    });

    test('MGCLK-FR-006: Partner can submit evaluation and lock the link', function (): void {
        $student = User::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $action = app(GenerateAssessmentMagicLinkAction::class);
        $link = $action->execute(new GenerateMagicLinkData(
            registrationId: (string) $registration->id,
        ));

        $submitAction = app(SubmitPartnerAssessmentAction::class);
        $response = $submitAction->execute(new SubmitPartnerAssessmentData(
            token: $link->token,
            evaluatorName: 'Budi Santoso',
            evaluatorPhone: '081234567890',
            feedback: 'Siswa bekerja dengan sangat baik dan disiplin tinggi.',
            scores: ['comp-1' => ['ind-1' => 90]],
        ));

        expect($response->success)->toBeTrue();

        $freshLink = $link->fresh();
        expect($freshLink->isSubmitted())->toBeTrue()
            ->and($freshLink->evaluator_name)->toBe('Budi Santoso')
            ->and($freshLink->feedback)->toContain('disiplin');

        // Verify page reflects submitted status
        $test = Livewire::test(PartnerAssessmentPage::class, ['token' => $link->token]);
        $test->assertOk()
            ->assertSet('isSubmitted', true)
            ->assertSet('evaluatorName', 'Budi Santoso');
    });
});
