<?php

declare(strict_types=1);

use App\Domain\Settings\Rules\ValidSettingKey;

describe('ValidSettingKey', function () {
    it('passes for lowercase dotted key', function () {
        $rule = new ValidSettingKey;
        $failed = false;

        $rule->validate('key', 'app.name', function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('passes for underscored key', function () {
        $rule = new ValidSettingKey;
        $failed = false;

        $rule->validate('key', 'brand_logo', function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails for uppercase key', function () {
        $rule = new ValidSettingKey;
        $failed = false;

        $rule->validate('key', 'APP_NAME', function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for key starting with number', function () {
        $rule = new ValidSettingKey;
        $failed = false;

        $rule->validate('key', '1st_key', function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for key with spaces', function () {
        $rule = new ValidSettingKey;
        $failed = false;

        $rule->validate('key', 'my key', function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});
