<?php

declare(strict_types=1);

namespace Tests\Partners\Partnership\Actions;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\Partners\Domain\Partnership\Actions\UpdatePartnershipAction;
use App\Modules\Partners\Domain\Partnership\Data\PartnershipData;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipUpdated;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

test('NTHQA-FR-PC2: UpdatePartnershipAction successfully updates a partnership', function () {
    Event::fake();

    // Arrange
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->for($company)->create([
        'title' => 'Old Title',
        'agreement_number' => 'OLD-AGR-001',
    ]);

    $newData = PartnershipData::from([
        'companyId' => $company->id,
        'agreementNumber' => 'NEW-AGR-002',
        'title' => 'Updated Partnership Title',
        'startDate' => '2026-02-01',
        'endDate' => '2027-02-01',
        'scope' => 'Updated Scope',
    ]);

    // Act
    $action = app(UpdatePartnershipAction::class);
    $updatedPartnership = $action->execute($partnership, $newData);

    // Assert
    expect($updatedPartnership)->toBeInstanceOf(Partnership::class)
        ->and($updatedPartnership->id)->toBe($partnership->id)
        ->and($updatedPartnership->title)->toBe('Updated Partnership Title')
        ->and($updatedPartnership->agreement_number)->toBe('NEW-AGR-002');

    $this->assertDatabaseHas('partnerships', [
        'id' => $partnership->id,
        'title' => 'Updated Partnership Title',
        'agreement_number' => 'NEW-AGR-002',
    ]);

    Event::assertDispatched(PartnershipUpdated::class, fn (PartnershipUpdated $event) => $event->partnership->is($updatedPartnership));
});

test('NTHQA-FR-PC2: UpdatePartnershipAction updates only specified fields', function () {
    Event::fake();

    // Arrange
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->for($company)->create([
        'title' => 'Original Title',
        'agreement_number' => 'ORIG-AGR-001',
        'scope' => 'Original Scope',
        'notes' => 'Original Notes',
    ]);

    $newData = PartnershipData::from([
        'companyId' => $company->id,
        'agreementNumber' => 'ORIG-AGR-001',
        'title' => 'Original Title',
        'startDate' => $partnership->start_date->format('Y-m-d'),
        'endDate' => $partnership->end_date->format('Y-m-d'),
        'scope' => 'New Partial Scope',
        // Other fields implicitly remain unchanged as they are not explicitly set in data and data is not partial
    ]);

    // Act
    $action = app(UpdatePartnershipAction::class);
    $updatedPartnership = $action->execute($partnership, $newData);

    // Assert
    expect($updatedPartnership)->toBeInstanceOf(Partnership::class);
    $this->assertDatabaseHas('partnerships', [
        'id' => $partnership->id,
        'scope' => 'New Partial Scope',
        'notes' => 'Original Notes',
    ]);

    Event::assertDispatched(PartnershipUpdated::class);
});

test('NTHQA-FR-PC2: UpdatePartnershipAction throws RejectedException if updating to a non-existent company', function () {
    // Arrange
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->for($company)->create();

    $invalidCompanyId = 'non-existent-uuid';
    $data = PartnershipData::from([
        'companyId' => $invalidCompanyId,
        'agreementNumber' => $partnership->agreement_number,
        'title' => $partnership->title,
        'startDate' => $partnership->start_date->format('Y-m-d'),
        'endDate' => $partnership->end_date->format('Y-m-d'),
    ]);

    // Act & Assert
    $action = app(UpdatePartnershipAction::class);
    expect(fn () => $action->execute($partnership, $data))
        ->toThrow(RejectedException::class); // Assuming a foreign key constraint or validation would reject this.
});

test('NTHQA-FR-PC2: UpdatePartnershipAction throws exception for duplicate agreement number when updating another partnership', function () {
    // Arrange
    $company = Company::factory()->create();
    $partnership1 = Partnership::factory()->for($company)->create(['agreement_number' => 'AGR-UNIQUE-001']);
    $partnership2 = Partnership::factory()->for($company)->create(['agreement_number' => 'AGR-TO-BE-DUPLICATED']);

    $newData = PartnershipData::from([
        'companyId' => $company->id,
        'agreementNumber' => 'AGR-UNIQUE-001', // This agreement number already exists for partnership1
        'title' => 'Updated Title',
        'startDate' => $partnership2->start_date->format('Y-m-d'),
        'endDate' => $partnership2->end_date->format('Y-m-d'),
    ]);

    // Act & Assert
    $action = app(UpdatePartnershipAction::class);
    expect(fn () => $action->execute($partnership2, $newData))
        ->toThrow(\Illuminate\Database\QueryException::class); // Expect QueryException for unique constraint violation.
});
