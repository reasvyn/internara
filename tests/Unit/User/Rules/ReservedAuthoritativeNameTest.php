<?php

declare(strict_types=1);

use App\Domain\User\Rules\ReservedAuthoritativeName;
use Illuminate\Support\Facades\Validator;

describe('ReservedAuthoritativeName', function () {
    it('passes for non-reserved name', function () {
        $validator = Validator::make(['name' => 'John Doe'], [
            'name' => [new ReservedAuthoritativeName],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('fails for reserved name "admin"', function () {
        $validator = Validator::make(['name' => 'admin'], [
            'name' => [new ReservedAuthoritativeName],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for reserved name "administrator"', function () {
        $validator = Validator::make(['name' => 'administrator'], [
            'name' => [new ReservedAuthoritativeName],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for reserved name "superadmin"', function () {
        $validator = Validator::make(['name' => 'superadmin'], [
            'name' => [new ReservedAuthoritativeName],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for reserved name "root"', function () {
        $validator = Validator::make(['name' => 'root'], [
            'name' => [new ReservedAuthoritativeName],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for case-insensitive reserved name', function () {
        $validator = Validator::make(['name' => 'Admin'], [
            'name' => [new ReservedAuthoritativeName],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('passes for non-string value', function () {
        $validator = Validator::make(['name' => 12345], [
            'name' => [new ReservedAuthoritativeName],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes for null value', function () {
        $validator = Validator::make(['name' => null], [
            'name' => [new ReservedAuthoritativeName],
        ]);

        expect($validator->passes())->toBeTrue();
    });
});
