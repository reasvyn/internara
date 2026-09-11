<?php

declare(strict_types=1);

use App\Modules\Assignment\Entities\AssignmentRules;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class AssignmentRulesModelDouble extends Model
{
    protected $guarded = [];
}

describe('T657Z: assignment rules', function (): void {
    test('T657Z-FR-ASG-002: fromModel bridges mandatory flag and due date without persisting', function (): void {
        $model = new AssignmentRulesModelDouble([
            'is_mandatory' => true,
            'due_date' => Carbon::parse('2026-03-01 23:59:59'),
        ]);

        $rules = AssignmentRules::fromModel($model);

        expect($rules->isMandatory())->toBeTrue();
        expect($rules->isOverdue(Carbon::parse('2026-03-02 00:00:00')))->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('T657Z-FR-ASG-002: fromModel treats a missing due date as an open window', function (): void {
        $model = new AssignmentRulesModelDouble(['is_mandatory' => false, 'due_date' => null]);

        $rules = AssignmentRules::fromModel($model);

        expect($rules->isMandatory())->toBeFalse();
        expect($rules->isOverdue(Carbon::parse('2026-12-31 00:00:00')))->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('T657Z-FR-ASG-002: fromArray hydrates the pair and rejects missing params', function (): void {
        $rules = AssignmentRules::fromArray(['isMandatory' => true, 'dueDate' => null]);

        expect($rules->isMandatory())->toBeTrue();

        expect(fn (): AssignmentRules => AssignmentRules::fromArray(['isMandatory' => true]))
            ->toThrow(InvalidArgumentException::class, 'dueDate');
    });

    test('T657Z-FR-SUBM-003: isOverdue compares the due date against the given clock', function (): void {
        $due = Carbon::parse('2026-03-01 23:59:59');
        $rules = AssignmentRules::fromArray(['isMandatory' => false, 'dueDate' => $due]);

        expect($rules->isOverdue(Carbon::parse('2026-03-01 23:59:59')))->toBeFalse();
        expect($rules->isOverdue(Carbon::parse('2026-03-02 00:00:00')))->toBeTrue();
        expect($rules->isOverdue(Carbon::parse('2026-02-28 00:00:00')))->toBeFalse();
    });

    test('T657Z-FR-SUBM-003: isOverdue is false when no due date is set', function (): void {
        $rules = AssignmentRules::fromArray(['isMandatory' => true, 'dueDate' => null]);

        expect($rules->isOverdue(Carbon::parse('2030-01-01 00:00:00')))->toBeFalse();
    });
});
