# Development Workflow

This document serves as the **Standard Operating Procedure (SOP)** for all development activities within the Internara project. It integrates high-level version planning with the specific technical steps required to build features in our Modular Monolith architecture.

**The Golden Rule:** Never start coding without a plan. Never finish without verification.

---

**Table of Contents**

- [1. Phase 1: Pre-Development (Context & Planning)](#1-phase-1-pre-development-context--planning)
- [2. Phase 2: Development Execution (Implementation)](#2-phase-2-development-execution-implementation)
    - [Step 1: Create the Module](#step-1-create-the-module)
    - [Step 2: Create Model & Migration](#step-2-create-model--migration)
    - [Step 3: Create Service (Logic)](#step-3-create-service-logic)
    - [Step 4: Create UI (Livewire)](#step-4-create-ui-livewire)
    - [Step 5: Define Routes](#step-5-define-routes)
- [3. Phase 3: Post-Development (Verification)](#3-phase-3-post-development-verification)
    - [3.1 Testing (Pest)](#31-testing-pest)
    - [3.2 Security & Privacy Audit](#32-security--privacy-audit)
    - [3.3 Code Quality & Linting](#33-code-quality--linting)
    - [3.4 Documentation](#34-documentation)

---

## 1. Phase 1: Pre-Development (Context & Planning)

Before writing a single line of code, you must establish the "Why" and "What".

### 1.1. Review Previous Version Context
- **Action:** Read the documentation of the immediately preceding version (e.g., if working on v0.5, read v0.4).
- **Goal:** Understand the existing architectural constraints and "finished" state to avoid regressions.
- **Check:** Are there breaking changes in the previous version that affect my new feature?

### 1.2. Define Current Version Scope
- **Action:** Refer to the **Current Version Document** (e.g., `docs/versions/v0.5.x-alpha.md`).
- **Goal:** Identify the specific **Keystone** you are working on.
- **Check:**
    - What are the **Architectural Constraints** for this version? (e.g., "No raw CSS", "Strict Typing").
    - What is the specific **Goal** of this Keystone?

---

## 2. Phase 2: Development Execution (Implementation)

Once the plan is clear, proceed with the implementation using the **Modular Monolith Standard Workflow**.

### Core Philosophy
- **Isolation:** Modules must not depend on other modules' concrete classes. Use Interfaces.
- **Layering:** UI -> Service -> Model.
- **Namespace:** Omit `src` (e.g., `Modules\User\Services`, not `Modules\User\src\Services`).

### Step 1: Create the Module
If the feature requires a new business domain, generate a module.
```bash
php artisan module:make Journal
```

### Step 2: Create Model & Migration
Define the data structure. Remember: **No cross-module foreign keys**. Use UUIDs if portability is required.
```bash
php artisan module:make-model JournalEntry Journal --migration
```

### Step 3: Create Service (Logic)
Encapsulate business logic. Always define an **Interface** (Contract) first.

**1. Create Interface:**
```bash
php artisan module:make-interface Services/Contracts/JournalService Journal
```

**2. Create Implementation:**
Extend `EloquentQuery` for standard CRUD capabilities.
```bash
php artisan module:make-service JournalService Journal
```
*Tip: Inject the Model in the Service constructor using `$this->setModel(new JournalEntry());`.*

### Step 4: Create UI (Livewire)
Create the interaction layer. Use the `UI` module components (`<x-ui::button>`) strictly.
```bash
php artisan module:make-livewire CreateJournalEntry Journal --view
```

### Step 5: Define Routes
Expose the feature in `modules/{Module}/routes/web.php`.
```php
Route::get('/journal/create', CreateJournalEntry::class)->middleware(['auth']);
```

---

## 3. Phase 3: Post-Development (Verification)

A feature is not "Done" until it passes all four verification gates.

### 3.1 Testing (Pest)
- **Requirement:** Every Service method and Livewire component must have a corresponding test.
- **Command:** `php artisan test --filter=Journal`
- **Standard:** Aim for high coverage on "Happy Path" and "Edge Cases" (e.g., unauthorized access).

### 3.2 Security & Privacy Audit
- **Security Check:**
    - **IDOR:** Can User A see User B's data? (Verify Policy logic).
    - **XSS:** Is all user output escaped?
    - **Authorization:** Does every route have middleware?
- **Privacy Check:**
    - **PII:** Are sensitive fields (phone, email) protected?
    - **Leakage:** Are strict visibility modifiers (`protected`/`private`) used?

### 3.3 Code Quality & Linting
Ensure the code is clean, consistent, and follows project standards.
- **Command:** `composer lint` (Runs Pint and Prettier).
- **Check:** No unused imports, correct typing, standard indentation.

### 3.4 Documentation
"Documentation is Code". Update the artifacts.
- **Update:** `docs/versions/{current-version}.md` (Mark Keystone as checked).
- **Update:** `CHANGELOG.md` (Add entry under Unreleased).
- **Update:** Module README or specific guides if a new pattern was introduced.

---

**Navigation**

[← Previous: Software Development Life Cycle](software-lifecycle.md) |
[Next: Role & Permission Management Guide →](role-permission-management.md)
