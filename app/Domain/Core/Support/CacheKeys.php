<?php

declare(strict_types=1);

namespace App\Domain\Core\Support;

/**
 * Central registry of all application cache keys.
 *
 * Every cache key used across the codebase MUST be defined here as a constant.
 * This prevents key collisions, makes cache dependencies discoverable,
 * and ensures stale keys can be flushed systematically.
 *
 * Naming convention: {domain}.{purpose}[.{qualifier}]
 *   - domain:  the owning bounded context (e.g., setup, admin, theme)
 *   - purpose: what the cached value represents (e.g., is_installed, stats)
 *   - qualifier: optional disambiguator (e.g., user ID placeholder)
 *
 * TTL legend:
 *   short  = <5 min          (realtime data, user-facing)
 *   medium = 5 min - 1 hour  (dashboard aggregates)
 *   long   = 1 hour - 24h    (infrequently changing metadata)
 *   static = until flush      (service-provider discovery)
 *   forever= never expires    (configuration, must be manually flushed)
 */
final readonly class CacheKeys
{
    // ─── Setup & Installation ──────────────────────────────────────────────
    // Invalidation: FinalizeSetupAction, GenerateSetupTokenAction
    public const string SETUP_INSTALLED = 'setup.is_installed';

    // ─── Admin Dashboard ───────────────────────────────────────────────────
    // Invalidation: User/Department/Internship CRUD actions
    public const string ADMIN_DASHBOARD_STATS = 'admin.dashboard.stats';

    // ─── Theme / Branding ──────────────────────────────────────────────────
    // Invalidation: Settings update (color change)
    public const string THEME_CSS_VARIABLES = 'theme.css_variables';

    // ─── Notifications ────────────────────────────────────────────────────
    // Key pattern: notification.unread:{userId}
    // Invalidation: MarkAsReadAction, MarkAllAsReadAction, MarkBatchAsReadAction,
    //               SendNotificationAction
    public const string NOTIFICATION_UNREAD = 'notification.unread:';

    // ─── Core Integrity ────────────────────────────────────────────────────
    // Invalidation: composer.json changes (manual flush)
    public const string CORE_INTEGRITY = 'core.integrity_verified';

    public const string CORE_APP_NAME = 'core.app_name';

    // ─── AppInfo (composer.json metadata) ──────────────────────────────────
    // Invalidation: composer.json changes (manual flush)
    public const string APPINFO_METADATA = 'appinfo.metadata';

    // ─── Domain Service Discovery ──────────────────────────────────────────
    // Invalidation: adding/removing Livewire components, policies, or views
    //              (should run php artisan cache:clear after structural changes)
    public const string DOMAIN_LIVEWIRE = 'domain.discovered_livewire';

    public const string DOMAIN_POLICIES = 'domain.discovered_policies';

    public const string DOMAIN_VIEWS = 'domain.discovered_views';

    // ─── Auth / Rate Limiting ──────────────────────────────────────────────
    // Key pattern: auth.login-failures:{userId}
    // Invalidation: successful login (LoginAction::clearFailedAttempts)
    public const string AUTH_LOGIN_FAILURES = 'auth.login-failures:';

    // ─── Health Check ──────────────────────────────────────────────────────
    public const string HEALTH_CHECK = 'health_check';

    // ─── Super Admin Recovery ──────────────────────────────────────────────
    // Key pattern: recover_admin_attempts_{md5(email)}
    // Invalidation: successful recovery (RecoverSuperAdminAction)
    public const string RECOVER_ADMIN_ATTEMPTS = 'recover_admin_attempts_';

    // ─── Settings ──────────────────────────────────────────────────────────
    // Key pattern: settings.{key}
    // Invalidation: Settings::set(), Settings::forget()
    // TTL: forever — flushed on setting changes
    public const string SETTINGS_ALL = 'settings.all';

    public const string SETTINGS_GROUP = 'settings.group.';

    public const string SETTINGS_KEYS = 'settings.keys';

    public const string SETTINGS_KEY = 'settings.';
}
