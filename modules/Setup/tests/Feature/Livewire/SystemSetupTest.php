<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;
use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\SystemSetup;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Gate::define("performStep", fn () => true);
    Gate::define("finalize", fn () => true);
    session(["setup_authorized" => true]);
    session(["setup_authorized" => true]);
});

beforeEach(function () {
    app(SettingService::class)->setValue('setup_token', 'test-token');
    session(['setup_authorized' => true]);
});

describe('SystemSetup Component', function () {
    test('it renders correctly', function () {
        app(SettingService::class)->setValue('setup_step_internship', true);

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(SystemSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.system.headline'));
    });

    test('it saves settings and proceeds to next step', function () {
        app(SettingService::class)->setValue('setup_step_internship', true);

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(SystemSetup::class)
            ->set('mail_host', 'smtp.example.com')
            ->set('mail_port', '587')
            ->set('mail_from_address', 'noreply@internara.id')
            ->set('mail_from_name', 'Internara')
            ->call('save')
            ->assertRedirect(route('setup.complete'));

        expect(setting('mail_host'))->toBe('smtp.example.com');
    });

    test('it can skip SMTP configuration', function () {
        app(SettingService::class)->setValue('setup_step_internship', true);

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(SystemSetup::class)
            ->call('skip')
            ->assertRedirect(route('setup.complete'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'internship' not completed
        Livewire::test(SystemSetup::class)->assertRedirect(route('setup.internship'));
    });
});
