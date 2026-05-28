<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\HasValidationRules;

describe('HasValidationRules', function () {
    it('can be implemented by an anonymous class', function () {
        $class = new class implements HasValidationRules
        {
            public static function rules(?string $excludeId = null): array
            {
                return ['name' => ['required', 'string', 'max:255']];
            }

            public static function messages(): array
            {
                return ['name.required' => 'Name is required.'];
            }
        };

        expect($class)->toBeInstanceOf(HasValidationRules::class);
    });

    it('supports optional excludeId parameter', function () {
        $class = new class implements HasValidationRules
        {
            public static function rules(?string $excludeId = null): array
            {
                $rules = ['email' => ['required', 'email']];

                if ($excludeId !== null) {
                    $rules['email'][] = 'unique:users,email,'.$excludeId;
                }

                return $rules;
            }

            public static function messages(): array
            {
                return [];
            }
        };

        $withoutExclude = $class::rules();
        $withExclude = $class::rules('abc-123');

        expect($withoutExclude['email'])->not->toContain('unique')
            ->and($withExclude['email'])->toContain('unique:users,email,abc-123');
    });

    it('returns messages array from contract', function () {
        $class = new class implements HasValidationRules
        {
            public static function rules(?string $excludeId = null): array
            {
                return [];
            }

            public static function messages(): array
            {
                return ['email.required' => 'Email cannot be blank.'];
            }
        };

        expect($class::messages())->toHaveKey('email.required', 'Email cannot be blank.');
    });
});
