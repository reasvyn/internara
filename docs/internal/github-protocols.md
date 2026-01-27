# GitHub Protocols: Engineering Collaboration Standards

This document defines the authoritative standards for GitHub operations within the Internara
project. It ensures that all collaboration is traceable, secure, and aligned with our **Modular
Monolith** architecture and **Version Management Guide**.

> **Governance Mandate:** All GitHub operations must support the requirements defined in the
> **[Internara Specs](internara-specs.md)** and follow the
> **[Software Lifecycle](software-lifecycle.md)**.

---

## 1. Commit Protocols

Internara uses **Conventional Commits** to ensure a readable and automated Git history.

### 1.1 Commit Message Format

Each commit message must consist of a **header**, an optional **body**, and an optional **footer**.

```text
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

- **Types**:
    - `feat`: A new feature (corresponds to a Minor version change).
    - `fix`: A bug fix (corresponds to a Patch version change).
    - `refactor`: Code change that neither fixes a bug nor adds a feature.
    - `docs`: Documentation only changes.
    - `style`: Changes that do not affect the meaning of the code (white-space, formatting).
    - `test`: Adding missing tests or correcting existing tests.
    - `chore`: Changes to the build process or auxiliary tools.
- **Scope**: The name of the module (e.g., `user`, `setup`, `internship`) or `core`/`shared`.
- **Description**: Short, imperative mood, no period at the end.

### 1.2 Referencing Issues

Always link commits to issues where applicable:

- Use `Ref #123` for general references.
- Use `Closes #123` or `Fixes #123` to trigger auto-closure upon merging to `main`.

---

## 2. Repository & Branching Strategy

We follow a modified **Git Flow** strategy. All code must pass through the standard integration
pipeline before reaching production.

### 2.1 Branch Types

- **`main`**: The stable production branch. Only receives merges from `develop` or `hotfix/*`.
- **`develop`**: The integration branch for the next release.
- **`feature/{module}/{desc}`**: For new features (e.g., `feature/user/oauth-login`).
- **`fix/{module}/{desc}`**: For bug fixes (e.g., `fix/core/uuid-collision`).
- **`release/{version}`**: For final stabilization and documentation sync before a release.
- **`hotfix/{desc}`**: For critical production fixes targeting `main`.

---

## 3. Tagging Standards

Tags represent immutable snapshots of the application state.

### 3.1 Format

Tags must follow **SemVer** with a `v` prefix and mandatory stage suffix for non-stable releases:

- **Pre-release**: `v0.9.0-alpha`, `v0.9.0-beta.1`
- **Stable**: `v1.0.0`

### 3.2 Protocol

- **Sabuk Pengaman (Safety Belt)**: Suffixes are required for any release not considered stable to
  prevent accidental production deployment.
- **Annotated Tags**: Always use annotated tags for releases (`git tag -a`) to include metadata.
- **Immutability**: Once a tag is pushed, it must **NEVER** be moved or deleted unless for
  catastrophic correction (requires Lead approval).

---

## 4. GitHub Releases

Releases turn technical tags into user-facing artifacts.

### 4.1 Naming Convention

The Release Title must include the version, stage, and the theme from the release notes:

- **Format**: `v{Version}-{Stage} ({Theme})`
- **Example**: `v0.9.0-alpha (System Initialization)`

### 4.2 Release Content

1. **Source**: Use the content from the stable release note file: `docs/versions/vX.Y.Z.md`.
2. **Maturity Label**: Mark as **Pre-release** if the stage is Alpha, Beta, or RC.
3. **Drafting**: Use the `gh release create` command to automate creation from local files.

### 4.3 CLI Operation

```bash
gh release create v0.9.0-alpha --title "v0.9.0-alpha (System Initialization)" --notes-file docs/versions/v0.9.0.md --prerelease
```

---

## 5. Issue & Label Management

Issues are the primary units of work and discussion.

### 5.1 Labels

We use a two-tier labeling system:

1.  **Type Labels**: `enhancement`, `bug`, `technical-debt`, `documentation`.
2.  **Series Labels**: `Series: ARC01-{CODE}` (e.g., `Series: ARC01-BOOT`). These map 1:1 with the
    Series defined in **Application Blueprints**.

