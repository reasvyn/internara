<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Events\Setup\SetupFinalized;
use App\Models\Setup;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;

final class FinalizeSetupAction
{
    public function execute(): string
    {
        // Mark as installed
        Setup::markInstalled();

        // Generate recovery key for break-glass admin recovery
        $recoveryKey = Setup::generateRecoveryKey();

        // Invalidate token
        Setup::invalidateToken();

        // Dispatch domain event
        Event::dispatch(new SetupFinalized(
            schoolId: Setup::first()?->school_id,
            installedAt: now()->toDateTimeImmutable(),
        ));

        // Clear session
        Session::forget(['setup.authorized', 'setup.token', 'setup.token_input', 'setup.form_data']);

        return $recoveryKey;
    }
}
