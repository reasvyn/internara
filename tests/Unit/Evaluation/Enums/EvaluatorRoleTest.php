<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Evaluation\Enums\EvaluatorRole;

test('evaluator role has all expected cases', function () {
    expect(EvaluatorRole::cases())->toHaveCount(4);

    expect(EvaluatorRole::ADMIN)->toBeInstanceOf(EvaluatorRole::class);
    expect(EvaluatorRole::TEACHER)->toBeInstanceOf(EvaluatorRole::class);
    expect(EvaluatorRole::SUPERVISOR)->toBeInstanceOf(EvaluatorRole::class);
    expect(EvaluatorRole::SYSTEM)->toBeInstanceOf(EvaluatorRole::class);
});

test('evaluator role implements LabelEnum', function () {
    expect(EvaluatorRole::ADMIN)->toBeInstanceOf(LabelEnum::class);
});

test('evaluator role is string backed', function () {
    expect(EvaluatorRole::ADMIN->value)->toBe('admin');
    expect(EvaluatorRole::TEACHER->value)->toBe('teacher');
    expect(EvaluatorRole::SUPERVISOR->value)->toBe('supervisor');
    expect(EvaluatorRole::SYSTEM->value)->toBe('system');
});

test('evaluator role label returns non-empty string for each case', function () {
    foreach (EvaluatorRole::cases() as $case) {
        $label = $case->label();
        expect($label)->toBeString();
        expect(trim($label))->not->toBeEmpty();
    }
});

test('evaluator role label returns translated values', function () {
    expect(EvaluatorRole::ADMIN->label())->toBe(__('Admin'));
    expect(EvaluatorRole::TEACHER->label())->toBe(__('Teacher'));
    expect(EvaluatorRole::SUPERVISOR->label())->toBe(__('Industry Supervisor'));
    expect(EvaluatorRole::SYSTEM->label())->toBe(__('System (Auto)'));
});
