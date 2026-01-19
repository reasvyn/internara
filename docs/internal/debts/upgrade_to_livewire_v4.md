# Livewire v3 to v4 Upgrade Guide

Below is a complete, actionable migration checklist to move a Livewire v3 app to Livewire v4. It
follows the official upgrade guide and release notes, and adds concrete commands, search/replace
snippets, and verification steps so you can perform the upgrade in a controlled way.

Plan and prerequisites

- Make a branch for the upgrade: git checkout -b upgrade/livewire-3-to-4
- Run the app’s test suite and note failures before upgrade. Keep a backup or tag.
- Use a staging environment for testing before deploying to production.
- Make sure your Laravel version meets Livewire v4 requirements (docs mention Laravel 10+ is
  typical).
- Read the full v4 release notes: https://github.com/livewire/livewire/releases/tag/v4.0.0
- Read the upgrade guide (full): https://github.com/livewire/livewire/blob/main/docs/upgrading.md

Step 0 — update package

- Update composer:
    - composer require livewire/livewire:^4.0
- Clear caches:
    - php artisan optimize:clear
- If you serve Livewire assets yourself, rebuild/publish as appropriate for your setup.

Step 1 — config changes (required) Open config/livewire.php (or publish new config from package if
needed) and apply these changes:

1. Rename keys (search & replace)

- layout -> component_layout
    - Example:
        - Before: 'layout' => 'components.layouts.app',
        - After: 'component_layout' => 'layouts::app',
- lazy_placeholder -> component_placeholder
    - Before: 'lazy_placeholder' => 'livewire.placeholder',
    - After: 'component_placeholder' => 'livewire.placeholder',

2. Changed defaults

- smart_wire_keys now defaults to true
    - If you set it explicitly in your config, consider whether to remove override or set to true to
      match v4.

3. Add new options (if you rely on default behavior or want new features)

- component_locations (array of folders Livewire will scan for view-based components)
- component_namespaces (map for namespaced view components e.g., 'pages' =>
  resource_path('views/pages'))
- make_command => ['type' => 'sfc'|'mfc'|'class', 'emoji' => true|false]
- csp_safe => false by default (enable only if you need CSP mode)

4. Useful commands to add/check:

- To view the canonical v4 config:
  https://github.com/livewire/livewire/blob/main/config/livewire.php
- Search for renamed keys across your repo:
    - grep -R "lazy_placeholder" -n || true
    - grep -R "layout' =>" -n || true

Step 2 — routing

- Replace Route::get(..., Component::class) full-page usage with Route::livewire where appropriate
  (recommended and required for SFC/MFC pages):
    - Before:
        - Route::get('/dashboard', Dashboard::class);
    - After:
        - Route::livewire('/dashboard', Dashboard::class);
    - For view-based pages:
        - Route::livewire('/dashboard', 'pages::dashboard');

Bulk find/replace suggestions:

- grep -R "Route::get(.\*::class" -n
- Manually review each full-page route and determine if Route::livewire is appropriate.

Step 3 — component tags & slot support

- Component tags must be closed in v4 (because slots are supported).
    - Self-close single-tag components:
        - Before: <livewire:foo>
        - After: <livewire:foo />
- Search for unclosed tags (manual inspection recommended):
    - grep -R "<livewire:" resources/views -n
    - Inspect each occurrence and ensure proper closing / slot usage.

Step 4 — wire:model behavioral change

- wire:model now ignores child events by default (equivalent to .self).
- If your code relied on bubbling child events, explicitly use .deep:
    - Before: <div wire:model="value">...</div>
    - After: <div wire:model.deep="value">...</div>
- Search for potential places:
    - grep -R "wire:model=" -n resources/views
    - Audit any container usage (non-input elements) and decide whether to add .deep.

Step 5 — wire:scroll -> wire:navigate:scroll

- Replace wire:scroll usage for navigate-preserve-scroll with wire:navigate:scroll:
    - grep -R "wire:scroll" -n
    - Replace examples used for navigations:
        - Before (v3): <div class="overflow-y-scroll" wire:scroll>...
        - After (v4): <div class="overflow-y-scroll" wire:navigate:scroll>...

Step 6 — wire:transition changes

