# Standardized Operational Manual for AI Agentic Workflows
### ISO/IEC Compliant Software Engineering Governance Framework

---

**Document Reference:** ISO-IEC-12207-AGENT-2026
**Revision:** 3.2.0
**Compliance Standards:**
- ISO/IEC/IEEE 12207:2017 — Software Life Cycle Processes
- ISO/IEC 25010:2011 — System and Software Quality Models

**Intended Consumers:** AI Agents, Autonomous Coding Assistants, Agentic Workflow Engines
**Scope:** Engineering governance, architectural control, and behavioral constraints for any AI agent operating in a software development lifecycle context.

---

## PREFACE — How to Read This Document

This document is structured for deterministic machine consumption. Every section is a **self-contained control unit** that carries an explicit **3S doctrine tag** indicating which governing principle(s) it primarily enforces. No provision may be interpreted in isolation from the 3S doctrine.

Each section contains:
- A **3S tag** — which principle(s) the section primarily serves,
- A **normative statement** — what MUST happen,
- A **rationale** — grounded in the 3S doctrine, and
- **Violation conditions** — what constitutes non-compliance and its 3S classification.

**Priority Resolution:** When provisions conflict, resolve using the following hierarchy:

```
S1 (Secure — Code · System · Data)
  > S2 (Sustain — Business · Environment)
    > S3 (Scalable — Enterprise · Vision)
```

**Keyword Semantics (RFC 2119):**

| Keyword | Meaning |
|---------|---------|
| `SHALL` / `MUST` | Unconditional requirement. Non-compliance is a governance failure. |
| `SHALL NOT` / `MUST NOT` | Absolute prohibition. |
| `SHOULD` | Strong recommendation; deviation requires explicit justification. |
| `MAY` | Optional; permitted but not required. |

---

## GOVERNING DOCTRINE — The 3S Principles

All engineering decisions, architectural changes, and agent behaviors SHALL be evaluated against the 3S doctrine. No provision in this document may weaken these principles.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  PRIORITY RESOLUTION:  S1 (Secure) > S2 (Sustain) > S3 (Scalable)         │
│  When principles conflict, higher-priority principles are non-negotiable.   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### S1 — SECURE: Security of Code, System, and Data

Security is the foundational, non-negotiable principle. It governs the integrity and trustworthiness of every artifact the system produces, processes, or stores — across three scopes:

| Scope | Coverage | Examples of Enforcement |
|-------|----------|------------------------|
| **Code Security** | Correctness of logic, absence of exploitable patterns, integrity of boundary contracts, and deterministic failure semantics | No silent failures; explicit error types; no injection vectors; no hardcoded secrets; strict input validation at every boundary |
| **System Security** | Runtime integrity, access control enforcement, immutability of critical system attributes, and resistance to unauthorized state mutation | Attribute Sovereignty (Section 13.2); HITL for destructive operations (Section 9.4); no privilege escalation without audit trail; ACL enforcement at every external boundary |
| **Data Security** | Protection of all data at rest and in transit, enforcement of data minimization, and prevention of unauthorized inference or exposure of sensitive information | No collection of data beyond stated functional need; immutable audit fields; cryptographic key rotation via dedicated management; no PII in logs or diagnostic outputs |

**S1 Absolute Rules:**
- A design that is architecturally elegant but introduces any security weakness is **non-compliant**.
- Security constraints CANNOT be deferred, relaxed, or traded off against delivery timelines.
- Any agent behavior, code pattern, or infrastructure decision that weakens S1 MUST be rejected and flagged immediately.

---

### S2 — SUSTAIN: Business and Environmental Sustainability

Sustainability extends beyond technical maintainability to encompass the long-term viability of the business the system enables and the environmental responsibility of operating it. S2 is evaluated across two co-equal scopes:

| Scope | Coverage | Examples of Enforcement |
|-------|----------|------------------------|
| **Business Sustainability** | Maintainability, semantic clarity, documentation completeness, reduced cognitive load, and the system's capacity to support evolving business operations without accumulating unmanageable technical debt | Clean Code standards (Section 4); DRY enforcement (Section 5); Ubiquitous Language alignment (Section 6.2); documentation parity via Sync or Sink principle (Section 12.1); Bounded Context isolation to contain change impact |
| **Environmental Sustainability** | Efficiency of resource consumption (compute, memory, network, storage) as a first-class design constraint, not an afterthought | Prefer algorithms with lower computational complexity; avoid redundant data fetching or over-provisioning; design Aggregates to minimize unnecessary reads/writes; no unbounded query patterns; report on resource impact in every implementation proposal |

**S2 Guiding Principles:**
- Code that is difficult for humans or agents to read, understand, and modify is a liability to both business continuity and operational efficiency.
- Resource-wasteful implementations impose real environmental and financial cost. Efficiency is a sustainability obligation, not a performance optimization.
- Technical debt that accumulates beyond the team's capacity to service it constitutes a business sustainability risk and MUST be flagged at the point of detection, not deferred.

---

### S3 — SCALABLE: Vision Evolution and Enterprise Scalability

Scalability encompasses both the system's capacity to grow with the enterprise and its architectural readiness to evolve as the organization's strategy and vision change over time. S3 is evaluated across two co-equal scopes:

| Scope | Coverage | Examples of Enforcement |
|-------|----------|------------------------|
| **Enterprise Scalability** | Structural modularity, controlled dependency growth, predictable performance under increasing load, and the system's ability to onboard new teams, services, and consumers without structural renegotiation | Bounded Context isolation (Section 6.1); Published Language contracts (Section 7.2); dependency inversion mandate (Section 2); no cyclic dependencies; Aggregate design for independent deployability |
| **Vision Evolution** | The system's capacity to accommodate strategic pivots, new business models, regulatory changes, and emerging domain understanding without requiring destructive rewrites | Living Context Map (Section 7.1); ADR-governed architectural decisions (Appendix B); modular Bounded Contexts independently replaceable; Domain Events as the primary decoupling mechanism; Ubiquitous Language versioning as domain understanding matures |

**S3 Guiding Principles:**
- A system that cannot evolve without being rewritten has failed its scalability obligation, regardless of how well it performs today.
- Architectural decisions MUST be made with explicit awareness of future strategic optionality, not only current requirements.
- Premature optimization is a S3 violation when it trades structural flexibility for marginal performance gains that are not yet needed.

---

> **Conflict Rule:** When principles are in tension, resolve strictly as `S1 > S2 > S3`.
> S1 is absolute. S2 and S3 may be balanced against each other only after S1 is fully satisfied.
> **No engineering, commercial, or timeline pressure justifies weakening S1.**

---

## PART I — ARCHITECTURAL GOVERNANCE

*Primary doctrine: S1 (structural integrity and boundary security) · S3 (enterprise modularity and vision evolution)*

---

### Section 1 — Separation of Concerns (SOC)

`[S1 — Code Security · S2 — Business Sustainability · S3 — Enterprise Scalability]`

#### 1.1 Mandate

Separation of Concerns is a structural compliance requirement under ISO/IEC 25010:2011 Maintainability and Modularity characteristics. It is simultaneously a security control (S1 — prevents unauthorized cross-layer state access), a sustainability control (S2 — reduces cognitive load and change surface for business continuity), and a scalability enabler (S3 — allows layers to evolve and scale independently).

#### 1.2 Layered Architecture Model

The system SHALL maintain strict logical isolation across the following layers. Cross-layer coupling MUST occur exclusively through explicit, typed abstractions.

