# Configuration Documentation: Internara

## 1. Overview

Internara uses a **three-tier configuration system** to separate concerns and provide flexibility:

1. **`config()` - Laravel Configuration** → Static, code-level defaults
2. **`setting()` - Database Settings** → Dynamic, user-configurable values
3. **`AppInfo` - Application Metadata** → Immutable app identity (SSoT)

---

## 2. The Three-Tier System

### Visual Hierarchy
```
┌─────────────────────────────────────────────────────────────┐
│                    REQUEST                      │
│              (Runtime Overrides)              │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              AppInfo (app_info.json)              │
│           Immutable App Identity                │
│    Name, Version, Author, License               │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│         Database Settings (settings table)        │
│      Dynamic, Cached, User-Configurable         │
│   brand_name, site_title, mail_settings, etc.    │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│            Laravel Config (config/)              │
│        Static Defaults, Infrastructure            │
│   app.php, database.php, mail.php, etc.       │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              Default Parameter                  │
│          Last Resort Fallback                 │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. `config()` - Laravel Configuration

### Purpose
**Static configuration** that defines how the application works at the code level. These are **infrastructure settings** that rarely change.

### Location
- **Files**: `config/*.php`
- **Access**: `config('file.key')` or `config()->get('file.key')`
- **Environment**: `.env` file overrides via `env('KEY', 'default')`

### When to Use `config()`
✅ **Infrastructure settings**:
- Database connections (`config/database.php`)
- Cache drivers (`config/cache.php`)
- Session drivers (`config/session.php`)
- Mail settings (`config/mail.php`)
- File system disks (`config/filesystems.php`)

✅ **Code-level defaults**:
- App name, version (`config/app.php`)
- Debug mode (`config/app.php`)
- Pulse settings (`config/pulse.php`)

✅ **Package configurations**:
- Spatie Activity Log (`config/activitylog.php`)
- Spatie Permissions (`config/permission.php`)
- Spatie Media Library (`config/media-library.php`)

### Example Config Files
| Config File | Purpose | Example Key |
|------------|---------|-------------|
| `app.php` | Application core | `app.name`, `app.debug`, `app.url` |
| `database.php` | Database connections | `database.default`, `database.connections.mysql` |
| `cache.php` | Cache drivers | `cache.default`, `cache.stores.redis` |
| `session.php` | Session management | `session.driver`, `session.lifetime` |
| `mail.php` | Email configuration | `mail.default`, `mail.mailers.smtp` |
| `filesystems.php` | File storage | `filesystems.default`, `filesystems.disks.s3` |
| `livewire.php` | Livewire settings | `livewire.middleware`, `livewire.endpoint` |
| `activitylog.php` | Audit logging | `activitylog.default_log_name` |
| `permission.php` | RBAC settings | `permission.table_names` |

### Example Usage
```php
// Get config value
$name = config('app.name');
$debug = config('app.debug');
$dbConnection = config('database.default');

// Check if config exists
if (config()->has('custom.key')) {
    $value = config('custom.key');
}

// Set runtime config (temporary, not persisted)
config(['app.name' => 'New Name']);  // Only for current request
```

### Environment Overrides (.env)
```env
APP_NAME="Internara"
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
CACHE_STORE=redis
SESSION_DRIVER=database
```

**Priority**: `.env` → `config/*.php` → default parameter

---

## 4. `setting()` - Database Settings

### Purpose
**Dynamic configuration** stored in database (`settings` table) that can be changed by users/admins at runtime. These are **business settings** that affect app behavior.

### Location
- **Table**: `settings` (UUID primary key)
- **Model**: `App\Models\Setting`
- **Service**: `App\Support\Settings`
- **Helper**: `setting()` function (`app/Support/helpers.php`)

### When to Use `setting()`
✅ **User-configurable settings**:
- Site name, branding (`brand_name`, `brand_logo`)
- Academic settings (`active_academic_year`, `attendance_check_in_start`)
- Notification settings (`mail_from_address`, `mail_from_name`)
- Feature flags (`feature.internship_enabled`, `feature.assessment_enabled`)

✅ **Runtime-modifiable values**:
- Settings that change without code deployment
- Values that need admin UI for modification
- Multi-tenant settings (if applicable)

### Database Structure
```php
// Migration: 2026_04_29_100804_create_settings_table.php
Schema::create('settings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('key')->unique();
    $table->text('value')->nullable();  // Cast via SettingValueCast
    $table->string('type')->default('string');
    $table->text('description')->nullable();
    $table->string('group')->nullable()->index();
    $table->timestamps();
});
```

### Setting Model
```php
class Setting extends Model {
    use HasFactory, HasUuid;
    
    protected $fillable = ['key', 'value', 'type', 'description', 'group'];
    
    protected $casts = [
        'value' => SettingValueCast::class,  // Auto-cast to typed value
    ];
}
```

### Settings Service (Tiered Resolution)
```php
class Settings {
    const CACHE_PREFIX = 'settings.';
    
    // Resolution chain:
    // 1. Runtime overrides (for testing)
    // 2. AppInfo (app_info.json) - for app metadata
    // 3. Database (cached forever)
    // 4. Laravel config (fallback)
    // 5. Default parameter
    
    public static function get(string|array $key, mixed $default = null): mixed
    {
        // Check runtime overrides first
        if (array_key_exists($key, self::$overrides)) {
            return self::$overrides[$key];
        }
        
        // Check AppInfo SSoT
        if ($infoValue = self::resolveAppInfoValue($key)) {
            return $infoValue;
        }
        
        // Check database (cached)
        return Cache::rememberForever(
            self::CACHE_PREFIX . $key,
            fn () => Setting::where('key', $key)->first()?->value ?? $default
        );
    }
}
```

### Helper Function `setting()`
```php
// app/Support/helpers.php
function setting(
    string|array|null $key = null,
    mixed $default = null,
    bool $skipCache = false,
): mixed {
    // Return Settings service instance
    if ($key === null) {
        return app(Settings::class);
    }
    
    // Set multiple settings (batch)
    if (is_array($key) && !empty($key) && is_string(array_key_first($key))) {
        return app(SetSettingAction::class)->executeBatch($key);
    }
    
    // Get single setting
    if (is_string($key)) {
        return Settings::get($key, $default);
    }
    
    return $default;
}
```

### Usage Examples
```php
// Get setting
$siteTitle = setting('site_title', 'Default Title');
$brandName = setting('brand_name');

// Get multiple settings
$settings = setting(['site_title', 'brand_name', 'mail_from_address']);

// Set setting (via Action)
app(SetSettingAction::class)->execute('site_title', 'New Title');

// Set multiple (batch)
setting([
    'site_title' => 'New Title',
    'brand_name' => 'New Brand',
]);

// Check if setting exists
if (setting()->has('feature.internship_enabled')) {
    // Feature enabled
}
```

### Caching Strategy
- **Cache Key**: `settings.{key}` (prefixed)
- **Cache Duration**: Forever (until manually cleared)
- **Invalidation**: `Settings::forget('key')` or `Cache::forget('settings.key')`
- **Group Cache**: `settings.group.{group}` for batch retrieval

---

## 5. `AppInfo` - Application Metadata

### Purpose
**Immutable application identity** stored in `app_info.json`. This is the **Single Source of Truth (SSoT)** for app metadata.

### Location
- **File**: `app_info.json` (root directory)
- **Class**: `App\Support\AppInfo`
- **Access**: `AppInfo::get('key')` or `AppInfo::all()`

### When to Use `AppInfo`
✅ **App identity** (never changes after deployment):
- App name (`name`)
- Version (`version`)
- Author info (`author.name`, `author.email`, `author.github`)
- License (`license`)

✅ **Static metadata**:
- Values that are compiled into the app
- Used for signatures, about pages, credits

### File Structure (`app_info.json`)
```json
{
    "name": "Internara",
    "version": "0.1.0",
    "support": "Experimental",
    "author": {
        "name": "Reas Vyn",
        "email": "reasvyn@gmail.com",
        "github": "https://github.com/reasvyn"
    },
    "license": "MIT"
}
```

### AppInfo Class
```php
class AppInfo {
    private static ?array $info = null;
    
    public static function all(): array {
        if (self::$info === null) {
            $path = base_path('app_info.json');
            self::$info = json_decode(File::get($path), true) ?? [];
        }
        return self::$info;
    }
    
    public static function get(string $key, mixed $default = null): mixed {
        return data_get(self::all(), $key, $default);
    }
    
    public static function version(): string {
        return self::get('version', '0.0.0');
    }
    
    public static function author(): array {
        return self::get('author', []);
    }
}
```

### Usage in Settings Resolution
The `Settings` service checks `AppInfo` as tier 2:
```php
// In Settings::resolveSingle()
if ($infoValue = self::resolveAppInfoValue($key)) {
    return $infoValue;
}

// Maps setting keys to AppInfo fields
protected static array $appInfoMap = [
    'app_name' => 'name',
    'app_version' => 'version',
    'app_author' => 'author.name',
    'app_support' => 'support',
    'app_license' => 'license',
];
```

---

## 6. Context Boundaries: When to Use Which?

### Decision Matrix
| Use Case | Use `config()` | Use `setting()` | Use `AppInfo` |
|----------|---------------|---------------|--------------|
| **Database connection** | ✅ | ❌ | ❌ |
| **Cache driver** | ✅ | ❌ | ❌ |
| **Session lifetime** | ✅ | ❌ | ❌ |
| **Site title** | ❌ | ✅ | ❌ |
| **Brand logo** | ❌ | ✅ | ❌ |
| **Academic year** | ❌ | ✅ | ❌ |
| **App name (identity)** | ❌ | ❌ (prefer) | ✅ (SSoT) |
| **App version** | ❌ | ❌ (prefer) | ✅ (SSoT) |
| **Author info** | ❌ | ❌ | ✅ (SSoT) |
| **Mail host** | ✅ (infrastructure) | ✅ (from address) | ❌ |
| **Feature flags** | ❌ | ✅ | ❌ |
| **Debug mode** | ✅ | ❌ | ❌ |

### Rule of Thumb
1. **If it's infrastructure** → `config()`
2. **If it's user-configurable** → `setting()`
3. **If it's app identity** → `AppInfo` (SSoT)

---

## 7. Real-World Usage Examples

### Example 1: System Setting Page (`app/Livewire/Admin/SystemSetting.php`)
```php
class SystemSetting extends Component {
    public function mount(): void {
        // Use setting() for dynamic values
        $this->site_title = setting('site_title', 'Internara');
        $this->brand_name = setting('brand_name', 'Internara');
        $this->active_academic_year = setting('active_academic_year');
        
        // Use config() for infrastructure
        $this->mail_host = config('mail.mailers.smtp.host');
        $this->mail_port = config('mail.mailers.smtp.port');
    }
    
    public function save(): void {
        // Save dynamic settings via Action
        app(SetSettingAction::class)->executeBatch([
            'site_title' => $this->site_title,
            'brand_name' => $this->brand_name,
            'active_academic_year' => $this->active_academic_year,
        ]);
        
        // Infrastructure configs stay in .env / config()
        // They require .env modification + deployment
    }
}
```

### Example 2: App Signature (`app/Livewire/Layout/AppSignature.php`)
```php
class AppSignature extends Component {
    public function render() {
        return view('livewire.layout.app-signature', [
            // Use AppInfo for immutable app identity
            'app_name' => AppInfo::get('name', config('app.name')),
            'app_version' => AppInfo::version(),
            'author' => AppInfo::author(),
        ]);
    }
}
```

### Example 3: Attendance Check-In (`app/Actions/Attendance/ClockInAction.php`)
```php
class ClockInAction {
    public function execute(Student $student): AttendanceLog {
        // Get business rule from setting()
        $checkInStart = setting('attendance_check_in_start', '07:00');
        $lateThreshold = setting('attendance_late_threshold', '08:00');
        
        // Get infrastructure config
        $timezone = config('app.timezone', 'Asia/Jakarta');
        
        // Logic...
    }
}
```

---

## 8. Caching Strategy

### Settings Caching (`setting()`)
```php
// Cache forever (until setting changes)
Cache::rememberForever('settings.site_title', fn () => {
    return Setting::where('key', 'site_title')->first()?->value;
});

// Clear cache when setting updated
Settings::forget('site_title');  // Clears 'settings.site_title'
Settings::clearGroup('mail');  // Clears all 'settings.group.mail*'
Settings::clearAll();  // Clears 'settings.all'
```

### Config Caching
```bash
# Cache config for production (faster loading)
php artisan config:cache

# Clear config cache
php artisan config:clear
```

**Note**: `config()` is **not cached** during development (always reads from files). In production, use `php artisan config:cache` for performance.

---

## 9. Testing Strategies

### Testing `config()`
```php
test('config returns correct value', function () {
    // Set runtime config
    config(['app.name' => 'Test App']);
    
    expect(config('app.name'))->toBe('Test App');
});

test('config has default', function () {
    expect(config('non.existent', 'default'))->toBe('default');
});
```

### Testing `setting()`
```php
test('setting returns value from database', function () {
    // Create setting in database
    Setting::create([
        'key' => 'test_key',
        'value' => 'test_value',
        'type' => 'string',
    ]);
    
    expect(setting('test_key'))->toBe('test_value');
});

test('setting falls back to config', function () {
    // No setting in DB, but config exists
    expect(setting('app.name'))->toBe(config('app.name'));
});

test('setting uses runtime override', function () {
    // Set runtime override (for testing)
    Settings::override(['test_key' => 'override_value']);
    
    expect(setting('test_key'))->toBe('override_value');
    
    Settings::clearOverrides();
});
```

### Testing `AppInfo`
```php
test('AppInfo returns correct metadata', function () {
    expect(AppInfo::get('name'))->toBe('Internara');
    expect(AppInfo::version())->toBe('0.1.0');
    expect(AppInfo::author())->toBeArray();
});
```

---

## 10. Migration from `config()` to `setting()`

### When to Migrate
✅ **Good candidates to move to `setting()`**:
- Values that users need to change without code deployment
- Business rules (thresholds, time limits, feature flags)
- Branding (site name, logo, colors, favicon)

❌ **Keep in `config()`**:
- Infrastructure (database, cache, session, mail)
- Security (encryption keys, debug mode)
- Package configurations

### Migration Example
```php
// Old: config('custom.site_title', 'Default')
// New: setting('site_title', 'Default')

// In SetSettingAction, create if not exists
$setting = Setting::firstOrCreate(
    ['key' => 'site_title'],
    ['value' => 'Internara', 'type' => 'string', 'group' => 'branding']
);
```

---

## 11. Security Considerations (S1)

### S1 - Secure: Sensitive Data
```php
// ❌ DON'T store secrets in settings table
setting('api_secret', 'super-secret-key');  // ❌ Exposed in DB!

// ✅ DO store secrets in .env / config()
// In .env:
API_SECRET=super-secret-key

// In config/services.php:
'api_secret' => env('API_SECRET'),

// Access:
config('services.api_secret');  // ✅ Not in database
```

### S1 - Secure: User Permissions
```php
// Only admins can modify settings
// In SetSettingAction:
public function execute(string $key, mixed $value): void
{
    $this->authorize('update', Setting::class);  // Gate check
    
    // Update setting...
}
```

---

## 12. Performance Optimization (S3)

### Settings Optimization
```php
// ✅ DO: Batch retrieval
$settings = setting(['site_title', 'brand_name', 'mail_from_address']);
// Results in 1 database query + cached

// ❌ DON'T: Individual calls in loop
foreach ($keys as $key) {
    $value = setting($key);  // Multiple queries!
}
```

### Cache Invalidation
```php
// When setting changes, clear cache
// In SetSettingAction:
public function execute(string $key, mixed $value): void
{
    $setting = Setting::where('key', $key)->firstOrFail();
    $setting->update(['value' => $value]);
    
    // Clear cache
    Settings::forget($key);
    if ($setting->group) {
        Settings::clearGroup($setting->group);
    }
}
```

---

## 13. Troubleshooting

### Setting Not Found
1. Check database: `SELECT * FROM settings WHERE key = 'your_key';`
2. Check cache: `php artisan cache:clear`
3. Verify fallback: `config('your.key')` exists?
4. Check AppInfo: Is it in `app_info.json`?

### Config Not Taking Effect
1. Clear config cache: `php artisan config:clear`
2. Check `.env` file: Is the variable set?
3. Verify `env('KEY')` in config file
4. Restart server after `.env` changes

### Performance Issues
1. **Too many `setting()` calls**: Batch them
2. **Cache not working**: Check `CACHE_STORE` in `.env`
3. **Settings table slow**: Add index on `key` column (already exists)

---

**Last Updated**: April 30, 2026