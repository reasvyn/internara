# Software Development Lifecycle (SDLC)

This document defines the **Software Development Lifecycle (SDLC)** of Internara as a product.
It describes how the system evolves from concept to formally released artifacts, independent of
day-to-day development workflows or implementation techniques.

The SDLC exists to ensure that every release is **traceable, classifiable, and governable**.

---

## 1. SDLC Scope & Principles

Internara’s SDLC governs:

* **When** a version is considered releasable
* **How** a version is classified after release
* **What guarantees** (if any) apply to a released artifact

The SDLC explicitly **does not** define:

* Coding practices
* Development workflows
* Branching or environment strategies
* Tooling or implementation details

---

## 2. Lifecycle Phases

These phases describe the **product-level evolution** of a version, not engineering activities.

### 2.1 Conceptualization

A version begins as a **conceptual milestone**, defined by intended business value or architectural
direction.

* Scope is identified
* Version lineage (Series Code) is established
* No release artifact exists yet

**Output**: A defined version intent
**Status**: `Planned`

---

### 2.2 Construction

The version is under active construction toward its intended scope.

* Features may be incomplete
* Internal iteration is expected
* The version is not yet considered a published artifact

**Output**: An internally coherent system state
**Status**: `In Progress`

---

### 2.3 Qualification

The version reaches **scope closure**.

* Intended features for the milestone are complete
* The system is internally consistent
* Documentation accurately reflects behavior

This phase determines **eligibility for release**, not stability.

**Output**: A releasable artifact candidate
**Status**: `Stabilizing`

---

### 2.4 Release

The version is formally published as a **versioned artifact**.

* The version is tagged
* Release notes and analytical documentation are finalized
* The artifact becomes externally referable

Release does **not** imply:

* Production readiness
* Long-term support
* Absence of defects

**Output**: A published version
**Status**: `Released`

---

### 2.5 Post-Release Lifecycle

After release, the version transitions through post-publication states:

| State      | Meaning                                |
| ---------- | -------------------------------------- |
| Active     | Still relevant and possibly referenced |
| Deprecated | Discouraged for continued use          |
| Archived   | Preserved for historical reference     |
| EOL        | No longer maintained                   |

These states reflect **product policy decisions**, not technical quality.

---

## 3. Lifecycle Classification Axes

Every released version is classified across **three orthogonal axes**.

### 3.1 Stage (Maturity)

Describes the **quality and stability expectation** of the artifact.

* Experimental
* Alpha
* Beta
* Release Candidate (RC)
* Stable
* LTS

---

### 3.2 Support Policy

Defines **what guarantees apply after release**.

* Snapshot (no guarantees)
* Bugfix Only
* Security Only
* Full Support
* EOL

---

### 3.3 Status

Represents the **current operational state** of the version.

* Planned
* In Progress
* Stabilizing
* Released
* Deprecated
* Archived

> These axes are independent and may change without altering the version identifier.

---

## 4. Release Eligibility (SDLC Perspective)

From an SDLC standpoint, a version is eligible for release when:

1. The intended milestone scope is internally complete
2. The system state is coherent and documented
3. The artifact can be uniquely identified and referenced

No requirement exists for:

* Feature completeness across the product
* Stability guarantees
* Backward compatibility

Those concerns are expressed via **Stage** and **Policy**, not release permission.

---

## 5. SDLC Artifacts

The SDLC is reflected through the following artifacts:

* **Version Identifier** (SemVer)
* **Series Code** (lineage context)
* **Analytical Version Notes** (as-built narrative)
* **Versions Overview** (Single Source of Truth for lifecycle classification)

---

*Internara’s SDLC treats releases as **governed product milestones**, not accidental byproducts of
development. This separation enables clarity, historical integrity, and scalable project growth.*
