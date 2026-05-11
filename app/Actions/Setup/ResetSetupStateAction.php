<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Models\Setup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

final class ResetSetupStateAction
{
    /**
     * @return array{plaintext: string, expires_at: Carbon}
     */
    public function execute(): array
    {
        // Remove lock file
        $lockPath = base_path('.installed');

        if (File::exists($lockPath)) {
            File::delete($lockPath);
        }

        // Reset setup state
        $setup = Setup::first();

        if ($setup !== null) {
            $setup->is_installed = false;
            $setup->completed_steps = [];
            $setup->save();
        }

        // Clear session
        Session::forget(['setup.authorized', 'setup.token', 'setup.token_input', 'setup.form_data']);

        // Generate new token
        $plaintext = Str::random(64);
        $encrypted = Crypt::encryptString($plaintext);
        $expiresAt = now()->addHour();

        $setup = Setup::firstOrCreate([]);
        $setup->update([
            'setup_token' => $encrypted,
            'token_expires_at' => $expiresAt,
        ]);

        return [
            'plaintext' => $plaintext,
            'expires_at' => $expiresAt,
        ];
    }
}
