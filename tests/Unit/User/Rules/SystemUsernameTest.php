<?php

declare(strict_types=1);

use App\Domain\User\Rules\SystemUsername;
use Illuminate\Support\Facades\Validator;

describe('SystemUsername', function () {
    it('passes for valid lowercase username', function () {
        $validator = Validator::make(['username' => 'john'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes for alphanumeric username', function () {
        $validator = Validator::make(['username' => 'john123'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes for minimum length username', function () {
        $validator = Validator::make(['username' => 'abc'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes for 30 character username', function () {
        $validator = Validator::make(['username' => 'abcdefghijabcdefghijabcdefghij'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('fails for username starting with number', function () {
        $validator = Validator::make(['username' => '1john'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for uppercase username', function () {
        $validator = Validator::make(['username' => 'John'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for username with special characters', function () {
        $validator = Validator::make(['username' => 'john_doe'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for too short username', function () {
        $validator = Validator::make(['username' => 'ab'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for too long username', function () {
        $validator = Validator::make(['username' => 'abcdefghijabcdefghijabcdefghijk'], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for non-string value', function () {
        $validator = Validator::make(['username' => 123], [
            'username' => [new SystemUsername],
        ]);

        expect($validator->fails())->toBeTrue();
    });
});
