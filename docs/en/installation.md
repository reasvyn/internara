# Installation

## Step-by-Step Process Conceptually

Installing the application involves preparing the environment, configuring
the database, running the setup wizard, and ensuring background processes are
running. Each step prepares the next, and the setup wizard automates what
would otherwise be a sequence of manual Artisan commands.

### 1. Environment Configuration

The `.env` file contains all environment-specific configuration. A template
(`.env.example`) is provided with sensible defaults for development. Copying
and editing this file is the first step. The application key — a 32-character
base64 string used for encryption — must be generated. Every environment
(development, staging, production) needs a unique key.

### 2. Database Setup

For development, the database is a file. Touching the file creates it. No
server, no credentials, no configuration beyond the default. For production,
a database server must be running, a database created with the correct
character set, and credentials provided in `.env`.

### 3. Default Drivers

The application defaults to zero-dependency database-backed drivers for
cache, session, and queue. This means the application works immediately after
migrations without Redis, Memcached, or any external service. For production,
these drivers can be upgraded to Redis for better performance, but nothing
breaks if they are not.

### 4. Setup Wizard

The setup wizard is a two-phase installation process. The command-line phase
audits the environment, publishes vendor configurations, runs migrations, and
seeds initial data (roles, permissions, settings, academic years). It then
generates a one-time signed URL. The web-based phase is a 7-step wizard:

1. School setup — name, address, optional logo
2. Branding — color preset selection, tagline
3. Academic year — year name and dates
4. Departments — initial department creation
5. Admin account — create the first super administrator
6. Mail configuration — SMTP settings
7. Completion — summary and redirect to login

Each step validates before proceeding. The wizard can be rerun with a force
flag if needed.

### 5. Admin Account

If the setup wizard is skipped, an interactive command creates a super
administrator account. A recovery command exists for the scenario where all
admin access is lost — it runs from the console and either resets a password
or creates a new admin.

### 6. Queue and Scheduler Requirements

The application depends on background job processing for notifications, media
conversions, and scheduled maintenance. A queue worker must be running at all
times in production. This is managed by Supervisor or systemd. The scheduler
(powered by a single cron entry that runs every minute) triggers daily
cleanup tasks, cache warming, and other periodic operations. Without these
two processes, the application appears functional but certain features
(notifications, certificate generation, log pruning) will not work.

### 7. Production Hardening

Before going live: disable debug mode, cache the configuration and routes,
build frontend assets, create the storage symlink, install SSL, configure
file permissions, and implement a backup strategy.

## Where to Find It

The `.env.example` file is at the project root. The setup wizard logic is in
`app/Domain/Setup/` — the console command is at `Console/Commands/SetupInstall.php`
and the Livewire wizard is at `Livewire/SetupWizard.php`. The admin commands
are at `app/Domain/Auth/Console/Commands/`. The queue worker and scheduler
configuration are described in the infrastructure guide (see
`docs/en/infrastructure.md`).
