# Infrastructure

## Deployment Options

The application can be deployed in three primary ways, each suited to
different operational capabilities.

Laravel Cloud provides zero-ops deployment: connect a Git repository,
configure environment variables through a dashboard, and the platform handles
servers, scaling, SSL, and background processes. This is the recommended
approach for teams that want to minimize operational overhead.

A traditional VPS deployment uses Nginx or Caddy as the web server, PHP-FPM
for application execution, Supervisor for process management, and optionally
Redis for cache and queue. This approach gives full control over the
environment but requires manual configuration of each component.

Docker deployment containerizes the application with its dependencies. A
Docker Compose file defines services for the application, queue worker,
Reverb WebSocket server, database, and Redis. This approach is reproducible
across environments and is a good choice for teams using container
orchestration.

## Required Background Processes

Three background processes must be running at all times in production.

The queue worker processes queued jobs: notifications, media conversions,
mail delivery, and any deferred operations. Without it, jobs pile up in the
queue table and are never executed. The worker is managed by Supervisor or
systemd and configured to retry failed jobs and gracefully stop on timeout.

The scheduler cron entry runs every minute and triggers the Laravel scheduler,
which in turn runs daily cleanup tasks, cache warming, pulse data recording,
and activity log pruning. Without the cron entry, scheduled tasks never run.

The Reverb server handles WebSocket connections for real-time broadcasting.
It receives notification broadcast events from the application and pushes
them to connected browsers. Without Reverb, the in-app notification system
still works, but users must refresh the page to see new notifications.

## Production Database Considerations

SQLite works for development but is not suitable for production with
concurrent users. SQLite locks the entire database file during writes, so
multiple simultaneous requests will encounter lock contention. MySQL 8+,
MariaDB, or PostgreSQL 14+ provide row-level locking and concurrent write
support.

In production, the database server should be tuned: buffer pool size for
MySQL, shared buffers for PostgreSQL, appropriate connection limits, and
SSD storage. For high-traffic deployments, consider read replicas for
reporting queries and connection pooling.

## Storage Considerations

The public storage symlink (`public/storage` -> `storage/app/public`) is
required for serving uploaded files. Without it, media URLs return 404.

For single-server deployments, local storage works fine. For multi-server
deployments, local storage must be replaced with a shared solution: NFS
mounts or S3-compatible object storage. The media library and filesystem
configuration support both options equally.

Storage should be included in backup routines alongside the database.
File uploads are irreplaceable if lost.

## Where to Find It

Deployment-specific configurations are in `Dockerfile`, `docker-compose.yml`,
and the infrastructure documentation. The Supervisor configuration examples
are in this file. The health check command at
`app/Domain/Core/Console/Commands/HealthCommand.php` verifies that all
required processes and services are operational. The cleanup command at
`app/Domain/Core/Console/Commands/CleanupCommand.php` handles scheduled
maintenance.
