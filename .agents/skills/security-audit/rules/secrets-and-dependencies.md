# Secrets, Configuration & Dependencies — Supply-Chain Hygiene

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

This rule covers the two quiet leak channels: the **config** surface (secrets that end up in code,
git, or `.env` misconfigurations) and the **dependency** surface (known-vulnerable or abandoned
packages). Neither shows up in functional QA, and both fail silently — a leaked `APP_KEY` or an
outdated package is invisible until it is exploited.

## Rationale

- **A hardcoded secret is compromised the moment the repo is shared.** `APP_KEY`, database
  credentials, API keys, or recovery secrets in code/config files cannot be un-leaked by rotation
  discipline alone — and `.env` committed to git makes every clone a credential dump. `APP_KEY`
  specifically: if two installations share one, session cookies and encrypted data are decryptable
  across both.
- **Dependencies are the largest unmanaged attack surface.** A known CVE in `composer.lock` is
  exploitable in production even though the code is "written fine"; an abandoned package has security
  issues that will never be fixed. Automated scanning (`composer audit`, `npm audit`) only helps if
  it runs — hence the `dependabot.yml` check.
- **Config and secrets audit together because they fail together** — the classic leak is not a
  password in a `.php` file but a `.env` that made it to git, or a config value that read from a
  committed default instead of the environment.

## How to Apply — Secrets & Configuration

- **No hardcoded secrets in code or config files.** Scan source for literal credentials, keys, and
  tokens.
- **`.env` excluded from version control** — check `.gitignore`; the file must never appear in the
  repo. (A previously-committed `.env` in git history is a finding even if removed after.)
- **`APP_KEY` must be unique per installation.** Two environments sharing a key means encrypted
  session/cookie cross-decryption. Recommend a fresh `php artisan key:generate` per deployment.
- **Database credentials in `.env` only** — never defaulted in `config/database.php` or committed
  example files with real values.

## How to Apply — Dependencies

- **Check `composer.json` / `composer.lock` for known vulnerabilities:**

  ```bash
  composer audit 2>&1
  npm audit 2>&1
  ```

- **Verify package versions are current** — `composer outdated` / `npm outdated`; flag abandoned
  packages (no maintainer, no recent release, unresolved security issues).
- **Check `dependabot.yml` for automated scanning** — automated dependency alerts exist; a missing or
  disabled config means vulnerabilities rely on manual memory.

## Examples

```bash
# .gitignore (must contain the entry — check it exists)
.env
.env.backup

# Confirming the key is not committed
git ls-files | rg '^\.env'
```

```bash
# Dependency sweep
composer audit  --format=json   # advisory IDs, severities, fixes
npm audit                       # incl. transitive dependency findings
```

## Anti-Patterns & Pitfalls

- **Secrets in example files** — `composer.json` `extra` blocks, `config/*.php` defaults, seeders,
  and `.env.example` with real credentials "for convenience".
- **`APP_KEY` copied across environments** to "save setup time" — cross-install decryption is the
  cost.
- **Pinning `*` or unbounded versions** — `"laravel/framework": "*"` or `>=8` without upper bound
  silently adopts breaking or vulnerable changes.
- **Ignoring abandoned-but-working packages** — no CVE today, no fixes tomorrow; record replacement
  evaluation.
- **Infrequent audits** — scanning once and never again; dependency health changes every week.

## Verification & Detection

```bash
# Secrets search in source and config
rg -n "(password|secret|api_key|token)\s*=>?\s*['\"][^'\"]{4,}" app/ config/ --include="*.php" | rg -v "env\(|__\("

# .env tracked by git — must return nothing
git ls-files | rg '(^|/)\.env($|\.)'

# Dependency vulnerability scan
composer audit 2>&1
npm audit 2>&1

# Automated scanning configured
test -f .github/dependabot.yml && echo "dependabot present" || echo "MISSING: dependabot.yml"
```