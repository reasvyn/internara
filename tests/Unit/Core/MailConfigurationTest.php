<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Support\MailConfiguration;
use App\Domain\Core\Support\Settings;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('mail.mailers.smtp.host', 'default-host');
    Config::set('mail.mailers.smtp.port', 25);
    Config::set('mail.from.address', 'default@example.com');
});

test('apply configures mail from settings', function () {
    Settings::override([
        'mail_host' => 'smtp.example.com',
        'mail_port' => '587',
        'mail_from_address' => 'noreply@example.com',
    ]);

    MailConfiguration::apply();

    expect(Config::get('mail.mailers.smtp.host'))->toBe('smtp.example.com');
    expect(Config::get('mail.mailers.smtp.port'))->toBe('587');
    expect(Config::get('mail.from.address'))->toBe('noreply@example.com');
});

test('apply skips empty values', function () {
    Settings::override([
        'mail_host' => '',
        'mail_from_address' => null,
    ]);

    MailConfiguration::apply();

    expect(Config::get('mail.mailers.smtp.host'))->not->toBe('');
});

test('apply does nothing when no settings exist', function () {
    Settings::clearOverrides();

    MailConfiguration::apply();

    expect(Config::get('mail.mailers.smtp.host'))->toBe('default-host');
});

test('apply configures encryption', function () {
    Settings::override([
        'mail_encryption' => 'tls',
        'mail_from_name' => 'My Application',
    ]);

    MailConfiguration::apply();

    expect(Config::get('mail.mailers.smtp.encryption'))->toBe('tls');
    expect(Config::get('mail.from.name'))->toBe('My Application');
});

test('apply configures credentials', function () {
    Settings::override([
        'mail_username' => 'user@example.com',
        'mail_password' => 'secret-password',
    ]);

    MailConfiguration::apply();

    expect(Config::get('mail.mailers.smtp.username'))->toBe('user@example.com');
    expect(Config::get('mail.mailers.smtp.password'))->toBe('secret-password');
});

test('mailConfiguration class is final', function () {
    $reflection = new \ReflectionClass(MailConfiguration::class);

    expect($reflection->isFinal())->toBeTrue();
});
