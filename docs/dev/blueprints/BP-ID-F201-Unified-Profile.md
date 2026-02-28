# Blueprint: Unified Profile (BP-ID-F201)

**Blueprint ID**: `BP-ID-F201` | **Requirement ID**: `SYRS-F-201` | **Scope**: Identity & Security

---

## 1. Context & Strategic Intent

This blueprint defines the single-profile mapping for NISN/NIP and the protection of Sensitive Personal Information (PII). It ensures that every user has a unique institutional identity secured by encryption.

---

## 2. Technical Implementation

### 2.1 PII Protection (S1 - Secure)
- **Field Encryption**: Sensitive fields (Phone, Address, National ID) MUST be stored using Laravel's `encrypted` cast.
- **Zero-Exposure**: PII MUST NOT be written to plain-text system logs.

### 2.2 Atomic Mapping (S2 - Sustain)
- **One-to-One**: Every `User` record MUST be atomically linked to exactly one `Profile` record.
- **UUID Identity**: Both models MUST use UUID v4 as their primary identifier.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Encryption Audit**: Verify that raw database queries return ciphertext for `phone` and `national_identifier`.
    - **IDOR Protection**: Verify that a user cannot update another user's profile UUID.
- **Unit (`Unit/`)**:
    - **Hydration Audit**: Verify that sensitive fields are automatically decrypted when the Model is retrieved.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Architectural (`arch/`)**:
    - **Identity Standards**: Verify all Identity models use `HasUuid` trait.
    - **Namespace Rules**: Ensure Models are in `Modules\*\Models` and use property hooks for hooks.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Modular Sovereignty**: Ensure only `User` and `Profile` modules contain identity-related Eloquent models.
- **Feature (`Feature/`)**:
    - **Atomic Creation**: Verify that a transaction rollback on Profile creation correctly rolls back the User record.
    - **N+1 Audit**: Verify that loading user-profiles for 50 records uses only 2 queries.

---

## 4. Documentation Strategy
- **Governance Standards**: Update `docs/dev/governance.md` to document the PII protection invariants and the single-profile strategy.
- **Developer Guide**: Update `modules/Profile/README.md` to list encrypted fields and the atomic relationship with the `User` model.
- **Privacy Audit**: Ensure the `PiiMaskingProcessor` configuration is documented in `docs/dev/security.md`.
