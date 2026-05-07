# Known Issues

## Architectural Transition

The project is currently transitioning from a legacy **Modular Monolith** architecture (located in `legacy/internara-modular-monolith/`) to the current **Domain-First, Action-Oriented MVC** architecture. 

Specific complexities identified in the legacy system include:
- **Extreme Fragmentation**: Over 29 independent modules (e.g., `User`, `Profile`, `Student`, `Teacher` were separate modules), each with its own internal structure (config, database, routes).
- **Over-Engineered Infrastructure**: Heavy reliance on `nwidart/laravel-modules` and `composer-merge-plugin`, creating significant overhead for dependency management and discovery.
- **Maintenance Burden**: Each module acted as a semi-autonomous application, making cross-domain refactoring and system-wide updates difficult.

The current architecture streamlines these into unified business domains with single-purpose Actions to improve maintainability and developer velocity. During this migration phase, developers may encounter overlapping patterns or legacy logic that still needs to be refactored into the new structure.

## Incomplete Domains

While most domains have been scaffolded, the following have specific implementation gaps:

| Domain | Current State | Missing / Identified Issue |
|---|---|---|
| Mentor | Substantially implemented | Needs further refinement in distinguishing business logic between **School Teachers** and **Industry Supervisors**. |
| Mentee | Models & Livewire Manager exist | Basic competency tracking is present; requires more integration with the Internship domain. |

## maryUI Component Usage

Most Livewire components successfully utilize `x-mary-*` components. However, some core system views (like MCP authorization) and older scaffolded pages still use plain HTML/Tailwind.

## Private Disk Naming Inconsistency

The application code in `DownloadReportAction` and `GenerateReportJob` calls `Storage::disk('private')`. The `config/filesystems.php` does not define a `private` disk explicitly, but the `local` disk points to `storage/app/private` and serves the same purpose. The code should use `Storage::disk('local')` instead, or a `private` alias should be added to the configuration for clarity.
