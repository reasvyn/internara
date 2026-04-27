<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\SystemSetup;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    Gate::define('performStep', fn() => true);
    Gate::define('saveSettings', fn() => true);
});

describe('SystemSetup Component', function () {
    test('it renders correctly', function () {
        app(SettingService::class)->setValue('setup_step_internship', true);

        $this->get(route('setup.system', ['token' => 'test-token']));

        Livewire::test(SystemSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.system.headline'));
    });

    test('it saves settings and proceeds to next step', function () {
        app(SettingService::class)->setValue('setup_step_internship', true);

        $this->get(route('setup.system', ['token' => 'test-token']));

        Livewire::test(SystemSetup::class)
            ->set('mail_from_name', 'Internara New')
            ->set('mail_host', 'localhost')
            ->set('mail_port', '587')
            ->set('mail_from_address', 'test@example.com')
            ->call('save')
            ->assertRedirect(route('setup.complete'));

        expect(setting('mail_from_name'))->toBe('Internara New');
    });

    test('it can skip SMTP configuration', function () {
        app(SettingService::class)->setValue('setup_step_internship', true);

        $this->get(route('setup.system', ['token' => 'test-token']));

        Livewire::test(SystemSetup::class)->call('skip')->assertRedirect(route('setup.complete'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'internship' not completed
        app(SettingService::class)->setValue('setup_step_internship', false);

        $this->get(route('setup.system', ['token' => 'test-token']));

        Livewire::test(SystemSetup::class)->assertRedirect(route('setup.internship'));
    });
});