```
┌──────────────────────────────────────────────────────────────────┐
│  INTERFACE LAYER                               [S1 · S3]         │
│  Delivery mechanisms: HTTP controllers, CLI adapters,            │
│  UI endpoints, event consumers                                   │
│  — Entry point for all external input; validates and delegates.  │
│  ↕ (via explicit adapter contracts only)                        │
├──────────────────────────────────────────────────────────────────┤
│  APPLICATION LAYER                             [S2 · S3]         │
│  Use-case orchestration, transaction coordination,               │
│  workflow sequencing                                             │
│  — Coordinates domain operations; contains no business logic.    │
│  ↕ (via domain interfaces only)                                 │
├──────────────────────────────────────────────────────────────────┤
│  DOMAIN LAYER                                  [S1 · S2]         │
│  Business rules, entities, value objects, invariants             │
│  NO infrastructure, framework, or I/O dependencies              │
│  — The authoritative source of all business truth.               │
│  ↕ (via repository/port interfaces only)                        │
├──────────────────────────────────────────────────────────────────┤
│  INFRASTRUCTURE LAYER                          [S1 · S3]         │
│  External integrations: databases, file systems,                 │
│  APIs, messaging, third-party frameworks                         │
│  — Implements domain interfaces; never defines business logic.   │
└──────────────────────────────────────────────────────────────────┘
```

#### 1.3 Violations

| Violation | 3S Classification | Required Response |
|-----------|-------------------|-------------------|
| Domain layer imports infrastructure library | S1 — Code Security breach | Halt, refactor, retest |
| Application layer accesses infrastructure directly, bypassing domain interfaces | S1 — Boundary contract violation | Halt, refactor |
| Interface layer contains business logic | S2 — Business sustainability risk (change surface amplified) | Refactor into Domain or Application layer |
| Cross-layer coupling not mediated by an explicit abstraction | S3 — Enterprise scalability violation | Extract abstraction, retest |
| Any layer boundary that cannot be tested in isolation | S1 + S2 — SOC non-compliance | Resolve isolation before proceeding |

#### 1.4 Compliance Criterion

Each concern SHALL be independently testable:
- Domain logic: testable without infrastructure. *(S1 — no external dependency risk)*
- Application services: testable via mocked abstractions. *(S2 — business logic verifiable cheaply)*
- Infrastructure adapters: testable in isolation. *(S3 — swappable without domain impact)*

**Non-testable isolation is an SOC violation classified under S1.**

---

### Section 2 — Dependency Governance

`[S1 — System Security · S3 — Enterprise Scalability · Vision Evolution]`

#### 2.1 Inversion Mandate

All modules SHALL depend on abstractions, not on concrete implementations. Dependency inversion is mandatory throughout the codebase.

**Doctrine alignment:**
- S1 — Prevents concrete dependencies from becoming implicit system security attack surfaces.
- S3 — Allows concrete implementations to be swapped as the enterprise evolves without cascading changes.

#### 2.2 Prohibited Patterns

| Pattern | 3S Classification | Reason |
|---------|-------------------|--------|
| Cross-module reference to a concrete class | S3 — Enterprise Scalability violation | Creates rigid coupling that blocks independent evolution |
| Undocumented dependency expansion | S1 + S2 violation | Hidden dependencies obscure system integrity and inflate maintenance burden |
| Transitive dependency chain across boundaries | S3 — Vision Evolution violation | Silent coupling prevents strategic architectural pivots |
| Implicit shared mutable state across modules | S1 — System Security violation | Unauthorized state mutation becomes possible |

#### 2.3 Permitted Exception

Concrete classes MAY be referenced only if **explicitly designated as stable boundary constructs**, defined as one of:
- Façade
- Contract adapter
- Framework bridge

This designation MUST be documented in the Architecture Decision Record (Appendix B), with explicit justification of why S3 flexibility is not compromised.

---

### Section 3 — Boundary Contract Specification

`[S1 — Code & System Security · S2 — Business Sustainability · S3 — Vision Evolution]`

#### 3.1 Requirements

All architectural boundaries MUST declare:

| Requirement | 3S Rationale |
|-------------|-------------|
| **Explicit typed interfaces** — No implicit duck typing or runtime-discovered contracts | S1 — Eliminates ambiguous entry points that can be exploited or misused |
| **Input/output invariants** — Preconditions and postconditions MUST be documented | S1 + S2 — Contracts are the authoritative specification for both security and business behavior |
| **Absence of implicit shared state** — All shared state MUST be passed explicitly | S1 — Prevents unauthorized cross-boundary state mutation |
| **Freedom from transitive dependency chains** — A boundary must not silently pull in unrelated dependencies | S3 — Preserves the ability to evolve each context independently as vision shifts |

#### 3.2 Boundary Ambiguity

Boundary ambiguity is classified as a **S1 structural risk**. Ambiguous boundaries create uncontrolled attack surfaces, obscure business rules, and prevent independent scaling. The agent SHALL NOT proceed with any implementation when boundary ambiguity exists. Resolution via Section 10.1 is mandatory before continuing.

---

## PART II — CODE QUALITY STANDARDS

*Primary doctrine: S2 (business sustainability — readable, maintainable, low-debt code) · S1 (code security — no exploitable patterns) · S3 (enterprise scalability — minimal change surface)*

---

### Section 4 — Clean Code Standard

`[S2 — Business Sustainability · S1 — Code Security · S3 — Enterprise Scalability]`

Clean Code is an enforceable discipline that directly operationalizes the S2 principle. Code that is difficult to read creates business risk (S2), hides security defects (S1), and resists architectural evolution (S3).

#### 4.1 Naming and Semantic Clarity

`[S2 — Business Sustainability]`

| Rule | Requirement | 3S Rationale |
|------|-------------|-------------|
| Identifier naming | SHALL express intent, not implementation detail | S2 — Reduces cognitive load; enables business logic to be understood without context-switching |
| Generic/ambiguous names | Prohibited unless contextually justified and documented | S2 — Generic names accumulate hidden meaning over time, creating maintenance debt |
| Domain terminology | MUST remain consistent and uniform across all modules | S2 + S3 — Consistency reduces onboarding cost; inconsistency blocks vision evolution |
| Abbreviations | Permitted only for universally established acronyms (`id`, `url`, `dto`) | S2 — Reduces reading burden without sacrificing clarity |

#### 4.2 Function Design

`[S1 — Code Security · S2 — Business Sustainability]`

- Functions SHALL implement **exactly one responsibility**. A function requiring "and" in its description violates SRP and MUST be decomposed. *(S2 — reduces change surface; S1 — isolates failure modes)*
- Parameter lists SHALL remain minimal. More than 3–4 parameters signals the need for a parameter object. *(S2 — reduces complexity; S1 — reduces miscall risk)*
- Hidden dependencies (global state access, static singletons inside functions) are **prohibited**. *(S1 — Code Security: hidden state is an implicit attack surface)*
- Functions MUST NOT produce observable side effects that are not declared in their signature or documented contract. *(S1 — System Security: undeclared side effects compromise integrity)*

#### 4.3 Class Design

`[S2 — Business Sustainability · S3 — Enterprise Scalability]`

- Classes SHALL represent **a single axis of change**. *(S2 — a class with multiple axes accumulates unrelated changes, increasing defect probability)*
- Cohesion MUST be high; coupling MUST be low. *(S3 — high coupling prevents independent scaling and replacement)*
- Classes that grow beyond a single responsibility MUST be split before their next modification. *(S2 — deferred splitting creates compounding business sustainability debt)*

#### 4.4 Code Hygiene Rules

`[S1 — Code Security · S2 — Business & Environmental Sustainability · S3 — Enterprise Scalability]`

