<?php

declare(strict_types=1);

use App\Evaluation\Enums\EvaluatorRole;

test('evaluator role has all expected cases', function () {
    $cases = EvaluatorRole::cases();

    expect($cases)->toHaveCount(4);
    expect(EvaluatorRole::ADMIN->value)->toBe('admin');
    expect(EvaluatorRole::TEACHER->value)->toBe('teacher');
    expect(EvaluatorRole::SUPERVISOR->value)->toBe('supervisor');
    expect(EvaluatorRole::SYSTEM->value)->toBe('system');
});

test('evaluator role label returns non-empty string', function () {
    foreach (EvaluatorRole::cases() as $role) {
        expect($role->label())->toBeString()->not->toBeEmpty();
    }
});
