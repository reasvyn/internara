# Contributing to Internara

Thank you for your interest in contributing. This document covers the practical workflow — from
setting up your environment to getting your changes merged.

---

## Before You Start

1. **Read the docs.** Start with [`docs/architecture.md`](docs/architecture.md) and
   [`docs/conventions.md`](docs/conventions.md) to understand the project's patterns.
2. **Check existing issues.** Look for open or closed issues related to your change at
   [github.com/reasvyn/internara/issues](https://github.com/reasvyn/internara/issues).
3. **Open an issue first** for significant changes (new features, refactors, architecture changes)
   to discuss the approach before writing code.

---

## Development Setup

```bash
git clone https://github.com/reasvyn/internara.git
cd internara
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan setup:install
```

The `setup:install` command audits your environment, runs migrations, seeds defaults, and outputs a
signed setup URL. Open it in your browser to complete the 6-step wizard.

---

## Coding Standards

- `declare(strict_types=1)` in every PHP file except migrations and config
- Follow the **Action Triad** — Command (transaction+log), Read (query only), Process
  (orchestration). Every Action has exactly one `execute()` method
- Business rules go in **Entities** (`final readonly`), not in Models
- Use `#[Fillable]` attribute on Models, not `$fillable`/`$guarded`
- All user-facing strings use `__()` — add keys to both `lang/en/` and `lang/id/`
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `die()` in committed code
- Cache keys must be registered in `config/cache-keys.php` — never inline strings
- DTOs for 3+ params, `ActionResponse` for structured returns

Run the linter before committing:

```bash
vendor/bin/pint --dirty --format agent
```

---

## Branch Naming

```
feat/{kebab-description}       New feature
fix/{description}              Bug fix
hotfix/{description}           Critical production fix
refactor/{module}-{scope}      Refactoring
docs/{what}                    Documentation
chore/{task}                   Maintenance, deps, tooling
```

---

## Commit Messages

```
type(scope): Short description

- Bullet points for details (optional)
- Reference issues: #123
```

Types: `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`

---

## Pre-commit Checklist

- [ ] `declare(strict_types=1)` present
- [ ] No debug calls (`dd/dump/ray/var_dump/print_r/die`)
- [ ] Action uses the correct triad base class
- [ ] Business rules delegated to Entity (not inline)
- [ ] DTO used for 3+ params; `ActionResponse` for structured returns
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] No N+1 queries — eager loading verified
- [ ] All user-facing strings use `__()` helper
- [ ] New/changed behavior has corresponding tests
- [ ] `vendor/bin/pint --dirty --format agent` — clean
- [ ] `vendor/bin/phpstan analyse --no-progress` — passes
- [ ] `php artisan test --compact` — all tests pass
- [ ] Relevant docs updated (see documentation-first approach in `docs/conventions.md`)

---

## Testing

```bash
composer run test              # Full test suite
composer run test:feature      # Feature tests only
composer run test:unit         # Unit tests only
composer run analyse           # PHPStan static analysis
```

Every Action must have its own test file. Follow the existing test structure:

```
tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php
```

- Use `LazilyRefreshDatabase` over `RefreshDatabase`
- Use `assertModelExists()` over `assertDatabaseHas()`
- Never mock Eloquent models — use factories + real database
- Mock external boundaries only (HTTP, mail, queue, filesystem)

---

## Pull Request Process

1. Ensure the pre-commit checklist is complete
2. Keep PRs focused on a single concern — no mixed refactors with features
3. Reference the related issue in the PR description
4. A maintainer will review within a few days
5. Address review feedback with additional commits (they'll be squashed on merge)

---

## AI Agent Development

Internara uses AI agents extensively. The project includes:

- **Skills (.agents/skills/)** — Reusable workflows for tasks like code refactoring, testing, and auditing
- **Agent rules (AGENTS.md)** — Project invariants and behavioral guidelines
- **MCP server integration** — Laravel Boost MCP server for database schema, error logs, and doc search

When creating new agents or updating agent configuration:

1. Add new skills to `.agents/skills/{name}/SKILL.md`
2. Follow the existing skill structure (description, workflow, rules, references)
3. Avoid duplicating rules that already exist in `docs/` — reference them instead
4. Update `AGENTS.md` if project-wide invariants change

### MCP Server Resources

The Laravel Boost MCP server exposes:

| Resource | Purpose |
| -------- | ------- |
| Application info | PHP/Laravel versions, installed packages |
| Database schema | Table structures, columns, indexes, FKs |
| Error logs | Read last N entries from application log |
| Browser logs | Read last N entries from browser console |
| Documentation search | Version-specific Laravel ecosystem docs |

For new MCP capabilities, update `boost.json` and document them in `docs/infrastructure/`.

---

## Questions?

Open a [discussion](https://github.com/reasvyn/internara/discussions) or email
[reasvyn@gmail.com](mailto:reasvyn@gmail.com).