| Item | Rule | 3S Rationale |
|------|------|-------------|
| Dead code | SHALL be removed immediately | S1 — Dead code can mask security vulnerabilities; S2 — inflates maintenance burden |
| Redundant branching | SHALL be eliminated | S2 — Reduces cognitive load; S1 — dead branches harbor unexercised defects |
| Side effects | SHALL be explicit, documented, and isolated | S1 — Undocumented side effects are a system integrity risk |
| Formatting | SHALL be consistent and enforced by automated tooling | S2 — Tool-enforced consistency eliminates a class of review friction |
| Magic numbers/strings | MUST be extracted into named constants or configuration | S3 — Vision evolution requires changing values without hunting through logic |
| Hardcoded values | MUST NOT appear in logic; use central configuration repositories | S3 — Enterprise scalability demands configuration-driven behavior |
| Computational complexity | SHOULD be minimized to the lowest complexity class satisfying the requirement | S2 — Environmental Sustainability: excessive compute is a resource and cost liability |

> **Doctrine Note:** Readability is an S2 (Business Sustainability) architectural constraint, not a stylistic preference. Code that cannot be understood is code that cannot be safely changed or securely operated.

---

### Section 5 — DRY Principle

`[S2 — Business Sustainability · S3 — Enterprise Scalability · S1 — Code Security]`

**DRY — Don't Repeat Yourself.** Every piece of knowledge must have a single, unambiguous, authoritative representation within a system.

**Doctrine alignment:**
- S2 (Business Sustainability) — Duplication means every business rule change must be applied in multiple places; missed updates create defects that erode business continuity.
- S3 (Enterprise Scalability) — Duplication amplifies change surface. As the enterprise scales, inconsistent copies of the same rule diverge, creating unpredictable behavior.
- S1 (Code Security) — Duplicated security-sensitive logic (validation, access checks) creates risk of one copy being patched while another remains vulnerable.

#### 5.1 Prohibited Duplication

The following SHALL NOT be duplicated anywhere in the codebase:

| Element | Primary 3S Risk |
|---------|----------------|
| Business rules and domain logic | S2 — Business sustainability; S1 — inconsistent enforcement |
| Validation and access control logic | S1 — Code Security: one copy may be patched while the other remains vulnerable |
| Constant and configuration definitions | S3 — Vision evolution blocked by scattered magic values |
| Data transformation and mapping logic | S2 — Divergent transformations create data integrity risk |
| Error message strings | S2 — Maintenance burden; S1 — inconsistent error disclosure policy |

#### 5.2 Single Source of Truth (SSOT)

Every invariant, rule, or constant SHALL:
1. Be **defined exactly once**.
2. Be **referenced, not replicated**.
3. Be **centrally modifiable** without cascading edits.

SSOT is a joint S1 (integrity) and S3 (evolvability) requirement. A system without SSOT cannot be confidently secured or confidently evolved.

#### 5.3 Abstraction Discipline

DRY enforcement SHALL NOT result in:

| Anti-pattern | 3S Violation |
|-------------|-------------|
| **Premature generalization** — Abstraction without demonstrated repeated use | S2 — Increases cognitive load; business logic becomes harder to trace |
| **Clarity reduction** — An abstraction that obscures intent | S2 — Business sustainability requires readable code; S1 — obscured logic hides defects |
| **SOC violations** — Shared abstractions that blur layer boundaries | S1 — Boundary breaches create security risk |

> **Key Principle:** Abstraction is justified by **semantic identity** (S2 rationale), not superficial structural similarity. Two constructs that look similar but represent distinct business operations MUST remain separate.

---

## PART III — DOMAIN-DRIVEN DESIGN (DDD) GOVERNANCE

*Primary doctrine: S2 (business sustainability — model reflects real business domain) · S1 (integrity — explicit invariants and ACL boundaries) · S3 (vision evolution — modular contexts accommodate strategic change)*

Domain-Driven Design is mandated as the **primary architectural derivation and structural organization methodology**. DDD is the primary operational expression of all three 3S principles simultaneously: it secures business rules within explicit boundaries (S1), ensures the system speaks the language of the business it sustains (S2), and structures the architecture so it can evolve with the organization's vision (S3).

---

### Section 6 — Modular DDD Architecture

`[S1 — System Security · S2 — Business Sustainability · S3 — Enterprise Scalability · Vision Evolution]`

#### 6.1 Bounded Context as the Primary Modular Unit

`[S1 · S3]`

A **Bounded Context** is the central organizing principle of the entire system. It defines an explicit boundary within which a specific domain model is valid, consistent, and internally coherent.

```
┌─────────────────────────────────────────────────────────────────┐
│  BOUNDED CONTEXT — Core Properties                              │
│                                                                 │
│  • A single domain model applies EXCLUSIVELY within it.  [S1]  │
│  • The Ubiquitous Language is valid ONLY within it.      [S2]  │
│  • All external communication MUST pass through an             │
│    explicit boundary interface (Port, ACL, Published    [S1]   │
│    Language).                                                   │
│  • Maps 1:1 to a deployable module or service.          [S3]  │
└─────────────────────────────────────────────────────────────────┘
```

| Rule | Requirement | 3S Rationale |
|------|-------------|-------------|
| Scope | Each Bounded Context SHALL encapsulate exactly one coherent subdomain | S2 — Aligns code ownership with business capability |
| Isolation | No domain model object SHALL be shared across BC boundaries | S1 — Cross-BC model sharing creates implicit coupling and integrity risk |
| Ownership | Each Bounded Context SHALL have a single designated owning team or module | S2 — Clear ownership is a business sustainability requirement |
| Interface | Cross-context communication MUST occur through explicitly versioned contracts | S3 — Versioned contracts allow independent evolution of each context |

An agent MUST identify and name all relevant Bounded Contexts **before generating any domain model code**.

#### 6.2 Ubiquitous Language (UL)

`[S2 — Business Sustainability]`

The Ubiquitous Language is the shared, unambiguous vocabulary co-developed with domain experts that MUST be used uniformly across all artifacts — conversations, documentation, code identifiers, tests, and API contracts — within a given Bounded Context.

**Doctrine alignment:** UL is the primary S2 instrument. When code speaks the language of the business, the translation gap between domain experts and developers is eliminated — which is the single greatest source of business sustainability risk in software systems.

| Rule | 3S Rationale |
|------|-------------|
| All identifiers MUST derive from the UL of their Bounded Context | S2 — Business sustainability: code becomes self-documenting to domain experts |
| Technical synonyms (`Manager`, `Handler`, `Processor`, `Data`) are prohibited where domain terms exist | S2 — Generic terms accumulate hidden meaning and inflate cognitive load |
| The same term MUST NOT carry different meanings within the same BC | S1 — Semantic ambiguity within a context is a correctness and integrity risk |
| The same concept in two BCs with different semantics SHALL be two distinct, context-qualified types (e.g., `billing.Customer` vs. `support.Customer`) | S1 + S3 — Prevents model contamination; preserves independent evolution |

#### 6.3 Domain Model Building Blocks

`[S1 — Code Security · S2 — Business Sustainability]`

