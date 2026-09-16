<?php

declare(strict_types=1);

use App\Modules\Document\Domain\Handbook\Events\HandbookCreated;
use App\Modules\Document\Domain\Handbook\Listeners\ClearHandbookCache;
use App\Modules\Enrollment\Domain\Registration\Events\StudentRegistered;
use App\Modules\Enrollment\Domain\Registration\Listeners\ClearDashboardOnRegistration;
use App\Modules\Partner\Domain\Company\Events\CompanyCreated;
use App\Modules\Partner\Domain\Company\Listeners\ClearDashboardOnCompanyChange;
use App\Modules\User\Domain\Notify\Events\NotificationRead;
use App\Modules\User\Domain\Notify\Listeners\ClearUnreadNotificationCache;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

if (! function_exists('zt6vsFreshConfig')) {
    /**
     * Re-evaluate a config file with a controlled environment so the
     * declared defaults (not the phpunit.xml test overrides) resolve.
     *
     * @param array<int, string> $withoutEnv env keys to hide
     * @param array<string, string> $withEnv env keys to force
     */
    function zt6vsFreshConfig(string $file, array $withoutEnv = [], array $withEnv = []): mixed
    {
        $keys = array_unique(array_merge($withoutEnv, array_keys($withEnv)));
        $saved = [];

        foreach ($keys as $key) {
            $saved[$key] = [
                'getenv' => getenv($key),
                'hasEnv' => array_key_exists($key, $_ENV),
                'env' => $_ENV[$key] ?? null,
                'hasServer' => array_key_exists($key, $_SERVER),
                'server' => $_SERVER[$key] ?? null,
            ];

            if (! array_key_exists($key, $withEnv)) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            }
        }

        foreach ($withEnv as $key => $value) {
            $string = (string) $value;
            putenv("{$key}={$string}");
            $_ENV[$key] = $string;
            $_SERVER[$key] = $string;
        }

        try {
            return require config_path($file);
        } finally {
            foreach ($saved as $key => $state) {
                if ($state['getenv'] === false) {
                    putenv($key);
                } else {
                    putenv("{$key}={$state['getenv']}");
                }

                if ($state['hasEnv']) {
                    $_ENV[$key] = $state['env'];
                } else {
                    unset($_ENV[$key]);
                }

                if ($state['hasServer']) {
                    $_SERVER[$key] = $state['server'];
                } else {
                    unset($_SERVER[$key]);
                }
            }
        }
    }
}

