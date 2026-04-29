# Architecture Design: Internara (Action-Oriented Laravel MVC)

## 1. Introduction
This document defines the architectural standards for the `internara` project following its transformation from a modular monolith to a modern Laravel MVC architecture. This design prioritizes the separation of **Business Rules (Stateful)** and **Application Logic (Stateless)** to achieve 3S quality standards (Secure, Sustain, Scalable).

## 2. The 3S Doctrine Alignment

### S1 - Secure (Security of Code, System, and Data)
- **Input Validation**: Validation is performed at the outermost layer (Form Requests) before entering any business logic.
- **Explicit Failure**: Custom Exceptions are used to handle business logic failures explicitly without leaking internal system details.
- **Protected Rules**: Business rules are centralized within Eloquent Models, ensuring they cannot be bypassed by direct database access in Controllers.

### S2 - Sustain (Sustainability)
- **Clarity & Project Language**: Action and Model naming follows business terminology (e.g., `ClockInAction` instead of `SaveAttendance`).
- **Single Responsibility**: Each Action has a single `execute()` method representing one specific Use Case.
- **Maintainability**: Removes the overhead of module management (`nwidart/laravel-modules`) to accelerate development and simplify onboarding.

### S3 - Scalable (Enterprise Scalability)
- **Stateless Actions**: Application logic is stateless, allowing for reusability across different entry points (Web, API, CLI).
- **Domain-Driven Grouping**: `Actions/` and `Models/` folders are grouped by business domain, facilitating scalability as the feature set grows.

## 3. Layered Architecture

### A. HTTP/UI Layer (Controllers & Livewire)
- **Responsibility**: Receive requests, validate input via Form Requests, invoke Actions, and return responses.
- **Constraint**: Must not contain business logic or complex database queries.

### B. Action Layer (Stateless Logics / Use Cases)
- **Location**: `app/Actions/{Domain}/`
- **Responsibility**: Orchestrate application workflows.
- **Properties**: 
    - Must be Stateless.
    - Receives structured input (DTOs or Models).
    - Invokes Business Rules in Models.
    - Performs side-effects (database writes, emails, integrations).

### C. Domain Layer (Rich Models / Business Rules)
- **Location**: `app/Models/`
- **Responsibility**: Handle stateful business rules.
- **Methods**: Contains logic for "Is it allowed?", "What is the status?", or internal calculations.

### D. Data Layer (DTOs & Enums)
- **Location**: `app/Data/` & `app/Enums/`
- **Responsibility**: Standardize data flow between layers and define fixed business statuses.

## 4. Implementation Guidelines

### Use Case: Single Action Pattern
Each Use Case must be implemented in a single Action class.
```php
namespace App\Actions\Internship;

class ApplyForInternshipAction {
    public function execute(Student $student, InternshipPost $post): Application {
        // Workflow logic here...
    }
}
```

### Business Rule in Model
Rules that depend on model data must reside within the model itself.
```php
namespace App\Models;

class Internship extends Model {
    public function canBeApproved(): bool {
        return $this->status === InternshipStatus::PENDING && $this->documents->isComplete();
    }
}
```

## 5. Documentation & Sync
Every change to this architecture must be recorded in **Decision Records** according to the `AGENTS.md` standards. The code must always remain in sync with this documentation.
