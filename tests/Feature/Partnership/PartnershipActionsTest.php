<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
});

describe('UpdateCompanyAction', function () {
    it('updates a company', function () {
        $company = Company::factory()->create();

        $updated = app(UpdateCompanyAction::class)->execute($company, [
            'name' => 'Updated Corp',
        ]);

        expect($updated->name)->toBe('Updated Corp');
    });
});

describe('DeleteCompanyAction', function () {
    it('deletes a company with no placements or partnerships', function () {
        $company = Company::factory()->create();

        app(DeleteCompanyAction::class)->execute($company);

        expect(Company::find($company->id))->toBeNull();
    });
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
});

describe('UpdatePartnershipAction', function () {
    it('updates a partnership', function () {
        $company = Company::factory()->create();
        $partnershipId = (string) Str::uuid();
        DB::table('partnerships')->insert([
            'id' => $partnershipId,
            'company_id' => $company->id,
            'agreement_number' => 'AG-'.str()->random(6),
            'title' => 'Original',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $partnership = Partnership::find($partnershipId);

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
    it('deletes a partnership', function () {
        $company = Company::factory()->create();
        $partnershipId = (string) Str::uuid();
        DB::table('partnerships')->insert([
            'id' => $partnershipId,
            'company_id' => $company->id,
            'agreement_number' => 'AG-'.str()->random(6),
            'title' => 'To Delete',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $partnership = Partnership::find($partnershipId);

        app(DeletePartnershipAction::class)->execute($partnership);

        expect(Partnership::find($partnershipId))->toBeNull();
    });
});

describe('TerminatePartnershipAction', function () {
    it('terminates an active partnership', function () {
        $company = Company::factory()->create();
        $partnershipId = (string) Str::uuid();
        DB::table('partnerships')->insert([
            'id' => $partnershipId,
            'company_id' => $company->id,
            'agreement_number' => 'AG-'.str()->random(6),
            'title' => 'Active Partnership',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $partnership = Partnership::find($partnershipId);

        $terminated = app(TerminatePartnershipAction::class)->execute($partnership);

        expect($terminated->status)->toBe('terminated');
    });
});

describe('RenewPartnershipAction', function () {
    it('renews a non-active partnership', function () {
        $company = Company::factory()->create();
        $oldId = (string) Str::uuid();
        DB::table('partnerships')->insert([
            'id' => $oldId,
            'company_id' => $company->id,
            'agreement_number' => 'AG-'.str()->random(6),
            'title' => 'Expired Partnership',
            'status' => 'expired',
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $oldPartnership = Partnership::find($oldId);

        $newPartnership = app(RenewPartnershipAction::class)->execute($oldPartnership, [
            'agreement_number' => 'AG-'.str()->random(6),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        expect($newPartnership)->toBeInstanceOf(Partnership::class)
            ->and($newPartnership->company_id)->toBe($company->id)
            ->and($oldPartnership->fresh()->status)->toBe('expired');
    });
});
