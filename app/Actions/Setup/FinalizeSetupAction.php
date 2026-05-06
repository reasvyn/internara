<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Domain\Setup\Events\SetupFinalized;
use App\Models\Setup;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;

final class FinalizeSetupAction
{
    public function execute(): void
    {
        // Mark as installed
        Setup::markInstalled();

        // Invalidate token
        Setup::invalidateToken();

        // Dispatch domain event
        Event::dispatch(new SetupFinalized(
            schoolId: Setup::first()?->school_id,
            installedAt: now()->toDateTimeImmutable(),
        ));

        // Clear session
        Session::forget(['setup.authorized', 'setup.token', 'setup.token_input', 'setup.form_data']);
    }
}
