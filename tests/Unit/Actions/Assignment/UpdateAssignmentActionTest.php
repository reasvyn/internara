<?php

declare(strict_types=1);

use App\Actions\Assignment\UpdateAssignmentAction;
use Database\Factories\AssignmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates assignment fields', function () {
        $assignment = AssignmentFactory::new()->create();

        $result = app(UpdateAssignmentAction::class)->execute(
            $assignment,
            title: 'Updated Title',
            description: 'Updated description',
            isMandatory: true,
        );

        expect($result->title)->toBe('Updated Title')
            ->and($result->description)->toBe('Updated description')
            ->and($result->is_mandatory)->toBeTrue();
    });

    it('ignores null values and keeps existing data', function () {
        $assignment = AssignmentFactory::new()->create([
            'title' => 'Original Title',
            'description' => 'Original description',
        ]);

        $result = app(UpdateAssignmentAction::class)->execute(
            $assignment,
            title: 'Only Title Updated',
        );

        expect($result->title)->toBe('Only Title Updated')
            ->and($result->description)->toBe('Original description');
    });
});
