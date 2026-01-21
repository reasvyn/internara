# Laravel Framework: The Core Foundation

Laravel provides the robust infrastructure upon which Internara is built. While we leverage its
standard features, we have tailored its usage to support a **Modular Monolith** architecture.

---

## 1. Modular Integration Patterns

We use Laravel's native features through a modular lens:

### 1.1 Eloquent ORM

Models are encapsulated within each module's `src/Models` directory.

- **Rule**: Direct database queries (`DB::`) are forbidden in feature modules. All data access must
  go through Eloquent.
- **Scoping**: Use **Global Scopes** within models to enforce multi-tenancy or academic year
  filtering.

### 1.2 Validation & Form Requests

We utilize Laravel's validation engine but move the logic to the **Service** or **Form Request**
layer.

- **Mandate**: Never perform validation logic directly inside a Livewire component.

### 1.3 Queues & Background Processing

Long-running tasks (e.g., PDF generation, bulk emails) are offloaded to Laravel Queues.

- **Modular Events**: Events dispatched by one module can trigger jobs handled by another, ensuring
  true decoupling.

---

## 2. Engineering Constraints

1.  **Named Routes**: All URLs must be generated via `route('name')`. Hardcoded URLs are prohibited.
2.  **Config over Env**: Never call `env()` directly outside of `config/*.php` files. This ensures
    that configuration can be properly cached and overridden.
3.  **Dependency Injection**: Use the Service Container for everything. This allows for easy mocking
    and testing of modular boundaries.

---

_Laravel is our "Single Source of Infrastructure." By following these patterns, we ensure that the
framework works for us, not against us, in a modular environment._
