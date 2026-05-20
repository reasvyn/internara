# System Requirements

## PHP Version and Extensions

The application requires PHP 8.4 or later. This version is needed because the
codebase uses modern PHP features extensively: constructor property promotion,
readonly classes, property hooks, typed class constants, and more.

Several PHP extensions are required. Each one exists because a specific
capability in the application depends on it:

The mbstring extension handles multibyte string operations — names, slugs,
localization strings, and any text that contains non-ASCII characters. Without
it, string truncation and comparison would malfunction on accented or
non-Latin characters.

The PDO extension with its database-specific drivers (SQLite, MySQL,
PostgreSQL) provides the database abstraction layer. The PDO extension itself
is the base; `ext-pdo_sqlite`, `ext-pdo_mysql`, and `ext-pdo_pgsql` enable
connection to each respective database engine. Only the driver matching your
chosen database is strictly required, but all three are listed to support
different deployment targets.

The GD extension processes images — resizing avatars, generating thumbnail
conversions, producing WebP output. Without it, the media library cannot
perform image manipulations.

The cURL extension makes HTTP requests for remote media downloads,
outgoing webhooks, and external API calls.

The BCMath extension provides arbitrary-precision arithmetic for score
calculations. Floating-point operations are not precise enough for grade
computation, so BCMath handles the math correctly.

The fileinfo extension detects MIME types of uploaded files. Without it, the
application would have to guess file types, creating a security risk.

Several extensions are built into PHP and enabled by default: JSON (API
communication), tokenizer (Blade rendering), XML (feed generation), ctype
(character validation), filter (input sanitization), hash (hashing), openssl
(encryption and HTTPS), session (session handling), and zlib (compression).

## Recommended but Optional Extensions

The Redis extension (`ext-redis`) enables high-performance caching, session
storage, and queue processing. Without it, the application uses database-backed
alternatives that work correctly but are slower.

ImageMagick (`ext-imagick`) is an alternative to GD with better quality for
image conversions. The Vips extension (`ext-vips`) offers even higher
performance for large-scale image processing.

OPcache (`ext-opcache`) caches compiled PHP bytecode in memory, dramatically
improving response times in production. It is not needed in development
where files change frequently.

The sockets extension is required by Laravel Reverb for WebSocket connections.
Without it, real-time broadcasting will not work.

## Server Recommendations

In development, any environment that runs PHP 8.4 and Composer will work.
SQLite is the default database and requires no server process. Node.js and
npm are needed for frontend asset compilation.

In production, a dedicated web server (Nginx or Apache with mod_rewrite) is
standard. PHP-FPM should be configured with enough worker processes to handle
concurrent requests. MySQL 8+ or PostgreSQL 14+ is recommended over SQLite
for concurrent write workloads. Redis is recommended if using the application
at scale. Supervisor or systemd must manage the queue worker, the scheduler
cron entry, and the Reverb WebSocket server.

## Development vs Production Differences

In development, SQLite is used (no server needed), the file or database cache
driver is sufficient, the sync queue driver can be used (jobs run
immediately), and debug mode is enabled. In production, MySQL/PostgreSQL is
used, Redis handles cache and queue, the queue worker runs via Supervisor,
OPcache is enabled, Composer runs with `--optimize-autoloader --no-dev`,
and debug mode is disabled.

## Where to Find It

The composer.json file defines PHP version and extension requirements. The
Dockerfile and Laravel Sail configuration document the server environment.
Environment-specific notes and commands live in this file's sections below.
