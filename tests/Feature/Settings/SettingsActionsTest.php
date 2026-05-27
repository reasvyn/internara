<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Settings\Actions\BatchSetSettingAction;
use App\Domain\Settings\Actions\SetSettingAction;
use App\Domain\Settings\Actions\TestMailSettingsAction;
use App\Domain\Settings\Actions\UploadBrandAssetAction;
use App\Domain\Settings\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

describe('SetSettingAction', function () {
    it('creates a new setting', function () {
        $setting = app(SetSettingAction::class)->execute('site_name', 'My App', 'general');

        expect($setting)->toBeInstanceOf(Setting::class)
            ->and($setting->key)->toBe('site_name')
            ->and($setting->value)->toBe('My App')
            ->and($setting->group)->toBe('general')
            ->and($setting->type)->toBe('string');
    });

    it('updates an existing setting', function () {
        Setting::create(['key' => 'site_name', 'value' => 'Old Name', 'group' => 'general', 'type' => 'string']);

        $setting = app(SetSettingAction::class)->execute('site_name', 'New Name', 'app');

        expect($setting->value)->toBe('New Name')
            ->and($setting->group)->toBe('app');
    });

    it('detects type automatically', function () {
        expect(app(SetSettingAction::class)->execute('flag', true)->type)->toBe('boolean');
        expect(app(SetSettingAction::class)->execute('count', 42)->type)->toBe('integer');
        expect(app(SetSettingAction::class)->execute('ratio', 3.14)->type)->toBe('float');
        expect(app(SetSettingAction::class)->execute('tags', ['a', 'b'])->type)->toBe('json');
    });

    it('validates setting key format', function () {
        app(SetSettingAction::class)->execute('Invalid Key!', 'value');
    })->throws(ValidationException::class);
});

describe('BatchSetSettingAction', function () {
    it('sets multiple settings at once', function () {
        $results = app(BatchSetSettingAction::class)->execute([
            'app_name' => 'My App',
            'app_debug' => false,
            'mail_driver' => ['value' => 'smtp', 'group' => 'mail'],
        ]);

        expect($results)->toHaveCount(3)
            ->and($results[0]->key)->toBe('app_name')
            ->and($results[0]->value)->toBe('My App')
            ->and($results[1]->key)->toBe('app_debug')
            ->and($results[1]->type)->toBe('boolean')
            ->and($results[2]->group)->toBe('mail');
    });
});

describe('TestMailSettingsAction', function () {
    it('returns false on invalid config', function () {
        $result = app(TestMailSettingsAction::class)->execute('test@example.com', [
            'host' => '',
            'port' => 0,
        ]);

        expect($result)->toBeFalse();
    });

    it('returns true when notification sent successfully', function () {
        Notification::fake();

        $result = app(TestMailSettingsAction::class)->execute('test@example.com', [
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'user',
            'password' => 'pass',
            'from_address' => 'from@example.com',
            'from_name' => 'Test',
        ]);

        expect($result)->toBeTrue();
    });
});

describe('UploadBrandAssetAction', function () {
    it('stores a brand asset and returns its media URL', function () {
        $file = UploadedFile::fake()->image('logo.png');
        $url = app(UploadBrandAssetAction::class)->execute($file, 'logo');

        expect($url)->toBeString()->not->toBeEmpty();
    });

    it('stores a favicon and returns its media URL', function () {
        $file = UploadedFile::fake()->image('favicon.png');
        $url = app(UploadBrandAssetAction::class)->execute($file, 'favicon');

        expect($url)->toBeString()->not->toBeEmpty();
    });
});
