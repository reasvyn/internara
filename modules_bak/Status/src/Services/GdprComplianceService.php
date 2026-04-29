<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;
use ZipArchive;

/**
 * GdprComplianceService
 *
 * Implements GDPR compliance requirements:
 * 1. Right to Access - Export all user data in portable format
 * 2. Right to be Forgotten - Anonymize or delete user data
 * 3. Accountability - Maintain deletion/anonymization audit trail
 * 4. Data Portability - Export in standard formats (JSON, CSV)
 * 5. Legitimate Interest - Log all processing purposes
 *
 * Supports both anonymization (safe) and deletion (destructive) modes.
 */
class GdprComplianceService
{
    private AccountAuditLogger $auditLogger;

    public function __construct(AccountAuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Export user's complete data as per GDPR Article 15
     *
     * Creates a portable, machine-readable export containing:
     * - User profile & metadata
     * - Full audit trail
     * - Account status history
     * - Activity logs
     * - Restrictions
     * - Related records
     *
     * @return string Path to exported ZIP file
     */
    public function exportUserData(User $user): string
    {
        return DB::transaction(function () use ($user) {
            // Create temporary directory for export
            $tempDir = storage_path('app/temp/' . $user->id . '_' . uniqid());
            mkdir($tempDir, 0755, true);

            try {
                // 1. Export user profile
                $this->exportUserProfile($user, $tempDir);

                // 2. Export audit trail
                $this->exportAuditTrail($user, $tempDir);

                // 3. Export status history
                $this->exportStatusHistory($user, $tempDir);

                // 4. Export restrictions
                $this->exportRestrictions($user, $tempDir);

                // 5. Export login history
                $this->exportLoginHistory($user, $tempDir);

                // 6. Create metadata
                $this->createExportMetadata($user, $tempDir);

                // 7. Zip all files
                $zipPath = $this->createZipArchive($user, $tempDir);

                // Log data export
                $this->auditLogger->log(
                    user: $user,
                    event: 'data_export_requested',
                    metadata: [
                        'export_format' => 'zip',
                        'file_path' => $zipPath,
                        'exported_at' => now()->toIso8601String(),
                        'exported_by' => auth()->id(),
                    ],
                );

                // Clean up temp directory
                $this->removeDirectory($tempDir);

                return $zipPath;
            } catch (\Exception $e) {
                // Clean up temp directory on error
                $this->removeDirectory($tempDir);
                Log::error('Data export failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Anonymize user data (GDPR Article 17 - Right to be Forgotten)
     *
     * Removes all personally identifiable information while maintaining
     * audit trail integrity. Data is irreversibly changed.
     *
     * @param string $reason Reason for anonymization
     */
    public function anonymizeUser(User $user, string $reason = 'gdpr_request'): bool
    {
        return DB::transaction(function () use ($user, $reason) {
            // Generate anonymization token
            $anonymizedId = 'ANON_' . hash('sha256', $user->id . config('app.key'));

            // Store anonymization details
            $anonymizationData = [
                'anonymized_at' => now()->toIso8601String(),
                'anonymized_by' => auth()->id(),
                'reason' => $reason,
                'anonymized_id' => $anonymizedId,
                'original_id' => $user->id,
            ];

            // Update user with anonymized data
            $user->update([
                'name' => $anonymizedId,
                'email' => $anonymizedId . '@anonymized.local',
                'phone' => null,
                'address' => null,
                'profile_picture' => null,
                'metadata' => $anonymizationData,
                'account_status' => 'archived', // Mark as archived
            ]);

            // Anonymize related data
            $this->anonymizeRelatedData($user, $anonymizedId);

            // Log anonymization
            $this->auditLogger->log(
                user: $user,
                event: 'gdpr_anonymization',
                metadata: $anonymizationData,
            );

            Log::alert("GDPR Anonymization: User {$user->id} anonymized. Reason: {$reason}");

            return true;
        });
    }

    /**
     * Delete user data permanently (destructive - use with caution)
     *
     * IMPORTANT: This is irreversible. Consider anonymizeUser instead.
     * Only use for accounts confirmed for permanent deletion.
     *
     * @param string $reason Reason for deletion
     */
    public function deleteUserPermanently(User $user, string $reason = 'gdpr_request'): bool
    {
        return DB::transaction(function () use ($user, $reason) {
            // Create deletion record for accountability
            DB::table('gdpr_deletion_logs')->insert([
                'user_id' => $user->id,
                'user_email' => $user->email,
                'reason' => $reason,
                'deleted_by' => auth()->id(),
                'deleted_at' => now(),
                'metadata' => json_encode([
                    'user_data_snapshot' => $user->toArray(),
                ]),
            ]);

            // Delete related records (cascade will handle this)
            $user->delete();

            Log::alert(
                "GDPR Deletion: User permanently deleted. Email: {$user->email}. Reason: {$reason}",
            );

            return true;
        });
    }

    /**
     * Export user profile
     */
    private function exportUserProfile(User $user, string $tempDir): void
    {
        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'account_status' => $user->account_status,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'metadata' => $user->metadata,
        ];

        file_put_contents(
            "$tempDir/profile.json",
            json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Export audit trail
     */
    private function exportAuditTrail(User $user, string $tempDir): void
    {
        $auditLog = DB::table('activity_log')
            ->where('subject_id', $user->id)
            ->orWhere('causer_id', $user->id)
            ->get();

        file_put_contents(
            "$tempDir/audit_trail.json",
            json_encode($auditLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Export account status history
     */
    private function exportStatusHistory(User $user, string $tempDir): void
    {
        $history = DB::table('account_status_history')->where('user_id', $user->id)->get();

        file_put_contents(
            "$tempDir/status_history.json",
            json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Export account restrictions
     */
    private function exportRestrictions(User $user, string $tempDir): void
    {
        $restrictions = DB::table('account_restrictions')->where('user_id', $user->id)->get();

        file_put_contents(
            "$tempDir/restrictions.json",
            json_encode($restrictions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Export login history
     */
    private function exportLoginHistory(User $user, string $tempDir): void
    {
        $loginHistory = DB::table('login_history')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(1000) // Last 1000 logins
            ->get();

        file_put_contents(
            "$tempDir/login_history.json",
            json_encode($loginHistory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Create metadata file
     */
    private function createExportMetadata(User $user, string $tempDir): void
    {
        $metadata = [
            'export_version' => '1.0',
            'user_id' => $user->id,
            'exported_at' => now()->toIso8601String(),
            'exported_by' => auth()->user()?->email,
            'gdpr_article' => '15 (Right of Access)',
            'files_included' => [
                'profile.json' => 'User profile and account information',
                'audit_trail.json' => 'Complete audit trail of user activities',
                'status_history.json' => 'Account status transition history',
                'restrictions.json' => 'Account restrictions and access controls',
                'login_history.json' => 'Login/authentication history',
            ],
        ];

        file_put_contents(
            "$tempDir/README.json",
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Create ZIP archive of all export files
     *
     * @return string Path to ZIP file
     */
    private function createZipArchive(User $user, string $tempDir): string
    {
        $zipFileName = "user_{$user->id}_data_export_" . now()->format('Y_m_d_H_i_s') . '.zip';
        $zipPath = storage_path("app/gdpr-exports/$zipFileName");

        // Ensure export directory exists
        mkdir(dirname($zipPath), 0755, true);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Add all files from temp directory
        $files = scandir($tempDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $zip->addFile("$tempDir/$file", $file);
            }
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Anonymize related data (cascade anonymization)
     */
    private function anonymizeRelatedData(User $user, string $anonymizedId): void
    {
        // Anonymize in audit logs
        DB::table('activity_log')
            ->where('subject_id', $user->id)
            ->update(['subject_id' => null, 'causer_id' => null]);

        // Anonymize in status history
        DB::table('account_status_history')
            ->where('user_id', $user->id)
            ->update(['user_id' => null]);
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $path = "$dir/$file";
                    if (is_dir($path)) {
                        $this->removeDirectory($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
