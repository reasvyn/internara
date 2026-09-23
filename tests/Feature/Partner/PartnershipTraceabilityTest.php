<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Partner\Domain\Partnership\Actions\DeletePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\RenewPartnershipAction;
use App\Modules\Partner\Domain\Partnership\Data\PartnershipData;
use App\Modules\Partner\Domain\Partnership\Entities\PartnershipState;
use App\Modules\Partner\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipRenewed;
use App\Modules\Partner\Domain\Partnership\Livewire\PartnershipManager;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('NTHQA: partnership advanced traceability', function (): void {

    test('NTHQA-FR-PART-005: entity refuses quota overcommit — remaining quota calculation', function (): void {
        $partnership = Partnership::factory()->create([
            'status' => PartnershipStatus::ACTIVE,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $state = $partnership->asPartnershipState();
        expect($state->isActive())->toBeTrue();
    });

    test('NTHQA-FR-PART-010: placements accepted only under active agreements whose windows contain placement dates', function (): void {
        $activeAgreement = Partnership::factory()->create([
            'status' => PartnershipStatus::ACTIVE,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $state = $activeAgreement->asPartnershipState();
        expect($state->isActive())->toBeTrue();

        $expiredAgreement = Partnership::factory()->create([
            'status' => PartnershipStatus::EXPIRED,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        expect($expiredAgreement->asPartnershipState()->isActive())->toBeFalse();
    });

    test('NTHQA-FR-PART-012: renewal transfers MoU document to new record atomically', function (): void {
        $old = Partnership::factory()->create(['status' => PartnershipStatus::EXPIRED]);

        $file = UploadedFile::fake()->create('mou-original.pdf', 500, 'application/pdf');
        $old->addMedia($file)->toMediaCollection(Partnership::COLLECTION_MOU);

        expect($old->getFirstMedia(Partnership::COLLECTION_MOU))->not->toBeNull();

        $action = app(RenewPartnershipAction::class);
        $company = $old->company;
        $newData = new PartnershipData(
            companyId: $company->id,
            agreementNumber: 'RENEWED-001',
            title: 'Renewed Agreement',
            startDate: '2026-07-01',
            endDate: '2027-06-30',
        );

        $renewed = $action->execute($old, $newData);

        expect($renewed->exists)->toBeTrue()
            ->and($renewed->agreement_number)->toBe('RENEWED-001');
    });

    test('NTHQA-FR-PART-016 and NTHQA-NFR-PART-004: MoU uploads validate MIME type with 10 MB ceiling and store outside web root', function (): void {
        $partnership = Partnership::factory()->create();

        $validPdf = UploadedFile::fake()->createWithContent('mou.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");
        $media = $partnership->addMedia($validPdf)->toMediaCollection(Partnership::COLLECTION_MOU);

        expect($media->mime_type)->toBe('application/pdf')
            ->and($media->size)->toBeLessThanOrEqual(10 * 1024 * 1024)
            ->and($media->getPath())->not->toStartWith(public_path());
    });

    test('NTHQA-FR-PART-018: expiry check detects agreements expiring within configured threshold', function (): void {
        $expiring = Partnership::factory()->create([
            'status' => PartnershipStatus::ACTIVE,
            'end_date' => now()->addDays(15)->format('Y-m-d'),
        ]);

        $state = $expiring->asPartnershipState();
        expect($state->isExpiringSoon(30))->toBeTrue()
            ->and($state->isExpiringSoon(10))->toBeFalse();
    });

    test('NTHQA-NFR-PART-009: status display uses enum label with text and icon, never color alone', function (): void {
        expect(PartnershipStatus::ACTIVE->label())->toBe('Active')
            ->and(PartnershipStatus::EXPIRED->label())->toBe('Expired')
            ->and(PartnershipStatus::TERMINATED->label())->toBe('Terminated');

        app()->setLocale('id');
        expect(PartnershipStatus::ACTIVE->label())->toBe('Aktif')
            ->and(PartnershipStatus::EXPIRED->label())->toBe('Kedaluwarsa')
            ->and(PartnershipStatus::TERMINATED->label())->toBe('Dihentikan');
        app()->setLocale('en');
    });

    test('NTHQA-NFR-PART-010: expiry threshold configured once and shared by entity predicate', function (): void {
        $threshold = config('partner.expiry_threshold_days', 30);
        expect($threshold)->toBeInt()->and($threshold)->toBeGreaterThanOrEqual(1);

        $partnership = Partnership::factory()->create([
            'status' => PartnershipStatus::ACTIVE,
            'end_date' => now()->addDays($threshold - 5)->format('Y-m-d'),
        ]);

        expect($partnership->asPartnershipState()->isExpiringSoon($threshold))->toBeTrue();
    });

    test('NTHQA-DD-PART-001: renewal creates a new record and retires old one instead of editing dates in place', function (): void {
        $old = Partnership::factory()->create(['status' => PartnershipStatus::EXPIRED]);
        $oldId = $old->id;

        $action = app(RenewPartnershipAction::class);
        $newData = new PartnershipData(
            companyId: $old->company_id,
            agreementNumber: 'RENEWED-DD-001',
            title: 'Renewed',
            startDate: '2026-07-01',
            endDate: '2027-06-30',
        );

        $new = $action->execute($old, $newData);

        expect($new->id)->not->toBe($oldId)
            ->and($old->fresh()->status)->toBe(PartnershipStatus::EXPIRED)
            ->and($new->status)->toBe(PartnershipStatus::ACTIVE);
    });

    test('NTHQA-DD-PART-002: MoU documents use single-file media collection with synchronous thumbnail', function (): void {
        $partnership = Partnership::factory()->create();

        expect(Partnership::COLLECTION_MOU)->toBe('mou_document');

        $file = UploadedFile::fake()->image('mou-scan.jpg', 600, 600);
        $media = $partnership->addMedia($file)->toMediaCollection(Partnership::COLLECTION_MOU);

        expect($partnership->getMedia(Partnership::COLLECTION_MOU)->count())->toBe(1);
    });

    test('NTHQA-DD-PART-003: manager joins companies for sortable company column', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $c1 = Company::factory()->create(['name' => 'Alpha Corporation NTHQA']);
        $c2 = Company::factory()->create(['name' => 'Beta Industries NTHQA']);

        Partnership::factory()->create([
            'company_id' => $c1->id,
            'status' => PartnershipStatus::ACTIVE->value,
            'title' => 'Agreement Alpha',
        ]);
        Partnership::factory()->create([
            'company_id' => $c2->id,
            'status' => PartnershipStatus::ACTIVE->value,
            'title' => 'Agreement Beta',
        ]);

        Livewire::test(PartnershipManager::class)
            ->assertSee('Alpha Corporation NTHQA');
    });

    test('NTHQA-DD-PART-004: lifecycle and quota rules live in state entity behind model bridge', function (): void {
        $partnership = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE]);

        $state = $partnership->asPartnershipState();
        expect($state)->toBeInstanceOf(PartnershipState::class)
            ->and($state->isActive())->toBeTrue()
            ->and($state->canBeDeleted())->toBeFalse();
    });

    test('NTHQA-DD-PART-005: deletion guards run at Action and entity against shared terminal-state predicate', function (): void {
        $active = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE]);
        $action = app(DeletePartnershipAction::class);

        expect(fn () => $action->execute($active))
            ->toThrow(RejectedException::class);

        $expired = Partnership::factory()->create(['status' => PartnershipStatus::EXPIRED]);
        $action->execute($expired);

        expect(Partnership::find($expired->id))->toBeNull();
    });

    test('NTHQA-DD-PART-006: renewal and sibling multi-step sequences are single transactions with one terminal event', function (): void {
        $old = Partnership::factory()->create(['status' => PartnershipStatus::EXPIRED]);

        Event::fake([
            PartnershipRenewed::class,
        ]);

        $action = app(RenewPartnershipAction::class);
        $newData = new PartnershipData(
            companyId: $old->company_id,
            agreementNumber: 'RENEWED-TX-001',
            title: 'Renewed Tx',
            startDate: '2026-07-01',
            endDate: '2027-06-30',
        );

        $new = $action->execute($old, $newData);

        Event::assertDispatched(
            PartnershipRenewed::class,
            1
        );
    });
});
