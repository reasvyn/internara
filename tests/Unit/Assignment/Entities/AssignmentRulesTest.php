<?php

declare(strict_types=1);

use App\Domain\Assignment\Entities\AssignmentRules;
use App\Domain\Core\Entities\BaseEntity;
use Carbon\Carbon;

describe('AssignmentRules entity', function () {
    it('detects mandatory', function () {
        $entity = new AssignmentRules(isMandatory: true, dueDate: null);

        expect($entity->isMandatory())->toBeTrue();
    });

    it('detects optional', function () {
        $entity = new AssignmentRules(isMandatory: false, dueDate: null);

        expect($entity->isMandatory())->toBeFalse();
    });

    it('detects overdue', function () {
        $entity = new AssignmentRules(
            isMandatory: true,
            dueDate: Carbon::now()->subDay(),
        );

        expect($entity->isOverdue(Carbon::now()))->toBeTrue();
    });

    it('detects not overdue', function () {
        $entity = new AssignmentRules(
            isMandatory: true,
            dueDate: Carbon::now()->addDay(),
        );

        expect($entity->isOverdue(Carbon::now()))->toBeFalse();
    });

    it('handles null due date', function () {
        $entity = new AssignmentRules(isMandatory: true, dueDate: null);

        expect($entity->isOverdue(Carbon::now()))->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(AssignmentRules::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(AssignmentRules::class)->toExtend(BaseEntity::class);
    });
});