### 5.2 Naming Issues

Issue titles should include the target milestone for quick scanning:

- `[v0.9.0] Automated Installer CLI (app:install)`

### 5.3 Backlog Prioritization (MoSCoW Framework)

Every issue in the Backlog must be assigned a priority to guide execution velocity:

1.  **Must-Have (P0)**: Critical requirements. The release cannot be successful without these.
2.  **Should-Have (P1)**: Important but not vital. These are high-priority but can be deferred to a
    patch release if absolutely necessary.
3.  **Could-Have (P2)**: Desirable "Nice-to-have" features. Implementation depends on remaining time
    within the milestone.
4.  **Won't-Have (P3)**: Lowest priority or out of scope for the current series. Documented for
    future series roadmap.

---

## 6. Native Milestones

GitHub Milestones are the authoritative tool for tracking release progress.

### 6.1 Mapping

- Milestones must correspond 1:1 with the versions defined in `docs/versions/`.
- **Title**: `vX.Y.Z` (e.g., `v0.9.0`).
- **Description**: Summary of the strategic theme (from the Blueprint).

### 6.2 CLI Operation

```bash
# Create milestone via API
gh api repos/:owner/:repo/milestones -f title="v0.10.0" -f description="Integrative Excellence theme"
```

---

## 7. Pull Requests (PRs)

PRs are the gatekeepers of code quality.

### 7.1 PR Requirements

- **Atomic**: One PR per feature or fix.
- **Verified**: Must pass all automated tests (`composer test`) and linting (`composer lint`).
- **Documented**: Any structural change must include updated `.md` documentation.
- **Isolation**: Changes in Module A must not break Module B's isolation boundaries.

---

## 8. GitHub Projects & Automation

GitHub Projects is used for high-level roadmap visualization and tactical task tracking.

- **Status Mapping**:
    - `Planned` (Blueprint) -> `Backlog` or `Ready` (Project)
    - `In Progress` (Construction) -> `In Progress` (Project)
    - `Preview` (Demonstration) -> `In Review` (Project)
    - `Released` (Closure) -> `Done` (Project)

---

## 10. Full Synchronization Sweep (Remote & Local)

To ensure zero drift between the local environment and GitHub, a **Full Synchronization Sweep** must
be performed regularly, especially before and after major development milestones.

### 10.1 Phase A: Preparatory Fetch (Remote → Local)

Synchronize technical snapshots and remote state:

1.  **Fetch All**: `git fetch --all --tags --prune`
    - _Goal_: Align local knowledge of remote branches and tags.
2.  **Verify Tags**: Compare `git tag -l` with `gh release list`.
    - _Action_: Delete any local tags that do not exist on remote (unless they are new and
      unpushed).

### 10.2 Phase B: Artifact & Release Sync (Local → Remote)

Ensure user-facing documentation matches remote artifacts:

1.  **Content Audit**: Compare local `docs/versions/vX.Y.Z.md` with the corresponding GitHub Release
    body.
2.  **Update Command**: If local documentation has evolved, sync the remote release:
    ```bash
    gh release edit v0.9.0-alpha --notes-file docs/versions/v0.9.0.md --title "v0.9.0-alpha (Theme)"
    ```
3.  **Maturity Check**: Ensure `pre-release` flags on GitHub correctly reflect the **Stage** defined
    in local metadata.

### 10.3 Phase C: Tactical Metadata Sync (Issues & Milestones)

Align the project's strategic state:

1.  **Milestone Audit**: Ensure the number of native Milestones matches the number of version files
    in `docs/versions/`.
2.  **Issue Status**: Sync Issue states (`Open`/`Closed`) with GitHub Project columns (`Todo` to
    `Done`).
3.  **Label Consistency**: Ensure all Issues have the correct `Series: ARC01-XXXX` label for
    blueprint traceability.

---

_By adhering to these protocols, we maintain a high-velocity, high-quality engineering environment
that honors the integrity of the Internara system._
