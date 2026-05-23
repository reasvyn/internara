# ADR-007: SQLite as Default Database

## Status
Accepted

## Context
Every Laravel installation needs a database connection. The default choice historically has
been MySQL or PostgreSQL, but these require a running server process, user account management,
and port configuration.

For development environments — especially during initial development, CI pipelines, and
local testing — the overhead of managing a database server is unnecessary. The same codebase
also needs to support MySQL/PostgreSQL in production without code changes.

Three alternatives considered:
1. **MySQL/PostgreSQL everywhere**: Consistent environment, but every developer must install
   and configure a database server. CI pipelines become slower (service containers needed).
   Tests run on a full database server.
2. **SQLite in development, MySQL/PostgreSQL in production**: Zero-install development
   database, fast CI, production-grade database in deployment. This introduces the risk of
   SQL-flavor incompatibilities between environments.
3. **Laravel's `database` driver with `:memory:` for tests**: SQLite in-memory for tests is
   already Laravel convention. Extending SQLite to development too is natural.

## Decision
SQLite is the default database for development and testing. SQLite is used in two modes:
- **File-based** (`database.sqlite`) for local development — persistent across server restarts
- **In-memory** for test suites — faster than file-based, auto-discarded after each test class

The `database.md` document catalogs known SQLite-vs-MySQL differences and the `db:validate`
command can warn about queries that may behave differently across engines. This approach
ensures zero-install development while keeping production deployment flexible.

## Consequences
- **Positive**: No database server installation needed — PHP's built-in SQLite support works
  immediately.
- **Positive**: CI pipelines need no service containers — tests run directly.
- **Positive**: Database state is trivially reset — delete the file or reconnect in-memory.
- **Positive**: Test suites using `LazilyRefreshDatabase` run faster without server round-trips.
- **Negative**: SQLite lacks some MySQL/PostgreSQL features (`FULL OUTER JOIN`, `CHECK`
  constraints enforced, `ALTER TABLE` limitations, concurrent write performance).
- **Negative**: Production deployments must explicitly configure MySQL/PostgreSQL — the
  `.env.example` provides templates.
- **Negative**: Schema differences between SQLite and MySQL can hide bugs until production
  (e.g., SQLite does not enforce column length limits).

## References
- `config/database.php` — SQLite default connection
- `docs/database.md`
- `docs/known-issues.md` — SQLite vs MySQL differences
- `tests/Pest.php` — `LazilyRefreshDatabase` for in-memory SQLite
