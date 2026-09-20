<?php

declare(strict_types=1);
use App\Modules\SysAdmin\Http\Controllers\AccountSlipController;

describe('EWCZ0: account slip static contracts', function (): void {
    test('EWCZ0-NFR-ASLIP-016: every slip PHP file declares strict types', function (): void {
        $files = [
            base_path('app/Modules/User/Domain/UserManagement/Actions/GenerateAccountSlipAction.php'),
            base_path('app/Modules/User/Domain/UserManagement/Actions/GenerateAccountSlipBatchAction.php'),
            base_path('app/Modules/User/Domain/UserManagement/Actions/RenderAccountSlipAction.php'),
            base_path('app/Modules/User/Domain/UserManagement/Livewire/Concerns/DownloadsAccountSlips.php'),
            base_path('app/Modules/SysAdmin/Http/Controllers/AccountSlipController.php'),
        ];

        foreach ($files as $file) {
            expect(file_get_contents($file))->toContain('declare(strict_types=1)');
        }
    });

    test('EWCZ0-FR-ASLIP-023: every trait flash message resolves through translation (also NFR-ASLIP-017)', function (): void {
        $trait = file_get_contents(base_path('app/Modules/User/Domain/UserManagement/Livewire/Concerns/DownloadsAccountSlips.php'));

        expect($trait)->toContain("__('user.manager.code_regenerated')")
            ->and($trait)->toContain("__('user.manager.code_sent')")
            ->and($trait)->toContain("__('common.actions.no_records_selected')");
    });

    test('EWCZ0-NFR-ASLIP-018: slip translation keys exist in both locales', function (): void {
        foreach (['user.manager.code_regenerated', 'user.manager.code_sent', 'sysadmin.account_slip.code_expiry'] as $key) {
            expect(__($key))->not->toBe($key);
        }

        $en = file_get_contents(base_path('lang/en/sysadmin.php'));
        $id = file_get_contents(base_path('lang/id/sysadmin.php'));

        expect($en)->toContain("'code_expiry'")
            ->and($id)->toContain("'code_expiry'");
    });

    test('EWCZ0-FR-ASLIP-024: the modal binds to slip state at size sm (also FR-ASLIP-030)', function (): void {
        $modal = file_get_contents(base_path('resources/views/user/user-management/components/account-slip-modal.blade.php'));

        expect($modal)->toContain('wire="showAccountSlip"')
            ->and($modal)->toContain('size="sm"')
            ->and($modal)->toContain("\$set('showAccountSlip', false)");
    });

    test('EWCZ0-FR-ASLIP-027: the modal wires download, regenerate, send, and expiry note (also FR-ASLIP-028, FR-ASLIP-029, FR-ASLIP-025, FR-ASLIP-026, FR-ASLIP-031)', function (): void {
        $modal = file_get_contents(base_path('resources/views/user/user-management/components/account-slip-modal.blade.php'));

        expect($modal)->toContain('wire:click="downloadSlip"')
            ->and($modal)->toContain('wire:click="regenerateCode"')
            ->and($modal)->toContain('wire:click="sendCode"')
            ->and($modal)->toContain('font-mono')
            ->and($modal)->toContain('code_expiry')
            ->and($modal)->toContain('separator')
            ->and($modal)->toContain('blur');
    });

    test('EWCZ0-FR-ASLIP-032: AccountSlipController is final with constructor injection only (also FR-ASLIP-036)', function (): void {
        $ref = new ReflectionClass(AccountSlipController::class);
        expect($ref->isFinal())->toBeTrue();

        $content = file_get_contents(base_path('app/Modules/SysAdmin/Http/Controllers/AccountSlipController.php'));
        expect($content)->not->toContain('app(')
            ->and($content)->not->toContain('resolve(');
    });

    test('EWCZ0-NFR-ASLIP-008: slip documents have headings and accessible code labels (also NFR-ASLIP-009, NFR-ASLIP-010, NFR-ASLIP-011, NFR-ASLIP-013, NFR-ASLIP-019)', function (): void {
        $view = file_get_contents(base_path('resources/views/user/user-management/account-slip-pdf.blade.php'));
        expect($view)->toContain('<h1')
            ->and($view)->toContain('<h2');

        $modal = file_get_contents(base_path('resources/views/user/user-management/components/account-slip-modal.blade.php'));
        expect($modal)->toContain('loading="regenerateCode"')
            ->and($modal)->toContain('uppercase');
    });
});
