<?php

declare(strict_types=1);

use App\Actions\Setup\FinalizeSetupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('finalizes setup and returns a recovery key', function () {
        $recoveryKey = app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'SMKN 1 Jakarta',
                'institutional_code' => '10293847',
                'address' => '-',
                'email' => 'info@school.sch.id',
            ],
            departmentData: [
                'name' => 'RPL',
            ],
            adminData: [
                'name' => 'Admin',
                'email' => 'admin@school.sch.id',
                'username' => 'admin',
                'password' => 'password123',
            ],
        );

        expect($recoveryKey)->toBeString();
    });
});
