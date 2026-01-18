# ⚡ Livewire 4.0

Livewire 4.0 is finally here.

This release represents a massive step forward for Livewire, bringing powerful new features,
improved developer experience, and a more solid foundation for building dynamic Laravel
applications.

full docs: https://github.com/livewire/livewire/releases/tag/v4.0.0

## Getting Started

**Upgrading from v3?** Follow the [upgrade guide](https://livewire.laravel.com/docs/4.x/upgrading)
for step-by-step instructions.

**Wanna go deep?** Check out the
[Livewire 4.0 series on Laracasts](https://laracasts.com/series/everything-new-in-livewire-4) to get
up to speed.

## What's New

Livewire 4.0 introduces several game-changing features:

### View-based Components

Write your component class, template, styles, and JavaScript all in one file—or use the multi-file
format that keeps everything together in a single directory. The new single-file format is the
default when you run `php artisan make:livewire`, and you can convert between formats anytime with
`php artisan livewire:convert`.

### Route::livewire()

Reference components by name everywhere in your app, including routes. The new `Route::livewire()`
macro provides a consistent way to define full-page component routes.

### Namespaces

Livewire now ships with `pages::` and `layouts::` namespaces by default, with support for custom
namespaces to organize components however your application needs.

### Component Scripts and Styles

Add `<script>` and `<style>` tags directly in your templates. Styles are automatically scoped to
your component, and scripts have access to `this` for component context. Both are served as native
.js/.css files with browser caching.

### Islands

Create isolated regions within a component that update independently from the rest of your page.
Islands can be lazy-loaded, named for cross-component targeting, and support appending content for
infinite scroll patterns. When combined with computed properties, islands optimize queries from the
database all the way to rendered HTML.

### Slots and Attribute Forwarding

Full Blade component parity—inject content into child components while keeping everything reactive,
and forward HTML attributes seamlessly.

### Drag Sorting

Use `wire:sort` to make any group of elements draggable and sortable with smooth animations—no
external library required. Supports drag handles, multiple lists, and custom positioning logic.

### Smooth Transitions

The `wire:transition` directive adds hardware-accelerated animations using the browser's View
Transitions API. Control transition direction for step wizards and carousels.

### Optimistic UI

Make interfaces feel instant with directives that update immediately:

- `wire:show` - Toggle visibility using CSS
- `wire:text` - Update text content
- `wire:bind` - Bind any HTML attribute reactively
- `$dirty` - Track unsaved changes

### Loading States

Automatic `data-loading` attributes on any element that triggers a network request, making it simple
to style loading states directly from CSS.

### Inline Placeholders

Define loading states with the `@placeholder` directive right next to the content they replace—no
separate placeholder views needed.

### JavaScript Power Tools

When you need to drop into JavaScript, Livewire 4 meets you there:

- `wire:ref` - Name elements and target them from PHP or JavaScript
- `#[Json]` - Return data directly to JavaScript
- `$js` actions - Run client-side only actions
- Interceptors - Hook into requests at every level with component-level or global interceptors

...and dozens of bug fixes, performance improvements, and quality-of-life enhancements.

## Thank You

A massive thank you to everyone who contributed to this release—whether through code, bug reports,
documentation, or community support. Special recognition to @joshhanley and @ganyicz for their
tireless work on core features and fixes throughout this release cycle.

## What's Changed (since the last beta release)

- Fix morph markers when using `view:cache` by @27pchrisl in
  https://github.com/livewire/livewire/pull/8068
- Change the error modal to be a dialog element by @joshhanley in
  https://github.com/livewire/livewire/pull/9320
- Fix disable back button cache not being reset by @joshhanley in
  https://github.com/livewire/livewire/pull/9327
- Fix snapshot errors with smart wire keys by @joshhanley in
  https://github.com/livewire/livewire/pull/9310
- Fix pagination path by @joshhanley in https://github.com/livewire/livewire/pull/9332
- Update documentation for published stubs by @manelgavalda in
  https://github.com/livewire/livewire/pull/9330
- Add return docblock for Testable `invade()` method by @PerryvanderMeer in
  https://github.com/livewire/livewire/pull/9325
- Remove invalid void return by @AJenbo in https://github.com/livewire/livewire/pull/9309
- Add `withoutLazyLoading` to facade docblock by @NicTorgersen in
  https://github.com/livewire/livewire/pull/9298
- Fix incorrect `getValue()` exception message labelled with `set` by @rinodrummer in
  https://github.com/livewire/livewire/pull/9282
- Fix component not found error by @joshhanley in https://github.com/livewire/livewire/pull/9339
- Remove unused `mount()` arguments from Livewire directive by @AJenbo in
  https://github.com/livewire/livewire/pull/9194
- Insert new `wire:stream` content into the DOM by @austincarpenter in
  https://github.com/livewire/livewire/pull/9204
- Change facade to reference class directly by @AJenbo in
  https://github.com/livewire/livewire/pull/9220
- Fix `method_exists()` error when resetting arrays using nested syntax by @danie-ramdhani in
  https://github.com/livewire/livewire/pull/9221
- Add return type to FrontendAssets `scripts()` and `styles()` methods by @AJenbo in
  https://github.com/livewire/livewire/pull/9274
- Fix navigate progress bar so it completes on page load by @iammarjamal in
  https://github.com/livewire/livewire/pull/9196
- Add navigate `preserve-scroll` by @joshhanley in https://github.com/livewire/livewire/pull/9341
- Add release tokens for deployment invalidation by @joshhanley in
  https://github.com/livewire/livewire/pull/9326
- Fix error when there is a single parameter in `Livewire.dispatch()` by @GC-Max in
  https://github.com/livewire/livewire/pull/9163
- Fix optional route parameters by @moisish in https://github.com/livewire/livewire/pull/9014
- Allow rewriting of temporary upload/download urls for S3 by @austincarpenter in
  https://github.com/livewire/livewire/pull/8963
- Add default middleware to `FileUploadController` so it can be overridden by @marianperca in
  https://github.com/livewire/livewire/pull/8953
- Smart wire keys fixes by @joshhanley in https://github.com/livewire/livewire/pull/9351
- Add support for multiple action parameters to `wire:target` by @blazery in
  https://github.com/livewire/livewire/pull/8940
- Fix csp nonce causing duplicate Alpine/Livewire instance by @dorkster100 in
  https://github.com/livewire/livewire/pull/8793
- Make wire:navigate.hover cache temporary by @stancl in
  https://github.com/livewire/livewire/pull/8175
- Support hooks within form classes by @gdebrauwer in https://github.com/livewire/livewire/pull/8682
- Support for partials by @calebporzio in https://github.com/livewire/livewire/pull/9362
- Docs: Remove duplicated "the" word from `wire-model.md` by @matiaslauriti in
  https://github.com/livewire/livewire/pull/9373
- Memoize discovered legacy computed properties by @joshhanley in
  https://github.com/livewire/livewire/pull/9372
- Fix long filenames in file uploads by @joshhanley in
  https://github.com/livewire/livewire/pull/9360
- Fix navigate loading conflict by @joshhanley in https://github.com/livewire/livewire/pull/9378
- Fix navigate disable progress bar by @joshhanley in https://github.com/livewire/livewire/pull/9379
- Fix navigate failed fetch handling by @joshhanley in
  https://github.com/livewire/livewire/pull/9380
- Requests by @joshhanley in https://github.com/livewire/livewire/pull/9395
- Fix sfc compiler clean up by @joshhanley in https://github.com/livewire/livewire/pull/9398
- Add multi file component support by @joshhanley in https://github.com/livewire/livewire/pull/9404
- Paginators by @joshhanley in https://github.com/livewire/livewire/pull/9415
- Fix morph markers generation on Octane by @AlexandreBonaventure in
  https://github.com/livewire/livewire/pull/9399
- Add Route::livewire() macro by @calebporzio in https://github.com/livewire/livewire/pull/9454
- Add wire:ref by @calebporzio in https://github.com/livewire/livewire/pull/9457
- Add CSP-safe by @calebporzio in https://github.com/livewire/livewire/pull/9461
- Fix morph inside custom web elements by @joshhanley in
  https://github.com/livewire/livewire/pull/9474
- Fix `wire:current` after morph by @joshhanley in https://github.com/livewire/livewire/pull/9384
- Single file components by @calebporzio in https://github.com/livewire/livewire/pull/9466
- Rewrite request system by @calebporzio in https://github.com/livewire/livewire/pull/9488
- Islands by @calebporzio in https://github.com/livewire/livewire/pull/9483
- Slots by @calebporzio in https://github.com/livewire/livewire/pull/9497
- Html attribute forwarding by @calebporzio in https://github.com/livewire/livewire/pull/9498
- Fix tests by @joshhanley in https://github.com/livewire/livewire/pull/9500
- Fix tests dependent on new requests by @joshhanley in
  https://github.com/livewire/livewire/pull/9502
- [3.x] Fix wire loading multiple methods and params by @joshhanley in
  https://github.com/livewire/livewire/pull/9513
- Fix redirect interceptor by @joshhanley in https://github.com/livewire/livewire/pull/9515
- Fix nested lazy islands by @joshhanley in https://github.com/livewire/livewire/pull/9514
- Add `prepareViewsForCompilationUsing` by @ganyicz in
  https://github.com/livewire/livewire/pull/9529
- Replace `lazy_placeholder` with `component_placeholder` in lazy docs by @hebbet in
  https://github.com/livewire/livewire/pull/9524
- [3.x] update wire-model.js by @ganyicz in https://github.com/livewire/livewire/pull/9565
- Support class namespaces in make command by @joshhanley in
  https://github.com/livewire/livewire/pull/9573
- [3.x] Add file append support for Flux by @joshhanley in
  https://github.com/livewire/livewire/pull/9583
- Add Volt to upgrade guide by @joshhanley in https://github.com/livewire/livewire/pull/9584
- Fixed mode not defined error by @guratr in https://github.com/livewire/livewire/pull/9581
- Fix ensureAnonymousClassHasReturn regex to check only class declaration by @cyppe in
  https://github.com/livewire/livewire/pull/9602
- Fix: Multi-File Components Generate Empty Script Files by @cyppe in
  https://github.com/livewire/livewire/pull/9603
- Support test files with single file components by @calebporzio in
  https://github.com/livewire/livewire/pull/9604
- Unify internal and user-defined debounce on `wire:model` by @ganyicz in
  https://github.com/livewire/livewire/pull/9597
- Change `wire:scroll` to `wire:navigate:scroll` by @calebporzio in
  https://github.com/livewire/livewire/pull/9605
- Allow forwarding wire:model using by @calebporzio in
  https://github.com/livewire/livewire/pull/9606
- Fix `data-current` to use exact matching by @joshhanley in
  https://github.com/livewire/livewire/pull/9622
- Fix morph markers by @joshhanley in https://github.com/livewire/livewire/pull/9638
- Add php 8.5 support by @joshhanley in https://github.com/livewire/livewire/pull/9641
- Fix anonymous class return regex by @joshhanley in https://github.com/livewire/livewire/pull/9647
- Fix tests by @joshhanley in https://github.com/livewire/livewire/pull/9648
- Fix component scripts by @joshhanley in https://github.com/livewire/livewire/pull/9649
- Add #[Json] by @calebporzio in https://github.com/livewire/livewire/pull/9652
- Add wire:current.ignore by @ganyicz in https://github.com/livewire/livewire/pull/9655
- Fix navigate back after error by @joshhanley in https://github.com/livewire/livewire/pull/9659
- Event interceptors by @calebporzio in https://github.com/livewire/livewire/pull/9653
- Fix morph markers in scripts by @joshhanley in https://github.com/livewire/livewire/pull/9666
- Backport wire:current.ignore to v3 by @ganyicz in https://github.com/livewire/livewire/pull/9668
- History coordinator by @joshhanley in https://github.com/livewire/livewire/pull/9661
- Fix component tag regex for short attribute syntax by @calebporzio in
  https://github.com/livewire/livewire/pull/9669
- Add `$slots` syntax for named slots by @calebporzio in
  https://github.com/livewire/livewire/pull/9670
- Island fixes by @calebporzio in https://github.com/livewire/livewire/pull/9672
- Change island append syntax by @calebporzio in https://github.com/livewire/livewire/pull/9673
- Add $wire.$dirty() by @calebporzio in https://github.com/livewire/livewire/pull/9674
- Shore up interceptors by @calebporzio in https://github.com/livewire/livewire/pull/9683
- Fix Blade conditional wire transitions by @joshhanley in
  https://github.com/livewire/livewire/pull/9690
- Add navigate `onSwap` by @ganyicz in https://github.com/livewire/livewire/pull/9677
- Delete untitled files by @ramonrietdijk in https://github.com/livewire/livewire/pull/9471
- Fix missing named argument on registering class based component in docs by @danie-ramdhani in
  https://github.com/livewire/livewire/pull/9619
- Fix invalid `wire:stream` selector via escaping by @godismyjudge95 in
  https://github.com/livewire/livewire/pull/9632
- Add documentation about the js directive by @ramonrietdijk in
  https://github.com/livewire/livewire/pull/9470
- Support styles in view-based components by @calebporzio in
  https://github.com/livewire/livewire/pull/9694
- Detect CSP Nonce from Vite and inject automatically by @valorin in
  https://github.com/livewire/livewire/pull/9473
- Consolidate update diffs when array/object size changes or all items change by @calebporzio in
  https://github.com/livewire/livewire/pull/9691
- Fix SingleFileParser test after style support was added by @joshhanley in
  https://github.com/livewire/livewire/pull/9695
- Add support for `optimize:clear` command in `ClearCachedFiles ` by @sajjadhossainshohag in
  https://github.com/livewire/livewire/pull/9611
- Fix component discovery when `livewire.view_path` is customized by @imhayatunnabi in
  https://github.com/livewire/livewire/pull/9578
- Return unwatch to unwatch manually if needed by @MaxencePaulin in
  https://github.com/livewire/livewire/pull/9519
- Add Testable PHPDoc @template type for targeted component by @aguingand in
  https://github.com/livewire/livewire/pull/9509
- Add `$this->redirectAction()` to the docs by @ghabriel25 in
  https://github.com/livewire/livewire/pull/9359
- Fix duplicate route registration when using custom Livewire update route by @joshhanley in
  https://github.com/livewire/livewire/pull/9699
- Add claude GitHub actions 1768234586455 by @calebporzio in
  https://github.com/livewire/livewire/pull/9704
- Drop real-time facade usage to support read-only filesystems by @JaZo in
  https://github.com/livewire/livewire/pull/9703
- Fix incorrect variable in mergeQueuedUpdates causing queued updates to be lost by @benbetterrx in
  https://github.com/livewire/livewire/pull/9697
- Add upgrade information about unclosed livewire tags by @skeemer in
  https://github.com/livewire/livewire/pull/9693
- Fix rendering styles and script multiple times by @stayallive in
  https://github.com/livewire/livewire/pull/9123
- [3.x] add broadcastAs() documentation for Echo events by @faisuc in
  https://github.com/livewire/livewire/pull/9587
- Docs/broadcast as echo events by @calebporzio in https://github.com/livewire/livewire/pull/9710
- Fix/json bigint querystring by @calebporzio in https://github.com/livewire/livewire/pull/9711
- Add better PersistantMiddleware unresolved route handling by @blazery in
  https://github.com/livewire/livewire/pull/9464
- Fixes query string except bug after loading with existing value. by @totov in
  https://github.com/livewire/livewire/pull/9650
- Add lifecycle hooks for `#[reactive]` props by @cyppe in
  https://github.com/livewire/livewire/pull/9643
- Fix version hash for configured asset_url by @calebporzio in
  https://github.com/livewire/livewire/pull/9709
- Add token verification for file upload paths by @joshhanley in
  https://github.com/livewire/livewire/pull/9713
- Add env var for setting livewire tmp file upload disk by @craigpotter in
  https://github.com/livewire/livewire/pull/9400
- Support styles with convert command by @calebporzio in
  https://github.com/livewire/livewire/pull/9715
- Fix Prevent header modification error when streaming responses end by @MrCrayon in
  https://github.com/livewire/livewire/pull/9383
- Shard browser tests for faster PR feedback by @joshhanley in
  https://github.com/livewire/livewire/pull/9716
- Fix `Route::livewire` authorisation middleware support by @joshhanley in
  https://github.com/livewire/livewire/pull/9717
- Fix JS/CSS module loading for nested namespaced components by @joshhanley in
  https://github.com/livewire/livewire/pull/9718
- Fix JS/CSS module loading for Vite dev server and subfolder deployments by @joshhanley in
  https://github.com/livewire/livewire/pull/9721
- Fix View Transitions API compatibility for Firefox by @joshhanley in
  https://github.com/livewire/livewire/pull/9722
- Fix #[Js] attribute documentation by @joshhanley in https://github.com/livewire/livewire/pull/9723
- Fix lazy and defer false not working by @joshhanley in
  https://github.com/livewire/livewire/pull/9725
- Fix error when resolving non-namespaced component classes by @joshhanley in
  https://github.com/livewire/livewire/pull/9726

## New Contributors

- @27pchrisl made their first contribution in https://github.com/livewire/livewire/pull/8068
- @NicTorgersen made their first contribution in https://github.com/livewire/livewire/pull/9298
- @iammarjamal made their first contribution in https://github.com/livewire/livewire/pull/9196
- @GC-Max made their first contribution in https://github.com/livewire/livewire/pull/9163
- @moisish made their first contribution in https://github.com/livewire/livewire/pull/9014
- @marianperca made their first contribution in https://github.com/livewire/livewire/pull/8953
- @dorkster100 made their first contribution in https://github.com/livewire/livewire/pull/8793
- @matiaslauriti made their first contribution in https://github.com/livewire/livewire/pull/9373
- @hebbet made their first contribution in https://github.com/livewire/livewire/pull/9524
- @AqibTeam made their first contribution in https://github.com/livewire/livewire/pull/9539
- @yehuuu6 made their first contribution in https://github.com/livewire/livewire/pull/9543
- @pushpak1300 made their first contribution in https://github.com/livewire/livewire/pull/9446
- @guratr made their first contribution in https://github.com/livewire/livewire/pull/9581
- @cyppe made their first contribution in https://github.com/livewire/livewire/pull/9602
- @godismyjudge95 made their first contribution in https://github.com/livewire/livewire/pull/9632
- @imhayatunnabi made their first contribution in https://github.com/livewire/livewire/pull/9578
- @MaxencePaulin made their first contribution in https://github.com/livewire/livewire/pull/9519
- @aguingand made their first contribution in https://github.com/livewire/livewire/pull/9509
- @ghabriel25 made their first contribution in https://github.com/livewire/livewire/pull/9359
- @JaZo made their first contribution in https://github.com/livewire/livewire/pull/9703
- @benbetterrx made their first contribution in https://github.com/livewire/livewire/pull/9697
- @stayallive made their first contribution in https://github.com/livewire/livewire/pull/9123
- @craigpotter made their first contribution in https://github.com/livewire/livewire/pull/9400
- @MrCrayon made their first contribution in https://github.com/livewire/livewire/pull/9383

**Full Changelog**: https://github.com/livewire/livewire/compare/v3.6.4...v4.0.0

---

Full Upgrade guide (docs/upgrading.md)
https://github.com/livewire/livewire/blob/main/docs/upgrading.md

Livewire v4 introduces several improvements and optimizations while maintaining backward
compatibility wherever possible. This guide will help you upgrade from Livewire v3 to v4.

> [!tip] Smooth upgrade path Most applications can upgrade to v4 with minimal changes. The breaking
> changes are primarily configuration updates and method signature changes that only affect advanced
> usage.
>
> Want to save time? You can use [Laravel Shift](https://laravelshift.com) to help automate your
> application upgrades.

## Installation

Update your `composer.json` to require Livewire v4:

```bash
composer require livewire/livewire:^4.0
```

After updating, clear your application's cache:

```bash
php artisan optimize:clear
```

> [!info] View all changes on GitHub
>
> > For a complete overview of all code changes between v3 and v4, you can review the full diff on
> > GitHub: [Compare 3.x to main →](https://github.com/livewire/livewire/compare/3.x...main)

## High-impact changes

These changes are most likely to affect your application and should be reviewed carefully.

### Config file updates

Several configuration keys have been renamed, reorganized, or have new defaults. Update your
`config/livewire.php` file:

> [!tip] View the full config file
>
> > For reference, you can view the complete v4 config file on GitHub:
> > [livewire.php →](https://github.com/livewire/livewire/blob/main/config/livewire.php)

#### Renamed configuration keys

**Layout configuration:**

```php
// Before (v3)
'layout' => 'components.layouts.app',

// After (v4)
'component_layout' => 'layouts::app',
```

The layout now uses the `layouts::` namespace by default, pointing to
`resources/views/layouts/app.blade.php`.

**Placeholder configuration:**

```php
// Before (v3)
'lazy_placeholder' => 'livewire.placeholder',

// After (v4)
'component_placeholder' => 'livewire.placeholder',
```

#### Changed defaults

**Smart wire:key behavior:**

```php
// Now defaults to true (was false in v3)
'smart_wire_keys' => true,
```

This helps prevent wire:key issues on deeply nested components. Note: You still need to add
`wire:key` manually in loops—this setting doesn't eliminate that requirement.

[Learn more about wire:key →](/docs/4.x/nesting#rendering-children-in-a-loop)

#### New configuration options

**Component locations:**

```php
'component_locations' => [
    resource_path('views/components'),
    resource_path('views/livewire'),
],
```

Defines where Livewire looks for single-file and multi-file (view-based) components.

**Component namespaces:**

```php
'component_namespaces' => [
    'layouts' => resource_path('views/layouts'),
    'pages' => resource_path('views/pages'),
],
```

Creates custom namespaces for organizing view-based components (e.g.,
`<livewire:pages::dashboard />`).

**Make command defaults:**

```php
'make_command' => [
    'type' => 'sfc',  // Options: 'sfc', 'mfc', or 'class'
    'emoji' => true,   // Whether to use ⚡ emoji prefix
],
```

Configure default component format and emoji usage. Set `type` to `'class'` to match v3 behavior.

**CSP-safe mode:**

```php
'csp_safe' => false,
```

Enable Content Security Policy mode to avoid `unsafe-eval` violations. When enabled, Livewire uses
the [Alpine CSP build](https://alpinejs.dev/advanced/csp). Note: This mode restricts complex
JavaScript patterns—consult docs before enabling.

### Routing changes

For full-page components, the recommended routing approach has changed:

```php
// Before (v3) - still works but not recommended
Route::get('/dashboard', Dashboard::class);

// After (v4) - recommended for all component types
Route::livewire('/dashboard', Dashboard::class);

// For view-based components, you can use the component name
Route::livewire('/dashboard', 'pages::dashboard');
```

Using `Route::livewire()` is now the preferred method and is required for single-file and multi-file
components to work correctly as full-page components.

[Learn more about routing →](/docs/4.x/components#page-components)

### `wire:model` now ignores child events by default

In v3, `wire:model` would respond to input/change events that bubbled up from child elements. This
caused unexpected behavior when using `wire:model` on container elements (like modals or
accordions).

In v4, `wire:model` now only listens for events originating directly on the element itself
(equivalent to the `.self` modifier behavior).

If you have code that relies on capturing events from child elements, add the `.deep` modifier:

```blade
<!-- Before (v3) - listened to child events by default -->
<div wire:model="value">
    <input type="text" />
</div>

<!-- After (v4) - add .deep to restore old behavior -->
<div wire:model.deep="value">
    <input type="text" />
</div>
```

> [!tip] Most apps won't need changes This change primarily affects non-standard uses of
> `wire:model` on container elements. Standard form input bindings (inputs, selects, textareas) are
> unaffected.

### Use `wire:navigate:scroll`

When using `wire:scroll` to preserve scroll in a scrollable container across `wire:navigate`
requests in v3, you will need to instead use `wire:navigate:scroll` in v4:

```
@persist('sidebar')
    <div class="overflow-y-scroll" wire:scroll> <!-- [tl! remove] -->
    <div class="overflow-y-scroll" wire:navigate:scroll> <!-- [tl! add] -->
        <!-- ... -->
    </div>
@endpersist
```

### Component tags must be closed

In v3, Livewire component tags would render even without being properly closed. In v4, with the
addition of slot support, component tags must be properly closed—otherwise Livewire interprets such
tags differently.

```blade
<!-- Before (v3) - unclosed tag -->
<livewire:component-name>
    <!-- After (v4) - Self-closing tag -->
    <livewire:component-name />
</livewire:component-name>
```

[Learn more about rendering components →](/docs/4.x/components#rendering-components)

[Learn more about slots →](/docs/4.x/nesting#slots)

## Medium-impact changes

These changes may affect certain parts of your application depending on which features you use.

### `wire:transition` now uses View Transitions API

In v3, `wire:transition` was a wrapper around Alpine's `x-transition` directive, supporting
modifiers like `.opacity`, `.scale`, `.duration.200ms`, and `.origin.top`.

In v4, `wire:transition` uses the browser's native
[View Transitions API](https://developer.mozilla.org/en-US/docs/Web/API/View_Transitions_API)
instead. Basic usage still works—elements will fade/transition—but some old modifiers are no longer
supported:

```blade
<!-- This still works in v4 -->
<div wire:transition>...</div>

<!-- These modifiers are no longer supported -->
<div wire:transition.opacity>...</div>
<!-- [tl! remove] -->
<div wire:transition.scale.origin.top>...</div>
<!-- [tl! remove] -->
<div wire:transition.duration.500ms>...</div>
<!-- [tl! remove] -->
```

[Learn more about wire:transition →](/docs/4.x/wire-transition)

### Performance improvements

Livewire v4 includes significant performance improvements to the request handling system:

- **Non-blocking polling**: `wire:poll` no longer blocks other requests or is blocked by them
- **Parallel live updates**: `wire:model.live` requests now run in parallel, allowing faster typing
  and quicker results

These improvements happen automatically—no changes needed to your code.

### Update hooks consolidate array/object changes

When replacing an entire array or object from the frontend (e.g.,
`$wire.items = ['new', 'values']`), Livewire now sends a single consolidated update instead of
granular updates for each index.

**Before:** Setting `$wire.items = ['a', 'b']` on an array of 4 items would fire
`updatingItems`/`updatedItems` hooks multiple times—once for each index change plus `__rm__`
removals.

**After:** The same operation fires the hooks once with the full new array value, matching v2
behavior.

If your code relies on individual index hooks firing when replacing entire arrays, you may need to
adjust. Single-item changes (like `wire:model="items.0"`) still fire granular hooks as expected.

### Method signature changes

If you're extending Livewire's core functionality or using these methods directly, note these
signature changes:

**Streaming:**

The `stream()` method parameter order has changed:

```php
// Before (v3)
$this->stream(to: '#container', content: 'Hello', replace: true);

// After (v4)
$this->stream(content: 'Hello', replace: true, el: '#container');
```

If you're using named parameters (as shown above), note that `to:` has been renamed to `el:`. If
you're using positional parameters, you'll need to update to the following:

```php
// Before (v3) - positional parameters
$this->stream('#container', 'Hello');

// After (v4) - positional/named parameters
$this->stream('Hello', el: '#container');
```

[Learn more about streaming →](/docs/4.x/wire-stream)

**Component mounting (internal):**

If you're extending `LivewireManager` or calling the `mount()` method directly:

```php
// Before (v3)
public function mount($name, $params = [], $key = null)

// After (v4)
public function mount($name, $params = [], $key = null, $slots = [])
```

This change adds support for passing slots when mounting components and generally won't affect most
applications.

## Low-impact changes

These changes only affect applications using advanced features or customization.

### JavaScript deprecations

#### Deprecated: `$wire.$js()` method

The `$wire.$js()` method for defining JavaScript actions has been deprecated:

```js
// Deprecated (v3)
$wire.$js('bookmark', () => {
    // Toggle bookmark...
})

// New (v4)
$wire.$js.bookmark = () => {
    // Toggle bookmark...
}
```

The new syntax is cleaner and more intuitive.

#### Deprecated: `$js` without prefix

The use of `$js` in scripts without `$wire.$js` or `this.$js` prefix has been deprecated:

```js
// Deprecated (v3)
$js('bookmark', () => {
    // Toggle bookmark...
})

// New (v4)
$wire.$js.bookmark = () => {
    // Toggle bookmark...
}
// Or
this.$js.bookmark = () => {
    // Toggle bookmark...
}
```

> [!tip] Old syntax still works Both `$wire.$js('bookmark', ...)` and `$js('bookmark', ...)` will
> continue to work in v4 for backward compatibility, but you should migrate to the new syntax when
> convenient.

#### Deprecated: `commit` and `request` hooks

The `commit` and `request` hooks have been deprecated in favor of a new interceptor system that
provides more granular control and better performance.

> [!tip] Old hooks still work The deprecated hooks will continue to work in v4 for backward
> compatibility, but you should migrate to the new system when convenient.

#### Migrating from `commit` hook

The old `commit` hook:

```js
// OLD - Deprecated
Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
    respond(() => {
        // Runs after response received but before processing
    })

    succeed(({ snapshot, effects }) => {
        // Runs after successful response
    })

    fail(() => {
        // Runs if request failed
    })
})
```

Should be replaced with the new `interceptMessage`:

```js
// NEW - Recommended
Livewire.interceptMessage(({ component, message, onFinish, onSuccess, onError, onFailure }) => {
    onFinish(() => {
        // Equivalent to respond()
    })

    onSuccess(({ payload }) => {
        // Equivalent to succeed()
        // Access snapshot via payload.snapshot
        // Access effects via payload.effects
    })

    onError(() => {
        // Equivalent to fail() for server errors
    })

    onFailure(() => {
        // Equivalent to fail() for network errors
    })
})
```

#### Migrating from `request` hook

The old `request` hook:

```js
// OLD - Deprecated
Livewire.hook('request', ({ url, options, payload, respond, succeed, fail }) => {
    respond(({ status, response }) => {
        // Runs when response received
    })

    succeed(({ status, json }) => {
        // Runs on successful response
    })

    fail(({ status, content, preventDefault }) => {
        // Runs on failed response
    })
})
```

Should be replaced with the new `interceptRequest`:

```js
// NEW - Recommended
Livewire.interceptRequest(({ request, onResponse, onSuccess, onError, onFailure }) => {
    // Access url via request.uri
    // Access options via request.options
    // Access payload via request.payload

    onResponse(({ response }) => {
        // Equivalent to respond()
        // Access status via response.status
    })

    onSuccess(({ response, responseJson }) => {
        // Equivalent to succeed()
        // Access status via response.status
        // Access json via responseJson
    })

    onError(({ response, responseBody, preventDefault }) => {
        // Equivalent to fail() for server errors
        // Access status via response.status
        // Access content via responseBody
    })

    onFailure(({ error }) => {
        // Equivalent to fail() for network errors
    })
})
```

#### Key differences

1. **More granular error handling**: The new system separates network failures (`onFailure`) from
   server errors (`onError`)
2. **Better lifecycle hooks**: Message interceptors provide additional hooks like `onSync`,
   `onMorph`, and `onRender`
3. **Cancellation support**: Both messages and requests can be cancelled/aborted
4. **Component scoping**: Message interceptors can be scoped to specific components using
   `$wire.intercept(...)`

For complete documentation on the new interceptor system, see the
[JavaScript Interceptors documentation](/docs/4.x/javascript#interceptors).

## Upgrading Volt

Livewire v4 now supports single-file components, which use the same syntax as Volt class-based
components. This means you can migrate from Volt to Livewire's built-in single-file components.

### Update component imports

Replace all instances of `Livewire\Volt\Component` with `Livewire\Component`:

```php
// Before (Volt)
use Livewire\Volt\Component;

new class extends Component { ... }

// After (Livewire v4)
use Livewire\Component;

new class extends Component { ... }
```

### Remove Volt service provider

Delete the Volt service provider file:

```bash
rm app/Providers/VoltServiceProvider.php
```

Then remove it from the providers array in `bootstrap/providers.php`:

```php
// Before
return [App\Providers\AppServiceProvider::class, App\Providers\VoltServiceProvider::class];

// After
return [App\Providers\AppServiceProvider::class];
```

### Remove Volt package

Uninstall the Volt package:

```bash
composer remove livewire/volt
```

### Install Livewire v4

After completing the above changes, install Livewire v4. Your existing Volt class-based components
will work without modification since they use the same syntax as Livewire's single-file components.

## New features in v4

Livewire v4 introduces several powerful new features you can start using immediately:

### Component features

**Single-file and multi-file components**

v4 introduces new component formats alongside the traditional class-based approach. Single-file
components combine PHP and Blade in one file, while multi-file components organize PHP, Blade,
JavaScript, and tests in a directory.

By default, view-based component files are prefixed with a ⚡ emoji to distinguish them from regular
Blade files in your editor and searches. This can be disabled via the `make_command.emoji` config.

```bash
php artisan make:livewire create-post        # Single-file (default)
php artisan make:livewire create-post --mfc  # Multi-file
php artisan livewire:convert create-post     # Convert between formats
```

[Learn more about component formats →](/docs/4.x/components)

**Slots and attribute forwarding**

Components now support slots and automatic attribute bag forwarding using `{{ $attributes }}`,
making component composition more flexible.

[Learn more about nesting components →](/docs/4.x/nesting)

**JavaScript in view-based components**

View-based components can now include `<script>` tags without the `@script` wrapper. These scripts
are served as separate cached files for better performance and automatic `$wire` binding:

```blade
<div>
    <!-- Your component template -->
</div>

<script>
    // $wire is automatically bound as 'this'
    this.count++ // Same as $wire.count++

    // $wire is still available if preferred
    $wire.save()
</script>
```

[Learn more about JavaScript in components →](/docs/4.x/javascript)

### Islands

Islands allow you to create isolated regions within a component that update independently,
dramatically improving performance without creating separate child components.

```blade
@island(name: 'stats', lazy: true)
    <div>{{ $this->expensiveStats }}</div>
@endisland
```

[Learn more about islands →](/docs/4.x/islands)

### Loading improvements

**Deferred loading**

In addition to lazy loading (viewport-based), components can now be deferred to load immediately
after the initial page load:

```blade
<livewire:revenue defer />
```

```php
#[Defer]
class Revenue extends Component { ... }
```

**Bundled loading**

Control whether multiple lazy/deferred components load in parallel or bundled together:

```blade
<livewire:revenue lazy.bundle />
<livewire:expenses defer.bundle />
```

```php
#[Lazy(bundle: true)]
class Revenue extends Component { ... }
```

[Learn more about lazy and deferred loading →](/docs/4.x/lazy)

### Async actions

Run actions in parallel without blocking other requests using the `.async` modifier or `#[Async]`
attribute:

```blade
<button wire:click.async="logActivity">Track</button>
```

```php
#[Async]
public function logActivity() { ... }
```

[Learn more about async actions →](/docs/4.x/actions#parallel-execution-with-async)

### New directives and modifiers

**`wire:sort` - Drag-and-drop sorting**

Built-in support for sortable lists with drag-and-drop:

```blade
<ul wire:sort="updateOrder">
    @foreach ($items as $item)
        <li wire:sort:item="{{ $item->id }}" wire:key="{{ $item->id }}">{{ $item->name }}</li>
    @endforeach
</ul>
```

[Learn more about wire:sort →](/docs/4.x/wire-sort)

**`wire:intersect` - Viewport intersection**

Run actions when elements enter or leave the viewport, similar to Alpine's
[`x-intersect`](https://alpinejs.dev/plugins/intersect):

```blade
<!-- Basic usage -->
<div wire:intersect="loadMore">...</div>

<!-- With modifiers -->
<div wire:intersect.once="trackView">...</div>
<div wire:intersect:leave="pauseVideo">...</div>
<div wire:intersect.half="loadMore">...</div>
<div wire:intersect.full="startAnimation">...</div>

<!-- With options -->
<div wire:intersect.margin.200px="loadMore">...</div>
<div wire:intersect.threshold.50="trackScroll">...</div>
```

Available modifiers:

- `.once` - Fire only once
- `.half` - Wait until half is visible
- `.full` - Wait until fully visible
- `.threshold.X` - Custom visibility percentage (0-100)
- `.margin.Xpx` or `.margin.X%` - Intersection margin

[Learn more about wire:intersect →](/docs/4.x/wire-intersect)

**`wire:ref` - Element references**

Easily reference and interact with elements in your template:

```blade
<div wire:ref="modal">
    <!-- Modal content -->
</div>

<button wire:click="$js.scrollToModal">Scroll to modal</button>

<script>
    this.$js.scrollToModal = () => {
        this.$refs.modal.scrollIntoView()
    }
</script>
```

[Learn more about wire:ref →](/docs/4.x/wire-ref)

**`.renderless` modifier**

Skip component re-rendering directly from the template:

```blade
<button wire:click.renderless="trackClick">Track</button>
```

This is an alternative to the `#[Renderless]` attribute for actions that don't need to update the
UI.

**`.preserve-scroll` modifier**

Preserve scroll position during updates to prevent layout jumps:

```blade
<button wire:click.preserve-scroll="loadMore">Load More</button>
```

**`data-loading` attribute**

Every element that triggers a network request automatically receives a `data-loading` attribute,
making it easy to style loading states with Tailwind:

```blade
<button wire:click="save" class="data-loading:opacity-50 data-loading:pointer-events-none">
    Save Changes
</button>
```

[Learn more about loading states →](/docs/4.x/loading-states)

### JavaScript improvements

**`$errors` magic property**

Access your component's error bag from JavaScript:

```blade
<div wire:show="$errors.has('email')">
    <span wire:text="$errors.first('email')"></span>
</div>
```

[Learn more about validation →](/docs/4.x/validation)

**`$intercept` magic**

Intercept and modify Livewire requests from JavaScript:

```blade
<script>
    this.$intercept('save', ({ ... }) => {
        // ...
    })
</script>
```

[Learn more about JavaScript interceptors →](/docs/4.x/javascript#interceptors)

**Island targeting from JavaScript**

Trigger island renders directly from the template:

```blade
<button wire:click="loadMore" wire:island.append="stats">Load more</button>
```

[Learn more about islands →](/docs/4.x/islands)

## Getting help

If you encounter issues during the upgrade:

- Check the [documentation](https://livewire.laravel.com) for detailed feature guides
- Visit the [GitHub discussions](https://github.com/livewire/livewire/discussions) for community
  support
