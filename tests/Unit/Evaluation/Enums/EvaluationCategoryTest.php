<?php

declare(strict_types=1);

use App\Evaluation\Enums\EvaluationCategory;

test('evaluation category has all expected cases', function () {
    $cases = EvaluationCategory::cases();

    expect($cases)->toHaveCount(5);
    expect(EvaluationCategory::MENTOR->value)->toBe('mentor');
    expect(EvaluationCategory::PROGRAM->value)->toBe('program');
    expect(EvaluationCategory::COMPANY->value)->toBe('company');
    expect(EvaluationCategory::FACILITY->value)->toBe('facility');
    expect(EvaluationCategory::OVERALL->value)->toBe('overall');
});

test('evaluation category label returns non-empty string', function () {
    foreach (EvaluationCategory::cases() as $category) {
        expect($category->label())->toBeString()->not->toBeEmpty();
    }
});

test('default criteria returns array for each category', function () {
    $criteria = EvaluationCategory::MENTOR->defaultCriteria();

    expect($criteria)->toBeArray();
    expect($criteria)->toHaveKey('communication');
    expect($criteria)->toHaveKey('responsiveness');
    expect($criteria)->toHaveKey('guidance_quality');

    expect(EvaluationCategory::PROGRAM->defaultCriteria())->toHaveKey('curriculum_relevance');
    expect(EvaluationCategory::COMPANY->defaultCriteria())->toHaveKey('workplace_safety');
    expect(EvaluationCategory::FACILITY->defaultCriteria())->toHaveKey('equipment_quality');
    expect(EvaluationCategory::OVERALL->defaultCriteria())->toHaveKey('overall_satisfaction');
});

test('default criteria keys differ per category', function () {
    $mentorKeys = array_keys(EvaluationCategory::MENTOR->defaultCriteria());
    $programKeys = array_keys(EvaluationCategory::PROGRAM->defaultCriteria());
    $companyKeys = array_keys(EvaluationCategory::COMPANY->defaultCriteria());

    expect($mentorKeys)->not->toBe($programKeys);
    expect($programKeys)->not->toBe($companyKeys);
});
