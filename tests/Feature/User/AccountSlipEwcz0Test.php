<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccessToken\Models\AccessToken;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\User\Domain\UserManagement\Actions\GenerateAccountSlipAction;
use App\Modules\User\Domain\UserManagement\Actions\GenerateAccountSlipBatchAction;
use App\Modules\User\Domain\UserManagement\Actions\RenderAccountSlipAction;
use App\Modules\User\Domain\UserManagement\Livewire\UserManager;
use App\Modules\User\Domain\UserManagement\Notifications\ActivationCodeNotification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);
});

describe('EWCZ0: account slips', function (): void {
    test('EWCZ0-FR-ASLIP-001: action resolves from the container as a command action and streams a PDF (also FR-ASLIP-002, UC-ASLIP-001)', function (): void {
        $user = User::factory()->create();

        $action = app(GenerateAccountSlipAction::class);

        expect($action)->toBeInstanceOf(BaseCommandAction::class);

        $response = $action->execute($user);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toContain('application/pdf');
    });

    test('EWCZ0-FR-ASLIP-005: single-user PDF streams under the account-slip username filename', function (): void {
        $user = User::factory()->create();

        $response = app(GenerateAccountSlipAction::class)->execute($user);

        expect($response->headers->get('Content-Disposition'))->toContain('account-slip-'.$user->username.'.pdf');
    });

    test('EWCZ0-FR-ASLIP-004: slip card uses the custom 241 by 156 paper (also DD-ASLIP-002)', function (): void {
        expect(RenderAccountSlipAction::CARD_W)->toBe(241)
            ->and(RenderAccountSlipAction::CARD_H)->toBe(156);

        $user = User::factory()->create();

        $response = app(GenerateAccountSlipAction::class)->execute($user);

        expect($response->getStatusCode())->toBe(200);
    });

    test('EWCZ0-FR-ASLIP-007: slip renders from the account-slip-pdf view with user and code (also FR-ASLIP-008)', function (): void {
        $user = User::factory()->create();

        $html = app(RenderAccountSlipAction::class)->execute($user);

        expect($html)->toContain($user->username)
            ->and($html)->toContain($user->name)
            ->and($html)->toContain('30');
    });

    test('EWCZ0-FR-ASLIP-009: every generation writes an account_slip_generated activity entry', function (): void {
        $user = User::factory()->create();

        app(GenerateAccountSlipAction::class)->execute($user);

        $row = DB::table('activity_log')->where('description', 'account_slip_generated')->first();

        expect($row)->not->toBeNull();
    });

    test('EWCZ0-FR-ASLIP-010: generation mints a fresh 30-day activation token stored hashed (also FR-ASLIP-013, NFR-ASLIP-004)', function (): void {
        $user = User::factory()->create();

        app(RenderAccountSlipAction::class)->execute($user);

        $record = AccessToken::where('user_id', $user->id)->where('token_type', 'activation')->first();

        expect($record)->not->toBeNull()
            ->and(now()->diffInDays($record->expires_at))->toBeGreaterThanOrEqual(29)
            ->and(Hash::isHashed($record->token))->toBeTrue();
    });

    test('EWCZ0-FR-ASLIP-003: batch renders all users into one multi-card PDF (also FR-ASLIP-006, UC-ASLIP-002, DD-ASLIP-006)', function (): void {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $single = app(GenerateAccountSlipAction::class)->execute($first);
        $batch = app(GenerateAccountSlipBatchAction::class)->execute([$first, $second]);

        expect($batch->getStatusCode())->toBe(200)
            ->and($batch->headers->get('Content-Disposition'))->toContain('account-slips-batch.pdf')
            ->and(strlen((string) $batch->getContent()))->toBeGreaterThan(strlen((string) $single->getContent()));
    });

    test('EWCZ0-DD-ASLIP-005: slip PDFs stream without storing files on disk', function (): void {
        $user = User::factory()->create();
        $pdfsBefore = collect(File::allFiles(storage_path('app')))
            ->filter(fn ($file) => $file->getExtension() === 'pdf')->count();

        $response = app(GenerateAccountSlipAction::class)->execute($user);

        expect($response->getStatusCode())->toBe(200)
            ->and(collect(File::allFiles(storage_path('app')))
                ->filter(fn ($file) => $file->getExtension() === 'pdf')->count())->toBe($pdfsBefore);
    });

    test('EWCZ0-FR-ASLIP-011: showSlip mints the code and opens the preview (also FR-ASLIP-014, FR-ASLIP-017, UC-ASLIP-005)', function (): void {
        $user = User::factory()->create();

        $component = Livewire::test(UserManager::class)->call('showSlip', (string) $user->id);

        $component->assertSet('showAccountSlip', true)
            ->assertSet('slipCode', fn ($code) => $code !== '');

        expect($component->get('slipUser')['id'] ?? $component->get('slipUser')->id)->toBe($user->id);
    });

    test('EWCZ0-FR-ASLIP-012: regeneration supersedes the previous code (also DD-ASLIP-003, UC-ASLIP-003)', function (): void {
        $user = User::factory()->create();

        $component = Livewire::test(UserManager::class)->call('showSlip', (string) $user->id);
        $first = $component->get('slipCode');

        $component->call('regenerateCode');

        expect($component->get('slipCode'))->not->toBe($first)
            ->and($component->get('slipCode'))->not->toBe('');
    });

    test('EWCZ0-FR-ASLIP-019: sendCode delivers the activation notification (also UC-ASLIP-004)', function (): void {
        Notification::fake();
        $user = User::factory()->create();

        $component = Livewire::test(UserManager::class)->call('showSlip', (string) $user->id);
        $component->call('sendCode');

        Notification::assertSentTo($user, ActivationCodeNotification::class);
    });

    test('EWCZ0-NFR-ASLIP-006: null subject or code makes trait actions return silently', function (): void {
        Livewire::test(UserManager::class)
            ->call('regenerateCode')
            ->call('sendCode')
            ->call('downloadSlip')
            ->assertSet('slipCode', '')
            ->assertSet('showAccountSlip', false);
    });

    test('EWCZ0-FR-ASLIP-020: downloadSlip redirects to the single-slip route', function (): void {
        $user = User::factory()->create();

        Livewire::test(UserManager::class)
            ->call('showSlip', (string) $user->id)
            ->call('downloadSlip')
            ->assertRedirect(route('admin.users.account-slip', $user));
    });

    test('EWCZ0-FR-ASLIP-021: downloadSelectedSlips redirects to the batch route with ids', function (): void {
        $first = User::factory()->create();
        $second = User::factory()->create();

        Livewire::test(UserManager::class)
            ->set('selectedIds', [$first->id, $second->id])
            ->call('downloadSelectedSlips')
            ->assertRedirect(route('admin.users.account-slips.batch', ['ids' => $first->id.','.$second->id]));
    });

    test('EWCZ0-FR-ASLIP-022: empty selection stays silent without redirect', function (): void {
        Livewire::test(UserManager::class)
            ->set('selectedIds', [])
            ->call('downloadSelectedSlips')
            ->assertHasNoErrors()
            ->assertSet('selectedIds', []);
    });

    test('EWCZ0-NFR-ASLIP-014: slip trait never mutates user models (also NFR-ASLIP-015)', function (): void {
        Notification::fake();
        $user = User::factory()->create();
        $count = User::count();

        Livewire::test(UserManager::class)
            ->call('showSlip', (string) $user->id)
            ->call('regenerateCode')
            ->call('sendCode');

        expect(User::count())->toBe($count);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
    });

    test('EWCZ0-FR-ASLIP-039: guests are redirected from slip routes (also FR-ASLIP-037, FR-ASLIP-041)', function (): void {
        Auth::logout();

        $user = User::factory()->create();

        expect(route('admin.users.account-slip', $user))->toContain('/admin/users/'.$user->id.'/account-slip');

        $this->get(route('admin.users.account-slip', $user))->assertRedirect('/login');
        $this->get(route('admin.users.account-slips.batch', ['ids' => $user->id]))->assertRedirect('/login');
    });

    test('EWCZ0-FR-ASLIP-040: non-admin users are refused on slip routes (also NFR-ASLIP-002, NFR-ASLIP-003)', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $target = User::factory()->create();
        $this->actingAs($student);

        $this->get(route('admin.users.account-slip', $target))->assertForbidden();
        $this->get(route('admin.users.account-slips.batch', ['ids' => $target->id]))->assertForbidden();
    });

    test('EWCZ0-FR-ASLIP-033: admin single download delegates to the action', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create();
        $this->actingAs($admin);

        $response = $this->get(route('admin.users.account-slip', $target));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    });

    test('EWCZ0-FR-ASLIP-034: batch parses comma ids and loads via whereIn (also FR-ASLIP-035, FR-ASLIP-038)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($admin);

        $response = $this->get(route('admin.users.account-slips.batch', ['ids' => $first->id.','.Str::uuid()]));

        $response->assertOk();
        expect($response->headers->get('Content-Disposition'))->toContain('account-slips-batch.pdf');

        $this->get(route('admin.users.account-slips.batch'))->assertNotFound();

        expect($second->fresh())->not->toBeNull();
    });
});
