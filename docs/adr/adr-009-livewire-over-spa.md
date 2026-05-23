# ADR-009: Livewire over SPA

## Status
Accepted

## Context
The application needs a reactive, dynamic user interface — dashboards with real-time data,
forms with conditional fields, inline editing, and modal dialogs. The team evaluated two
primary approaches for building the frontend:

1. **Single Page Application (SPA)** with an API backend — Vue.js, React, or Alpine.js as a
   full SPA framework. The backend becomes a JSON API, the frontend is a separate application.
2. **Livewire** — server-rendered HTML with reactive updates via AJAX. PHP handles both
   backend logic and frontend state. JavaScript is augmented but not required.

SPA approaches introduce significant complexity for this application:
- Separate frontend build pipeline, deployment, and testing
- Duplicated validation logic (frontend + backend)
- State synchronization between client and server
- Authentication token management (CSRF, JWT, Sanctum)
- 87+ separate endpoints (one per Livewire component) must be designed as a cohesive API
- Two codebases to maintain for a team that is primarily PHP-focused

Livewire eliminates this complexity by keeping state on the server. Component properties
are PHP class properties; network requests are automatic and transparent. Alpine.js handles
the small amount of client-side interactivity that Livewire cannot (animations, DOM
manipulation, third-party widget integration).

## Decision
The entire UI is built with Livewire 4 components. Alpine.js is the only client-side
JavaScript library — used for transitions, dropdown menus, and third-party widget wrappers.
Tailwind CSS (via DaisyUI and maryUI component libraries) provides styling.

maryUI provides pre-built Livewire components (tables, forms, modals, buttons, badges)
that work out of the box. DaisyUI provides additional Tailwind component classes. Both are
Composer-installed PHP packages, not npm packages, meaning UI components are versioned
alongside backend code.

## Consequences
- **Positive**: Single codebase, single language (PHP), single deployment. No API layer to
  design, version, or maintain.
- **Positive**: Form validation is written once in PHP — `$this->validate()` in Livewire
  components, mirrored on the backend in Actions.
- **Positive**: 87+ Livewire components map directly to 87+ distinct UI states, each tested
  via server-side tests (Pest) without browser automation.
- **Positive**: Server-side rendering means SEO works without prerendering — search engine
  crawlers receive fully rendered HTML.
- **Positive**: maryUI and DaisyUI are Composer-managed — `composer update` updates the UI
  toolkit alongside backend dependencies.
- **Negative**: Every user interaction requires a server round-trip. For frequently-updating
  UI (real-time typing, drag-and-drop, game-like interactions), Livewire feels less
  responsive than a client-rendered SPA. Mitigated by `wire:model.live.debounce` and
  Alpine.js where needed.
- **Negative**: Server must maintain component state in memory (or session) between requests.
  Long-lived pages with many interactive elements increase memory pressure.
- **Negative**: Rich client-side interactions (canvas, WebGL, complex animations) still
  require JavaScript — Alpine.js integration adds complexity.

## References
- `app/Domain/*/Livewire/` — 87 Livewire components
- `resources/views/` — Blade views per domain
- `composer.json` — `livewire/livewire` v4, `mary-ui` dependencies
- `docs/ui-ux.md`
