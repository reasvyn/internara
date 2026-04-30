# Todo: Resolve Legacy Module Dependency & Fix Checklist Accuracy

**Source:** `.agents/issues/2026-04-30-checklist-verification-audit.md`
**Created:** 2026-04-30
**Priority:** P0 + P1
**Assigned to:** Engineer Agent
**Estimated scope:** ~2-4 hours depending on module cleanup approach

---

## Step 1 — Resolve Legacy Module Dependency (P0)

**Problem:** Tests cannot execute. `./vendor/bin/pest` fails immediately with:
```
Trait "Modules\Core\Academic\Models\Concerns\HasAcademicYear" not found
```
Root cause: `modules/` directory (29 modules, 1,142 PHP files) is still autoloaded but incomplete/broken. `app/Console/Kernel.php` still imports `Modules\Status\Services\Jobs\DetectIdleAccountsJob`.

**Decision needed from human:** Choose ONE approach:

### Option A — Remove modules entirely (Recommended)
- Remove `modules/` directory
- Remove `config/modules.php` and `config/modules-livewire.php`
- Remove `nwidart/laravel-modules` from `composer.json` if present
- Remove `vite-module-loader.js` if present
- Replace `app/Console/Kernel.php` reference with equivalent in-app code or remove the scheduled job
- Run `composer dump-autoload`
- Verify tests can execute: `./vendor/bin/pest --list-tests`

### Option B — Fix module autoloading
- Identify what `Modules\Core\Academic\Models\Concerns\HasAcademicYear` is supposed to provide
- Either restore the missing trait or update the test file to not reference it
- Verify `app/Console/Kernel.php` import resolves correctly
- Run `composer dump-autoload`
- Verify tests can execute: `./vendor/bin/pest --list-tests`

**Exit criterion:** `./vendor/bin/pest --list-tests` succeeds without fatal errors.

**Acceptance:**
- [ ] No fatal errors when running pest
- [ ] `./vendor/bin/pest --list-tests` returns a valid test list
- [ ] No references to broken `Modules\` namespace remain in `app/`

---

## Step 2 — Verify or Remove Author Signature Claim (P1)

**Problem:** Checklist claims `[v] [v] [v] Author signature protection (fatal error on mismatch)` but `app/Livewire/Layout/AppSignature.php` is only a display component — no fatal error enforcement exists.

**Action:**
- Check if there is any author signature verification elsewhere in the codebase:
  ```bash
  grep -r 'author_signature\|fatal.*mismatch\|signature.*verify' app/ --include='*.php'
  ```
- **If no enforcement code exists:** Update the checklist entry to reflect reality: `[v] [!] [v]` — display exists, no enforcement
- **If enforcement code is found elsewhere:** Document where it is and update the checklist with the correct location

**Exit criterion:** Checklist entry accurately reflects what exists (either enforcement confirmed, or claim corrected).

**Acceptance:**
- [ ] Search completed
- [ ] Checklist entry `[v] [!] [v]` confirmed OR enforcement code located and documented

---

## Step 3 — Run Tests and Update Verification Summary (P1)

**Problem:** Checklist Verification Summary contains stale/unverifiable test statistics.

**Action:** (Run this AFTER Step 1 is complete)
- Run full test suite: `./vendor/bin/pest`
- Record actual results:
  - Total tests passed
  - Total tests failed
  - Total tests skipped
  - Total assertions
- Run architectural tests: `./vendor/bin/pest tests/Arch`
- Run quality tests: `./vendor/bin/pest tests/Quality`
- Update `.agents/KEY_FEATURES_CHECKLIST.md` Verification Summary with actual numbers
- For any failed tests: create a separate issue in `.agents/issues/` with reproduction details

**Exit criterion:** Verification Summary contains current, verified test statistics.

**Acceptance:**
- [ ] `./vendor/bin/pest` completes with output captured
- [ ] Pass/fail/skip/assertion counts recorded
- [ ] `KEY_FEATURES_CHECKLIST.md` Verification Summary updated with real numbers
- [ ] Failed tests documented as separate issues (if any)

---

## Step 4 — Review and Clean Remaining "NEEDS REVIEW" Items (P2, Optional)

**Problem:** Several checklist entries are marked `[?]` (Needs Review) and may have inaccurate statuses.

**Only proceed if time permits and human approves.** Review each:

| Checklist Line | Entry | Action |
|---------------|-------|--------|
| 40 | Optional Layers (Repositories, Events, Services) | Verify actual counts: Repos(1), Events(1), Listeners(1), Services(2) — confirm status |
| 44 | Cache and Session | Verify config files exist and are functional |
| 45 | File System and Static Assets | Verify filesystems.php and Spatie Media Library configured |
| 46 | System and user notification | Verify Notification model, actions, and UI exist |
| 68 | RBAC roles and permissions | Run query: actual role/permission counts in database |
| 69 | User dashboard and managerial stats | Check if dashboard components exist and are functional |
| 70 | Admin/student/teacher/mentor management | Verify all 4 manager Livewire components work |
| 71 | User authentication and authorization | Verify login flow and role middleware |

**Exit criterion:** Each reviewed item has its status updated to accurate `[v]`, `[*]`, `[+]`, or `[!]`.

**Acceptance:**
- [ ] Each `[?]` item either confirmed `[v]` or corrected to accurate status
- [ ] Changes documented in updated checklist

---

## Delegation Notes for Engineer Agent

- **Start with Step 1** — nothing else can be verified until tests can run
- **Do not refactor, rewrite, or "improve"** any module code — only remove or fix the broken reference
- **Keep changes minimal** — the goal is to unblock testing, not to complete the module migration
- **Report back** after each step with: what was done, what was found, any blockers
- **Do NOT run destructive operations** (deleting directories, removing packages) without explicit confirmation from human
- After completing all steps, create a summary file in `.agents/todo/` marking this todo as `[CLOSED]` with completion notes
