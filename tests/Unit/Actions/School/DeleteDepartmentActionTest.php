<?php

declare(strict_types=1);

use App\Actions\School\DeleteDepartmentAction;
use Database\Factories\DepartmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes a department', function () {
        $department = DepartmentFactory::new()->create();

        app(DeleteDepartmentAction::class)->execute($department);

        expect($department->fresh())->toBeNull();
    });
});
