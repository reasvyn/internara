<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Setup\Livewire\SystemSetup;
use Modules\Setup\Services\Contracts\SetupService;

uses(RefreshDatabase::class);

test('it renders correctly', function () {
    $setupServiceMock = Mockery::mock(SetupService::class);
    $setupServiceMock->shouldReceive('requireSetupAccess')->andReturn(true);
    
    $this->app->instance(SetupService::class, $setupServiceMock);

    Livewire::test(SystemSetup::class)
        ->assertStatus(200)
        ->assertSee('Pastikan Jalur Komunikasi Terbuka');
});

test('it validates mandatory fields', function () {
    $setupServiceMock = Mockery::mock(SetupService::class);
    $setupServiceMock->shouldReceive('requireSetupAccess')->andReturn(true);
    $this->app->instance(SetupService::class, $setupServiceMock);

    Livewire::test(SystemSetup::class)
        ->set('mail_host', '')
        ->set('mail_port', '')
        ->call('save')
        ->assertHasErrors(['mail_host', 'mail_port', 'mail_from_address']);
});

test('it saves settings and proceeds to next step', function () {
    $setupServiceMock = Mockery::mock(SetupService::class);
    $setupServiceMock->shouldReceive('requireSetupAccess')->andReturn(true);
    
    $setupServiceMock->shouldReceive('saveSystemSettings')
        ->once()
        ->with(Mockery::on(function ($settings) {
            return $settings['mail_host'] === 'smtp.mailtrap.io' &&
                   $settings['mail_port'] === '2525' &&
                   $settings['mail_from_address'] === 'test@internara.id';
        }))
        ->andReturn(true);

    $setupServiceMock->shouldReceive('performSetupStep')
        ->once()
        ->with('system', '')
        ->andReturn(true);

    $this->app->instance(SetupService::class, $setupServiceMock);

    Livewire::test(SystemSetup::class)
        ->set('mail_host', 'smtp.mailtrap.io')
        ->set('mail_port', '2525')
        ->set('mail_username', 'user123')
        ->set('mail_password', 'secret')
        ->set('mail_encryption', 'tls')
        ->set('mail_from_address', 'test@internara.id')
        ->set('mail_from_name', 'Internara Test')
        ->call('save')
        ->assertRedirect(route('setup.department'));
});

test('it handles back navigation', function () {
    $setupServiceMock = Mockery::mock(SetupService::class);
    $setupServiceMock->shouldReceive('requireSetupAccess')->andReturn(true);
    $this->app->instance(SetupService::class, $setupServiceMock);

    Livewire::test(SystemSetup::class)
        ->call('backToPrev')
        ->assertRedirect(route('setup.school'));
});
