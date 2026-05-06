<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Domain\Setup\Exceptions\SetupException;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Models\Setup;
use Illuminate\Support\Carbon;

/**
 * Orchestrates the full technical installation:
 * audit → provision → generate token.
 *
 * @return array{plaintext: string, expires_at: Carbon}
 */
final readonly class InstallSystemAction
{
    public function __construct(
        private EnvironmentAuditor $auditor,
        private ProvisionSystemAction $provision,
    ) {}

    /**
     * @throws SetupException If audit fails
     *
     * @return array{plaintext: string, expires_at: Carbon}
     */
    public function execute(bool $force = false): array
    {
        // Step 1: Audit environment
        $report = $this->auditor->audit();

        if (! $report->passed()) {
            throw SetupException::auditFailed();
        }

        // Step 2: Provision system (outside transaction to avoid SQLite VACUUM issues)
        $this->provision->execute($force);

        // Step 3: Generate and store setup token
        $tokenData = Setup::generateToken();

        return [
            'plaintext' => $tokenData['plaintext'],
            'expires_at' => $tokenData['expires_at'],
        ];
    }
}
