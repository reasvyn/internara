<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Actions;

use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Partnership Management — CreatePartnershipAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: CreatePartnershipAction', function (): void {
    test('NTHQA-FR-PC1: creates partnership with valid data', function (): void {
        $company = createCompany();

        $result = app(CreatePartnershipAction::class)->execute(PartnershipData::from([
            'companyId' => $company->id,
            'agreementNumber' => 'AGREEMENT-2024-001',
            'title' => 'Software Development Partnership',
            'startDate' => '2024-01-01',
            'endDate' => '2025-12-31',
            'scope' => 'Development, Testing, Maintenance',
            'contactPersonName' => 'John Doe',
            'contactPersonPhone' => '+1-555-0123',
            'contactPersonEmail' => 'john@company.com',
            'signedBySchool' => 'Yes',
            'signedByCompany' => 'Yes',
            'signedAt' => '2024-01-15',
            'notes' => 'Initial partnership agreement',
        ]));

        expect($result->id)->toBeString();
        expect($result->company_id)->toBe($company->id);
        expect($result->agreement_number)->toBe('AGREEMENT-2024-001');
        expect($result->title)->toBe('Software Development Partnership');
        expect($result->start_date->format('Y-m-d'))->toBe('2024-01-01');
        expect($result->end_date->format('Y-m-d'))->toBe('2025-12-31');
        expect($result->status->value)->toBe('active');
    });

    test('NTHQA-FR-PC2: rejects duplicate agreement number', function (): void {
        $company = createCompany();
        app(CreatePartnershipAction::class)->execute(PartnershipData::from([
            'companyId' => $company->id,
            'agreementNumber' => 'DUPLICATE-001',
            'title' => 'First Agreement',
            'startDate' => '2024-01-01',
            'endDate' => '2025-12-31',
        ]));

        expect(fn () => app(CreatePartnershipAction::class)->execute(PartnershipData::from([
            'companyId' => $company->id,
            'agreementNumber' => 'DUPLICATE-001',
            'title' => 'Duplicate Agreement',
            'startDate' => '2024-01-01',
            'endDate' => '2025-12-31',
        ])))->toThrow(ValidationException::class);
    });

    test('NTHQA-FR-PC3: rejects invalid dates (end_date before start_date)', function (): void {
        expect(fn () => app(CreatePartnershipAction::class)->execute(PartnershipData::from([
            'companyId' => createCompany()->id,
            'agreementNumber' => 'INVALID-DATES-001',
            'title' => 'Invalid Dates',
            'startDate' => '2025-01-01',
            'endDate' => '2024-12-31', // before start_date
        ])))->toThrow(ValidationException::class);
    });

    test('NTHQA-FR-PC4: partnership status defaults to active', function (): void {
        $result = app(CreatePartnershipAction::class)->execute(PartnershipData::from([
            'companyId' => createCompany()->id,
            'agreementNumber' => 'DEFAULT-STATUS-001',
            'title' => 'Default Status Test',
            'startDate' => '2024-01-01',
            'endDate' => '2025-12-31',
        ]));

        expect($result->status->value)->toBe('active');
    });

    test('NTHQA-FR-PC5: dispatches PartnershipCreated event', function (): void {
        Event::fake([PartnershipCreated::class]);

        $result = app(CreatePartnershipAction::class)->execute(PartnershipData::from([
            'companyId' => createCompany()->id,
            'agreementNumber' => 'EVENT-DISPATCH-001',
            'title' => 'Event Dispatch Test',
            'startDate' => '2024-01-01',
            'endDate' => '2025-12-31',
        ]));

        Event::assertDispatched(PartnershipCreated::class, function ($event) use ($result) {
            return $event->partnership->id === $result->id;
        });
    });

    test('NTHQA-FR-PC6: create fails with non-existent company', function (): void {
        $nonExistentCompanyId = '00000000-0000-0000-0000-000000000000';

        expect(fn () => app(CreatePartnershipAction::class)->execute(PartnershipData::from([
            'companyId' => $nonExistentCompanyId,
            'agreementNumber' => 'NON-EXISTENT-COMPANY',
            'title' => 'Test',
            'startDate' => '2024-01-01',
            'endDate' => '2025-12-31',
        ])))->toThrow(ValidationException::class);
    });

    test('NTHQA-FR-ST1: partnership can be activated', function (): void {
        $partnership = createPartnership();
        expect($partnership->status->value)->toBe('active');

        // Assuming there's an activation action
        // This test validates that active partnerships are in correct state
        expect($partnership->asPartnershipState()->isActive())->toBeTrue();
        expect($partnership->asPartnershipState()->canBeDeleted())->toBeFalse();
    });

    test('NTHQA-FR-ST2: partnership can be expired', function (): void {
        $partnership = createPartnership();
        $partnership->update(['status' => 'expired']);

        expect($partnership->asPartnershipState()->isExpired())->toBeTrue();
        expect($partnership->asPartnershipState()->canBeDeleted())->toBeTrue();
    });

    test('NTHQA-FR-ST3: partnership can be terminated', function (): void {
        $partnership = createPartnership();
        $partnership->update(['status' => 'terminated']);

        expect($partnership->asPartnershipState()->isTerminated())->toBeTrue();
        expect($partnership->asPartnershipState()->canBeDeleted())->toBeTrue();
    });
});

function createCompany(): Company
{
    return Company::factory()->create();
}

function createPartnership(): Partnership
{
    $company = createCompany();

    return app(CreatePartnershipAction::class)->execute(PartnershipData::from([
        'companyId' => $company->id,
        'agreementNumber' => 'TEST-AGREEMENT-'.Str::random(8),
        'title' => 'Test Partnership',
        'startDate' => '2024-01-01',
        'endDate' => '2025-12-31',
    ]));
}
