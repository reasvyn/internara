<?php

declare(strict_types=1);

use App\Actions\School\UpdateDepartmentAction;
use Database\Factories\DepartmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates a department', function () {
        $department = DepartmentFactory::new()->create();

        $result = app(UpdateDepartmentAction::class)->execute($department, [
            'name' => 'Science Department',
        ]);

        expect($result->name)->toBe('Science Department');
    });
});
