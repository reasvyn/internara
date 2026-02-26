<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Http;

use Modules\Setting\Services\Contracts\SettingService;
use Illuminate\Support\Str;

beforeEach(function () {
    app(SettingService::class)->setValue('app_installed', false);
    $this->token = Str::random(32);
    app(SettingService::class)->setValue('setup_token', $this->token);
});

test('setup forms require honeypot token', function () {
    $url = \Illuminate\Support\Facades\URL::signedRoute('setup.welcome', ['token' => $this->token]);
    
    $this->get($url)
        ->assertOk()
        ->assertSee('name="my_name"'); // Default Spatie Honeypot field name
});

test('setup forms require turnstile validation', function () {
    $url = \Illuminate\Support\Facades\URL::signedRoute('setup.environment', ['token' => $this->token]);
    
    // Attempt next step without turnstile token
    $this->withSession(['setup_authorized' => true])
        ->post($url, [])
        ->assertSessionHasErrors(['cf-turnstile-response']);
});
