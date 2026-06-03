<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\AccountRecovery\Livewire;

use App\Domain\User\Aggregates\AccountRecovery\Actions\GenerateRecoverySlipAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RecoveryCode extends Component
{
    /** @var array<int, string> */
    public array $codes = [];

    public ?string $expiresAt = null;

    public function generate(GenerateRecoverySlipAction $action): void
    {
        $user = auth()->user();

        $result = $action->execute($user);

        $this->codes = $result['plaintext'];
        $this->expiresAt = $result['expires_at'];

        session()->put('recovery_codes', $this->codes);
        session()->put('recovery_codes_expires_at', $this->expiresAt);

        flash()->success(__('profile.recovery.code_generated'));
    }

    public function resetCode(): void
    {
        $this->reset('codes', 'expiresAt');
        session()->forget(['recovery_codes', 'recovery_codes_expires_at']);
    }

    public function downloadPdf()
    {
        $codes = session('recovery_codes', []);
        $expiresAt = session('recovery_codes_expires_at', now()->addHours(24)->format('d M Y H:i'));

        if (empty($codes)) {
            flash()->error(__('profile.recovery.no_codes'));

            return redirect()->back();
        }

        $username = auth()->user()->username;

        $pdf = Pdf::loadView('auth.account-recovery.pdf.recovery-codes', [
            'codes' => $codes,
            'username' => $username,
            'generatedAt' => now()->format('d M Y H:i'),
            'expiresAt' => $expiresAt,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'recovery-codes-'.$username.'.pdf',
        );
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        return view('user.account-recovery.recovery-code');
    }
}
