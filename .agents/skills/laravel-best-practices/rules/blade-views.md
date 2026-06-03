# Blade & Views

## What It Enforces

Views mirror domain structure in `resources/views/{domain}/`. maryUI components are preferred over raw HTML. Translation keys replace hardcoded strings. Queries are never executed in views — data is passed from the component/controller.

## Why It Matters

Domain-mirrored views keep template files discoverable. maryUI components provide consistent, accessible UI without reinventing HTML patterns. Translation keys make the application localizable. Prohibiting queries in views prevents N+1 and keeps presentation logic separate from data access.

## When It Applies

Every Blade view should:
- Live in `resources/views/{domain}/{aggregate}/{component-name}.blade.php` for aggregate-specific views, or `resources/views/{domain}/{component-name}.blade.php` for cross-aggregate views
- Use maryUI components (`x-mary-*`) for forms, tables, buttons, modals
- Use `__('domain.key')` for all user-facing strings
- Receive data from the component/controller — never call Eloquent directly
- Use `@json()` for PHP-to-JS data transfer (not inline JSON encoding)
- Use Blade components over `@include` (components have explicit props)
- Use Blade Fragments for partial re-renders in live-updating views

The confirm dialog pattern uses `<x-ui::confirm>` with `wire:model`, `message`, `confirmText`, and `cancelText` props.

Exceptions: None. These conventions apply universally.
