<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Events\Setup\SetupFinalized;
use App\Models\Setup;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

final class FinalizeSetupAction
{
    public function execute(array $stepsToComplete = ['school', 'department', 'account']): string
    {
        $setup = Setup::firstOrCreate([]);

        // Mark requested steps as completed
        $completedSteps = $setup->completed_steps ?? [];
        foreach ($stepsToComplete as $step) {
            if (! in_array($step, $completedSteps)) {
                $completedSteps[] = $step;
            }
        }
        $setup->update(['completed_steps' => $completedSteps]);

        // Mark as installed
        $setup->update(['is_installed' => true]);

        // Generate recovery key for break-glass admin recovery
        $plaintext = Str::random(64);
        $encrypted = Crypt::encryptString($plaintext);
        $setup->update(['recovery_key' => $encrypted]);

        // Invalidate token
        $setup->update([
            'setup_token' => null,
            'token_expires_at' => null,
        ]);

        // Dispatch domain event
        Event::dispatch(new SetupFinalized(
            schoolId: $setup->school_id,
            installedAt: now()->toDateTimeImmutable(),
        ));

        // Clear session
        Session::forget(['setup.authorized', 'setup.token', 'setup.token_input', 'setup.form_data']);

        return $plaintext;
    }
}
