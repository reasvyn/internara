<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

function composerRequirement(string $package): string
{
    $composer = json_decode((string) File::get(base_path('composer.json')), true);

    return $composer['require'][$package];
}

it('FR-TS1: requires PHP >= 8.4', function () {
    expect(composerRequirement('php'))->toBe('^8.4');
});

it('FR-TS2: requires Laravel >= 13.0', function () {
    expect(composerRequirement('laravel/framework'))->toBe('^13.0');
});

it('FR-TS3: requires Livewire >= 4.0', function () {
    expect(composerRequirement('livewire/livewire'))->toBe('^4.0');
});

it('FR-TS4: requires Tailwind CSS >= 4.3', function () {
    $package = json_decode((string) File::get(base_path('package.json')), true);

    expect($package['devDependencies']['tailwindcss'])->toBe('^4.3.3');
});

it('FR-TS5: requires DaisyUI >= 5.6', function () {
    $package = json_decode((string) File::get(base_path('package.json')), true);

    expect($package['devDependencies']['daisyui'])->toBe('^5.7.0');
});

it('FR-DB1: defaults to SQLite for development', function () {
    $config = (string) File::get(config_path('database.php'));

    expect($config)->toContain("'default' => env('DB_CONNECTION', 'sqlite')");
});

it('FR-DB2: supports MySQL, MariaDB and PostgreSQL connections', function () {
    $connections = array_keys(config('database.connections'));

    expect($connections)->toContain('mysql')
        ->toContain('mariadb')
        ->toContain('pgsql');
});

it('FR-DB3: enforces utf8mb4 charset', function () {
    expect(config('database.connections.mysql.charset'))->toBe('utf8mb4')
        ->and(config('database.connections.mariadb.charset'))->toBe('utf8mb4')
        ->and(config('database.connections.pgsql.charset'))->toBe('utf8');
});

it('FR-CACHE1: defaults to the file cache driver', function () {
    $config = (string) File::get(config_path('cache.php'));

    expect($config)->toContain("'default' => env('CACHE_STORE', 'file')");
});

it('FR-CACHE2: supports file, database, redis, memcached, dynamodb and array stores', function () {
    $stores = array_keys(config('cache.stores'));

    expect($stores)->toContain('file')
        ->toContain('database')
        ->toContain('redis')
        ->toContain('memcached')
        ->toContain('dynamodb')
        ->toContain('array');
});

it('FR-CACHE3: registers all cache keys in config/cache-keys.php', function () {
    expect(File::exists(config_path('cache-keys.php')))->toBeTrue();

    $keys = config('cache-keys');

    expect($keys)->toBeArray()->not->toBeEmpty();
});

it('FR-CACHE4: every cache key follows the {module}.{purpose}[.{qualifier}] pattern', function () {
    $keys = config('cache-keys');

    foreach ($keys as $key) {
        expect((string) $key)->toMatch('/^[a-z_]+\.[a-z0-9_.:]*$/');
    }
});

it('FR-CACHE10: uses the internara-cache- redis prefix', function () {
    $config = (string) File::get(config_path('cache.php'));

    expect($config)->toContain("'prefix' => env('CACHE_PREFIX', Str::slug")
        ->and(config('cache.prefix'))->toBe('internara-cache-');
});

it('FR-SESS1: defaults the session driver to database (auto-migrated)', function () {
    $config = (string) File::get(config_path('session.php'));

    expect($config)->toContain("'driver' => env('SESSION_DRIVER', 'database')");

    $usersMigration = (string) File::get(database_path('migrations/2026_01_02_000001_create_users_table.php'));

    expect($usersMigration)->toContain("Schema::create('sessions'");
});

it('FR-SESS3: session lifetime defaults to 120 minutes', function () {
    $config = (string) File::get(config_path('session.php'));

    expect($config)->toContain("'lifetime' => (int) env('SESSION_LIFETIME', 120)");
});

it('FR-SESS4: session encryption is enabled by default', function () {
    $config = (string) File::get(config_path('session.php'));

    expect($config)->toContain("'encrypt' => env('SESSION_ENCRYPT', true)");
});

it('FR-SESS5: cookies are HTTP-only with SameSite=lax and secure in production', function () {
    $config = (string) File::get(config_path('session.php'));

    expect($config)->toContain("'http_only' => env('SESSION_HTTP_ONLY', true)")
        ->toContain("'same_site' => env('SESSION_SAME_SITE', 'lax')")
        ->toContain("'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV', 'production') === 'production')");
});

it('FR-SESS7: garbage collection lottery is [2, 100]', function () {
    expect(config('session.lottery'))->toBe([2, 100]);
});

it('FR-Q1: defaults the queue connection to sync', function () {
    $config = (string) File::get(config_path('queue.php'));

    expect($config)->toContain("'default' => env('QUEUE_CONNECTION', 'sync')");
});

it('FR-Q2: supports sync, database, redis and beanstalkd connections', function () {
    $connections = array_keys(config('queue.connections'));

    expect($connections)->toContain('sync')
        ->toContain('database')
        ->toContain('redis')
        ->toContain('beanstalkd');
});

it('FR-Q3: queue tables are auto-created by migration', function () {
    $migration = (string) File::get(database_path('migrations/2026_01_01_000003_create_jobs_table.php'));

    expect($migration)->toContain("Schema::create('jobs'")
        ->toContain("Schema::create('failed_jobs'")
        ->toContain("Schema::create('job_batches'");
});

it('FR-Q4: failed_jobs table records the exception trace', function () {
    $migration = (string) File::get(database_path('migrations/2026_01_01_000003_create_jobs_table.php'));

    expect($migration)->toContain('exception');
});

it('FR-M1: defaults to the log mailer for development', function () {
    $config = (string) File::get(config_path('mail.php'));

    expect($config)->toContain("'default' => env('MAIL_MAILER', 'log')");
});

it('FR-M2: exposes SMTP configuration via env vars', function () {
    $config = (string) File::get(config_path('mail.php'));

    expect($config)->toContain("'host' => env('MAIL_HOST'")
        ->toContain("'port' => env('MAIL_PORT'")
        ->toContain("'username' => env('MAIL_USERNAME')")
        ->toContain("'password' => env('MAIL_PASSWORD')");
});

it('FR-M4: mail from address falls back from MAIL_FROM_ADDRESS env', function () {
    $config = (string) File::get(config_path('mail.php'));

    expect($config)->toContain("'address' => env('MAIL_FROM_ADDRESS'");
});
