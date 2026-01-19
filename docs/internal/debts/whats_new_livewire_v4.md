# Technical Analysis: What's New in Livewire v4

Livewire v4 is a paradigm shift for the TALL stack. It moves away from external extensions (like
Volt) toward a unified, high-performance core. This analysis outlines the features most relevant to
Internara's Modular Monolith.

---

## 1. Native Single-File Components (SFC)

Livewire now supports the "Volt syntax" natively.

- **Impact**: We can remove the `livewire/volt` dependency, reducing the maintenance burden and
  keeping our modules lean.
- **Syntax**: PHP logic and Blade templates can reside in one file with automatic styling and script
  scoping.

## 2. Component Islands

Islands allow parts of a component to update independently from the rest of the page.

- **Relevance**: Perfect for our role-based workspaces. For example, the `Student` dashboard can
  refresh the "Attendance Status" island without re-rendering the entire sidebar or navigation.

## 3. Parallel Hydration & Async Actions

Livewire v4 handles requests in parallel and provides an `.async` modifier.

- **Performance**: Significant reduction in "UI Jitter" when a user is performing multiple actions
  (e.g., uploading multiple journal attachments).

## 4. Native Drag-and-Drop (`wire:sort`)

Built-in sortable list support.

- **Relevance**: Simplifies the implementation of "Assessment Indicators" or "Requirement Lists"
  where teachers need to reorder items.

## 5. View Transitions API

Leverages the browser's native API for smooth animations.

- **Aesthetic**: Provides a more polished, "app-like" feel when navigating between modular
  workspaces without the overhead of custom Alpine transitions.

---

_For the execution roadmap, refer to the **[Migration Manual](upgrade_to_livewire_v4.md)**._
