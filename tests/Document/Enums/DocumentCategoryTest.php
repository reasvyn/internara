<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Document\Enums\DocumentCategory;

/*
|--------------------------------------------------------------------------
| 7H5D6 — Official Documents — DocumentCategory (spec-driven)
|--------------------------------------------------------------------------
*/

describe('7H5D6: DocumentCategory', function (): void {
    test('7H5D6-FR-DR1: implements LabelEnum and has 7 cases', function (): void {
        $ref = new ReflectionClass(DocumentCategory::class);

        expect($ref->implementsInterface(LabelEnum::class))->toBeTrue()
            ->and($ref->isEnum())->toBeTrue()
            ->and(DocumentCategory::cases())->toHaveCount(7);
    });

    test('7H5D6-FR-DR1: defines expected category values', function (): void {
        $values = array_map(fn ($c) => $c->value, DocumentCategory::cases());

        expect($values)->toBe([
            'application', 'permit', 'certificate', 'report',
            'letter', 'policy', 'handbook',
        ]);
    });

    test('7H5D6-FR-DR1: label() returns translated string via __()', function (): void {
        foreach (DocumentCategory::cases() as $category) {
            expect($category->label())->toBeString()->not->toBeEmpty();
        }
    });
});