describe('ZT6VS: core infrastructure service defaults', function (): void {
    test('ZT6VS-FR-CORE-001: default database connection resolves to sqlite', function (): void {
        $config = zt6vsFreshConfig('database.php', ['DB_CONNECTION']);

        expect($config['default'])->toBe('sqlite')
            ->and($config['connections']['sqlite']['driver'])->toBe('sqlite');
    });

    test('ZT6VS-FR-CORE-002: production database connections resolve via env with documented defaults', function (): void {
        $config = zt6vsFreshConfig('database.php', ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD']);

        expect($config['connections']['mysql']['driver'])->toBe('mysql')
            ->and($config['connections']['mysql']['host'])->toBe('127.0.0.1')
            ->and($config['connections']['mysql']['port'])->toBe('3306')
            ->and($config['connections']['mariadb']['driver'])->toBe('mariadb')
            ->and($config['connections']['mariadb']['host'])->toBe('127.0.0.1')
            ->and($config['connections']['pgsql']['driver'])->toBe('pgsql')
            ->and($config['connections']['pgsql']['host'])->toBe('127.0.0.1')
            ->and($config['connections']['pgsql']['port'])->toBe('5432');

        $wired = zt6vsFreshConfig('database.php', [], ['DB_HOST' => 'db.internal', 'DB_PORT' => '3307', 'DB_DATABASE' => 'smk_prod']);

        expect($wired['connections']['mysql']['host'])->toBe('db.internal')
            ->and($wired['connections']['mysql']['port'])->toBe('3307')
            ->and($wired['connections']['mysql']['database'])->toBe('smk_prod');
    });

    test('ZT6VS-FR-CORE-003: charset defaults keep multilingual content intact per driver', function (): void {
        $config = zt6vsFreshConfig('database.php', ['DB_CHARSET']);

        expect($config['connections']['mysql']['charset'])->toBe('utf8mb4')
            ->and($config['connections']['mariadb']['charset'])->toBe('utf8mb4')
            ->and($config['connections']['pgsql']['charset'])->toBe('utf8');
    });

    test('ZT6VS-FR-CORE-005: sqlite runs with WAL journal mode and busy timeout', function (): void {
        $config = zt6vsFreshConfig('database.php');

        expect($config['connections']['sqlite']['journal_mode'])->toBe('wal')
            ->and($config['connections']['sqlite']['busy_timeout'])->toBe(5000);
    });

    test('ZT6VS-FR-CORE-008: default cache store resolves to file', function (): void {
        $config = zt6vsFreshConfig('cache.php', ['CACHE_STORE']);

        expect($config['default'])->toBe('file')
            ->and($config['stores']['file']['driver'])->toBe('file');
    });

    test('ZT6VS-FR-CORE-009: supported cache drivers resolve via env, array stays test-only', function (): void {
        foreach (['file', 'database', 'redis', 'memcached'] as $driver) {
            $config = zt6vsFreshConfig('cache.php', [], ['CACHE_STORE' => $driver]);

            expect($config['default'])->toBe($driver);
        }

        $stores = zt6vsFreshConfig('cache.php');

        expect($stores['stores']['array']['driver'])->toBe('array')
            ->and($stores['stores']['database']['driver'])->toBe('database')
            ->and($stores['stores']['redis']['driver'])->toBe('redis')
            ->and($stores['stores']['memcached']['driver'])->toBe('memcached');

        $testing = zt6vsFreshConfig('cache.php', [], ['CACHE_STORE' => 'array']);

        expect($testing['default'])->toBe('array');
    });

    test('ZT6VS-FR-CORE-016: supported session drivers resolve via env', function (): void {
        foreach (['database', 'redis', 'file', 'array'] as $driver) {
            $config = zt6vsFreshConfig('session.php', [], ['SESSION_DRIVER' => $driver]);

            expect($config['driver'])->toBe($driver);
        }

        $table = zt6vsFreshConfig('session.php', ['SESSION_TABLE']);

        expect($table['table'])->toBe('sessions');
    });

    test('ZT6VS-FR-CORE-023: supported queue connections stay wired with correct drivers', function (): void {
        foreach (['sync', 'database', 'redis'] as $connection) {
            $config = zt6vsFreshConfig('queue.php', [], ['QUEUE_CONNECTION' => $connection]);

            expect($config['default'])->toBe($connection);
        }

        $connections = zt6vsFreshConfig('queue.php')['connections'];

        expect($connections['sync']['driver'])->toBe('sync')
            ->and($connections['database']['driver'])->toBe('database')
            ->and($connections['database']['table'])->toBe('jobs')
            ->and($connections['redis']['driver'])->toBe('redis');
    });

    test('ZT6VS-FR-CORE-010: cache key registry holds every key with namespaced values', function (): void {
        $keys = require config_path('cache-keys.php');

        expect($keys)->toBeArray()
            ->and(count($keys))->toBeGreaterThanOrEqual(25)
            ->and($keys['setting_all'])->toBe('setting.all')
            ->and($keys['setting_group'])->toBe('setting.group.')
            ->and($keys['health_check'])->toBe('system.health_check')
            ->and($keys['auth_login_lockout'])->toBe('auth.login.lockout:')
            ->and($keys['school_entity'])->toBe('academic.school.entity');

        foreach ($keys as $name => $value) {
            expect($name)->toBeString()->not->toBe('')
                ->and($value)->toBeString()->not->toBe('');
        }
    });

    test('ZT6VS-FR-CORE-011: every registered key follows the module dot-namespace convention', function (): void {
        $keys = require config_path('cache-keys.php');

        foreach ($keys as $name => $value) {
            expect($value)->toMatch('/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)*[.:]?$/', "cache key [{$name}] violates {module}.{purpose}[.{qualifier}]");
        }
    });

    test('ZT6VS-FR-CORE-013: invalidation listeners map domain events to registered-key forgets', function (): void {
        $listen = config('event.listen');

        expect($listen[HandbookCreated::class])
            ->toContain(ClearHandbookCache::class)
            ->and($listen[StudentRegistered::class])
            ->toContain(ClearDashboardOnRegistration::class)
            ->and($listen[CompanyCreated::class])
            ->toContain(ClearDashboardOnCompanyChange::class)
            ->and($listen[NotificationRead::class])
            ->toContain(ClearUnreadNotificationCache::class);
    });

    test('ZT6VS-FR-CORE-014: cache warming command is registered and scheduled for deploy', function (): void {
        expect(Artisan::all())->toHaveKey('system:cache-warm');

        $scheduled = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command)
            ->filter(fn ($command) => str_contains((string) $command, 'system:cache-warm'));

        expect($scheduled)->not->toBeEmpty();
    });

    test('ZT6VS-FR-CORE-015: default session driver resolves to database', function (): void {
        $config = zt6vsFreshConfig('session.php', ['SESSION_DRIVER']);

        expect($config['driver'])->toBe('database');
    });

    test('ZT6VS-FR-CORE-017: session lifetime defaults to 120 minutes and honors env', function (): void {
        $default = zt6vsFreshConfig('session.php', ['SESSION_LIFETIME']);

        expect($default['lifetime'])->toBe(120);

        $custom = zt6vsFreshConfig('session.php', [], ['SESSION_LIFETIME' => '90']);

        expect($custom['lifetime'])->toBe(90);
    });

    test('ZT6VS-FR-CORE-018: sessions are encrypted with hardened cookie flags', function (): void {
        $config = zt6vsFreshConfig('session.php', ['SESSION_ENCRYPT', 'SESSION_HTTP_ONLY', 'SESSION_SAME_SITE']);

        expect($config['encrypt'])->toBeTrue()
            ->and($config['http_only'])->toBeTrue()
            ->and($config['same_site'])->toBe('lax');

        $production = zt6vsFreshConfig('session.php', ['SESSION_SECURE_COOKIE'], ['APP_ENV' => 'production']);
        $local = zt6vsFreshConfig('session.php', ['SESSION_SECURE_COOKIE'], ['APP_ENV' => 'local']);

        expect($production['secure'])->toBeTrue()
            ->and($local['secure'])->toBeFalse();
    });

    test('ZT6VS-FR-CORE-020: session garbage collection uses the probabilistic lottery', function (): void {
        $config = zt6vsFreshConfig('session.php');

        expect($config['lottery'])->toBe([2, 100]);
    });

    test('ZT6VS-FR-CORE-022: default queue connection resolves to sync', function (): void {
        $config = zt6vsFreshConfig('queue.php', ['QUEUE_CONNECTION']);

        expect($config['default'])->toBe('sync');
    });

    test('ZT6VS-FR-CORE-025: failed jobs persist with the full exception trace', function (): void {
        $config = zt6vsFreshConfig('queue.php', ['QUEUE_FAILED_DRIVER']);

        expect($config['failed']['driver'])->toBe('database-uuids')
            ->and($config['failed']['table'])->toBe('failed_jobs');
    });

    test('ZT6VS-FR-CORE-026: batch document generation resolves to the documents pipeline', function (): void {
        $config = zt6vsFreshConfig('queue.php', ['REDIS_QUEUE_DOCUMENTS']);

        expect($config['connections']['documents']['driver'])->toBe('redis')
            ->and($config['connections']['documents']['queue'])->toBe('documents');
    });

    test('ZT6VS-FR-CORE-027: default mailer resolves to log', function (): void {
        $config = zt6vsFreshConfig('mail.php', ['MAIL_MAILER']);

        expect($config['default'])->toBe('log')
            ->and($config['mailers']['log']['transport'])->toBe('log');
    });

    test('ZT6VS-FR-CORE-028: smtp transport resolves host, port, and credentials via env', function (): void {
        $config = zt6vsFreshConfig(
            'mail.php',
            [],
            ['MAIL_MAILER' => 'smtp', 'MAIL_HOST' => 'smtp.sekolah.id', 'MAIL_PORT' => '587', 'MAIL_USERNAME' => 'admin', 'MAIL_PASSWORD' => 's3cret'],
        );

        expect($config['default'])->toBe('smtp')
            ->and($config['mailers']['smtp']['transport'])->toBe('smtp')
            ->and($config['mailers']['smtp']['host'])->toBe('smtp.sekolah.id')
            ->and($config['mailers']['smtp']['port'])->toBe('587')
            ->and($config['mailers']['smtp']['username'])->toBe('admin')
            ->and($config['mailers']['smtp']['password'])->toBe('s3cret')
            ->and($config['mailers']['ses']['transport'])->toBe('ses')
            ->and($config['mailers']['sendmail']['transport'])->toBe('sendmail');

        $from = zt6vsFreshConfig('mail.php', [], ['MAIL_FROM_ADDRESS' => 'noreply@sekolah.id']);

        expect($from['from']['address'])->toBe('noreply@sekolah.id');
    });

    test('ZT6VS-FR-CORE-031: default filesystem disk resolves to local', function (): void {
        $config = zt6vsFreshConfig('filesystems.php', ['FILESYSTEM_DISK']);

        expect($config['default'])->toBe('local')
            ->and($config['disks']['local']['driver'])->toBe('local');
    });

    test('ZT6VS-FR-CORE-032: public disk serves assets through the install-time symlink map', function (): void {
        $config = zt6vsFreshConfig('filesystems.php');

        expect($config['disks']['public']['driver'])->toBe('local')
            ->and($config['disks']['public']['root'])->toBe(storage_path('app/public'))
            ->and($config['disks']['public']['visibility'])->toBe('public')
            ->and($config['disks']['public']['url'])->toEndWith('/storage')
            ->and($config['links'])->toHaveKey(public_path('storage'))
            ->and($config['links'][public_path('storage')])->toBe(storage_path('app/public'));
    });

    test('ZT6VS-FR-CORE-033: s3 disk resolves object storage purely from AWS env', function (): void {
        $config = zt6vsFreshConfig(
            'filesystems.php',
            [],
            ['AWS_ACCESS_KEY_ID' => 'key-id', 'AWS_SECRET_ACCESS_KEY' => 'secret', 'AWS_DEFAULT_REGION' => 'ap-southeast-1', 'AWS_BUCKET' => 'smk-blobs', 'AWS_URL' => 'https://cdn.sekolah.id'],
        );

        expect($config['disks']['s3']['driver'])->toBe('s3')
            ->and($config['disks']['s3']['key'])->toBe('key-id')
            ->and($config['disks']['s3']['secret'])->toBe('secret')
            ->and($config['disks']['s3']['region'])->toBe('ap-southeast-1')
            ->and($config['disks']['s3']['bucket'])->toBe('smk-blobs')
            ->and($config['disks']['s3']['url'])->toBe('https://cdn.sekolah.id');
    });

    test('ZT6VS-FR-CORE-037: tier-1 matrix runs with zero external services', function (): void {
        $queue = zt6vsFreshConfig('queue.php', ['QUEUE_CONNECTION']);
        $cache = zt6vsFreshConfig('cache.php', ['CACHE_STORE']);
        $session = zt6vsFreshConfig('session.php', ['SESSION_DRIVER']);
        $disk = zt6vsFreshConfig('filesystems.php', ['FILESYSTEM_DISK']);

        expect($queue['default'])->toBe('sync')
            ->and($cache['default'])->toBe('file')
            ->and($session['driver'])->toBe('database')
            ->and($disk['default'])->toBe('local');
    });

    test('ZT6VS-FR-CORE-042: post-install defaults stay pinned to sync, file, and database', function (): void {
        $queue = zt6vsFreshConfig('queue.php', ['QUEUE_CONNECTION']);
        $cache = zt6vsFreshConfig('cache.php', ['CACHE_STORE']);
        $session = zt6vsFreshConfig('session.php', ['SESSION_DRIVER']);

        expect($queue['default'])->toBe('sync')
            ->and($cache['default'])->toBe('file')
            ->and($session['driver'])->toBe('database');
    });

    test('ZT6VS-FR-CORE-038: tier-2 growth is an env swap with zero code changes', function (): void {
        $cache = zt6vsFreshConfig('cache.php', [], ['CACHE_STORE' => 'redis']);
        $queue = zt6vsFreshConfig('queue.php', [], ['QUEUE_CONNECTION' => 'redis']);
        $session = zt6vsFreshConfig('session.php', [], ['SESSION_DRIVER' => 'redis']);
        $disk = zt6vsFreshConfig('filesystems.php', [], ['FILESYSTEM_DISK' => 's3']);

        expect($cache['default'])->toBe('redis')
            ->and($queue['default'])->toBe('redis')
            ->and($session['driver'])->toBe('redis')
            ->and($disk['default'])->toBe('s3');
    });

    test('ZT6VS-FR-CORE-040: one shared redis serves cache, queue, and session on distinct databases', function (): void {
        $config = zt6vsFreshConfig('database.php', ['REDIS_DB', 'REDIS_CACHE_DB', 'REDIS_QUEUE_DB', 'REDIS_SESSION_DB']);
        $redis = $config['redis'];

        expect(array_keys($redis))->toContain('default', 'cache', 'queue', 'session')
            ->and($redis['default']['database'])->toBe('0')
            ->and($redis['cache']['database'])->toBe('1')
            ->and($redis['queue']['database'])->toBe('2')
            ->and($redis['session']['database'])->toBe('3');

        $shared = zt6vsFreshConfig('database.php', [], ['REDIS_HOST' => '10.0.0.5', 'REDIS_PORT' => '6380']);

        foreach (['default', 'cache', 'queue', 'session'] as $connection) {
            expect($shared['redis'][$connection]['host'])->toBe('10.0.0.5')
                ->and($shared['redis'][$connection]['port'])->toBe('6380');
        }

        expect($shared['redis']['default'])->toHaveKey('password');
    });

    test('ZT6VS-FR-CORE-041: application key resolves from env for encryption at rest', function (): void {
        $config = zt6vsFreshConfig('app.php', [], ['APP_KEY' => 'base64:zt6vs-sentinel-key']);

        expect($config['key'])->toBe('base64:zt6vs-sentinel-key');
    });

    test('ZT6VS-NFR-CORE-001: session cookie hardening flags ship in the default config', function (): void {
        $config = zt6vsFreshConfig('session.php', ['SESSION_ENCRYPT', 'SESSION_HTTP_ONLY', 'SESSION_SAME_SITE'], ['APP_ENV' => 'production']);

        expect($config['encrypt'])->toBeTrue()
            ->and($config['http_only'])->toBeTrue()
            ->and($config['same_site'])->toBe('lax')
            ->and($config['secure'])->toBeTrue();
    });

    test('ZT6VS-NFR-CORE-005: no inline cache key string survives outside the registry', function (): void {
        $violations = [];
        $pattern = '/(Cache::(remember|rememberForever|put|get|forget|forever|add|flexible)\s*\(\s*[\'"]|(?<![:\w$>])cache\(\s*[\'"])/';

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            if (preg_match($pattern, $content, $matches)) {
                $violations[] = $file->getPathname().': '.$matches[0];
            }
        }

        expect($violations)->toBeEmpty();
    });
});
