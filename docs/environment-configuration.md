# Environment Configuration: Hardening & Invariants

This document formalizes the **Environment Configuration** standards for the Internara system, standardized according to **ISO/IEC 12207**.

---

## 1. Environment Classification

Internara recognizes three authoritative environment tiers:

1.  **Local (`local`)**: Development-only. Minimal security complexity.
2.  **Staging (`staging`)**: Verification baseline. Mirrors production configuration.
3.  **Production (`production`)**: Authoritative operational environment. High hardening.

---

## 2. Configuration SSoT: The `.env` Baseline

Environment variables MUST be managed via `.env` files. No application logic should call `env()` directly; utilize `config()` wrappers.

### 2.1 Critical Invariants (Production)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY`: 32-character base64 key (Encryption Anchor).
- `SESSION_SECURE_COOKIE=true`
- `LOG_LEVEL=info`

---

## 3. Dynamic Setting Orchestration

Business-level configurations (e.g., active academic year, late thresholds) are managed via the **Setting** module.

- **Storage**: Database-backed (`settings` table).
- **Caching**: Proactive caching in Redis/File to reduce database load.
- **Helper**: Access via the global `setting('key', 'default')` helper.

---

## 4. Security & Sensitive Data

- **Secret Management**: API keys and database credentials MUST NOT be committed to source control. Use encrypted environment secrets in CI/CD.
- **PII Encryption**: The `APP_KEY` serves as the cryptographic anchor for PII encryption. Compromising this key renders all student profiles unrecoverable.