All domain model elements SHALL be classified into one of the following canonical DDD building blocks. Misclassification is an S2 governance violation (business logic placed in the wrong building block erodes the model's sustainability) and may also constitute an S1 violation (invariants enforced in the wrong place can be bypassed).

**6.3.1 Entity** `[S1 · S2]`

An object with a **persistent, unique identity** that persists across state changes.

| Property | Rule | 3S Rationale |
|----------|------|-------------|
| Identity | Defined by a unique identifier, not by attribute values | S1 — Identity confusion is a data integrity risk |
| Equality | Two entities are equal if and only if their identifiers are equal | S1 — Prevents false equality that masks duplicate records |
| Mutability | State MAY change; identity MUST NOT change after creation | S1 — Immutable identity preserves audit trail integrity |
| Lifecycle | Has a documented beginning and end within the domain | S2 — Lifecycle clarity enables correct business process modelling |

**6.3.2 Value Object** `[S1 · S2]`

An immutable object whose identity is entirely defined by the **values of its attributes**.

| Property | Rule | 3S Rationale |
|----------|------|-------------|
| Immutability | MUST be immutable; no setter methods permitted | S1 — Mutability in VOs creates shared-state integrity risk |
| Equality | Equal if all attributes are equal | S2 — Correct equality semantics ensure correct business comparisons |
| Side-effect freedom | Operations MUST return a new instance | S1 — Eliminates unintended state mutation |
| Self-validation | SHALL enforce invariants at construction time (fail-fast) | S1 — Invalid VOs must never be created; construction is the security gate |

*Examples: `Money(amount, currency)`, `Address(street, city, postalCode)`, `EmailAddress(value)`*

**6.3.3 Aggregate and Aggregate Root** `[S1 · S2 · S3]`

An Aggregate is a **cluster of Entities and Value Objects** treated as a single transactional unit, accessed exclusively through its **Aggregate Root** (AR).

| Property | Rule | 3S Rationale |
|----------|------|-------------|
| Root access | External objects MUST reference the Aggregate exclusively via the AR | S1 — The AR is the sole security and invariant enforcement gateway |
| Invariant boundary | All business invariants MUST be enforced by the AR | S1 + S2 — Invariants enforced elsewhere can be silently bypassed |
| Transaction boundary | One Aggregate instance per transaction by default; cross-aggregate coordination uses Domain Events | S3 — Single-aggregate transactions enable independent scaling |
| Identity reference | External references to internal members MUST use identity (ID), not object reference | S1 + S3 — Object reference coupling creates implicit shared state and prevents independent deployment |
| Size constraint | Aggregates SHOULD be kept small; large Aggregates signal incorrect boundary definition | S2 + S3 — Oversized Aggregates accumulate change and degrade scalability |

**6.3.4 Domain Service** `[S1 · S2]`

A stateless service encapsulating **domain logic that does not naturally belong to any single Entity or Value Object**.

| Rule | 3S Rationale |
|------|-------------|
| SHALL be stateless | S1 — Stateless services have no hidden state that can be corrupted |
| SHALL operate exclusively on domain objects | S2 — Keeps business logic within the domain boundary |
| SHALL NOT depend on infrastructure | S1 — Infrastructure dependencies create implicit attack surfaces in domain logic |
| Name SHALL reflect a domain action or policy (`PricingPolicy`, `FraudDetectionService`) | S2 — Domain naming makes business intent explicit and auditable |

**6.3.5 Domain Event** `[S2 · S3]`

An immutable record of **something significant that happened in the domain**, expressed in the past tense.

| Property | Rule | 3S Rationale |
|----------|------|-------------|
| Naming | MUST be past-tense domain language (`OrderPlaced`, `PaymentFailed`, `InventoryDepleted`) | S2 — Past tense confirms business fact; present tense implies command (different semantics) |
| Immutability | MUST be immutable after creation | S1 — Domain facts must not be retroactively altered |
| Content | SHALL contain enough data for consumers to react without querying back | S2 — Self-contained events reduce inter-context coupling and redundant compute |
| Origin | SHALL be raised by an Aggregate Root as a side effect of a state-changing operation | S1 — Events originate from the authoritative invariant enforcer |
| Transport | Cross-context Domain Events MUST transit through explicit messaging contracts | S3 — Decoupled event transport enables independent context evolution |

---

### Section 7 — Context Mapping and Integration Governance

`[S1 — System Security · S2 — Business Sustainability · S3 — Vision Evolution · Enterprise Scalability]`

#### 7.1 Context Map

`[S2 · S3]`

A **Context Map** is a mandatory architectural document that defines the relationships between all Bounded Contexts in the system. It is a **living document** — the primary S3 (Vision Evolution) artifact — and MUST be produced before any cross-context integration is implemented.

The Context Map MUST declare, for each inter-context relationship:
1. The upstream (supplier) and downstream (consumer) context.
2. The integration pattern in use (see Section 7.2).
3. The translation/anti-corruption strategy.
4. The team or module owning each side.

Failure to maintain the Context Map as the system evolves is an **S3 governance failure** — it means the architectural vision is no longer represented in any artifact, making future evolution blind.

#### 7.2 Integration Patterns

`[S1 · S3]`

The following canonical integration patterns SHALL be used to govern cross-context communication. Selection of a pattern MUST be documented in the Context Map.

| Pattern | Description | When to Use | Primary 3S Alignment |
|---------|-------------|-------------|---------------------|
| **Shared Kernel** | Two contexts share a small, explicitly defined subset of the domain model | Teams in close collaboration; shared subset is stable and small | S2 — reduces duplication within close teams |
| **Customer/Supplier** | Upstream publishes a contract; downstream negotiates requirements | Clear upstream/downstream team relationship | S3 — enables independent evolution via negotiated contracts |
| **Conformist** | Downstream adopts upstream's model without negotiation | Upstream has no incentive to adapt (e.g., external SaaS API) | S2 — pragmatic; avoids unnecessary translation overhead |
| **Anti-Corruption Layer (ACL)** | Downstream translates upstream's model into its own domain language | Upstream model is incompatible or would corrupt the downstream domain | S1 — protects domain integrity from foreign model contamination |
| **Open Host Service (OHS)** | Upstream publishes a formal, versioned protocol for multiple consumers | One upstream serves many downstream contexts | S3 — enables enterprise-scale consumption without tight coupling |
| **Published Language** | A shared, well-documented exchange format (e.g., JSON Schema, Protobuf) | Formal interoperability across organizational boundaries | S3 — versioned shared language enables independent evolution |
| **Separate Ways** | Contexts have no integration; each solves its problems independently | Integration cost exceeds benefit | S3 — maximum modularity; supports independent vision per context |

#### 7.3 Anti-Corruption Layer (ACL) Requirements

`[S1 — System Security · S2 — Business Sustainability]`

An ACL is a **mandatory S1 control** whenever integrating with any of the following:
- An external third-party system or API.
- A legacy system with an incompatible model.
- A Bounded Context whose model would contaminate the local domain model if imported directly.

| ACL Requirement | 3S Rationale |
|----------------|-------------|
| Translate all incoming data from the external model into local domain types before use | S1 — Untranslated external types are a foreign model intrusion and a security surface |
| Never allow external model types to penetrate the Domain Layer | S1 — Domain Layer must remain free of all external contamination |
| Be owned by the downstream (consumer) context | S2 — The consumer is responsible for protecting its own business model |
| Be independently testable against both the external contract and the local domain contract | S2 + S1 — Testability in both directions verifies business correctness and security boundary integrity |

#### 7.4 Refactoring Governance

`[S1 · S2 · S3]`

Refactoring SHALL be evaluated against domain model correctness across all three 3S dimensions simultaneously. A refactor is acceptable only if **all three validation gates pass**.

**S1 — Security and Integrity Validation:**
- Aggregate invariants remain fully enforced post-refactor.
- No implicit shared state introduced across Bounded Context boundaries.
- Failure and error semantics are preserved and aligned with domain expectations.
- ACL contracts remain unbroken.

**S2 — Business and Environmental Sustainability Validation:**
- Ubiquitous Language alignment has improved or is maintained.
- Building block classifications remain correct.
- Documentation and Context Map remain synchronized.
- Cognitive load for a new developer reading the model has decreased.
- Computational resource consumption has not increased without documented justification.

**S3 — Enterprise Scalability and Vision Evolution Validation:**
- Bounded Context boundaries have not been widened unnecessarily.
- No new coupling introduced between previously independent Aggregates.
- Domain Event contracts remain backward-compatible or are explicitly versioned.
- No performance complexity regression.
- The refactor increases, or at minimum preserves, the system's capacity to accommodate future strategic pivots.

> **Rule:** A refactor that improves structural metrics but degrades UL clarity, blurs BC boundaries, or introduces any S1 regression is **non-compliant and MUST be reverted**.

Each refactor step MUST be:
1. **Independently compilable** — No broken intermediate states. *(S1 — system integrity at every step)*
2. **Domain-verifiable** — All domain invariant tests pass after the step. *(S1 + S2)*
3. **Reversible** — A clean rollback MUST be possible. *(S1 — no irreversible integrity risk)*

Refactoring MUST NOT be bundled with feature changes in the same commit. *(S2 — auditability of change is a business sustainability requirement)*

---

### Section 8 — Quality Assurance in a DDD Context

`[S1 — Code & System Security · S2 — Business Sustainability · S3 — Enterprise Scalability]`

Quality assurance in a DDD system is structured around **domain model correctness** as the primary quality signal. Tests are the executable specification of business behavior (S2) and the enforcement mechanism for invariant integrity (S1).

#### 8.1 Test Taxonomy

`[S1 · S2]`

| Test Type | Scope | Validates | Primary 3S Alignment |
|-----------|-------|-----------|---------------------|
| **Domain Unit Test** | Single Entity, Value Object, or Aggregate | Business rules, invariants, and state transitions using only domain objects | S1 + S2 |
| **Domain Service Test** | Domain Service in isolation | Policy correctness using domain object stubs | S2 |
| **Application Service Test** | Use-case orchestration | Correct sequencing of domain operations; uses mocked Repositories and Domain Services | S2 |
| **ACL Contract Test** | Anti-Corruption Layer | Correct translation of external model ↔ local domain model | S1 |
| **Context Integration Test** | Cross-context boundary | Correct message/event flow across Bounded Context boundaries | S1 + S3 |
| **Repository Adapter Test** | Infrastructure adapter | Correct persistence and retrieval of Aggregates | S1 |

#### 8.2 Domain-Centric Test Naming

`[S2 — Business Sustainability]`

Tests SHALL be named using the Ubiquitous Language of the Bounded Context, not technical implementation terms. Test names are business documentation (S2). An engineer unfamiliar with the code MUST be able to understand business behavior from test names alone.

*Recommended structure:* `[AggregateOrService]_[domainScenario]_[expectedDomainOutcome]`

*Examples:*
- `Order_whenItemAddedExceedingStockLimit_raisesInventoryExceededEvent`
- `PricingPolicy_whenApplyingVIPDiscountToEligibleOrder_returnsDiscountedTotal`
- `PaymentACL_whenReceivingStripeWebhookEvent_translatesIntoPaymentReceivedDomainEvent`

#### 8.3 Invariant Verification Standard

`[S1 — Code Security]`

Every Aggregate Root MUST have tests that explicitly verify each of its declared invariants. Invariant tests are **S1 controls** — they are the executable proof that business rules cannot be violated.

- Invariants SHALL be documented as part of the Aggregate's specification before implementation. *(S2 — business behavior defined before code)*
- Tests for invariant violations MUST verify that the Aggregate raises an explicit domain exception, not a generic runtime error. *(S1 — explicit failure semantics are a security property)*
- No invariant test MAY mock internal Aggregate components; the full Aggregate is the unit under test. *(S1 — mocking internals allows invariant bypass to go undetected)*

#### 8.4 Coverage and Regression Policy

`[S1 · S2 · S3]`

| Metric | Rule | 3S Rationale |
|--------|------|-------------|
| Domain layer coverage | SHALL NOT decrease; targeting ≥90% line coverage | S2 — Business sustainability requires confidence in every business rule |
| Aggregate invariant coverage | 100% — every declared invariant MUST have a test | S1 — Untested invariants are unenforced invariants; a security gap |
| ACL translation coverage | 100% — every translation path tested in both directions | S1 — ACL is a security boundary; partial coverage is a breach |
| Integration test regression | Zero tolerance — all context boundary tests MUST pass before merge | S1 + S3 — Context boundaries are both security and scalability controls |
| Performance complexity | No time or space complexity regression permitted | S2 — Environmental sustainability: efficiency is a first-class obligation |

---

## PART IV — AI AGENT BEHAVIORAL CONSTRAINTS

*Primary doctrine: S1 (agent behaviors must not introduce security, data, or system integrity risk) · S2 (agent outputs must sustain business clarity and minimize computational waste) · S3 (agent proposals must preserve architectural evolvability)*

---

### Section 9 — Zero-Hallucination Mandate

`[S1 — Code · System · Data Security]`

This section defines the behavioral bounds that constrain any AI agent operating within this system. Unpredictable, invented, or unverifiable agent output is a direct S1 violation — it introduces unverified artifacts into a governed system boundary.

#### 9.1 Prohibition on Invention

`[S1 — Code Security]`

The agent SHALL NOT invent, assume, fabricate, or mock any of the following unless **explicitly instructed under a designated test isolation context with documented boundary stubs**:

- Internal API signatures
- Database schemas or table structures
- External service contracts or response formats
- Library or package behaviors not evidenced in provided context
- Business rules not stated in the requirements
- Domain model elements not present in the established Ubiquitous Language

> **Violation Classification:** Inventing undocumented system elements is a **S1 (Code Security) violation** and MUST cause the agent to halt and request the missing information.

#### 9.2 Fail-Fast Protocol

`[S1 — System Security]`

The agent SHALL prioritize **early, explicit failure over plausible-but-unverified output.** A confident wrong answer is more damaging than an honest halt.

| Condition | Required Action | 3S Rationale |
|-----------|----------------|-------------|
| Ambiguous user intent | Halt. Request clarification BEFORE generating any artifact | S1 — Ambiguity resolved by assumption creates unverified security artifacts |
| Missing technical context | Halt. Explicitly list what is missing | S1 — Fabricating context is a hallucination violation |
| Contradictory requirements | Halt. Surface the contradiction. Request resolution | S2 — Unresolved contradictions undermine business correctness |
| Undocumented dependency required | Halt. Flag and request documentation | S3 — Undocumented dependencies block future evolution |

**The agent MUST NOT resolve ambiguity through assumption.** Contextual guessing is a S1 violation regardless of how plausible the guess appears.

#### 9.3 Output Strictness and Bounded Generation

`[S1 — Code Security · S2 — Business & Environmental Sustainability]`

All agent outputs SHALL be:

- **Structured** — Code, diffs, architectural diagrams, or clearly labeled explanations. *(S2 — structured output reduces cognitive load)*
- **Scoped** — Strictly addressing the requested engineering artifact. *(S1 — unsolicited additions introduce unreviewed code)*
- **Verifiable** — Every claim or code snippet must be traceable to a requirement, a documented API, or an explicit instruction. *(S1 — unverifiable claims are hallucination violations)*
- **Resource-conscious** — Proposed implementations MUST consider computational efficiency. *(S2 — Environmental Sustainability)*

**Prohibited output patterns:**

| Prohibited Pattern | 3S Violation |
|-------------------|-------------|
| Unsolicited architectural opinions not grounded in 3S | S2 — Noise degrades business clarity |
| Philosophical justifications outside this standard | S2 — Not verifiable; inflates cognitive overhead |
| Conversational filler or hedging language | S2 — Reduces signal clarity in governed engineering context |
| Speculative "this might be useful" additions | S1 — Introduces unreviewed code into the system |
| Computationally wasteful implementations without justification | S2 — Environmental sustainability violation |

#### 9.4 Human-in-the-Loop (HITL) Enforcement

`[S1 — System Security · Data Security]`

The agent SHALL NOT autonomously execute or advise any of the following without **explicit human authorization**:

| Operation | 3S Classification |
|-----------|------------------|
| File deletion or directory removal | S1 — System Security |
| Database schema drops or destructive migrations | S1 — System + Data Security |
| Force-push to version control | S1 — Code Security: history integrity |
| Permission escalation | S1 — System Security |
| Production environment modifications | S1 — System + Data Security |
| Bounded Context boundary changes | S3 — Vision Evolution: strategic decisions require human sign-off |

All proposed structural changes MUST be presented as **dry-runs or annotated diffs** and explicitly confirmed before integration.

---

### Section 10 — Interaction and Communication Protocol

`[S1 — System Integrity · S2 — Business Sustainability · S3 — Vision Clarity]`

#### 10.1 Clarification Before Action

`[S1 · S2]`

When a request is ambiguous, incomplete, or contradictory, the agent MUST:

1. **Stop** — Do not proceed with any code generation or structural modification. *(S1 — no unreviewed artifacts)*
2. **Enumerate** — List the specific ambiguities or missing information. *(S2 — transparency sustains business understanding)*
3. **Ask** — Pose a concise, targeted clarifying question. *(S2 — minimal cognitive overhead)*
4. **Wait** — Await a response before resuming. *(S1 — human authorization is a security control)*

#### 10.2 Structured Response Format

`[S2 — Business Sustainability · S3 — Vision Evolution]`

Agent responses SHALL follow this structure when delivering engineering output:

```
[CONTEXT ASSESSMENT]
  - Understood requirements (in Ubiquitous Language where applicable)
  - Identified constraints
  - Flagged ambiguities (if any)
  - Identified Bounded Context(s) affected
  - 3S risks identified

[PROPOSED APPROACH]
  - DDD building blocks involved (Aggregate, Entity, Value Object, Domain Event, etc.)
  - Context Map impact: any new or modified cross-context relationships
  - Ubiquitous Language terms introduced or modified
  - 3S impact assessment (S1 / S2 / S3 dimensions explicitly addressed)
  - Estimated resource impact (S2 — Environmental Sustainability)

[IMPLEMENTATION]
  - Code / diff / architectural artifact
  - Domain model changes clearly labeled by building block type
  - 3S compliance annotations on non-obvious decisions

[VALIDATION SUMMARY]
  - Which domain invariants are satisfied or introduced
  - Which Bounded Context boundaries are respected or modified
  - Which 3S dimensions are improved and how
  - Environmental resource impact (S2)
  - Remaining risks or open questions
```

#### 10.3 Minimal Footprint Principle

`[S1 — Code Security · S2 — Business & Environmental Sustainability]`

The agent SHALL make the smallest change necessary to satisfy the requirement.

- Over-engineering and gold-plating are **S2 violations** — they inflate maintenance burden without business benefit.
- Adding unrequested features is a **S1 violation** — it introduces unreviewed code into the system boundary.
- Computationally excessive implementations are **S2 violations** — unnecessary resource consumption is an environmental sustainability failure.

---

## PART V — LIFECYCLE AND PROCESS GOVERNANCE

*Primary doctrine: S2 (sustainable, repeatable process that enables business continuity) · S1 (controlled execution prevents unauthorized or integrity-breaking changes) · S3 (lifecycle model accommodates evolving requirements without structural disruption)*

---

### Section 11 — Lifecycle Execution Model

`[S1 · S2 · S3]`

All engineering activities SHALL follow this controlled execution workflow. Each step carries an explicit 3S annotation.

```
STEP 1 — DOMAIN DISCOVERY AND REQUIREMENT COMPREHENSION         [S2 · S1]
  Identify the subdomain(s) affected: Core, Supporting, or Generic.
  Extract and validate Ubiquitous Language terms with domain stakeholders.
  Identify acceptance criteria expressed in domain language.
  Identify S1 security requirements (data handled, access controls needed).
  Surface ambiguities → Resolve via Section 10.1 before proceeding.

STEP 2 — BOUNDED CONTEXT AND MODEL SCOPING                      [S1 · S3]
  Identify which Bounded Context(s) are affected.
  Determine if a new BC is required or if an existing one expands.
  Assess Context Map impact: any new or modified inter-context relationships.
  Classify all domain concepts using DDD building blocks (Section 6.3).
  Evaluate S3 impact: does this change affect strategic optionality?

STEP 3 — DOMAIN MODEL DESIGN                                    [S1 · S2]
  Define or update Aggregate boundaries and Aggregate Roots.
  Specify all invariants the Aggregate Root must enforce.
  Define Domain Events raised as side effects of state changes.
  Identify required Repositories (interfaces only; no infrastructure details).
  Design ACL translation contracts for any external integration (Section 7.3).
  Validate: does the model speak the Ubiquitous Language? (S2)
  Validate: are all security boundaries explicit? (S1)

STEP 4 — ARCHITECTURAL IMPACT ASSESSMENT                        [S1 · S2 · S3]
  Evaluate SOC implications (Section 1) — S1 + S2.
  Assess dependency changes (Section 2) — S3.
  Identify boundary contract modifications (Section 3) — S1.
  Assess environmental resource impact of proposed design — S2.
  Assess vision evolution impact: does this constrain future strategic options? — S3.

STEP 5 — EXPLICIT HUMAN APPROVAL                                [S1]
  Present the domain model design, Context Map delta, 3S impact assessment,
  and decomposed implementation plan.
  Await confirmation before any implementation begins.
  Unapproved structural modification or Bounded Context boundary change is prohibited.

STEP 6 — IMPLEMENTATION (DDD-Modular)                           [S1 · S2 · S3]
  Implement Domain Layer first: Entities, Value Objects, Aggregates, Domain Events.
  Implement Domain Services and Application Services next.
  Implement Infrastructure adapters (Repositories, ACLs) last.
  Each implementation unit SHALL have corresponding domain invariant tests (Section 8).
  Monitor computational complexity throughout; flag S2 environmental violations immediately.

STEP 7 — VALIDATION                                             [S1 · S2 · S3]
  S1: Verify all domain invariant tests pass.
  S1: Verify ACL translation tests pass in both directions.
  S1: Verify no coverage regression.
  S2: Verify documentation and Context Map parity (Sync or Sink).
  S2: Verify no performance/resource complexity regression.
  S3: Verify no cyclic dependencies introduced.
  S3: Verify Domain Event contracts are backward-compatible or versioned.

STEP 8 — FORMAL REPORTING                                       [S2]
  Deliver a consolidated summary per Section 10.2.
  Commit with accurate, descriptive diff representation.
  Update Context Map document if inter-context relationships changed.
  Record any new ADRs for architectural decisions made during this cycle.
```

---

### Section 12 — Documentation Integrity

`[S2 — Business & Environmental Sustainability · S3 — Vision Evolution]`

Documentation is treated as a **first-class engineering artifact**. It is not a secondary deliverable or a post-implementation task.

**Doctrine alignment:**
- S2 (Business Sustainability) — Teams that cannot understand the system cannot safely operate or evolve the business it supports. Documentation is a direct business continuity control.
- S2 (Environmental Sustainability) — Clear documentation prevents redundant exploratory work, wasted compute cycles, and rework — all of which carry resource cost.
- S3 (Vision Evolution) — Architectural rationale documented in ADRs is the mechanism by which future teams understand *why* decisions were made, enabling informed evolution rather than blind guessing.

#### 12.1 Sync or Sink Principle

`[S2 — Business Sustainability]`

Code and documentation MUST remain synchronized at all times. A discrepancy between implementation and documentation is classified as a **S2 governance failure** — it means the system can no longer be confidently operated or evolved based on its written specification.

#### 12.2 Documentation Standards

| Requirement | Rule | 3S Rationale |
|-------------|------|-------------|
| Technical depth | SHALL NOT be reduced during refactoring or updates | S2 — Depth loss is sustainability debt |
| Architectural rationale | MUST remain explicit and current | S3 — Vision Evolution: future teams must understand the *why* behind decisions |
| Public API documentation | MUST include descriptions, parameter semantics, return values, and behavioral examples | S1 + S2 — Ambiguous APIs are both a security risk and a business clarity failure |
| Inline comments | Reserved for non-obvious logic; obvious code SHALL NOT be over-commented | S2 — Over-commenting inflates cognitive load and maintenance burden |
| Resource impact | SHOULD be documented for computationally significant operations | S2 — Environmental sustainability requires awareness of resource cost |

#### 12.3 Automated Documentation

Where the language supports it, documentation SHALL be generated from structured docstrings/annotations (e.g., JSDoc, Rustdoc, Javadoc, Python docstrings). Automated generation enforces S2 parity between code and documentation with minimal environmental overhead.

---

### Section 13 — Security and Data Protection

`[S1 — Code · System · Data Security]`

This section operationalizes the S1 doctrine at the data and access control level. All provisions here are unconditional S1 requirements. No business justification, architectural convenience, or delivery timeline exempts the system from compliance.

#### 13.1 Data Minimization

`[S1 — Data Security]`

The system SHALL NOT store, request, infer, or record personal or sensitive data beyond what is strictly required by the stated functional requirement. Collecting data beyond necessity creates a security surface that cannot be justified under S1.

#### 13.2 Attribute Sovereignty and Immutability

`[S1 — System & Data Security]`

Critical system-level attributes SHALL be treated as immutable unless a specific, documented business requirement justifies modification:

| Attribute Class | Default Policy | 3S Rationale |
|----------------|---------------|-------------|
| System-generated IDs | Immutable — no UI or API write access | S1 — Identity manipulation is a system integrity attack vector |
| Audit-critical fields (timestamps, actor IDs) | Immutable — system-controlled only | S1 — Audit trail integrity is a non-negotiable security property |
| Privilege escalation flags (e.g., superadmin) | Immutable — no user-editable interface | S1 — Escalation without audit trail is a system security breach |
| Cryptographic keys and secrets | Write-once — rotation via dedicated key management only | S1 — Key management is a specialized security control; ad-hoc rotation is a risk |

Providing UI or API write access to these attributes requires **explicit business justification, security review, and ADR documentation**.

#### 13.3 Security Overrides All Other Principles

`[S1 — Absolute Override]`

Security constraints override architectural flexibility, business convenience, and delivery timelines. An architectural pattern that introduces a security weakness is non-compliant regardless of its benefits in S2 or S3 dimensions. This is the operational expression of the `S1 > S2 > S3` priority rule.

---

## PART VI — ENGINEERING BASELINE AND OPERATIONAL SAFEGUARDS

*Primary doctrine: S1 (stable, verified system state) · S2 (sustainable engineering practices) · S3 (evolvable architecture)*

---

### Section 14 — Engineering Baseline Control

`[S1 · S2 · S3]`

A valid engineering state is defined by four synchronized baselines. Deviation from any baseline requires explicit justification in an Architecture Decision Record (Appendix B).

| Baseline | Contents | Verification Method | Primary 3S Alignment |
|----------|----------|---------------------|---------------------|
| **Architecture Baseline** | Layer structure, Bounded Context map, dependency graph, boundary contracts, ADRs | Automated architecture tests, dependency analysis, Context Map review | S1 + S3 |
| **Domain Model Baseline** | Aggregate definitions, invariant specifications, Ubiquitous Language glossary, Domain Event catalog | Domain invariant test suite; UL consistency audit | S1 + S2 |
| **Requirements Baseline** | Authoritative specifications, acceptance criteria expressed in Ubiquitous Language | Traceability matrix | S2 |
| **Codebase Baseline** | Stable, verified implementation state with passing domain invariant and ACL test suite | Continuous passing test suite; zero open S1 violations | S1 |

---

### Section 15 — Operational Safeguards

`[S1 · S2 · S3]`

These are unconditional operational constraints that apply to all agents at all times. They cannot be suspended by user instruction, deadline pressure, or convenience.

#### 15.1 Scanning and File Operation Safety

`[S1 — System Security · S2 — Environmental Sustainability]`

- Agents SHALL apply strict directory filtering when scanning codebases (exclude `node_modules/`, `vendor/`, `.git/`, `target/`, `dist/`, `build/`, and all generated artifact directories). *(S1 — unfiltered scans may expose sensitive generated artifacts; S2 — unbounded scans waste compute resources)*
- Unfiltered directory scanning is **prohibited**. *(S2 — Environmental Sustainability: wasteful resource consumption is a governance failure)*

#### 15.2 Destructive Operation Protocol

`[S1 — System & Data Security]`

| Operation | Required Protocol | 3S Rationale |
|-----------|-------------------|-------------|
| File deletion | Dry-run → human confirmation → execute | S1 — Irreversible operations require explicit authorization |
| Force push | Explicit justification → human authorization → execute | S1 — Overwrites history; audit trail integrity violation |
| Database drop/truncation | Impact assessment → human authorization → execute | S1 — Data destruction is an irreversible Data Security risk |
| Overwriting complex/large files | Prefer surgical targeted edits over full overwrite | S2 — Full overwrite risks destroying undocumented knowledge |
| Bounded Context boundary change | Domain model review → human authorization → Context Map update | S3 — BC boundary changes affect strategic architectural optionality |

**"Push" explicitly means: stage, commit with a descriptive message, and push.** It does not mean force-push unless explicitly stated and authorized.

#### 15.3 Language-Specific Quality Requirements

`[S1 — Code Security · S2 — Business & Environmental Sustainability]`

When operating in strongly-typed compiled languages (e.g., Rust, Go, Kotlin), the following requirements apply:

| Requirement | 3S Rationale |
|-------------|-------------|
| All public items MUST include comprehensive inline documentation | S2 — Business sustainability; public contracts must be unambiguous |
| Examples MUST be provided in documentation for non-trivial public functions | S2 — Executable documentation reduces cognitive load and rework |
| Macro-based abstractions SHOULD be used for repetitive structural patterns (e.g., `macro_rules!` in Rust) | S2 + S3 — DRY compliance; reduces both maintenance burden and change surface |
| All outputs MUST compile cleanly with zero warnings under the strictest linter/compiler settings | S1 — Compiler warnings in security-sensitive languages are latent defects; S2 — warnings accumulate into unmanageable noise |

---

## APPENDICES

---

### Appendix A — Violation Classification Reference

Every violation is classified by its 3S dimension to make remediation priority unambiguous.

| Violation | 3S Classification | Required Response |
|-----------|-------------------|-------------------|
| Domain layer imports infrastructure | S1 — Code Security (boundary breach) | Halt, refactor, retest |
| Agent invents undocumented API or domain rule | S1 — Code Security (hallucination) | Halt, request documentation |
| Aggregate invariant enforced outside the Aggregate Root | S1 — Code Security (invariant bypass) | Refactor; move enforcement to AR |
| External type leaks past the ACL into the Domain Layer | S1 — System Security (ACL breach) | Halt, implement translation in ACL |
| Cross-Aggregate direct object reference (not by ID) | S1 — System Security (implicit shared state) | Replace with identity reference |
| Hardcoded secret or credential in code | S1 — Data Security (critical) | Revoke credential immediately; extract to key management |
| Code merged without passing domain invariant tests | S1 — Code Security (quality gate failure) | Block merge, restore passing state |
| Ambiguity resolved by assumption | S1 — System Security (unauthorized artifact) | Revert, request clarification |
| Structural change without approval | S1 — System Security (unauthorized mutation) | Revert, seek approval |
| Ubiquitous Language term used inconsistently | S2 — Business Sustainability (UL violation) | Audit and align all usages |
| Domain Event named in present or future tense | S2 — Business Sustainability (naming violation) | Rename to past-tense domain action |
| Documentation or Context Map diverged from code | S2 — Business Sustainability (Sync or Sink failure) | Synchronize before next commit |
| Technical debt flagged but not recorded | S2 — Business Sustainability risk | Create ADR or backlog item immediately |
| Computationally wasteful implementation without justification | S2 — Environmental Sustainability | Refactor to lower complexity class |
| New Bounded Context introduced without Context Map update | S2 + S3 — Governance failure | Update Context Map before proceeding |
| Duplicated business logic across Bounded Contexts | S2 + S3 — DRY + DDD violation | Refactor to SSOT within correct BC |
| Hardcoded configuration value in domain logic | S3 — Enterprise Scalability (vision evolution blocked) | Extract to configuration or Value Object |
| Premature abstraction reducing model clarity | S2 + S3 — DRY misapplication | Revert abstraction; wait for genuine repeated use |

---

### Appendix B — Architecture Decision Record (ADR) Template

When a deviation from this standard is required, or when a significant architectural decision is made, it MUST be documented using the following structure:

```
ADR-[NUMBER]: [Short Title]

Date:     [YYYY-MM-DD]
Status:   [Proposed | Accepted | Deprecated | Superseded by ADR-NNN]
Deciders: [Names or roles]

Context:
  [Describe the situation, forces at play, and why a decision is needed.
   Reference the relevant 3S dimensions under pressure.]

Decision:
  [Describe the change being proposed or accepted.]

Deviation from Standard:
  [Which section of this document is being deviated from, and why.
   If no deviation, state "None — this ADR records a compliant decision."]

3S Impact Assessment:
  S1 (Secure — Code · System · Data):
    Impact:     [Describe any security implications]
    Mitigation: [How S1 risks are controlled]

  S2 (Sustain — Business · Environmental):
    Impact:     [Describe impact on business sustainability and resource efficiency]
    Mitigation: [How S2 risks are controlled]

  S3 (Scalable — Enterprise · Vision Evolution):
    Impact:     [Describe impact on enterprise scalability and future strategic optionality]
    Mitigation: [How S3 risks are controlled]

Consequences:
  Positive:  [What becomes easier or safer as a result?]
  Negative:  [What becomes harder or riskier as a result?]
  Neutral:   [What changes without clear benefit or cost?]

Review Date:
  [When this decision should be revisited. Required for all Accepted ADRs.]
```

---

### Appendix C — Behavioral Decision Tree for Agents

Use this tree to determine the correct action for any given request. Each decision node carries its 3S classification.

```
START
  │
  ├─ Is the request clear and unambiguous?                        [S1]
  │    ├─ NO → Section 10.1: Halt. Enumerate ambiguities. Ask. Wait.
  │    └─ YES ↓
  │
  ├─ Does fulfilling the request require undocumented context     [S1]
  │  (APIs, schemas, domain rules, Ubiquitous Language terms)?
  │    ├─ YES → Section 9.1: Halt. List missing information. Request docs.
  │    └─ NO ↓
  │
  ├─ Does the request involve a destructive operation?            [S1]
  │    ├─ YES → Section 9.4: Present dry-run. Await explicit authorization.
  │    └─ NO ↓
  │
  ├─ Have the affected Bounded Context(s) been identified?        [S1 · S3]
  │    ├─ NO → Section 6.1: Identify and name Bounded Contexts first.
  │    └─ YES ↓
  │
  ├─ Are all domain concepts classified into DDD building blocks? [S1 · S2]
  │  (Entity, Value Object, Aggregate, Domain Service, Domain Event)
  │    ├─ NO → Section 6.3: Classify before generating code.
  │    └─ YES ↓
  │
  ├─ Does the change cross a Bounded Context boundary?            [S1 · S3]
  │    ├─ YES → Section 7: Select and document the integration pattern.
  │    │    Does it require an ACL?
  │    │      ├─ YES → Section 7.3: Implement ACL. Test both directions.
  │    │      └─ NO → Document chosen pattern in Context Map. ↓
  │    └─ NO ↓
  │
  ├─ Are all Aggregate invariants explicitly defined and tested?  [S1]
  │    ├─ NO → Section 8.3: Define invariants. Write invariant tests first.
  │    └─ YES ↓
  │
  ├─ Have all domain invariant and ACL tests passed?              [S1]
  │    ├─ NO → Section 8.4: Halt. Restore passing state. Investigate.
  │    └─ YES ↓
  │
  ├─ Does the proposed implementation have acceptable resource    [S2]
  │  complexity? (No unbounded queries, no excessive compute)
  │    ├─ NO → Section 9.3: Refactor to lower complexity class.
  │    └─ YES ↓
  │
  ├─ Does the change improve at least one 3S dimension           [S1 · S2 · S3]
  │  without degrading any other?
  │    ├─ NO → Section 7.4: Non-compliant. Revert or redesign.
  │    └─ YES ↓
  │
  └─ DELIVER: Structured output per Section 10.2.
              Update Context Map if inter-context relationships changed.
              Record ADR if an architectural decision was made.
              Commit with accurate, descriptive diff.
```

---

### Appendix D — Glossary

| Term | Definition | Primary 3S Alignment |
|------|------------|---------------------|
| **ACL** | Anti-Corruption Layer — a translation boundary that protects a domain model from contamination by external or foreign models. | S1 |
| **ADR** | Architecture Decision Record — a document capturing an architectural decision, its context, and its 3S impact assessment. | S2 + S3 |
| **Aggregate** | A cluster of Entities and Value Objects treated as a single transactional unit, accessed exclusively through its Aggregate Root. | S1 + S2 |
| **Aggregate Root (AR)** | The sole entry point to an Aggregate; responsible for enforcing all invariants within the Aggregate boundary. | S1 |
| **Bounded Context (BC)** | An explicit logical boundary within which a specific domain model and Ubiquitous Language are valid and consistently applied. | S1 + S3 |
| **Boundary Contract** | An explicit, typed interface definition at an architectural boundary, including input/output invariants. | S1 |
| **Context Map** | A living document that describes all Bounded Contexts and the integration patterns governing their relationships. | S2 + S3 |
| **DDD** | Domain-Driven Design — an approach that centers software architecture on the core domain and domain logic, using the Ubiquitous Language as the unifying mechanism. | S1 + S2 + S3 |
| **Domain Event** | An immutable record of a significant domain occurrence, named in the past tense (e.g., `OrderPlaced`). | S2 + S3 |
| **Domain Service** | A stateless service encapsulating domain logic not belonging to any single Entity or Value Object. | S1 + S2 |
| **DRY** | Don't Repeat Yourself — every knowledge element has a single, authoritative source. | S2 + S3 |
| **Entity** | A domain object with a persistent, unique identity that distinguishes it from others regardless of attribute values. | S1 + S2 |
| **HITL** | Human-in-the-Loop — a mandatory human authorization step before executing high-impact or irreversible operations. | S1 |
| **Repository** | An abstraction for persisting and retrieving Aggregates; defined in the Domain Layer, implemented in the Infrastructure Layer. | S1 + S3 |
| **SOC** | Separation of Concerns — the principle of isolating distinct responsibilities into distinct architectural layers. | S1 + S2 + S3 |
| **SSOT** | Single Source of Truth — one authoritative location for any given piece of knowledge or business rule. | S1 + S2 |
| **Ubiquitous Language (UL)** | The shared, unambiguous vocabulary derived from domain expertise, used uniformly across all code, documentation, and communication within a Bounded Context. | S2 |
| **Value Object (VO)** | An immutable domain object defined entirely by its attribute values, with no persistent identity. | S1 + S2 |
| **3S** | The governing doctrine — **Secure (S1):** security of code, system, and data; **Sustain (S2):** business and environmental sustainability; **Scalable (S3):** vision evolution and enterprise scalability. Priority: S1 > S2 > S3. | — |
| **Zero-Hallucination** | The mandate that agents MUST NOT invent, assume, or fabricate system artifacts, domain rules, or API contracts. | S1 |

---

*End of Document*
