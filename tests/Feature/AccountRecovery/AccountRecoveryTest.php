<?php

declare(strict_types=1);

use App\Livewire\Auth\AccountRecovery;
use App\Livewire\Auth\RecoverySlipManager;
use App\Models\AccountRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => Hash::make('password123'),
    ]);
    $this->user->assignRole('student');
});

describe('RecoverySlipManager (admin generate)', function () {

    it('allows super_admin to access', function () {
        $user = User::factory()->create()->assignRole('super_admin');
        $this->actingAs($user);

        Livewire::test(RecoverySlipManager::class)->assertSuccessful();
    });

    it('allows admin to access', function () {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(RecoverySlipManager::class)->assertSuccessful();
    });

    it('blocks student from accessing', function () {
        Livewire::test(RecoverySlipManager::class)->assertForbidden();
    });

    it('searches users', function () {
        $admin = User::factory()->create(['name' => 'Admin User'])->assignRole('super_admin');
        $this->actingAs($admin);

        Livewire::test(RecoverySlipManager::class)
            ->set('search', 'Test User')
            ->assertSee('Test User');
    });

    it('generates a recovery code for selected user', function () {
        $admin = User::factory()->create(['name' => 'Admin'])->assignRole('super_admin');
        $this->actingAs($admin);

        Livewire::test(RecoverySlipManager::class)
            ->call('selectUser', $this->user->id)
            ->call('generate')
            ->assertSet('generatedCode', fn ($code) => strlen($code) === 12);
    });

    it('stores recovery code hash in database', function () {
        $admin = User::factory()->create()->assignRole('super_admin');
        $this->actingAs($admin);

        Livewire::test(RecoverySlipManager::class)
            ->call('selectUser', $this->user->id)
            ->call('generate');

        expect(AccountRecoveryCode::where('user_id', $this->user->id)->count())->toBe(1);
    });

});

describe('AccountRecovery (user redeem)', function () {

    beforeEach(function () {
        $this->plaintext = AccountRecoveryCode::generateCode();
        AccountRecoveryCode::create([
            'user_id' => $this->user->id,
            'code_hash' => Hash::make($this->plaintext),
            'generated_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);
    });

    it('renders the recovery page', function () {
        Livewire::test(AccountRecovery::class)
            ->assertSuccessful();
    });

    it('validates required fields', function () {
        Livewire::test(AccountRecovery::class)
            ->call('redeem')
            ->assertHasErrors([
                'username' => 'required',
                'recoveryCode' => 'required',
                'password' => 'required',
            ]);
    });

    it('validates code is 12 characters', function () {
        Livewire::test(AccountRecovery::class)
            ->set('username', 'testuser')
            ->set('recoveryCode', 'short')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('redeem')
            ->assertHasErrors(['recoveryCode' => 'size']);
    });

    it('validates password minimum length', function () {
        Livewire::test(AccountRecovery::class)
            ->set('username', 'testuser')
            ->set('recoveryCode', 'ABCD1234XYZ9')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('redeem')
            ->assertHasErrors(['password' => 'min']);
    });

    it('validates password confirmation', function () {
        Livewire::test(AccountRecovery::class)
            ->set('username', 'testuser')
            ->set('recoveryCode', 'ABCD1234XYZ9')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'different')
            ->call('redeem')
            ->assertHasErrors(['password' => 'confirmed']);
    });

    it('redeems with valid code', function () {
        Livewire::test(AccountRecovery::class)
            ->set('username', 'testuser')
            ->set('recoveryCode', $this->plaintext)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('redeem')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        expect(Hash::check('newpassword123', $this->user->fresh()->password))->toBeTrue();
        expect($this->user->fresh()->password !== Hash::make('password123'))->toBeTrue();
    });

    it('marks code as used after redeem', function () {
        Livewire::test(AccountRecovery::class)
            ->set('username', 'testuser')
            ->set('recoveryCode', $this->plaintext)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('redeem');

        $code = AccountRecoveryCode::where('user_id', $this->user->id)->first();
        expect($code->used_at)->not->toBeNull();
    });

    it('fails with invalid code', function () {
        Livewire::test(AccountRecovery::class)
            ->set('username', 'testuser')
            ->set('recoveryCode', 'ABCD1234XYZ9')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('redeem')
            ->assertHasErrors('recoveryCode');
    });

    it('fails with wrong username', function () {
        Livewire::test(AccountRecovery::class)
            ->set('username', 'wronguser')
            ->set('recoveryCode', $this->plaintext)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('redeem')
            ->assertHasErrors('recoveryCode');
    });

    it('fails with expired code', function () {
        AccountRecoveryCode::where('user_id', $this->user->id)->update([
            'expires_at' => now()->subHour(),
        ]);

        Livewire::test(AccountRecovery::class)
            ->set('username', 'testuser')
            ->set('recoveryCode', $this->plaintext)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('redeem')
            ->assertHasErrors('recoveryCode');
    });

    it('fails with already used code', function () {
        AccountRecoveryCode::where('user_id', $this->user->id)->update([
            'used_at' => now(),
        ]);

        Livewire::test(AccountRecovery::class)
            ->set('username', 'testuser')
            ->set('recoveryCode', $this->plaintext)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('redeem')
            ->assertHasErrors('recoveryCode');
    });

});
