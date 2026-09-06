<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partners\Domain\Partnership\Actions\RenewPartnershipAction;
use App\Modules\Partners\Domain\Partnership\Data\PartnershipData;
use App\Modules\Partners\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipRenewed;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Partnership Renewal — RenewPartnershipAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: RenewPartnershipAction', function (): void {
    test('NTHQA-FR-RN1: renews expired partnership with new data', function (): void {
        $oldPartnership = Partnership::factory()->expired()->create([
            'title' => 'Old Title',
            'scope' => 'Old Scope',
        ]);

        $newData = new PartnershipData(
            companyId: $oldPartnership->company_id,
            agreementNumber: 'MOU-RENEWED-001',
            title: 'Renewed Title',
            startDate: '2027-01-01',
            endDate: '2027-12-31',
            scope: 'Renewed Scope',
        );

        $newPartnership = app(RenewPartnershipAction::class)->execute($oldPartnership, $newData);

        expect($newPartnership)->toBeInstanceOf(Partnership::class)
            ->and($newPartnership->agreement_number)->toBe('MOU-RENEWED-001')
            ->and($newPartnership->title)->toBe('Renewed Title')
            ->and($newPartnership->scope)->toBe('Renewed Scope')
            ->and($newPartnership->status)->toBe(PartnershipStatus::ACTIVE)
            ->and($newPartnership->id)->not->toBe($oldPartnership->id);
    });

    test('NTHQA-FR-RN1: marks old partnership as expired', function (): void {
        $oldPartnership = Partnership::factory()->create(['status' => PartnershipStatus::TERMINATED->value]);

        $newData = new PartnershipData(
            companyId: $oldPartnership->company_id,
            agreementNumber: 'MOU-RENEWED-002',
            title: 'New',
            startDate: '2027-01-01',
            endDate: '2027-12-31',
        );

        app(RenewPartnershipAction::class)->execute($oldPartnership, $newData);

        expect($oldPartnership->fresh()->status)->toBe(PartnershipStatus::EXPIRED);
    });

    test('NTHQA-FR-RN2: inherits old partnership data when new data fields are null', function (): void {
        $oldPartnership = Partnership::factory()->expired()->create([
            'title' => 'Inherited Title',
            'scope' => 'Inherited Scope',
            'contact_person_name' => 'Inherited Contact',
            'contact_person_phone' => '+62000000000',
            'contact_person_email' => 'inherited@example.com',
            'signed_by_school' => 'Old School Signer',
            'signed_by_company' => 'Old Company Signer',
        ]);

        $newData = new PartnershipData(
            companyId: $oldPartnership->company_id,
            agreementNumber: 'MOU-RENEWED-003',
            title: 'Inherited Title',
            startDate: '2027-01-01',
            endDate: '2027-12-31',
            // scope, contact fields intentionally null — action falls back to old values
            scope: null,
            contactPersonName: null,
            contactPersonPhone: null,
            contactPersonEmail: null,
            signedBySchool: null,
            signedByCompany: null,
        );

        $newPartnership = app(RenewPartnershipAction::class)->execute($oldPartnership, $newData);

        expect($newPartnership->title)->toBe('Inherited Title')
            ->and($newPartnership->scope)->toBe('Inherited Scope')
            ->and($newPartnership->contact_person_name)->toBe('Inherited Contact')
            ->and($newPartnership->contact_person_phone)->toBe('+62000000000')
            ->and($newPartnership->contact_person_email)->toBe('inherited@example.com')
            ->and($newPartnership->signed_by_school)->toBe('Old School Signer')
            ->and($newPartnership->signed_by_company)->toBe('Old Company Signer');
    });

    test('NTHQA-FR-RN3: rejects renewal of active partnership', function (): void {
        $activePartnership = Partnership::factory()->active()->create();

        $newData = new PartnershipData(
            companyId: $activePartnership->company_id,
            agreementNumber: 'MOU-RENEWED-BAD',
            title: 'Should Fail',
            startDate: '2027-01-01',
            endDate: '2027-12-31',
        );

        expect(fn () => app(RenewPartnershipAction::class)->execute($activePartnership, $newData))
            ->toThrow(RejectedException::class);
    });

    test('NTHQA-FR-RN4: dispatches PartnershipRenewed event', function (): void {
        Event::fake([PartnershipRenewed::class]);

        $oldPartnership = Partnership::factory()->expired()->create();

        $newData = new PartnershipData(
            companyId: $oldPartnership->company_id,
            agreementNumber: 'MOU-RENEWED-EVT',
            title: 'Event Renewal',
            startDate: '2027-01-01',
            endDate: '2027-12-31',
        );

        $newPartnership = app(RenewPartnershipAction::class)->execute($oldPartnership, $newData);

        Event::assertDispatched(PartnershipRenewed::class, function ($event) use ($newPartnership, $oldPartnership) {
            return $event->newPartnership->id === $newPartnership->id
                && $event->oldPartnership->id === $oldPartnership->id;
        });
    });

    test('NTHQA-FR-RN5: renewal creates new partnership record in database', function (): void {
        $oldPartnership = Partnership::factory()->expired()->create();
        $countBefore = Partnership::count();

        $newData = new PartnershipData(
            companyId: $oldPartnership->company_id,
            agreementNumber: 'MOU-RENEWED-DB',
            title: 'DB Check',
            startDate: '2027-01-01',
            endDate: '2027-12-31',
        );

        app(RenewPartnershipAction::class)->execute($oldPartnership, $newData);

        expect(Partnership::count())->toBe($countBefore + 1);
    });

    test('NTHQA-NFR-R1: wraps renewal in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(RenewPartnershipAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });
});
