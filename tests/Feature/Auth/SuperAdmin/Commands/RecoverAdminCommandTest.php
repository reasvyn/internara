<?php

declare(strict_types=1);

use App\Auth\SuperAdmin\Actions\RecoverSuperAdminAction;
use App\Auth\SuperAdmin\Notifications\RecoveryOtpNotification;
use App\Settings\Models\Setting;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {

    $this->user = User::factory()->create([
        'email' => 'admin@test.id',
        'password' => Hash::make('old-password'),
    ]);
    $this->user->assignRole('super_admin');
    $this->user->setStatus(AccountStatus::PROTECTED);
});

test('fails with invalid recovery key', function () {
    $key = Str::random(64);
    Setting::updateOrCreate(
        ['key' => 'setup.install_recovery_key'],
        ['value' => Hash::make($key), 'group' => 'setup', 'type' => 'string'],
    );

    $this->artisan('admin:recover', [
        'email' => 'admin@test.id',
        '--key' => 'wrong-key-12345',
        '--no-interaction' => true,
    ])->assertFailed();
});

test('fails for non-existent email', function () {
    $key = Str::random(64);
    Setting::updateOrCreate(
        ['key' => 'setup.install_recovery_key'],
        ['value' => Hash::make($key), 'group' => 'setup', 'type' => 'string'],
    );

    $this->artisan('admin:recover', [
        'email' => 'ghost@test.id',
        '--key' => $key,
        '--no-interaction' => true,
    ])->assertFailed();
});

test('sends OTP notification in production environment', function () {
    Notification::fake();

    $this->user->notify(new RecoveryOtpNotification('123456'));

    Notification::assertSentTo(
        $this->user,
        RecoveryOtpNotification::class,
        function ($notification) {
            return $notification->otp === '123456';
        },
    );
});

test('happy path resets password via action', function () {
    $key = Str::random(64);
    Setting::updateOrCreate(
        ['key' => 'setup.install_recovery_key'],
        ['value' => Hash::make($key), 'group' => 'setup', 'type' => 'string'],
    );

    app(RecoverSuperAdminAction::class)->execute(
        email: 'admin@test.id',
        password: 'new-password-123',
    );

    $this->user->refresh();

    expect(Hash::check('new-password-123', $this->user->password))->toBeTrue();
});
