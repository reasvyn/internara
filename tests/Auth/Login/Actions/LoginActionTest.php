<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Actions\LoginAction;
use App\Modules\Auth\Domain\Login\Data\LoginData;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: LoginAction', function (): void {
    beforeEach(function (): void {
        Role::findOrCreate('student', 'web');
    });

    test('YB7RG-FR-LI1: user can login with email without error', function (): void {
        $user = User::factory()->create(['email' => 'login-email@test.test', 'password' => 'Secret123!', 'status' => 'verified']);
        $user->assignRole('student');
        $result = app(LoginAction::class)->execute(new LoginData(identifier: 'login-email@test.test', password: 'Secret123!'));
        expect($result->id)->toBe($user->id);
        expect(Auth::check())->toBeTrue();
    });

    test('YB7RG-FR-LI1: user can login with username without error', function (): void {
        $user = User::factory()->create(['username' => 'testuser123', 'password' => 'Secret123!', 'status' => 'verified']);
        $user->assignRole('student');
        $result = app(LoginAction::class)->execute(new LoginData(identifier: 'testuser123', password: 'Secret123!'));
        expect($result->id)->toBe($user->id);
    });

    test('YB7RG-FR-LI2: detects email vs username via FILTER_VALIDATE_EMAIL', function (): void {
        $code = file_get_contents(app_path('Modules/Auth/Domain/Login/Actions/LoginAction.php'));
        expect($code)->toContain('FILTER_VALIDATE_EMAIL');
    });

    test('YB7RG-FR-LI8: regenerates session on login', function (): void {
        $user = User::factory()->create(['email' => 'regen@test.test', 'password' => 'Secret123!', 'status' => 'verified']);
        $user->assignRole('student');
        $oldId = session()->getId();
        app(LoginAction::class)->execute(new LoginData(identifier: 'regen@test.test', password: 'Secret123!'));
        expect(session()->getId())->not->toBe($oldId);
    });
});
