# Support Module

## Overview

Core infrastructure module for Internara v0.14.0. Provides testing infrastructure with memory leak prevention, session management, and modular testing orchestration for 29+ modules.

## Features

### 🧪 Testing Infrastructure

**Memory-Safe Test Execution:**
- Isolated processes via `Symfony/Process`
- No memory leaks across test suites
- Automated garbage collection
- Retry logic for flaky tests

**Session Management:**
- Automated cleanup (max sessions, pruning)
- PII masking in session data
- Session integrity validation
- Configurable lifetime

**Result Reporting:**
- JUnit XML export (CI/CD)
- JSON export (API consumption)
- Console table output
- Coverage integration (90%+ required)

### 🏗️ Architecture

```
modules/Support/
├── src/
│   ├── Testing/
│   │   ├── Support/
│   │   │   ├── ProcessExecutor.php       # Memory-safe execution
│   │   │   ├── SessionManager.php       # Session cleanup
│   │   │   ├── ResultReporter.php       # Export results
│   │   │   └── AppTestOrchestrator.php # Main orchestrator
│   │   ├── Contracts/
│   │   │   ├── ProcessExecutorInterface.php
│   │   │   ├── SessionManagerInterface.php
│   │   │   ├── ResultReporterInterface.php
│   │   │   └── OrchestratorInterface.php
│   │   └── Commands/
│   │       └── AppTestCommand.php
│   └── Providers/
│       └── SupportServiceProvider.php
├── config/
│   └── testing.php                      # Configuration
└── tests/
    ├── Feature/
    └── Unit/
```

## Usage

### Running Tests

```bash
# Run all module tests
php artisan app:test

# Run specific modules
php artisan app:test --modules=Log,Support

# With coverage
php artisan app:test --coverage

# Export results
php artisan app:test --format=junit --output=results.xml
```

### Programmatic Usage

```php
$orchestrator = app(AppTestOrchestrator::class);

$results = $orchestrator->run([
    'modules' => ['Log', 'Support'],
    'coverage' => true,
    'format' => 'json'
]);

echo $results['summary'];
// "Total: 150 tests, Passed: 148, Failed: 2, Coverage: 92.5%"
```

## Configuration

See `config/testing.php`:
```php
return [
    'memory_limit' => '512M',
    'max_sessions' => 10,
    'session_lifetime' => 3600,
    'process' => [
        'timeout' => 300,
        'retry_attempts' => 3,
    ],
    'coverage' => [
        'minimum' => 90,
    ],
];
```

## Testing

```bash
php artisan test modules/Support --filter=Testing
```

**Coverage**: 90%+
- ProcessExecutor memory isolation tests
- SessionManager cleanup tests
- ResultReporter export tests
- Orchestrator DI wiring tests

## Core Components

### ProcessExecutor
```php
// Isolated test execution
$executor = app(ProcessExecutorInterface::class);
$results = $executor->execute(['Log', 'Support'], 'feature');
```

### SessionManager
```php
// Automated session cleanup
$sessionManager = app(SessionManagerInterface::class);
$sessionManager->pruneExpiredSessions();
```

### ResultReporter
```php
// Export test results
$reporter = app(ResultReporterInterface::class);
$xml = $reporter->exportJUnit($results);
```

## Documentation

Full documentation: [docs/testing-infrastructure.md](../docs/testing-infrastructure.md)

The `Support` module provides the **Infrastructure & Operational Bridge** for Internara. It
centralizes technical system installation, environment auditing, help documentation, and developer
automation.

---

## 1. Domain Domains

### 1.1 Technical Installation Domain

Handles the technical initialization of the Internara system.

- **`SystemInstaller`**: Handles low-level technical installation tasks (env creation, app key
  generation, migration execution, and storage symlinking).
- **`InstallationAuditor`**: Performs pre-flight environment checks (PHP extensions, directory
  permissions, and database connectivity).
- **`SystemInstallCommand`**: Automated technical initialization via CLI
  (`php artisan system:install`).

### 1.2 Help & Documentation Domain

Provides the knowledge base and support infrastructure for application users.

- **Help Articles**: Managed FAQ and documentation.
- **Support Center**: UI for user guidance.

### 1.3 Developer Scaffolding Domain

Provides custom Artisan generators that enforce Internara's architectural standards.

- `module:make-class`: Generates a final PHP class with direct namespacing.
- `module:make-interface`: Generates a contract in the appropriate domain folder.
- `module:make-trait`: Generates a reusable concern.

---

## 2. Engineering Standards

- **Infrastructure Sovereignty**: The `Support` module is authorized to interact with low-level
  system resources (Filesystem, Shell, PHP Configuration) to facilitate installation and auditing.
- **Domain Separation**: Technical installation concerns are strictly separated from the business
  configuration (handled by the `Setup` module).
- **Wizard Concerns**: Utilizes the shared `HandlesWizardSteps` trait for unified UI logic in the
  installation welcome and requirement check screens.

---

## 3. Verification & Validation (V&V)

- **Unit Tests**: Validates the audit logic and installation engine.

- **Command**: `php artisan test modules/Support`

---

_The Support module shields business domains from operational complexity through automation._
