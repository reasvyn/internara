<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Partner\Domain\Partnership\Actions\BatchDeletePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\CreatePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\DeletePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\RenewPartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\TerminatePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\UpdatePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Data\PartnershipData;
use App\Modules\Partner\Domain\Partnership\Entities\PartnershipState;
use App\Modules\Partner\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

function partnershipLifecycleData(string $agreementNumber, ?Company $company = null, array $overrides = []): PartnershipData
{
    $company ??= Company::factory()->create();

    return new PartnershipData(
        companyId: $company->id,
        agreementNumber: $agreementNumber,
        title: $overrides['title'] ?? 'Kerja sama PKL '.$agreementNumber,
        startDate: $overrides['startDate'] ?? '2026-07-01',
        endDate: $overrides['endDate'] ?? '2027-06-30',
        scope: $overrides['scope'] ?? null,
        contactPersonName: $overrides['contactPersonName'] ?? 'Budi Santoso',
        contactPersonPhone: $overrides['contactPersonPhone'] ?? '081234567890',
        contactPersonEmail: $overrides['contactPersonEmail'] ?? 'budi@example.co.id',
        signedBySchool: $overrides['signedBySchool'] ?? 'Kepala Sekolah',
        signedByCompany: $overrides['signedByCompany'] ?? 'Direktur',
        signedAt: $overrides['signedAt'] ?? '2026-07-02',
        notes: $overrides['notes'] ?? null,
    );
}

