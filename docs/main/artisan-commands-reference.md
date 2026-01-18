# Artisan Commands Reference

This document provides a comprehensive and categorized reference of all available Artisan commands
within the Internara application. Use this guide to quickly find commands for development,
maintenance, and debugging tasks.

---

**Table of Contents**

1.  [General Commands](#1-general-commands)
2.  [Authentication (`auth`) Commands](#2-authentication-auth-commands)
3.  [Boost (`boost`) Commands](#3-boost-boost-commands)
4.  [Cache (`cache`) Commands](#4-cache-cache-commands)
5.  [Channel (`channel`) Commands](#5-channel-channel-commands)
6.  [Configuration (`config`) Commands](#6-configuration-config-commands)
7.  [Database (`db`) Commands](#7-database-db-commands)
8.  [Environment (`env`) Commands](#8-environment-env-commands)
9.  [Event (`event`) Commands](#9-event-event-commands)
10. [Installation (`install`) Commands](#10-installation-install-commands)
11. [Key (`key`) Commands](#11-key-key-commands)
12. [Language (`lang`) Commands](#12-language-lang-commands)
13. [Livewire (`livewire`) Commands](#13-livewire-livewire-commands)
14. [Make (`make`) Commands](#14-make-make-commands)
15. [MCP (`mcp`) Commands](#15-mcp-mcp-commands)
16. [Migration (`migrate`) Commands](#16-migration-migrate-commands)
17. [Model (`model`) Commands](#17-model-model-commands)
18. [Module (`module`) Commands](#18-module-module-commands)
19. [Optimization (`optimize`) Commands](#19-optimization-optimize-commands)
20. [Package (`package`) Commands](#20-package-package-commands)
21. [Pest (`pest`) Commands](#21-pest-pest-commands)
22. [Queue (`queue`) Commands](#22-queue-queue-commands)
23. [Roster (`roster`) Commands](#23-roster-roster-commands)
24. [Route (`route`) Commands](#24-route-route-commands)
25. [Sail (`sail`) Commands](#25-sail-sail-commands)
26. [Schedule (`schedule`) Commands](#26-schedule-schedule-commands)
27. [Schema (`schema`) Commands](#27-schema-schema-commands)
28. [Storage (`storage`) Commands](#28-storage-storage-commands)
29. [Stub (`stub`) Commands](#29-stub-stub-commands)
30. [Vendor (`vendor`) Commands](#30-vendor-vendor-commands)
31. [View (`view`) Commands](#31-view-view-commands)
32. [Volt (`volt`) Commands](#32-volt-volt-commands)

---

## 1. General Commands

| Command          | Description                                                                                 |
| ---------------- | ------------------------------------------------------------------------------------------- |
| `about`          | Display basic information about your Laravel application environment.                       |
| `clear-compiled` | Remove the compiled class file, effectively clearing the compiled application.              |
| `completion`     | Dump the shell completion script for easier Artisan command usage.                          |
| `db`             | Start a new database CLI session, allowing direct interaction with the database.            |
| `docs`           | Access the Laravel documentation directly from the command line.                            |
| `down`           | Put the application into maintenance mode, displaying a custom message or view.             |
| `env`            | Display the current framework environment (e.g., `local`, `production`).                    |
| `help`           | Display help for any given Artisan command.                                                 |
| `inspire`        | Display an inspiring quote (a classic Laravel easter egg).                                  |
| `list`           | List all available Artisan commands.                                                        |
| `migrate`        | Run all pending database migrations.                                                        |
| `optimize`       | Cache framework bootstrap, configuration, and metadata to increase application performance. |
| `pail`           | Tail the application logs in real-time.                                                     |
| `serve`          | Serve the application on the PHP development server (e.g., `http://127.0.0.1:8000`).        |
| `test`           | Run the application's PHPUnit/Pest tests.                                                   |
| `tinker`         | Interact with your application through a powerful REPL (Read-Eval-Print Loop).              |
| `up`             | Bring the application out of maintenance mode.                                              |

## 1. Application (`app`) Commands

| Command    | Description                                                    |
| ---------- | -------------------------------------------------------------- |
| `app:info` | Display application identity, version, and author information. |

## 2. Authentication (`auth`) Commands

| Command             | Description                                            |
| ------------------- | ------------------------------------------------------ |
| `auth:clear-resets` | Flush expired password reset tokens from the database. |

## 3. Boost (`boost`) Commands

| Command         | Description                                                             |
| --------------- | ----------------------------------------------------------------------- |
| `boost:install` | Installs Laravel Boost related resources.                               |
| `boost:mcp`     | Starts the Laravel Boost MCP server (usually initiated via `mcp.json`). |
| `boost:update`  | Update the Laravel Boost guidelines to the latest guidance.             |

## 4. Cache (`cache`) Commands

| Command                  | Description                                                  |
| ------------------------ | ------------------------------------------------------------ |
| `cache:clear`            | Flush the entire application cache.                          |
| `cache:forget`           | Remove a specific item from the cache.                       |
| `cache:prune-stale-tags` | Prune stale cache tags from the cache (primarily for Redis). |

## 5. Channel (`channel`) Commands

| Command        | Description                                                        |
| -------------- | ------------------------------------------------------------------ |
| `channel:list` | List all registered private broadcast channels in the application. |

## 6. Configuration (`config`) Commands

| Command          | Description                                                                                        |
| ---------------- | -------------------------------------------------------------------------------------------------- |
| `config:cache`   | Create a cache file for faster configuration loading in production environments.                   |
| `config:clear`   | Remove the configuration cache file.                                                               |
| `config:publish` | Publish all publishable configuration files from vendors to your application's `config` directory. |
| `config:show`    | Display all of the values for a given configuration file or key.                                   |

## 7. Database (`db`) Commands

| Command      | Description                                                                |
| ------------ | -------------------------------------------------------------------------- |
| `db:monitor` | Monitor the number of connections on the specified database(s).            |
| `db:seed`    | Seed the database with records using the `DatabaseSeeder` class.           |
| `db:show`    | Display information about the configured database connection.              |
| `db:table`   | Display information (schema details) about a specific database table.      |
| `db:wipe`    | Drop all tables, views, and types from the database. **Use with caution.** |

## 8. Environment (`env`) Commands

| Command       | Description                                                      |
| ------------- | ---------------------------------------------------------------- |
| `env:decrypt` | Decrypt an environment file (e.g., `.env.production.encrypted`). |
| `env:encrypt` | Encrypt an environment file for secure storage.                  |

## 9. Event (`event`) Commands

| Command       | Description                                                                |
| ------------- | -------------------------------------------------------------------------- |
| `event:cache` | Discover and cache the application's events and listeners for performance. |
| `event:clear` | Clear all cached events and listeners.                                     |
| `event:list`  | List all registered events and their associated listeners.                 |

## 10. Installation (`install`) Commands

| Command                | Description                                                                |
| ---------------------- | -------------------------------------------------------------------------- |
| `install:api`          | Create an API routes file and install Laravel Sanctum or Laravel Passport. |
| `install:broadcasting` | Create a broadcasting channels routes file.                                |

## 11. Key (`key`) Commands

| Command        | Description                                              |
| -------------- | -------------------------------------------------------- |
| `key:generate` | Set the application key (`APP_KEY`) in your `.env` file. |

## 12. Language (`lang`) Commands

| Command        | Description                                                                          |
| -------------- | ------------------------------------------------------------------------------------ |
| `lang:add`     | Install new localizations (language files).                                          |
| `lang:publish` | Publish all language files that are available for customization to `resources/lang`. |
| `lang:reset`   | Resets installed localizations to their default state.                               |
| `lang:rm`      | Remove existing localizations.                                                       |
| `lang:update`  | Update installed localizations.                                                      |

## 13. Livewire (`livewire`) Commands

| Command                                | Description                                                                                       |
| -------------------------------------- | ------------------------------------------------------------------------------------------------- |
| `livewire:attribute`                   | Create a new Livewire attribute class.                                                            |
| `livewire:configure-s3-upload-cleanup` | Configure temporary file upload S3 directory to automatically clean up files older than 24 hours. |
| `livewire:copy`                        | Copy an existing Livewire component to a new location.                                            |
| `livewire:delete`                      | Delete a Livewire component.                                                                      |
| `livewire:form`                        | Create a new Livewire form class.                                                                 |
| `livewire:layout`                      | Create a new application layout file for Livewire components.                                     |
| `livewire:make`                        | Create a new Livewire component.                                                                  |
| `livewire:move`                        | Move a Livewire component to a different location.                                                |
| `livewire:publish`                     | Publish Livewire configuration files.                                                             |
| `livewire:stubs`                       | Publish Livewire stubs for customization.                                                         |
| `livewire:upgrade`                     | Interactive upgrade helper to migrate Livewire from v2 to v3.                                     |

## 14. Make (`make`) Commands

These commands are used to generate various classes and files within the Laravel application.

| Command                    | Description                                                  |
| -------------------------- | ------------------------------------------------------------ |
| `make:cache-table`         | Create a migration for the cache database table.             |
| `make:cast`                | Create a new custom Eloquent cast class.                     |
| `make:channel`             | Create a new channel class for broadcasting.                 |
| `make:class`               | Create a new generic PHP class.                              |
| `make:command`             | Create a new Artisan console command.                        |
| `make:component`           | Create a new view component class.                           |
| `make:config`              | Create a new configuration file.                             |
| `make:controller`          | Create a new controller class.                               |
| `make:enum`                | Create a new enum class.                                     |
| `make:event`               | Create a new event class.                                    |
| `make:exception`           | Create a new custom exception class.                         |
| `make:factory`             | Create a new model factory for seeding.                      |
| `make:interface`           | Create a new PHP interface.                                  |
| `make:job`                 | Create a new job class for the queue.                        |
| `make:job-middleware`      | Create a new job middleware class.                           |
| `make:listener`            | Create a new event listener class.                           |
| `make:livewire`            | Create a new Livewire component.                             |
| `make:mail`                | Create a new email Mailable class.                           |
| `make:mcp-prompt`          | Create a new MCP prompt class.                               |
| `make:mcp-resource`        | Create a new MCP resource class.                             |
| `make:mcp-server`          | Create a new MCP server class.                               |
| `make:mcp-tool`            | Create a new MCP tool class.                                 |
| `make:middleware`          | Create a new HTTP middleware class.                          |
| `make:migration`           | Create a new database migration file.                        |
| `make:model`               | Create a new Eloquent model class.                           |
| `make:notification`        | Create a new notification class.                             |
| `make:notifications-table` | Create a migration for the notifications database table.     |
| `make:observer`            | Create a new observer class for Eloquent models.             |
| `make:policy`              | Create a new policy class for authorization.                 |
| `make:provider`            | Create a new service provider class.                         |
| `make:queue-batches-table` | Create a migration for the queue batches database table.     |
| `make:queue-failed-table`  | Create a migration for the failed queue jobs database table. |
| `make:queue-table`         | Create a migration for the queue jobs database table.        |
| `make:request`             | Create a new form request class for validation.              |
| `make:resource`            | Create a new API resource class.                             |
| `make:rule`                | Create a new validation rule class.                          |
| `make:scope`               | Create a new Eloquent scope class.                           |
| `make:seeder`              | Create a new seeder class.                                   |
| `make:service`             | Create a new service class (often used in modular contexts). |
| `make:session-table`       | Create a migration for the session database table.           |
| `make:test`                | Create a new test class (PHPUnit or Pest).                   |
| `make:trait`               | Create a new PHP trait.                                      |
| `make:view`                | Create a new Blade view file.                                |
| `make:volt`                | Create a new Volt component.                                 |

## 15. MCP (`mcp`) Commands

| Command         | Description                                                |
| --------------- | ---------------------------------------------------------- |
| `mcp:inspector` | Open the MCP Inspector tool to debug and test MCP Servers. |
| `mcp:start`     | Start the MCP Server for a given handle.                   |

## 16. Migration (`migrate`) Commands

| Command            | Description                                                                                                   |
| ------------------ | ------------------------------------------------------------------------------------------------------------- |
| `migrate:fresh`    | Drop all tables from the database and re-run all migrations. **Use with caution, as this destroys all data.** |
| `migrate:install`  | Create the `migrations` table in the database if it doesn't exist.                                            |
| `migrate:refresh`  | Rollback all migrations and then re-run them from the beginning.                                              |
| `migrate:reset`    | Rollback all database migrations.                                                                             |
| `migrate:rollback` | Rollback the last batch of database migrations.                                                               |
| `migrate:status`   | Show the status (ran or not ran) of each migration.                                                           |

## 17. Model (`model`) Commands

| Command       | Description                                                                                    |
| ------------- | ---------------------------------------------------------------------------------------------- |
| `model:prune` | Prune models that are no longer needed, typically based on a defined schedule.                 |
| `model:show`  | Show detailed information about an Eloquent model, including attributes, relations, and casts. |

## 18. Module (`module`) Commands

These commands are specific to the `nwidart/laravel-modules` package and are used for managing the
modular architecture.

**Important Note on Naming & Namespaces:** When using `module:make-*` commands, always refer to the
[Development Conventions](development-conventions.md) for guidelines on naming and namespace
structures, particularly regarding the omission of the `app` segment in namespaces.

| Command                          | Description                                                                         |
| -------------------------------- | ----------------------------------------------------------------------------------- |
| `module:composer-update`         | Update the `composer.json` autoload for a module or all modules.                    |
| `module:delete`                  | Delete a module entirely from the application.                                      |
| `module:disable`                 | Disable one or more modules.                                                        |
| `module:dump`                    | Dump-autoload the specified module's composer configuration or for all modules.     |
| `module:enable`                  | Enable a disabled module.                                                           |
| `module:install`                 | Install a module by its given package name (e.g., `vendor/name`).                   |
| `module:lang`                    | Check for missing language keys in the specified module.                            |
| `module:list`                    | Show a list of all registered modules.                                              |
| `module:list-commands`           | List all Artisan commands defined within a specific module or all modules.          |
| `module:make`                    | Create a new module with default structure.                                         |
| `module:make-action`             | Create a new action class for the specified module.                                 |
| `module:make-cast`               | Create a new Eloquent cast class for a module.                                      |
| `module:make-channel`            | Create a new broadcasting channel class for a module.                               |
| `module:make-class`              | Create a new plain PHP class for a module, with a direct namespace.                 |
| `module:make-command`            | Generate a new Artisan command for a module.                                        |
| `module:make-component`          | Create a new view component class for a module.                                     |
| `module:make-component-view`     | Create a new component view (Blade file) for a module.                              |
| `module:make-controller`         | Generate a new RESTful controller for a module.                                     |
| `module:make-entity`             | Create a new entity class (DTO) for a module.                                       |
| `module:make-enum`               | Create a new enum class for a module.                                               |
| `module:make-event`              | Create a new event class for a module.                                              |
| `module:make-event-provider`     | Create a new event service provider class for a module.                             |
| `module:make-exception`          | Create a new exception class for a module.                                          |
| `module:make-factory`            | Create a new model factory for a module.                                            |
| `module:make-helper`             | Create a new helper class for a module.                                             |
| `module:make-interface`          | Create a new interface class for a module.                                          |
| `module:make-job`                | Create a new job class for a module.                                                |
| `module:make-listener`           | Create a new event listener class for a module.                                     |
| `module:make-livewire`           | Create a new Livewire component within a module.                                    |
| `module:make-mail`               | Create a new email Mailable class for a module.                                     |
| `module:make-middleware`         | Create a new HTTP middleware class for a module.                                    |
| `module:make-migration`          | Create a new database migration for a module.                                       |
| `module:make-model`              | Create a new Eloquent model for a module.                                           |
| `module:make-notification`       | Create a new notification class for a module.                                       |
| `module:make-observer`           | Create a new observer class for a module.                                           |
| `module:make-policy`             | Create a new policy class for a module.                                             |
| `module:make-provider`           | Create a new service provider class for a module.                                   |
| `module:make-repository`         | Create a new repository class for a module.                                         |
| `module:make-request`            | Create a new form request class for a module.                                       |
| `module:make-resource`           | Create a new API resource class for a module.                                       |
| `module:make-rule`               | Create a new validation rule class for a module.                                    |
| `module:make-scope`              | Create a new Eloquent scope class for a module.                                     |
| `module:make-seed`               | Create a new seeder for a module.                                                   |
| `module:make-service`            | Create a new service class for a module.                                            |
| `module:make-test`               | Create a new test class for a module.                                               |
| `module:make-trait`              | Create a new trait class for a module.                                              |
| `module:make-view`               | Create a new Blade view for a module.                                               |
| `module:migrate`                 | Run migrations from the specified module or all modules.                            |
| `module:migrate-fresh`           | Drop all tables and re-run all migrations for modules.                              |
| `module:migrate-refresh`         | Rollback and re-migrate all migrations for modules.                                 |
| `module:migrate-reset`           | Rollback all migrations for modules.                                                |
| `module:migrate-rollback`        | Rollback the last batch of migrations for modules.                                  |
| `module:migrate-status`          | Show the migration status for all modules.                                          |
| `module:model-show`              | Show information about an Eloquent model within a module.                           |
| `module:prune`                   | Prune models by module that are no longer needed.                                   |
| `module:publish`                 | Publish a module's assets to the application's public directory.                    |
| `module:publish-config`          | Publish a module's config files to the application's `config` directory.            |
| `module:publish-migration`       | Publish a module's migrations to the application's `database/migrations` directory. |
| `module:publish-translation`     | Publish a module's translations to the application's `resources/lang` directory.    |
| `module:route-provider`          | Create a new route service provider for a module.                                   |
| `module:seed`                    | Run database seeders from the specified module or all modules.                      |
| `module:setup`                   | Set up module folders for first use (e.g., symlinks).                               |
| `module:unuse`                   | Forget the currently used module.                                                   |
| `module:update`                  | Update dependencies for a specified module or all modules.                          |
| `module:update-phpunit-coverage` | Update `phpunit.xml` source/include path with enabled modules.                      |
| `module:use`                     | Set the specified module as the currently used module.                              |
| `module:v6:migrate`              | Migrate `laravel-modules` v5 module statuses to v6.                                 |

## 19. Optimization (`optimize`) Commands

| Command          | Description                                                      |
| ---------------- | ---------------------------------------------------------------- |
| `optimize:clear` | Remove the cached bootstrap files and other optimization caches. |

## 20. Package (`package`) Commands

| Command            | Description                                               |
| ------------------ | --------------------------------------------------------- |
| `package:discover` | Rebuild the cached package manifest for Laravel packages. |

## 21. Pest (`pest`) Commands

| Command        | Description                     |
| -------------- | ------------------------------- |
| `pest:dataset` | Create a new Pest dataset file. |
| `pest:test`    | Create a new Pest test file.    |

## 22. Queue (`queue`) Commands

| Command               | Description                                                     |
| --------------------- | --------------------------------------------------------------- |
| `queue:clear`         | Delete all jobs from the specified queue.                       |
| `queue:failed`        | List all of the failed queue jobs.                              |
| `queue:flush`         | Flush all of the failed queue jobs from the database.           |
| `queue:forget`        | Delete a specific failed queue job.                             |
| `queue:listen`        | Listen to a given queue, continuously processing new jobs.      |
| `queue:monitor`       | Monitor the size of the specified queues.                       |
| `queue:prune-batches` | Prune stale entries from the job batches database table.        |
| `queue:prune-failed`  | Prune stale entries from the failed jobs table.                 |
| `queue:restart`       | Restart queue worker daemons after their current job completes. |
| `queue:retry`         | Retry a failed queue job by its ID.                             |
| `queue:retry-batch`   | Retry the failed jobs for a specific batch.                     |
| `queue:work`          | Start processing jobs on the queue as a daemon.                 |

## 23. Roster (`roster`) Commands

| Command       | Description                                             |
| ------------- | ------------------------------------------------------- |
| `roster:scan` | Detect packages & approaches in use and output as JSON. |

## 24. Route (`route`) Commands

| Command       | Description                                                            |
| ------------- | ---------------------------------------------------------------------- |
| `route:cache` | Create a route cache file for faster route registration in production. |
| `route:clear` | Remove the route cache file.                                           |
| `route:list`  | List all registered routes in the application.                         |

## 25. Sail (`sail`) Commands

| Command        | Description                                                               |
| -------------- | ------------------------------------------------------------------------- |
| `sail:add`     | Add a new service to an existing Sail installation.                       |
| `sail:install` | Install Laravel Sail's default Docker Compose file and related resources. |
| `sail:publish` | Publish the Laravel Sail Docker files for customization.                  |

## 26. Schedule (`schedule`) Commands

| Command                | Description                                               |
| ---------------------- | --------------------------------------------------------- |
| `schedule:clear-cache` | Delete the cached mutex files created by the scheduler.   |
| `schedule:interrupt`   | Interrupt the current schedule run.                       |
| `schedule:list`        | List all scheduled tasks defined in your application.     |
| `schedule:run`         | Run the scheduled commands that are due.                  |
| `schedule:test`        | Run a scheduled command immediately for testing purposes. |
| `schedule:work`        | Start the schedule worker as a daemon.                    |

## 27. Schema (`schema`) Commands

| Command       | Description                               |
| ------------- | ----------------------------------------- |
| `schema:dump` | Dump the given database schema to a file. |

## 28. Storage (`storage`) Commands

| Command          | Description                                                              |
| ---------------- | ------------------------------------------------------------------------ |
| `storage:link`   | Create the symbolic links configured for the application's storage.      |
| `storage:unlink` | Delete existing symbolic links configured for the application's storage. |

## 29. Stub (`stub`) Commands

| Command        | Description                                                                       |
| -------------- | --------------------------------------------------------------------------------- |
| `stub:publish` | Publish all stubs that are available for customization to your `stubs` directory. |

## 30. Vendor (`vendor`) Commands

| Command          | Description                                                                            |
| ---------------- | -------------------------------------------------------------------------------------- |
| `vendor:publish` | Publish any publishable assets (config, migrations, views, etc.) from vendor packages. |

## 31. View (`view`) Commands

| Command      | Description                                                            |
| ------------ | ---------------------------------------------------------------------- |
| `view:cache` | Compile all of the application's Blade templates for faster rendering. |
| `view:clear` | Clear all compiled view files.                                         |

## 32. Volt (`volt`) Commands

| Command        | Description                        |
| -------------- | ---------------------------------- |
| `volt:install` | Install all of the Volt resources. |

---

**Navigation**

[← Previous: Testing Guide](testing-guide.md) | [Next: Release Guidelines →](release-guidelines.md)
