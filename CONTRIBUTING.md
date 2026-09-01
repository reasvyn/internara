# Contributing to Internara

Thanks for your interest in contributing! This guide covers everything needed to get your first
pull request merged — from environment setup to review expectations.

---

## Code of Conduct

By participating in this project you agree to abide by
[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md). Report unacceptable behavior to
[reasvyn@gmail.com](mailto:reasvyn@gmail.com).

---

## Ways to Contribute

- **Report bugs** — open an issue with reproduction steps, expected vs. actual behavior, and the
  affected version
- **Suggest features** — open an issue describing the problem first, then the proposed solution
- **Improve documentation** — typo fixes, clarifications, and new guides are always welcome
- **Contribute code** — pick an open issue or propose one; see
  [GitHub Issues](https://github.com/reasvyn/internara/issues) for good first issues

Open an issue **before** starting significant work (new features, refactors, architecture changes)
so the approach can be discussed first.

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
composer run dev
```

`setup:install` audits your environment, runs migrations, seeds defaults, and outputs a signed
setup URL — open it in your browser to complete the 6-step wizard. Verify with
`php artisan system:health`.

Prerequisites: PHP 8.4+, Composer 2.x, Node.js 20+, npm 10+ (full list in
[`docs/getting-started.md`](docs/getting-started.md)).

---

## What to Read First

| Topic | Document |
|-------|----------|
| Architecture — 4-layer model, Action Triad | [`docs/architecture.md`](docs/architecture.md) |
| Coding conventions & invariants (C1–C8, D1–D6) | [`docs/conventions.md`](docs/conventions.md) |
| Module map | [`docs/refs/modules/index.md`](docs/refs/modules/index.md) |
| Feature specs (requirements source of truth) | [`docs/specs/index.md`](docs/specs/index.md) |

---

## Coding Standards

- `declare(strict_types=1)` in every PHP file except migrations and config
- Follow the **Action Triad** — Command (transaction + log), Read (query only), Process
  (orchestration); every Action has exactly one `execute()` method
- Business rules go in **Entities** (`final readonly`), not in Models
- Use `#[Fillable]` attribute on Models, never `$fillable`/`$guarded`
- All user-facing strings use `__()` — add keys to both `lang/en/` and `lang/id/`
- No debug calls (`dd()`, `dump()`, `ray()`, `var_dump()`, `die()`) in committed code
- Cache keys must be registered in `config/cache-keys.php`, never inline strings
- DTOs for 3+ parameters; Actions return `ActionResponse`

Run the style fixer before committing:

```bash
vendor/bin/pint --dirty --format agent
```

---

## Git Workflow

### Branch Naming

```
feat/{kebab-description}       New feature
fix/{description}              Bug fix
hotfix/{description}           Critical production fix
refactor/{module}-{scope}      Refactoring
docs/{what}                    Documentation
chore/{task}                   Maintenance, deps, tooling
```

### Commit Messages

```
type(scope): Short description

- Bullet points for details (optional)
- Reference issues: #123
```

Types: `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`

---

## Testing

Tests verify the spec — every test traces to a requirement ID (`FR-*` / `NFR-*` / `UC-*`) in
`docs/specs/{ID}-{feature}.md`. No spec requirement means no test (no orphan tests, no padding).

```bash
composer run test                                # Full test suite
vendor/bin/pest --testsuite={ModuleName}         # Single module suite
php artisan test --compact --filter={ClassName}  # Single test class
```

Test conventions:

- Location: `tests/{Module}/{SubModule}/{Name}Test.php`
- Use `LazilyRefreshDatabase` over `RefreshDatabase`; factories over model mocks
- Mock external boundaries only (HTTP, mail, queue, filesystem)

---

## Pre-commit Checklist

Run the quality gate before pushing:

```bash
composer run quality   # Lint + tests
npm run build          # For frontend changes
```

Manual checks not covered by tooling: no N+1 queries (eager loading verified), relevant docs
updated (documentation-first approach in `docs/conventions.md`).

---

## Pull Request Process

1. Ensure the quality gate passes and the checklist above is clean
2. Keep PRs focused on a single concern — no mixed refactors with features
3. Reference the related issue in the PR description
4. A maintainer will review within a few days
5. Address review feedback with additional commits (squashed on merge)

---

## AI Agent Development

Internara is developed with heavy AI-agent assistance. Agent-facing assets live in `.agents/`:
reusable skills (`.agents/skills/{name}/SKILL.md`), project memory (`.agents/context/`), and
project-wide invariants (`AGENTS.md`). When extending them: follow the existing skill structure,
reference rules in `docs/` instead of duplicating them, and update `AGENTS.md` if project-wide
invariants change.

---

## Getting Help & License

Questions? Open a [discussion](https://github.com/reasvyn/internara/discussions) or email
[reasvyn@gmail.com](mailto:reasvyn@gmail.com). Security issues follow
[SECURITY.md](SECURITY.md) — never public issues.

By contributing, you agree that your contributions will be licensed under the
[MIT License](LICENSE).