describe('NTHQA: partnership lifecycle, CRUD, renewal and guards', function (): void {
    test('NTHQA-UC-PART-001: admin creates a partnership with its MoU data and it starts active', function (): void {
        $partnership = app(CreatePartnershipAction::class)->execute(
            partnershipLifecycleData('012/MoU/SMK/2026')
        );

        expect($partnership->status)->toBe(PartnershipStatus::ACTIVE);
        $this->assertDatabaseHas('partnerships', [
            'id' => $partnership->id,
            'agreement_number' => '012/MoU/SMK/2026',
            'status' => PartnershipStatus::ACTIVE->value,
        ]);
        expect(Partnership::where('agreement_number', '012/MoU/SMK/2026')->exists())->toBeTrue();
    });

    test('NTHQA-FR-PART-008: terminate moves active forward one step and refuses anything else', function (): void {
        $active = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE->value]);

        $terminated = app(TerminatePartnershipAction::class)->execute($active);

        expect($terminated->refresh()->status)->toBe(PartnershipStatus::TERMINATED);
        $this->assertDatabaseHas('partnerships', [
            'id' => $active->id,
            'status' => PartnershipStatus::TERMINATED->value,
        ]);

        expect(fn () => app(TerminatePartnershipAction::class)->execute($terminated->refresh()))
            ->toThrow(RejectedException::class);
        expect($terminated->refresh()->status)->toBe(PartnershipStatus::TERMINATED);

        $expired = Partnership::factory()->expired()->create();

        expect(fn () => app(TerminatePartnershipAction::class)->execute($expired))
            ->toThrow(RejectedException::class);
        expect($expired->refresh()->status)->toBe(PartnershipStatus::EXPIRED);
    });

    test('NTHQA-FR-PART-008: termination refusal names the translatable cause', function (): void {
        app()->setLocale('en');
        $terminated = Partnership::factory()->create(['status' => PartnershipStatus::TERMINATED->value]);

        try {
            app(TerminatePartnershipAction::class)->execute($terminated);
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('partnership.terminate_blocked'));
        }
    });

    test('NTHQA-UC-PART-002: terminating mid-window leaves existing rows and windows untouched', function (): void {
        $ending = Partnership::factory()->create([
            'status' => PartnershipStatus::ACTIVE->value,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $untouched = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE->value]);

        app(TerminatePartnershipAction::class)->execute($ending);

        expect($ending->refresh()->status)->toBe(PartnershipStatus::TERMINATED);
        expect($ending->refresh()->start_date->format('Y-m-d'))->toBe('2026-07-01');
        expect($ending->refresh()->end_date->format('Y-m-d'))->toBe('2027-06-30');
        expect($untouched->refresh()->status)->toBe(PartnershipStatus::ACTIVE);
        $this->assertModelExists($untouched->fresh());
    });

    test('NTHQA-FR-PART-009: single delete throws on active rows and removes terminal ones', function (): void {
        app()->setLocale('en');
        $active = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE->value]);

        try {
            app(DeletePartnershipAction::class)->execute($active);
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('partnership.delete_blocked'));
        }
        $this->assertModelExists($active->fresh());

        $expired = Partnership::factory()->expired()->create();
        app(DeletePartnershipAction::class)->execute($expired);

        $this->assertModelMissing($expired);
    });

    test('NTHQA-NFR-PART-002: no path deletes a living agreement, including batch cleanup', function (): void {
        $active = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE->value]);
        $expired = Partnership::factory()->expired()->create();

        $result = app(BatchDeletePartnershipAction::class)->execute([$active->id, $expired->id]);

        expect($result['deleted'])->toBe(1)
            ->and($result['blocked'])->toBe(1);
        $this->assertModelExists($active->fresh());
        $this->assertModelMissing($expired);
        expect(Partnership::where('status', PartnershipStatus::ACTIVE->value)->whereKey($active->id)->exists())->toBeTrue();
    });

    test('NTHQA-UC-PART-004: batch delete on a mixed registry deletes the stale and report the living', function (): void {
        $stale = Partnership::factory()->expired()->create();
        $ended = Partnership::factory()->create(['status' => PartnershipStatus::TERMINATED->value]);
        $liveOne = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE->value]);
        $liveTwo = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE->value]);

        $result = app(BatchDeletePartnershipAction::class)->execute([
            $stale->id, $ended->id, $liveOne->id, $liveTwo->id, '00000000-0000-0000-0000-000000000000',
        ]);

        expect($result)->toBe(['deleted' => 2, 'blocked' => 2]);
        $this->assertModelMissing($stale);
        $this->assertModelMissing($ended);
        $this->assertModelExists($liveOne->fresh());
        $this->assertModelExists($liveTwo->fresh());
    });

    test('NTHQA-UC-PART-005: editing contact details never disturbs lifecycle state', function (): void {
        $partnership = Partnership::factory()->create([
            'status' => PartnershipStatus::ACTIVE->value,
            'contact_person_name' => 'Old Name',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $updated = app(UpdatePartnershipAction::class)->execute(
            $partnership,
            partnershipLifecycleData($partnership->agreement_number, $partnership->company, [
                'contactPersonName' => 'New Name',
                'contactPersonPhone' => '089999999999',
            ])
        );

        expect($updated->contact_person_name)->toBe('New Name')
            ->and($updated->contact_person_phone)->toBe('089999999999')
            ->and($updated->status)->toBe(PartnershipStatus::ACTIVE)
            ->and($updated->start_date->format('Y-m-d'))->toBe('2026-07-01')
            ->and($updated->end_date->format('Y-m-d'))->toBe('2027-06-30');
    });

    test('NTHQA-FR-PART-011: renewal refuses live sources and births a new active record', function (): void {
        $live = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE->value]);

        expect(fn () => app(RenewPartnershipAction::class)->execute(
            $live,
            partnershipLifecycleData('MOU/2027/RENEW-1')
        ))->toThrow(RejectedException::class);
        $this->assertModelExists($live->fresh());

        $lapsed = Partnership::factory()->expired()->create([
            'agreement_number' => 'MOU/2023/OLD-1',
            'contact_person_name' => 'Carried Contact',
        ]);

        $fresh = app(RenewPartnershipAction::class)->execute(
            $lapsed,
            partnershipLifecycleData('MOU/2027/NEW-1')
        );

        expect($fresh->status)->toBe(PartnershipStatus::ACTIVE)
            ->and($fresh->agreement_number)->toBe('MOU/2027/NEW-1')
            ->and($fresh->id)->not->toBe($lapsed->id);
        expect($lapsed->refresh()->status)->toBe(PartnershipStatus::EXPIRED);
        expect($lapsed->refresh()->agreement_number)->toBe('MOU/2023/OLD-1');
        $this->assertDatabaseHas('partnerships', ['id' => $fresh->id, 'status' => 'active']);
    });

    test('NTHQA-UC-PART-003: renewed history keeps the old terms intact beside the new row', function (): void {
        $old = Partnership::factory()->expired()->create([
            'agreement_number' => 'MOU/2023/TEXTILE-1',
            'title' => 'Old textile terms',
            'contact_person_name' => 'Old Signatory',
        ]);

        $new = app(RenewPartnershipAction::class)->execute(
            $old,
            new PartnershipData(
                companyId: $old->company->id,
                agreementNumber: 'MOU/2027/TEXTILE-2',
                title: 'New textile terms',
                startDate: '2026-08-01',
                endDate: '2028-07-31',
            )
        );

        expect($old->refresh()->title)->toBe('Old textile terms')
            ->and($old->refresh()->status)->toBe(PartnershipStatus::EXPIRED);
        expect($new->title)->toBe('New textile terms')
            ->and($new->status)->toBe(PartnershipStatus::ACTIVE)
            ->and($new->contact_person_name)->toBe('Old Signatory');
    });

    test('NTHQA-FR-PART-013: a failed renewal leaves the registry byte-identical', function (): void {
        $doomed = Partnership::factory()->create([
            'status' => PartnershipStatus::TERMINATED->value,
            'agreement_number' => 'MOU/2023/DOOMED-1',
        ]);
        Partnership::factory()->create(['agreement_number' => 'MOU/2027/TAKEN-1']);
        $countBefore = Partnership::count();

        expect(fn () => app(RenewPartnershipAction::class)->execute(
            $doomed,
            partnershipLifecycleData('MOU/2027/TAKEN-1')
        ))->toThrow(QueryException::class);

        expect(Partnership::count())->toBe($countBefore);
        expect($doomed->refresh()->status)->toBe(PartnershipStatus::TERMINATED);
        expect($doomed->refresh()->agreement_number)->toBe('MOU/2023/DOOMED-1');
    });

    test('NTHQA-NFR-PART-006: renewal atomicity covers retirement, creation, and counts', function (): void {
        $source = Partnership::factory()->expired()->create(['agreement_number' => 'MOU/2023/ATOMIC-1']);
        $before = Partnership::count();

        $created = app(RenewPartnershipAction::class)->execute(
            $source,
            partnershipLifecycleData('MOU/2027/ATOMIC-2')
        );

        expect(Partnership::count())->toBe($before + 1);
        expect($source->refresh()->status)->toBe(PartnershipStatus::EXPIRED);
        expect($created->status)->toBe(PartnershipStatus::ACTIVE);
    });

    test('NTHQA-FR-PART-001: agreement rows carry UUID keys, unique numbers, and active defaults', function (): void {
        $partnership = app(CreatePartnershipAction::class)->execute(
            partnershipLifecycleData('MOU/2026/UNIQUE-1')
        );

        expect($partnership->getKey())->toBeString();
        expect(Str::isUuid($partnership->getKey()))->toBeTrue();
        expect($partnership->status)->toBe(PartnershipStatus::ACTIVE);

        expect(fn () => app(CreatePartnershipAction::class)->execute(
            partnershipLifecycleData('MOU/2026/UNIQUE-1')
        ))->toThrow(QueryException::class);
    });

    test('NTHQA-FR-PART-001: deleting a company cascades its agreements at the database level', function (): void {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->create(['company_id' => $company->id]);

        $company->delete();

        $this->assertModelMissing($company);
        $this->assertModelMissing($partnership);
    });

    test('NTHQA-FR-PART-002: model casts, company relation, bridge, and factory states', function (): void {
        $partnership = Partnership::factory()->create();

        expect($partnership->status)->toBeInstanceOf(PartnershipStatus::class);
        expect($partnership->start_date)->toBeInstanceOf(Carbon::class);
        expect($partnership->end_date)->toBeInstanceOf(Carbon::class);
        expect($partnership->company)->toBeInstanceOf(Company::class);
        expect($partnership->asPartnershipState())->toBeInstanceOf(PartnershipState::class);
        expect($partnership->asPartnershipState()->isActive())->toBeTrue();

        $lapsed = Partnership::factory()->expired()->create();

        expect($lapsed->status)->toBe(PartnershipStatus::EXPIRED);
        expect($lapsed->end_date->isPast())->toBeTrue();
    });

    test('NTHQA-NFR-PART-001: policy gates and action rules both refuse without a back door', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);

        $partnership = Partnership::factory()->create();

        expect(Gate::allows('create', Partnership::class))->toBeFalse();
        expect(Gate::allows('update', $partnership))->toBeFalse();

        expect(fn () => app(TerminatePartnershipAction::class)->execute(
            Partnership::factory()->expired()->create()
        ))->toThrow(RejectedException::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        expect(Gate::allows('create', Partnership::class))->toBeTrue();
        expect(Gate::allows('update', $partnership))->toBeTrue();
    });

    test('NTHQA-NFR-PART-003: partnership routes need auth and reserve the page for the admin group', function (): void {
        $this->get('/admin/companies/partnerships')->assertRedirect();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        $this->get('/admin/companies/partnerships')->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $this->get('/admin/companies/partnerships')->assertOk();
    });
});
