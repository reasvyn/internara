<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Partnership\Actions\CreateCompanyAction;
use App\Domain\Partnership\Actions\CreatePartnershipAction;
use App\Domain\Partnership\Actions\DeleteCompanyAction;
use App\Domain\Partnership\Actions\DeletePartnershipAction;
use App\Domain\Partnership\Actions\RenewPartnershipAction;
use App\Domain\Partnership\Actions\TerminatePartnershipAction;
use App\Domain\Partnership\Actions\UpdateCompanyAction;
use App\Domain\Partnership\Actions\UpdatePartnershipAction;
use App\Domain\Partnership\Models\Company;
use App\Domain\Partnership\Models\Partnership;
use App\Domain\Placement\Models\Placement;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
});

describe('CreateCompanyAction', function () {
    it('creates a company', function () {
        $company = app(CreateCompanyAction::class)->execute([
            'name' => 'Tech Corp',
            'email' => 'info@techcorp.com',
            'phone' => '021123456',
        ]);

        expect($company)->toBeInstanceOf(Company::class)
            ->and($company->name)->toBe('Tech Corp');
    });

    it('creates company with only name', function () {
        $company = app(CreateCompanyAction::class)->execute(['name' => 'Just Name']);

        expect($company->name)->toBe('Just Name');
    });
});

describe('UpdateCompanyAction', function () {
    it('updates company name', function () {
        $company = Company::factory()->create();

        $updated = app(UpdateCompanyAction::class)->execute($company, [
            'name' => 'Updated Corp',
        ]);

        expect($updated->name)->toBe('Updated Corp');
    });

    it('updates all fields', function () {
        $company = Company::factory()->create();

        $updated = app(UpdateCompanyAction::class)->execute($company, [
            'name' => 'Full Update Corp',
            'address' => '456 New St',
            'phone' => '021999888',
            'email' => 'new@company.com',
            'website' => 'https://newcompany.com',
            'description' => 'An updated description',
            'industry_sector' => 'Healthcare',
        ]);

        expect($updated->fresh()->name)->toBe('Full Update Corp')
            ->and($updated->fresh()->phone)->toBe('021999888')
            ->and($updated->fresh()->industry_sector)->toBe('Healthcare');
    });
});

describe('DeleteCompanyAction', function () {
    it('deletes a company with no placements or partnerships', function () {
        $company = Company::factory()->create();

        app(DeleteCompanyAction::class)->execute($company);

        expect(Company::find($company->id))->toBeNull();
    });

    it('blocks delete when has placements', function () {
        $company = Company::factory()->create();
        Placement::factory()->create(['company_id' => $company->id]);

        app(DeleteCompanyAction::class)->execute($company);
    })->throws(RejectedException::class);

    it('blocks delete when has partnerships', function () {
        $company = Company::factory()->create();
        Partnership::factory()->for($company)->create(['status' => 'expired']);

        app(DeleteCompanyAction::class)->execute($company);
    })->throws(RejectedException::class);
});

describe('CreatePartnershipAction', function () {
    it('creates a partnership', function () {
        $company = Company::factory()->create();

        $partnership = app(CreatePartnershipAction::class)->execute([
            'company_id' => $company->id,
            'agreement_number' => 'AG-'.str()->random(6),
            'title' => 'Partnership Agreement',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        expect($partnership)->toBeInstanceOf(Partnership::class)
            ->and($partnership->agreement_number)->not->toBeEmpty();
    });

    it('validates agreement_number uniqueness', function () {
        $company = Company::factory()->create();
        $number = 'AG-UNIQUE-001';
        app(CreatePartnershipAction::class)->execute([
            'company_id' => $company->id,
            'agreement_number' => $number,
            'title' => 'First',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        expect(fn () => app(CreatePartnershipAction::class)->execute([
            'company_id' => $company->id,
            'agreement_number' => $number,
            'title' => 'Duplicate',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]))->toThrow(ValidationException::class);
    });

    it('validates end_date after start_date', function () {
        $company = Company::factory()->create();

        expect(fn () => app(CreatePartnershipAction::class)->execute([
            'company_id' => $company->id,
            'agreement_number' => 'AG-DATE-001',
            'title' => 'Bad Dates',
            'start_date' => '2025-07-01',
            'end_date' => '2024-06-30',
        ]))->toThrow(ValidationException::class);
    });
});

describe('UpdatePartnershipAction', function () {
    it('updates a partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['title' => 'Original']);

        $updated = app(UpdatePartnershipAction::class)->execute($partnership, [
            'agreement_number' => $partnership->agreement_number,
            'title' => 'Updated Agreement',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        expect($updated->title)->toBe('Updated Agreement');
    });
});

describe('DeletePartnershipAction', function () {
    it('deletes a non-active partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'expired']);

        app(DeletePartnershipAction::class)->execute($partnership);

        expect(Partnership::find($partnership->id))->toBeNull();
    });

    it('blocks delete for active partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'active']);

        app(DeletePartnershipAction::class)->execute($partnership);
    })->throws(RejectedException::class);
});

describe('TerminatePartnershipAction', function () {
    it('terminates an active partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'active']);

        $terminated = app(TerminatePartnershipAction::class)->execute($partnership);

        expect($terminated->status->value)->toBe('terminated');
    });

    it('blocks terminate for expired partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'expired']);

        app(TerminatePartnershipAction::class)->execute($partnership);
    })->throws(RejectedException::class);

    it('blocks terminate for terminated partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'terminated']);

        app(TerminatePartnershipAction::class)->execute($partnership);
    })->throws(RejectedException::class);
});

describe('RenewPartnershipAction', function () {
    it('renews an expired partnership', function () {
        $company = Company::factory()->create();
        $old = Partnership::factory()->for($company)->create(['status' => 'expired']);

        $new = app(RenewPartnershipAction::class)->execute($old, [
            'agreement_number' => 'AG-NEW-001',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        expect($new)->toBeInstanceOf(Partnership::class)
            ->and($new->company_id)->toBe($company->id)
            ->and($new->agreement_number)->toBe('AG-NEW-001');
    });

    it('renews a terminated partnership', function () {
        $company = Company::factory()->create();
        $old = Partnership::factory()->for($company)->create(['status' => 'terminated']);

        $new = app(RenewPartnershipAction::class)->execute($old, [
            'agreement_number' => 'AG-RENEW-002',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        expect($new)->toBeInstanceOf(Partnership::class);
    });

    it('blocks renew for active partnership', function () {
        $company = Company::factory()->create();
        $old = Partnership::factory()->for($company)->create(['status' => 'active']);

        app(RenewPartnershipAction::class)->execute($old, [
            'agreement_number' => 'AG-BLOCKED',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);
    })->throws(RejectedException::class);

    it('inherits company_id from old partnership', function () {
        $company = Company::factory()->create();
        $old = Partnership::factory()->for($company)->create(['status' => 'expired']);

        $new = app(RenewPartnershipAction::class)->execute($old, [
            'agreement_number' => 'AG-INHERIT-001',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        expect($new->company_id)->toBe($company->id);
    });
});
