declare(strict_types=1);

namespace App\Domain\Core\Support;

use App\Services\Setup\SetupService;

/**
 * Centralized Application Metadata & Branding Service.
 *
 * S2 - Sustain: Single source of truth for all app metadata.
 * S3 - Scalable: Cached settings with fallback to Composer metadata.
 */
final class AppMetadata
{
    /**
     * Check if the application is installed.
     */
    private static function isInstalled(): bool
    {
        return app(SetupService::class)->isInstalled();
    }

    /**
     * Get the core application name from Composer (SSoT).
     */
    public static function appName(): string
    {
        return AppInfo::get('name', 'Internara');
    }

    /**
     * Get the dynamic brand name.
     * Returns Composer name if not installed, otherwise returns institution name from settings.
     */
    public static function brandName(): string
    {
        if (! self::isInstalled()) {
            return self::appName();
        }

        return (string) Settings::get('brand_name', self::appName());
    }

    /**
     * Get the site title for browser tabs.
     */
    public static function siteTitle(): string
    {
        if (! self::isInstalled()) {
            return self::appName().' - Setup';
        }

        return (string) Settings::get('site_title', self::brandName());
    }

    /**
     * Get the core application logo URL.
     */
    public static function appLogo(): string
    {
        return asset('/brand/logo.png');
    }

    /**
     * Get the dynamic brand logo URL.
     * Returns default logo if not installed, otherwise returns logo from settings.
     */
    public static function brandLogo(): string
    {
        $defaultLogo = self::appLogo();

        if (! self::isInstalled()) {
            return $defaultLogo;
        }

        $logo = Settings::get('brand_logo');

        return $logo ? (string) $logo : $defaultLogo;
    }

    /**
     * Get the site favicon URL.
     */
    public static function favicon(): string
    {
        $defaultFavicon = asset('/brand/favicon.ico');

        if (! self::isInstalled()) {
            return $defaultFavicon;
        }

        $favicon = Settings::get('site_favicon');

        if ($favicon) {
            return (string) $favicon;
        }

        // Fallback to logo if favicon is not set
        $logo = Settings::get('brand_logo');

        return $logo ? (string) $logo : $defaultFavicon;
    }

    /**
     * Get branding colors.
     *
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return [
            'primary' => Settings::get('primary_color', '#0ea5e9'),
            'secondary' => Settings::get('secondary_color', '#64748b'),
            'accent' => Settings::get('accent_color', '#f59e0b'),
        ];
    }

    /**
     * Get the application version from Composer.
     */
    public static function version(): string
    {
        return AppInfo::version();
    }

    /**
     * Get the author name from Composer.
     */
    public static function authorName(): string
    {
        $author = AppInfo::get('author', []);

        return (string) ($author['name'] ?? '');
    }

    /**
     * Get the author email from Composer.
     */
    public static function authorEmail(): string
    {
        $author = AppInfo::get('author', []);

        return (string) ($author['email'] ?? '');
    }

    /**
     * Get the application description from Composer.
     */
    public static function description(): string
    {
        return AppInfo::get('description', '');
    }

    /**
     * Get the license from Composer.
     */
    public static function license(): string
    {
        return AppInfo::get('license', 'MIT');
    }

    /**
     * Get a value by key with fallback chain:
     * 1. If installed: check settings (branding)
     * 2. Fallback to AppInfo (Composer metadata)
     * 3. Use default parameter.
     *
     * @param array<string, mixed> $default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $mapping = [
            'name' => fn () => self::brandName(),
            'app_name' => fn () => self::appName(),
            'logo' => fn () => self::brandLogo(),
            'app_logo' => fn () => self::appLogo(),
            'favicon' => fn () => self::favicon(),
            'site_title' => fn () => self::siteTitle(),
            'colors' => fn () => self::colors(),
            'version' => fn () => self::version(),
            'author_name' => fn () => self::authorName(),
            'author_email' => fn () => self::authorEmail(),
            'description' => fn () => self::description(),
            'license' => fn () => self::license(),
        ];

        // Dynamic branding values (from settings if installed)
        if (array_key_exists($key, $mapping)) {
            return $mapping[$key]();
        }

        // Fallback to AppInfo (Composer metadata)
        return AppInfo::get($key, $default);
    }
}