- wire:transition now uses the View Transitions API; many modifiers were removed (.opacity, .scale,
  .duration, .origin, etc.)
- Audit and remove/replace transition modifiers:
    - grep -R "wire:transition\." -n
    - Replace with plain <div wire:transition>...</div> and implement CSS transitions or the View
      Transitions API where needed.
- Test browser compatibility (Firefox fallback was implemented in v4 but validate your target
  browsers).

Step 7 — stream() signature change (server-side)

- If your code calls $this->stream(...) update parameter order/named params:
    - Before: $this->stream(to: '#container', content: 'Hello', replace: true);
    - After: $this->stream(content: 'Hello', replace: true, el: '#container');
- Positional parameters:
    - Before: $this->stream('#container', 'Hello');
    - After: $this->stream('Hello', el: '#container');
- Search for $this->stream( in code and update.

Step 8 — mount() changes for internal extension

- If you extend LivewireManager or call mount() directly, update signature to accept $slots:
    - Before: mount($name, $params = [], $key = null)
    - After: mount($name, $params = [], $key = null, $slots = [])
- Most apps won’t need changes; only update if your code touches these internals.

Step 9 — JavaScript changes & hook migrations Major JS deprecations and new interceptor APIs:

1. $wire.$js deprecation (syntax changed)

- Old: $wire.$js('name', fn(){})
- New: $wire.$js.name = () => { ... } OR this.$js.name = () => { ... }
- If you used global $js(...) without prefix, migrate to this.$js or $wire.$js

2. commit and request hooks -> interceptors

- Deprecated: Livewire.hook('commit', ...) and Livewire.hook('request', ...)
- New recommended APIs:
    - Livewire.interceptMessage(({ component, message, onFinish, onSuccess, onError, onFailure }) =>
      { ... })
    - Livewire.interceptRequest(({ request, onResponse, onSuccess, onError, onFailure }) => { ... })
- Example migration:
    - Old commit hook:
        - Livewire.hook('commit', ({ respond, succeed, fail }) => { respond(...); succeed(...);
          fail(...); })
    - New intercept:
        - Livewire.interceptMessage(({ onFinish, onSuccess, onFailure }) => { onFinish(...);
          onSuccess(({ payload }) => {...}); onFailure(...); })
- Search for Livewire.hook( in your assets and migrate.

3. interceptors support finer lifecycle hooks, failure vs server error separation, cancellation.

- Use onFailure for network errors; onError for server errors; onSuccess for successes.

Search & replace examples:

- grep -R "Livewire.hook(" resources/js -n
- grep -R "\$js(" -n (to find old $js usage)
- Replace with the new APIs and test behavior.

Step 10 — Volt / SFC / MFC changes If you used Volt or migration to SFC/MFC:

- Livewire v4 includes single-file components (SFC) and multi-file components (MFC).
- Migration actions:
    1. Replace Livewire\Volt\Component usages in Volt class-based components:
        - use Livewire\Volt\Component -> use Livewire\Component
    2. Remove Volt service provider (if present):
        - rm app/Providers/VoltServiceProvider.php
        - Remove provider from bootstrap/providers.php
    3. Remove the Volt package if you no longer need it:
        - composer remove livewire/volt
- Alternatively keep Volt if you prefer its API, but v4's SFC covers most use cases.

Step 11 — new features & opt-ins (optional but useful to test)

- Islands (performance patterns) — consider refactoring heavy parts into @island blocks.
- lazy.bundle / defer / bundled loading — evaluate how your lazy/deferred components should load.
- .async modifier and #[Async] for parallel actions — adopt where actions can safely run in
  parallel.
- wire:sort, wire:intersect, wire:ref — adopt if you need sorting/viewport/element referencing
  features.
- data-loading attribute — update CSS to use .data-loading: or Tailwind data-loading utilities for
  loading states.
- $errors magic property in JS — if you use JS checks for errors, consider migrating to $errors.

Step 12 — UI directives that changed

- wire:scroll -> wire:navigate:scroll (see Step 5).
- wire:transition modifiers removed — migrate to View Transitions or CSS as needed.
- If you rely on smart wire:key behavior, v4 defaults smart_wire_keys => true. Audit loops and
  wire:key usage but manual keys in loops still required.

Step 13 — testing & CI

- Update tests where they depended on old semantics (e.g., polling blocking behavior, commit/request
  hooks).
- Livewire v4 docs recommend Pest for examples; you can keep PHPUnit but check your test helpers.
- If you use CI steps that build Livewire assets, update build pipeline for any asset changes or
  caching changes.
- Run full test suite; manually run browser tests for interactions (View Transitions, Islands, Lazy
  loading).

Step 14 — docs & deprecations check

- Search your codebase for deprecated APIs:
    - Livewire.hook('commit' OR 'request')
    - $js(' or $wire.$js(' with function param
    - wire:transition.\* modifiers
    - wire:scroll usage
- Replace and test.

Step 15 — verification checklist (developer & staging)

- Unit tests green.
- Browser tests (Dusk/Puppeteer/Pest browser) pass for critical flows:
    - Form submit and validation
    - File uploads and S3 URL rewriting if used
    - Navigation and history/preserve-scroll
    - any islands and lazy loading scenarios
- Manual checklist:
    - Verify main pages render and full-page components via Route::livewire work.
    - Verify component scripts and styles included in SFCs load and run ($wire and this binding).
    - Verify wire:model binding works for inputs and confirm .deep usage if needed.
    - Check any custom stream() usage updates function properly.
    - Verify loading states styling via data-loading attribute.
    - Check View Transitions for key flows; fallback behavior in Firefox.
- Run profiling/monitoring on staging for performance regressions.

Step 16 — rollback & post-deploy monitoring

- Keep the release tag/branch ready to roll back.
- After deployment to production, monitor logs, Sentry, and performance dashboards for errors
  introduced by changes.
- Have a short rollback window and plan.

Common search & replace snippets (examples)

- Replace wire:scroll
    - grep -R "wire:scroll" -n
    - Use an editor or sed to update occurrences to wire:navigate:scroll where used for navigation.
- Replace stream param calls:
    - grep -R "\$this->stream(" -n
    - Manually open and update to named params: $this->stream(content: '... ', el: '#...')

Examples of code transformations (copy/paste friendly)

- Route migration
    - Before:
        - Route::get('/dashboard', Dashboard::class);
    - After:
        - Route::livewire('/dashboard', Dashboard::class);

- wire:model container case
    - Before:
        - <div wire:model="value"><input ... /></div>
    - After:
        - <div wire:model.deep="value"><input ... /></div>

- JS hook migration
    - Before:
        - Livewire.hook('commit', ({ respond, succeed, fail }) => { respond(...); succeed(...); })
    - After:
        - Livewire.interceptMessage(({ onFinish, onSuccess, onFailure }) => { onFinish(() => { ...
          }); onSuccess(({ payload }) => { ... }); onFailure(() => { ... }); });

- $js migration
    - Before:
        - $wire.$js('bookmark', () => { ... })
    - After:
        - $wire.$js.bookmark = () => { ... }
        - or in SFC script: this.$js.bookmark = () => { ... }

Checklist summary (quick)

1. Create branch & backup, run tests.
2. composer require livewire/livewire:^4.0; php artisan optimize:clear.
3. Update config/livewire.php: rename keys, add new options.
4. Migrate routes to Route::livewire where appropriate.
5. Close component tags; add slot support where used.
6. Update wire:model usage (.deep where needed).
7. Replace wire:scroll with wire:navigate:scroll where used for navigation.
8. Remove/replace wire:transition modifiers; test view transitions.
9. Update $this->stream(...) calls to new signature.
10. Migrate JS hooks: Livewire.hook -> Livewire.interceptMessage / interceptRequest.
11. Migrate $js syntax to property style.
12. Volt users: replace imports, remove provider, (optional) composer remove livewire/volt.
13. Run tests, browser tests, manual verification.
14. Deploy to staging; monitor; deploy to production with rollback plan.

Helpful links

- Livewire v4 release: https://github.com/livewire/livewire/releases/tag/v4.0.0
- Upgrade guide (docs/upgrading.md):
  https://github.com/livewire/livewire/blob/main/docs/upgrading.md
- Full changelog compare: https://github.com/livewire/livewire/compare/v3.6.4...v4.0.0
- JS interceptors docs (in repo docs JS section): /docs/4.x/javascript#interceptors (linked from
  upgrade guide)
