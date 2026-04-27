<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;

/**
 * AccountCloneDetectionService
 *
 * Detects suspicious account activity suggesting account compromise:
 * 1. Simultaneous logins from 2+ different locations (impossible travel)
 * 2. Login from new IP after extended idle period
 * 3. Login from unexpected geographic location
 * 4. Rapid location changes (impossible travel speed)
 * 5. Device fingerprint changes
 *
 * Triggers automatic security actions:
 * - Notify user of suspicious activity
 * - Force re-authentication if clone detected
 * - Log security event for compliance
 * - Optional: Auto-suspend account
 */
class AccountCloneDetectionService
{
    private AccountAuditLogger $auditLogger;

    private const IMPOSSIBLE_TRAVEL_THRESHOLD_MINUTES = 30;

    private const IMPOSSIBLE_TRAVEL_MIN_DISTANCE_KM = 1000; // 1000 km in 30 min = suspicious

    public function __construct(AccountAuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Check for account cloning/compromise on login
     *
     * @return array{is_suspicious: bool, reason: string, actions: string[]}
     */
    public function checkLoginSuspicion(
        User $user,
        string $currentIp,
        string $currentUserAgent,
    ): array {
        $suspicions = [];
        $actions = [];

        // 1. Check for simultaneous logins from different IPs
        if ($this->hasSimultaneousLoginFromDifferentIp($user, $currentIp)) {
            $suspicions[] = 'Simultaneous login from different IP detected';
            $actions[] = 'force_reauthentication';
            $actions[] = 'notify_user';
        }

        // 2. Check for impossible travel
        if ($this->isImpossibleTravel($user, $currentIp)) {
            $suspicions[] = 'Impossible travel pattern detected (too far, too fast)';
            $actions[] = 'force_reauthentication';
            $actions[] = 'notify_user';
            $actions[] = 'log_security_incident';
        }

        // 3. Check for login from completely new location after idle
        if ($this->isNewLocationAfterIdle($user, $currentIp)) {
            $suspicions[] = 'Login from new location after extended idle period';
            $actions[] = 'notify_user';
            $actions[] = 'log_security_incident';
        }

        // 4. Check for device fingerprint changes
        if ($this->deviceFingerprintChanged($user, $currentUserAgent)) {
            $suspicions[] = 'Device fingerprint changed significantly';
            $actions[] = 'notify_user';
        }

        $isSuspicious = count($suspicions) > 0;

        if ($isSuspicious) {
            $this->recordSuspiciousActivity(
                $user,
                $currentIp,
                $currentUserAgent,
                $suspicions,
                $actions,
            );
        }

        return [
            'is_suspicious' => $isSuspicious,
            'reason' => implode(', ', $suspicions),
            'actions' => $actions,
        ];
    }

    /**
     * Check if user has simultaneous logins from different IPs
     */
    private function hasSimultaneousLoginFromDifferentIp(User $user, string $currentIp): bool
    {
        // Check for login from different IP in last 5 minutes
        $recentLogins = DB::table('login_history')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->where('ip_address', '!=', $currentIp)
            ->where('successful', true)
            ->count();

        return $recentLogins > 0;
    }

    /**
     * Check for impossible travel (covered distance in too short time)
     *
     * @param string $currentIp Current login IP
     */
    private function isImpossibleTravel(User $user, string $currentIp): bool
    {
        // Get last login from different IP
        $lastLogin = DB::table('login_history')
            ->where('user_id', $user->id)
            ->where('successful', true)
            ->where('ip_address', '!=', $currentIp)
            ->where('created_at', '>=', now()->subHours(24)) // Within last 24h
            ->orderBy('created_at', 'desc')
            ->first(['created_at', 'ip_address', 'latitude', 'longitude']);

        if (!$lastLogin) {
            return false;
        }

        // Get current IP geolocation
        $currentLocation = $this->getIpGeolocation($currentIp);
        if (!$currentLocation) {
            return false; // Cannot determine location, assume safe
        }

        // Calculate distance between last login and current login
        if ($lastLogin->latitude && $lastLogin->longitude) {
            $distance = $this->calculateDistance(
                $lastLogin->latitude,
                $lastLogin->longitude,
                $currentLocation['latitude'],
                $currentLocation['longitude'],
            );

            // Calculate time difference in minutes
            $timeDiff = now()->diffInMinutes($lastLogin->created_at);

            // If distance > 1000km in < 30 minutes, it's impossible travel
            if (
                $distance > self::IMPOSSIBLE_TRAVEL_MIN_DISTANCE_KM &&
                $timeDiff < self::IMPOSSIBLE_TRAVEL_THRESHOLD_MINUTES
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if login is from new location after idle period
     */
    private function isNewLocationAfterIdle(User $user, string $currentIp): bool
    {
        // Get last login
        $lastLogin = DB::table('login_history')
            ->where('user_id', $user->id)
            ->where('successful', true)
            ->orderBy('created_at', 'desc')
            ->first(['created_at', 'ip_address']);

        if (!$lastLogin) {
            return false; // First login
        }

        // If last login was from same IP, not suspicious
        if ($lastLogin->ip_address === $currentIp) {
            return false;
        }

        // Check if user has been idle for 7+ days
        $idleDays = now()->diffInDays($lastLogin->created_at);
        if ($idleDays >= 7) {
            return true; // New location after 7+ days idle
        }

        return false;
    }

    /**
     * Check if device fingerprint changed significantly
     */
    private function deviceFingerprintChanged(User $user, string $currentUserAgent): bool
    {
        // Get user agent from last login
        $lastUserAgent = DB::table('login_history')
            ->where('user_id', $user->id)
            ->where('successful', true)
            ->orderBy('created_at', 'desc')
            ->value('user_agent');

        if (!$lastUserAgent) {
            return false; // First login
        }

        // Extract key components of user agent
        $currentBrowser = $this->extractBrowser($currentUserAgent);
        $lastBrowser = $this->extractBrowser($lastUserAgent);

        $currentOs = $this->extractOs($currentUserAgent);
        $lastOs = $this->extractOs($lastUserAgent);

        // If browser OR OS changed, it's potentially suspicious
        return $currentBrowser !== $lastBrowser || $currentOs !== $lastOs;
    }

    /**
     * Record suspicious activity for audit trail
     */
    private function recordSuspiciousActivity(
        User $user,
        string $ip,
        string $userAgent,
        array $suspicions,
        array $actions,
    ): void {
        // Log to security audit channel
        Log::channel('audit')->alert("🚨 Suspicious Account Activity: {$user->email}", [
            'user_id' => $user->id,
            'suspicions' => $suspicions,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'actions' => $actions,
        ]);

        // Record in database
        DB::table('suspicious_login_attempts')->insert([
            'user_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'suspicions' => json_encode($suspicions),
            'actions_taken' => json_encode($actions),
            'detected_at' => now(),
        ]);

        // Audit log
        $this->auditLogger->log(
            user: $user,
            event: 'suspicious_activity_detected',
            metadata: [
                'suspicions' => $suspicions,
                'ip_address' => $ip,
                'actions' => $actions,
            ],
        );

        // TODO: Send notification to user
        // TODO: Optional: Auto-suspend if high-confidence clone detected
    }

    /**
     * Get geolocation from IP address
     *
     * Integration point: Use MaxMind GeoIP2, IP2Location, or similar service
     *
     * @return array|null {latitude, longitude, country, city} or null
     */
    private function getIpGeolocation(string $ip): ?array
    {
        // Check cache first
        $cached = cache()->get("ip_location_{$ip}");
        if ($cached) {
            return $cached;
        }

        // TODO: Call GeoIP service
        // For now, return null (skip geolocation checks in MVP)
        // In production: use MaxMind GeoIP2 or similar

        return null;
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     *
     * @return float Distance in kilometers
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371; // Earth radius in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }

    /**
     * Extract browser name from user agent
     */
    private function extractBrowser(string $userAgent): ?string
    {
        if (preg_match('/Chrome/', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/Safari/', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/Firefox/', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/Edge/', $userAgent)) {
            return 'Edge';
        }
        if (preg_match('/Opera/', $userAgent)) {
            return 'Opera';
        }

        return 'Unknown';
    }

    /**
     * Extract OS from user agent
     */
    private function extractOs(string $userAgent): ?string
    {
        if (preg_match('/Windows/', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/Macintosh/', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        }
        if (preg_match('/iPhone|iPad/', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/Android/', $userAgent)) {
            return 'Android';
        }

        return 'Unknown';
    }

    /**
     * Get recent login history for user
     */
    public function getRecentLogins(User $user, int $limit = 10): Collection
    {
        return collect(
            DB::table('login_history')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['created_at', 'ip_address', 'user_agent', 'successful']),
        );
    }

    /**
     * Get suspicious activity history for user
     */
    public function getSuspiciousActivity(User $user): Collection
    {
        return collect(
            DB::table('suspicious_login_attempts')
                ->where('user_id', $user->id)
                ->orderBy('detected_at', 'desc')
                ->limit(20)
                ->get(),
        );
    }
}
