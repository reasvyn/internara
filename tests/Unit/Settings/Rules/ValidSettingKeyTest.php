<?php

declare(strict_types=1);

use App\Domain\Settings\Rules\ValidSettingKey;
use Illuminate\Support\Facades\Validator;

describe('ValidSettingKey', function () {
    it('passes for valid lowercase key', function () {
        $validator = Validator::make(['key' => 'valid_key'], [
            'key' => ['required', new ValidSettingKey],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes for alphanumeric key', function () {
        $validator = Validator::make(['key' => 'setting123'], [
            'key' => ['required', new ValidSettingKey],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes for dotted key', function () {
        $validator = Validator::make(['key' => 'group.setting'], [
            'key' => ['required', new ValidSettingKey],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes for complex valid key', function () {
        $validator = Validator::make(['key' => 'mail.smtp_port_587'], [
            'key' => ['required', new ValidSettingKey],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('fails for key starting with number', function () {
        $validator = Validator::make(['key' => '1nvalid'], [
            'key' => ['required', new ValidSettingKey],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for uppercase key', function () {
        $validator = Validator::make(['key' => 'INVALID'], [
            'key' => ['required', new ValidSettingKey],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for key with special characters', function () {
        $validator = Validator::make(['key' => 'invalid-key!'], [
            'key' => ['required', new ValidSettingKey],
        ]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for empty string', function () {
        $validator = Validator::make(['key' => ''], [
            'key' => ['required', new ValidSettingKey],
        ]);

        expect($validator->fails())->toBeTrue();
    });
});
