---
name: security-audit
description: SDLC Phase: ANALYSIS. Dedicated security and privacy audit for the entire codebase. Covers OWASP Top 10, PII handling, authentication, authorization, session security, rate limiting, secrets management, and dependency vulnerabilities. Produces structured findings in [GitHub Issues](https://github.com/reasvyn/internara/issues) with actionable fix recommendations.
upstream: [audit-protocol]
downstream: [roadmap-planning, code-refactoring]
---

# Security Audit Skill

## When to Activate

Apply this skill when performing a dedicated security and privacy audit. Unlike the general
`audit-protocol` (which covers broad convention enforcement), this skill focuses exclusively on
security and privacy issues:

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `audit-protocol` — broad findings that need security deep-dive |
| | Existing codebase |
| **This skill** | **ANALYSIS (Security)** — produces [GitHub Issues](https://github.com/reasvyn/internara/issues) |
| **Downstream (output)** | `roadmap-planning` — security findings feed roadmap |
| | `code-refactoring` — fixes security issues found |
| **Phase** | [Planning] → Analysis → [Design] → [Implementation] → [Testing] → [Maintenance] |

- **OWASP Top 10 vulnerabilities** — XSS, SQLi, broken auth, mass assignment, etc.
- **PII & privacy** — data leakage, masking gaps, encryption, retention
- **Authentication & session security** — password policies, session fixation, rate limiting
- **Authorization** — RBAC enforcement, missing policy checks, privilege escalation
- **Infrastructure security** — CSP, CORS, secrets management, dependency CVEs
- **Audit trail** — missing logs, insufficient event coverage

Every finding is recorded in [GitHub Issues](https://github.com/reasvyn/internara/issues) with reproduction steps and fix recommendations.

---

## Audit Modules (Execute in Order)

Each module produces findings in [GitHub Issues](https://github.com/reasvyn/internara/issues). Modules are independent — run the relevant
ones based on scope.

```
┌──────────────────────────────────────────────────────────────┐
│  1. XSS & Injection   2. Auth & Session   3. Authorization   │
│  4. Data Privacy      5. Infrastructure   6. Dependencies    │
│  7. Audit Logging     8. Report Generation                  │
└──────────────────────────────────────────────────────────────┘
```

---

## Phase 0 — Preparation

### 0.1 Load Security Context

| Source | What to extract |
|--------|-----------------|
| `docs/conventions.md §3` | Security conventions (XSS, SQLi, mass assignment, CSRF, CSP, file upload, rate limiting) |
| `docs/foundation/rbac.md` | Role hierarchy, bypass, permission model |
| `docs/foundation/account-recovery.md` | Recovery flow security, rate limits, hashing |
| `docs/infrastructure/session.md` | Session config, security flags, regeneration |
| `docs/infrastructure/notification.md` | Notification security, SPF/DKIM/DMARC |
| `docs/infrastructure/observability.md` | Audit logging, health checks |
| `docs/architecture/logging-pattern.md` | PII masking rules |
| `docs/architecture/exception-pattern.md` | Exception information leakage |
| `.env.example` | Secret keys that must never be committed |
| [GitHub Issues](https://github.com/reasvyn/internara/issues) | Existing security findings (avoid duplicates) |

### 0.2 Baseline

```bash
composer audit 2>/dev/null                          # dependency CVEs
npm audit 2>/dev/null                               # JS dependency CVEs
php artisan test --compact --filter="Security\|Auth" # security-related tests
```

### 0.3 Initialize Findings File

Open [GitHub Issues](https://github.com/reasvyn/internara/issues). Every security finding uses this template:

```markdown
### SEC-{N} — {Severity}: {Short Description}

| Attribute | Detail |
|-----------|--------|
| **Severity** | CRITICAL / HIGH / MEDIUM / LOW |
| **Category** | xss / sqli / mass-assignment / auth / session / authorization / privacy / infrastructure / dependency / audit |
| **CWE** | {CWE-ID} |
| **File** | `{file}:{line}` |
| **Rule violated** | `docs/conventions.md §{section}` |
| **What's wrong** | Explain the vulnerability in 1-2 sentences |
| **Proof of concept** | How to reproduce or verify |
| **Fix recommendation** | Actionable steps to resolve |
| **CVSS (if applicable)** | {score} |
```

---

## Module 1 — XSS & Injection

### 1.1 Cross-Site Scripting (XSS) — CWE-79

**Rule:** `docs/conventions.md §3.1`

| Check | Command | Pass |
|-------|---------|------|
| Unescaped output in Blade | `rg '\{!!.*\$' resources/ --type blade` | 0 results, or every match has inline safety comment |
| Inline `<script>` tags | `rg '<script>' resources/ --type blade` | 0 results |
| Alpine.js `x-html` with user content | `rg 'x-html' resources/ --type blade` | Only trusted sanitized sources |
| `onclick` / `onchange` / `on*` attributes | `rg 'on\w+="' resources/ --type blade` | 0 results (use Alpine) |

**For each `{!! $var !!}` found:**
1. Trace the variable source — is it user-supplied?
2. Check for inline safety comment explaining sanitization.
3. If user-supplied without purifier → **HIGH** finding.
4. If trusted content (e.g., markdown-rendered through HTML purifier) → add safety comment if missing.

**Record findings** under `SEC-XSS-*` IDs.

### 1.2 SQL Injection — CWE-89

**Rule:** `docs/conventions.md §3.2`

| Check | Command | Pass |
|-------|---------|------|
| Raw SQL in application code | `rg 'whereRaw\|orderByRaw\|havingRaw\|selectRaw\|DB::raw' app/ --type php` | 0 results, or every match uses `?` parameterized binding AND has docblock exception |
| String concatenation in queries | `rg 'whereRaw.*\.' app/ --type php` | 0 results |
| Unsafe `DB::statement()` | `rg 'DB::statement\(' app/ --type php` | 0 results |

**For each raw SQL match:**
1. Verify parameterized binding (`->whereRaw('col = ?', [$value])`) — NOT string interpolation.
2. Check docblock for documented exception.
3. If no docblock exception → **HIGH** finding.

**Record findings** under `SEC-SQLI-*` IDs.

### 1.3 Mass Assignment — CWE-915

**Rule:** `docs/conventions.md §3.3`

| Check | Command | Pass |
|-------|---------|------|
| `create($request->all())` | `rg '::create\(\$request->all\(\)\)\|::create\(\$this->all\(\)\)' app/ --type php` | 0 results |
| `update($request->all())` | `rg '->update\(\$request->all\(\)\)\|->update\(\$this->all\(\)\)' app/ --type php` | 0 results |
| `fill($request->all())` | `rg '->fill\(\$request->all\(\)\)\|->fill\(\$this->all\(\)\)' app/ --type php` | 0 results |
| `$fillable` property (should be `#[Fillable]`) | `rg 'protected \$fillable' app/ --type php` | 0 results |
| `$guarded` property | `rg 'protected \$guarded' app/ --type php` | 0 results |

**Record findings** under `SEC-MASS-*` IDs.

### 1.4 CSV Injection — CWE-1236

| Check | Command | Pass |
|-------|---------|------|
| CSV exports escape formula chars | `rg 'csv\|export\|CsvHandler' app/ --type php` | Verify `=` `+` `-` `@` are escaped |

**Record findings** under `SEC-CSV-*` IDs.

---

## Module 2 — Authentication & Session Security

### 2.1 Authentication

**Rule:** `docs/foundation/rbac.md`, `docs/conventions.md §2`

| Check | Command / Method | Pass |
|-------|------------------|------|
| Password hashing uses bcrypt | `rg 'Hash::make\|bcrypt' app/ --type php` | Verify bcrypt, not md5/sha1/plaintext |
| Bcrypt cost ≥ 12 | `grep BCRYPT_ROUNDS .env.example` | ≥ 12 |
| Password confirmation for sensitive ops | `rg 'password\.confirm\|PasswordConfirm' routes/ --type php` | Routes exist for email change, password change, account deletion |
| Account lockout after 10 failures | `rg 'locked_at\|lockout\|tooManyAttempts' app/Auth/ --type php` | Lock mechanism present |
| "Remember me" token rotation | `rg 'recaller\|remember_token' app/ --type php` | Token rotated on re-auth |
| Session regenerated on login/logout | `rg 'regenerate' app/Auth/ --type php` | Called in login and logout actions |

**Record findings** under `SEC-AUTH-*` IDs.

### 2.2 Session Security

**Rule:** `docs/infrastructure/session.md`

| Check | Source | Pass |
|-------|--------|------|
| `SESSION_HTTP_ONLY=true` | `config/session.php` | `'http_only' => true` |
| `SESSION_SECURE_COOKIE=true` (production) | `.env` / `config/session.php` | `'secure' => env('SESSION_SECURE_COOKIE', false)` — true in production |
| `SESSION_SAME_SITE=lax` | `config/session.php` | `'same_site' => 'lax'` |
| Session lifetime ≤ 120 min | `config/session.php` | `'lifetime' => 120` |
| Session driver not `file` in production | `config/session.php` | `'driver' => env('SESSION_DRIVER', 'database')` |

**Record findings** under `SEC-SESSION-*` IDs.

### 2.3 Rate Limiting

**Rule:** `docs/conventions.md §3.7`, `docs/foundation/account-recovery.md`

| Check | Source | Pass |
|-------|--------|------|
| Login throttled (5 attempts/60s) | `bootstrap/app.php` + `LoginAction` | Rate limiter defined and applied |
| Forgot password throttled (3/email+IP/3600s) | `bootstrap/app.php` + action | Rate limiter defined |
| Password reset throttled (5/email+IP/300s) | `bootstrap/app.php` + action | Rate limiter defined |
| Recovery slip throttled (3/username+IP/300s) | `bootstrap/app.php` + action | Rate limiter defined |
| Setup wizard throttled | `config/setup.php` security section | Configurable attempts/decay |
| Auth throttle middleware registered | `bootstrap/app.php` | `'auth.throttle'` alias exists |

**Record findings** under `SEC-RATE-*` IDs.

---

## Module 3 — Authorization

### 3.1 RBAC Enforcement

**Rule:** `docs/foundation/rbac.md`, `docs/conventions.md §8`

| Check | Command | Pass |
|-------|---------|------|
| All mutation Livewire methods authorize | `rg 'public function (create\|update\|delete\|save\|toggle\|approve\|reject)' app/ --type php -A 3` | Each has `$this->authorize()` or `Gate::authorize()` |
| All state-changing routes have auth middleware | `rg "Route::" routes/web/ --type php` | POST/PUT/PATCH/DELETE routes have `auth` middleware |
| Admin routes have role middleware | `rg "role:super_admin\|admin" routes/ --type php` | Admin-only routes restricted |
| Super admin bypass defined | `config/permission.php` | `Gate::before` callback registered |

**For each missing authorization check:**
1. Determine if the method mutates data.
2. If yes, determine required permission/role.
3. Add `$this->authorize()` or `Gate::authorize()` call.

**Record findings** under `SEC-AUTHZ-*` IDs.

### 3.2 Missing Policy Checks

| Check | Command | Pass |
|-------|---------|------|
| Every Action call in Livewire has policy check in calling layer | Manual audit of Action consumers | Policy check precedes `$action->execute()` |
| Console commands check authorization | `rg 'handle\(\)' app/*/Console/ --type php -A 10` | System-critical commands have user confirmation or role check |

**Record findings** under `SEC-POLICY-*` IDs.

---

## Module 4 — Data Privacy & PII

### 4.1 PII Masking

**Rule:** `docs/architecture/logging-pattern.md §4`

| Check | Command | Pass |
|-------|---------|------|
| PII masker covers all sensitive keys | `PiiMasker.php` — check `MASKED_KEYS` array | Includes: password, token, secret, api_key, access_token, refresh_token, authorization, credit_card, card_number, cvv, ssn, national_id, birth_date, phone, email, name, ip_address, user_agent |
| Every SmartLogger call has `withPiiMasking()` | `rg 'SmartLogger::' app/ --type php -A 5` | Each has `->withPiiMasking()` or is in `BaseAction::log()` (which enables it) |
| Exception context sanitized before output | `HasExceptionContext` trait | `sanitizeContext()` called before CLI/user output |
| PII not exposed in URLs | `rg 'email=\|phone=\|ssn=\|password=' resources/ --type blade` | 0 results |

**For each missing `withPiiMasking()`:**
1. Check if the log context contains user data.
2. If yes and no `withPiiMasking()` → **MEDIUM** finding.

**Record findings** under `SEC-PII-*` IDs.

### 4.2 Encryption & Hashing

**Rule:** `docs/conventions.md §2`, `docs/foundation/account-recovery.md`

| Check | Source | Pass |
|-------|--------|------|
| Passwords hashed with bcrypt | `rg 'Hash::make\|Hash::check' app/ --type php` | All password operations use `Hash` facade |
| Recovery codes hashed | `rg 'recovery.*Hash\|Hash.*recovery' app/Auth/ --type php` | `Hash::make()` used for recovery code storage |
| Recovery key stored hashed in DB | `SaveRecoveryKeyAction.php` | DB stores hash, file stores plaintext |
| Session data encrypted | `config/session.php` | `'encrypt' => env('SESSION_ENCRYPT', true)` |
| APP_KEY set (non-default) | `config/app.php` | `'key' => env('APP_KEY')` — verified by health check |
| `.env` in `.gitignore` | `.gitignore` | `.env` listed |

**Record findings** under `SEC-CRYPTO-*` IDs.

### 4.3 Data Retention & Leakage

| Check | Command / Source | Pass |
|-------|------------------|------|
| Activity log pruned after 365 days | `config/activitylog.php` | `delete_records_older_than_days` set |
| System log rotated daily (14 days) | `config/logging.php` | Daily channel configured |
| Exceptions don't leak stack traces to users | `docs/architecture/exception-pattern.md` | Infrastructure exceptions not user-facing |
| Debug mode disabled in production | `.env.example` | `APP_DEBUG=false` |
| Error pages don't expose server info | `resources/views/errors/*.blade.php` | Custom error pages, no framework defaults |

**Record findings** under `SEC-DATA-*` IDs.

---

## Module 5 — Infrastructure Security

### 5.1 Content Security Policy

**Rule:** `docs/conventions.md §3.5`

| Check | Source | Pass |
|-------|--------|------|
| CSP header set by middleware | `app/Core/Http/Middleware/SecurityHeaders.php` | `Content-Security-Policy` header present |
| CSP is enforced (not Report-Only) | `SecurityHeaders.php` | Header name is `Content-Security-Policy`, not `-Report-Only` |
| Inline scripts blocked | CSP `script-src` directive | No `'unsafe-inline'` in script-src |
| External resources whitelisted | CSP directives | Each external domain intentional and minimal |
| CSP permits Alpine.js (hash/nonce) | CSP `script-src` | Alpine works without `'unsafe-inline'` |

**Record findings** under `SEC-CSP-*` IDs.

### 5.2 CORS

| Check | Source | Pass |
|-------|--------|------|
| Allowed origins restricted | `config/cors.php` or equivalent | Not `'*'` |
| Credentials policy set | CORS config | `'supports_credentials' => true` for authenticated endpoints |

**Record findings** under `SEC-CORS-*` IDs.

### 5.3 CSRF

**Rule:** `docs/conventions.md §3.4`

| Check | Command | Pass |
|-------|---------|------|
| All Blade forms have `@csrf` | `rg 'form\|x-mary-form' resources/ --type blade -A 5` | Each has `@csrf` or uses Livewire |
| Exempt routes justified | `bootstrap/app.php` `validateCsrfTokens(except:)` | Each exemption has code comment |
| Token-based API routes exempt | `bootstrap/app.php` | API routes don't need CSRF |

**Record findings** under `SEC-CSRF-*` IDs.

### 5.4 File Upload Security

**Rule:** `docs/conventions.md §3.6`

| Check | Command / Source | Pass |
|-------|------------------|------|
| No `Storage::put()` for user uploads | `rg 'Storage::put\(' app/ --type php` | 0 results (all uploads via MediaLibrary) |
| MIME validated server-side | Each `registerMediaCollections()` | MIME validation in collection config, not client-side |
| File names sanitized | `rg 'getClientOriginalName\|Str::slug' app/ --type php` | Filename sanitized with `Str::slug()` |
| Max file size configured | `config/media-library.php` | `max_file_size` set |
| Temporary uploads not publicly accessible | `.env` — `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` | Not `public` disk |

**Record findings** under `SEC-UPLOAD-*` IDs.

### 5.5 Secrets Management

| Check | Source | Pass |
|-------|--------|------|
| No hardcoded secrets in config files | `rg 'api_key\|password\|secret\|token' config/ --type php -i` | Values are `env()` calls, not literals |
| `.env.example` has no real secrets | `.env.example` | All values are placeholders |
| No credentials in source code | `rg 'password=\|api_key=\|secret=' app/ --type php` | 0 results |
| AWS credentials in `.env` only | `rg 'AWS_' .env.example` | In `.env`, not in code |

**Record findings** under `SEC-SECRETS-*` IDs.

---

## Module 6 — Dependency Vulnerabilities

### 6.1 Composer Dependencies

```bash
composer audit 2>/dev/null
```

- **CRITICAL/HIGH** findings → immediate fix required.
- Flag packages with known CVEs. For each:
  - Note the CVE ID, severity, and affected versions.
  - Check if a patched version is available.
  - If not, document the risk and mitigation in [GitHub Issues](https://github.com/reasvyn/internara/issues).

### 6.2 NPM Dependencies

```bash
npm audit 2>/dev/null
```

- Same process as Composer.

### 6.3 Outdated Packages

```bash
composer outdated --direct 2>/dev/null
npm outdated 2>/dev/null
```

- Flag packages >1 major version behind.
- Flag packages with no updates in >2 years (abandoned).

**Record findings** under `SEC-DEP-*` IDs.

---

## Module 7 — Audit Logging

### 7.1 Missing Audit Events

**Rule:** `docs/architecture/logging-pattern.md §7`

| Check | Command | Pass |
|-------|---------|------|
| Every Command Action calls `log()` | `rg 'BaseCommandAction\|BaseProcessAction' app/ --type php -l` | Each file has `$this->log(` |
| Every status transition logs | `rg 'status.*=.*match\|->update\(\['status'\]' app/ --type php -A 10` | Has `$this->log()` or `SmartLogger` call |
| Auth events logged (login, logout, failed) | `app/Auth/Login/Actions/LoginAction.php` | Login success, failure, lockout logged |
| Recovery events logged | `app/Auth/AccountRecovery/Actions/` | Slip generation, redemption, admin recovery logged |
| Authorization failures logged | `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php` | Unauthorized access attempts logged |

**Record findings** under `SEC-AUDIT-*` IDs.

### 7.2 Log Security

| Check | Source | Pass |
|-------|--------|------|
| Log files not web-accessible | `storage/logs/` location | Outside `public/` |
| Logs don't contain plaintext passwords | PII masker covers `password` key | Automatic masking via `MASKED_KEYS` |
| Activity log failure is graceful | `docs/architecture/logging-pattern.md §9` | Try-catch around activity log write |

**Record findings** under `SEC-LOG-*` IDs.

---

## Module 8 — Report Generation

### 8.1 Consolidate Findings

1. Remove duplicates (same issue found by multiple modules).
2. Sort by severity (CRITICAL → HIGH → MEDIUM → LOW).
3. Group by category for readability.
4. Update [GitHub Issues](https://github.com/reasvyn/internara/issues) header with latest audit date.

### 8.2 Summary Statistics

```markdown
## Security Audit Summary — {date}

| Severity | Count |
|----------|-------|
| CRITICAL | N |
| HIGH     | N |
| MEDIUM   | N |
| LOW      | N |
| **Total** | **N** |

### By Category
| Category | Count |
|----------|-------|
| XSS | N |
| SQL Injection | N |
| Mass Assignment | N |
| Auth / Session | N |
| Authorization | N |
| PII / Privacy | N |
| Infrastructure | N |
| Dependencies | N |
| Audit Logging | N |
```

### 8.3 Remediation Priority

| Severity | Action | Timeline |
|----------|--------|----------|
| CRITICAL | Fix immediately, deploy hotfix | Within 24 hours |
| HIGH | Schedule in current/next sprint | Within 1 week |
| MEDIUM | Add to roadmap backlog | Within 1 month |
| LOW | Track, fix during refactoring | Next opportunity |

### 8.4 Final Verification

```bash
php artisan test --compact
vendor/bin/pint --format agent
vendor/bin/phpstan analyse --no-progress
composer audit 2>/dev/null
```

---

## References

| Document | Purpose |
|----------|---------|
| `docs/conventions.md §3` | Security conventions (XSS, SQLi, mass assignment, CSRF, CSP, file upload, rate limiting) |
| `docs/foundation/rbac.md` | Authorization model, roles, policies |
| `docs/foundation/account-recovery.md` | Recovery flow security |
| `docs/infrastructure/session.md` | Session security configuration |
| `docs/infrastructure/notification.md` | Notification channel security |
| `docs/infrastructure/observability.md` | Audit logging, health checks |
| `docs/architecture/logging-pattern.md` | PII masking rules |
| `docs/architecture/exception-pattern.md` | Exception information leakage |
| [GitHub Issues](https://github.com/reasvyn/internara/issues) | Findings target |
| `AGENTS.md` | Project invariants |
| `.agents/skills/audit-protocol/SKILL.md` | General codebase audit (upstream) |
| `.agents/skills/code-refactoring/SKILL.md` | Fix implementation after audit |
