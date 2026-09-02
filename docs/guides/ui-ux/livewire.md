# Livewire 4 — Complete Component Guide

## Description

Livewire is a full-stack framework for Laravel that enables building dynamic, reactive interfaces
using only PHP — no JavaScript required. This guide covers Livewire v4's complete UI system,
from core concepts to advanced patterns, aligned with Internara's architecture.

---

## Table of Contents

1. [Core Concepts & Architecture](#core-concepts--architecture)
2. [Component Structure & Lifecycle](#component-structure--lifecycle)
3. [Data Binding & Reactivity](#data-binding--reactivity)
4. [Form Handling & Validation](#form-handling--validation)
5. [Events & Actions](#events--actions)
6. [File Uploads](#file-uploads)
7. [JavaScript Integration](#javascript-integration)
8. [Performance Optimization](#performance-optimization)
9. [Testing Patterns](#testing-patterns)
10. [New Features in v4](#new-features-in-livewire-v4)

---

## Core Concepts & Architecture

### Philosophy

- Write PHP classes + Blade templates; Livewire handles the JavaScript
- Components are the building blocks — each has state (properties) and behavior (methods)
- Server-rendered with AJAX-powered reactivity under the hood

### Rendering Flow

```
Browser (Blade + wire:* directives)
  ↔ AJAX requests
    ↔ Livewire Component (PHP class with properties/methods)
      ↔ Laravel (Eloquent, Validation, Auth, etc.)
```

1. Component's `render()` method returns a Blade view
2. Livewire injects the rendered HTML into the page
3. User interactions trigger `wire:*` directives
4. AJAX calls invoke component methods on the server
5. Server re-renders and returns updated HTML
6. Livewire morphs the DOM to reflect changes

### Component Rendering in Internara

```php
// app/Modules/Enrollment/Livewire/Registration/ListRecords.php
namespace App\Modules\Enrollment\Livewire\Registration;

use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

class ListRecords extends BaseRecordManager
{
    public string $model = Registration::class;
    public string $moduleName = 'enrollment';
    public string $domainName = 'registration';

    public function columns(): array
    {
        return [
            'student' => ['label' => __('Student'), 'sortable' => true],
            'company' => ['label' => __('Company'), 'sortable' => true],
            'status' => ['label' => __('Status'), 'sortable' => true],
        ];
    }
}
```

---

## Component Structure & Lifecycle

### Component Formats

#### Single-File Components (SFC) — Default in v4

```blade
{{-- resources/views/components/post/⚡create.blade.php --}}
<?php
use Livewire\Component;

new class extends Component {
    public string $title = '';

    public function save()
    {
        // ...
    }
};
?>

<div>
    <input wire:model="title" type="text">
    <button wire:click="save">Save</button>
</div>
```

#### Multi-File Components (MFC)

```shell
php artisan make:livewire post.create --mfc
```

```
resources/views/components/post/⚡create/
├── create.php          # PHP class
├── create.blade.php    # Blade template
├── create.js           # JavaScript (optional)
├── create.css          # Scoped styles (optional)
├── create.global.css   # Global styles (optional)
└── create.test.php     # Pest test (optional)
```

#### Class-Based Components (v3-style, still supported)

```php
namespace App\Livewire;

use Livewire\Component;

class CreatePost extends Component
{
    public $title = '';

    public function render()
    {
        return view('livewire.create-post');
    }
}
```

### Lifecycle Hooks

| Hook | Purpose | When Called |
|------|---------|-------------|
| `mount()` | Initialize component state | Once on initial load (like constructor) |
| `render()` | Return Blade view | On every update |
| `hydrate()` | Restore component state | When state is restored from server |
| `dehydrate()` | Prepare state for client | Before state is sent to client |
| `updating($name, $value)` | Before property update | Before a property is updated |
| `updated($name, $oldValue)` | After property update | After a property is updated |

```php
public function mount(Post $post)
{
    $this->post = $post;
}

public function updatedSearch($value)
{
    // Called whenever $search is updated
    $this->results = Post::where('title', 'like', "%{$value}%")->get();
}
```

---

## Data Binding & Reactivity

### `wire:model` — Two-Way Data Binding

```blade
<input type="text" wire:model="title">
<textarea wire:model="content"></textarea>
<input type="checkbox" wire:model="active">
```

### Modifiers

| Modifier | Behavior | Use Case |
|----------|----------|----------|
| `wire:model` | Updates on `input` event (debounced) | Default, good for most inputs |
| `wire:model.live` | Updates on every keystroke | Real-time search, live preview |
| `wire:model.blur` | Updates when field loses focus | Reduce server requests |
| `wire:model.debounce.500ms` | Debounces updates by 500ms | Search inputs |
| `wire:model.lazy` | Updates only on `change` event | Select elements |
| `wire:model.number` | Casts value to number | Numeric inputs |
| `wire:model.fill` | Pre-fills from old input | After validation errors |

### Computed Properties

```php
use Livewire\Attributes\Computed;

#[Computed]
public function posts()
{
    return Auth::user()->posts;
}
```

Accessed in Blade as `$this->posts` — cached for the request lifecycle.

```blade
@foreach ($this->posts as $post)
    <div>{{ $post->title }}</div>
@endforeach
```

### Reactive Properties (v4)

Properties are automatically reactive. When a property changes, the component re-renders:

```php
public string $search = '';

// Automatically triggers re-render when $search changes
public function updatedSearch($value)
{
    // Called whenever $search is updated
}
```

### `wire:key` in Loops

Always use `wire:key` in loops to help Livewire track DOM elements:

```blade
@foreach ($posts as $post)
    <div wire:key="post-{{ $post->id }}">
        {{ $post->title }}
    </div>
@endforeach
```

---

## Form Handling & Validation

### Basic Form Submission

```blade
<form wire:submit="save">
    <input type="text" wire:model="title">
    @error('title') <span class="error">{{ $message }}</span> @enderror

    <button type="submit">
        <x-loading wire:loading wire:target="save" />
        Save
    </button>
</form>
```

```php
public function save()
{
    $validated = $this->validate([
        'title' => 'required|min:3',
        'content' => 'required',
    ]);

    Post::create($validated);
    return $this->redirect('/posts');
}
```

### `#[Validate]` Attribute — Co-located Rules

```php
use Livewire\Attributes\Validate;

class CreatePost extends Component
{
    #[Validate('required|min:3')]
    public $title = '';

    #[Validate('required|min:3', onUpdate: false)]
    public $content = '';

    public function save()
    {
        $this->validate(); // Validates all #[Validate] properties
        // ...
    }
}
```

**Parameters:**
- `as: 'display name'` — Custom attribute name in error messages
- `message: 'Custom message'` — Custom error message
- `onUpdate: false` — Disable auto-validation on property update
- `translate: false` — Opt out of localization

### Form Objects — Extracted Form Logic

```shell
php artisan livewire:form PostForm
```

```php
namespace App\Livewire\Forms;

use Livewire\Form;
use Livewire\Attributes\Validate;

class PostForm extends Form
{
    #[Validate('required|min:5')]
    public $title = '';

    #[Validate('required|min:5')]
    public $content = '';

    public function store()
    {
        $this->validate();
        Post::create($this->all());
    }
}
```

```php
// In component
public PostForm $form;

public function save()
{
    $this->form->store();
    return $this->redirect('/posts');
}
```

```blade
<input wire:model="form.title">
@error('form.title') {{ $message }} @enderror
```

### Real-Time Validation

```blade
{{-- Validates as user types --}}
<input wire:model.live="email">

{{-- Validates when field loses focus --}}
<input wire:model.live.blur="email">
```

### Validation Error Display with TallStackUI

```blade
<x-input label="Title" wire:model="title" :errors="$errors" />
```

---

## Events & Actions

### Dispatching Events

```php
// Dispatch from component
$this->dispatch('post-created', title: $post->title);
```

### Listening for Events

```php
use Livewire\Attributes\On;

#[On('post-created')]
public function refreshList($title)
{
    // Called when 'post-created' is dispatched
}
```

### Dynamic Event Names

```php
// Dispatch
$this->dispatch("post-updated.{$post->id}");

// Listen
#[On('post-updated.{post.id}')]
public function refreshPost() {}
```

### Parent-Child Communication

```blade
{{-- Listen to child events from parent --}}
<livewire:edit-post @saved="$refresh">
<livewire:edit-post @saved="close($event.detail.postId)">
```

### Action Directives

| Directive | Description |
|-----------|-------------|
| `wire:click="method"` | Call method on click |
| `wire:submit="method"` | Call method on form submit |
| `wire:keydown.enter="method"` | Call method on key press |
| `wire:change="method"` | Call method on value change |
| `wire:transitionend="method"` | Any browser event |

**Modifiers:**
```blade
<button wire:click.prevent="save">Save</button>
<button wire:click.stop="save">Save</button>
<button wire:click.outside="close">Close</button>
<input wire:keydown.ctrl.enter="submit">
```

### Passing Parameters

```blade
<button wire:click="delete({{ $post->id }})">Delete</button>
<button wire:click="delete(post: {{ $post->id }})">Delete</button>
```

```php
// Route model binding in actions
public function delete(Post $post)
{
    $post->delete();
}
```

---

## File Uploads

### Basic File Upload

```php
use Livewire\WithFileUploads;

class UploadPhoto extends Component
{
    use WithFileUploads;

    #[Validate('image|max:1024')]
    public $photo;

    public function save()
    {
        $this->photo->store('photos');
    }
}
```

```blade
<input type="file" wire:model="photo">

@if ($photo)
    <img src="{{ $photo->temporaryUrl() }}" alt="Preview">
@endif
```

### Multiple Uploads

```php
#[Validate('max:10')]
public $photos = [];

public function save()
{
    foreach ($this->photos as $photo) {
        $photo->store('photos');
    }
}
```

### Upload Configuration

```php
// config/livewire.php
'temporary_file_upload' => [
    'disk' => 's3',
    'rules' => ['file', 'max:102400'],
    'directory' => 'tmp',
    'middleware' => 'throttle:60,1',
    'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4'],
    'max_upload_time' => 5,
],
```

### TallStackUI Upload Component

```blade
<x-upload label="Documents" wire:model="documents" multiple
    accept="application/pdf,image/*" hint="Max 10MB per file" />
```

---

## JavaScript Integration

### The `$wire` Object

```blade
<div>
    <button wire:click="$js.increment">+</button>
</div>

<script>
    // Access component from JavaScript
    $wire.count = 5;
    $wire.save();
    $wire.$refresh();
    $wire.$el.querySelector('.modal');

    // Dispatch events
    $wire.$dispatch('post-created', { postId: 2 });

    // Listen for events
    $wire.$on('post-created', (event) => {
        console.log(event.postId);
    });

    // Register JS actions
    this.$js.increment = () => {
        console.log('increment');
    }
</script>
```

### `@script` and `@assets` Directives

```blade
@assets
<script src="https://cdn.example.com/library.js" defer></script>
<link rel="stylesheet" href="https://cdn.example.com/style.css">
@endassets

@script
<script>
    // Runs after DOM is ready, before Alpine initializes
    new Pikaday({ field: $wire.$el.querySelector('[data-picker]') });
</script>
@endscript
```

### Interceptors

```js
// Action interceptors
$wire.intercept(({ action, onSend, onSuccess, onError, onFinish }) => {
    onSend(({ call }) => { /* call: { method, params } */ });
    onSuccess((result) => { /* PHP return value */ });
    onError(({ response, preventDefault }) => { preventDefault(); });
    onFinish(() => { /* After DOM morph */ });
});

// Message interceptors
$wire.interceptMessage(callback);

// Request interceptors
$wire.interceptRequest(callback);
```

### Script Timing

Scripts run when markup is in the DOM but **before** Alpine initializes:
- `$wire.$el` and DOM queries work
- `$refs` and Alpine state don't exist yet (use `init()` hook or `$wire.$nextTick()`)
- `x-cloak` is honored until component initializes

---

## Performance Optimization

### Lazy Loading

```blade
{{-- Component only loads when visible --}}
<div x-intersect="$wire.loadData()">
    Loading...
</div>
```

### Polling

```blade
{{-- Refresh every 2 seconds --}}
<div wire:poll.2s>
    Current time: {{ now() }}
</div>

{{-- Keep-alive polling (doesn't stop when tab is hidden) --}}
<div wire:poll.keep-alive.15s>
    {{ $this->notificationCount }}
</div>
```

### Debouncing & Throttling

```blade
{{-- Debounce input by 500ms --}}
<input wire:model.debounce.500ms="search">

{{-- Throttle to once per second --}}
<input wire:model.throttle.1s="search">
```

### Caching Computed Properties

```php
#[Computed(cache: true, seconds: 300)]
public function expensiveCalculation()
{
    return Cache::remember('key', 300, fn() => /* ... */);
}
```

### Eager Loading & N+1 Prevention

```php
// Use with() to eager load relationships
public function mount()
{
    $this->posts = Post::with('author', 'comments')->get();
}
```

### Defer Loading

```blade
{{-- Load component after page is interactive --}}
<div wire:init="loadData">
    Loading...
</div>
```

---

## Testing Patterns

### Pest (Recommended)

```shell
composer require pestphp/pest --dev --with-all-dependencies
./vendor/bin/pest --init
```

### Configuring Pest for View-Based Components

```php
// tests/Pest.php
pest()->extend(Tests\TestCase::class)
    ->in('Feature', '../resources/views');
```

```xml
<!-- phpunit.xml -->
<testsuite name="Components">
    <directory suffix=".test.php">resources/views</directory>
</testsuite>
```

### Basic Test

```php
it('renders successfully', function () {
    Livewire::test('post.create')
        ->assertStatus(200);
});
```

### Testing Interactions

```php
it('can create a post', function () {
    Livewire::test('post.create')
        ->set('title', 'My Post')
        ->set('content', 'Content here')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/posts');
});
```

### Testing Validation

```php
it('requires title', function () {
    Livewire::test('post.create')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});
```

### Testing Views

```php
it('displays posts', function () {
    Post::factory()->create(['title' => 'My Post']);

    Livewire::test('show-posts')
        ->assertSee('My Post')
        ->assertViewHas('posts', fn($posts) => $posts->count() === 1);
});
```

### Testing with Authentication

```php
it('requires login', function () {
    Livewire::test('dashboard')
        ->assertRedirect('/login');
});

it('shows dashboard for authenticated users', function () {
    $user = User::factory()->create();

    Livewire::test('dashboard')
        ->actingAs($user)
        ->assertSee('Welcome');
});
```

### Browser Testing (Playwright)

```shell
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest
npx playwright install
```

```php
it('can create a post in browser', function () {
    Livewire::visit('post.create')
        ->type('[wire\:model="title"]', 'My Post')
        ->type('[wire\:model="content"]', 'Content')
        ->press('Save')
        ->assertSee('Post created successfully');
});
```

---

## New Features in Livewire v4

### 1. Single-File Components (Default)

The default component format is now single-file — PHP class + Blade template in one file with the `⚡` emoji prefix for easy recognition.

### 2. Multi-File Components

Split components into separate files for better organization:
```
⚡create/
├── create.php
├── create.blade.php
├── create.js
├── create.css
└── create.test.php
```

### 3. New Attribute System

| Attribute | Purpose |
|-----------|---------|
| `#[Layout('layouts::app')]` | Specify component layout |
| `#[Title('Page Title')]` | Set page title |
| `#[On('event')]` | Listen for events |
| `#[Validate('rules')]` | Co-locate validation rules |
| `#[Computed]` | Mark method as computed property |
| `#[Locked]` | Prevent property from being updated from frontend |
| `#[Session]` | Persist property in session |
| `#[Url]` | Persist property in URL query string |
| `#[Renderless]` | Skip re-render after action |

### 4. New `$wire` JavaScript API

```js
$wire.$el           // Root element
$wire.$refresh()    // Refresh component
$wire.$set('prop', value)
$wire.$dispatch('event', data)
$wire.$on('event', callback)
$wire.$nextTick(() => { /* after DOM update */ })
$wire.intercept(callback)
```

### 5. Interceptors

Three levels of request interception:
- **Action interceptors** — per method call
- **Message interceptors** — per component update
- **Request interceptors** — per HTTP request

### 6. Form Objects

Extracted form logic with `Livewire\Form` base class:
```php
class PostForm extends Form
{
    #[Validate('required')]
    public $title = '';
}
```

### 7. Route Model Binding in Actions

```php
public function delete(Post $post)  // Auto-resolved
{
    $post->delete();
}
```

### 8. Layout & Title Attributes

```php
new #[Layout('layouts::dashboard')] #[Title('Create Post')] class extends Component
{
    // ...
}
```

### 9. Improved Script Timing

Scripts run after DOM is present but before Alpine initializes, ensuring `Alpine.data()` providers and `$js` actions are registered before markup evaluates.

### 10. `@assets` Directive

Load external scripts/styles that are only loaded once per page regardless of component instances.

### 11. `wire:navigate` SPA Mode

```blade
<nav wire:navigate>
    <a href="/dashboard">Dashboard</a>  <!-- No full page reload -->
</nav>
```

### 12. `x-intersect` for Lazy Loading

```blade
<div x-intersect="$wire.loadMore()">
    Loading more...
</div>
```

---

## Best Practices for Internara

### 1. Keep Components Thin

Delegate business logic to Actions/Entities (matches project architecture):

```php
// Good: Thin component
public function save()
{
    $this->authorize('create', Post::class);
    CreatePost::execute(new PostData(
        title: $this->title,
        content: $this->content,
    ));
    $this->redirectRoute('posts.index');
}
```

### 2. Use `#[Validate]` for Real-Time Validation

Better UX with minimal code:

```php
#[Validate('required|min:3')]
public $title = '';
```

### 3. Extract Form Objects for Large Forms

Cleaner components, reusable logic:

```php
public PostForm $form;
```

### 4. Use `wire:model.live` Sparingly

Only when real-time validation is needed:

```blade
{{-- Good for search --}}
<input wire:model.live.debounce.300ms="search">

{{-- Avoid for regular forms --}}
<input wire:model="title">
```

### 5. Leverage `#[Computed]`

Cache expensive calculations:

```php
#[Computed]
public function stats(): array
{
    return [
        'total' => Post::count(),
        'published' => Post::published()->count(),
    ];
}
```

### 6. Use `wire:key` in Loops

Helps Livewire track DOM elements:

```blade
@foreach ($posts as $post)
    <div wire:key="post-{{ $post->id }}">
        {{ $post->title }}
    </div>
@endforeach
```

### 7. Prefer Events for Cross-Component Communication

Decouples components:

```php
// Dispatch
$this->dispatch('post-created');

// Listen
#[On('post-created')]
public function refresh() {}
```

### 8. Use `actingAs()` in Tests

Simplifies authenticated testing:

```php
Livewire::test('dashboard')
    ->actingAs(User::factory()->create())
    ->assertSee('Welcome');
```

### 9. Write Spec-Traceable Tests

Every test maps to a requirement ID:

```php
it('SE5Q9-FR-L1: renders record manager with search and pagination', function () {
    // ...
});
```

### 10. Use Interceptors for Cross-Cutting Concerns

Loading states, error handling:

```js
$wire.intercept(({ onSend, onFinish }) => {
    onSend(() => showLoading());
    onFinish(() => hideLoading());
});
```

---

## Related Documentation

- [UI/UX Index](./index.md) — Complete UI system overview
- [Tailwind CSS Guide](./tailwindcss.md) — Styling with Tailwind
- [TallStackUI Guide](./tallstackui.md) — Pre-built components
- [Integration Guide](./integration.md) — How all UI technologies work together
- [Livewire Pattern](../arch/livewire-pattern.md) — Project-specific Livewire patterns
- [Testing Pattern](../arch/testing-pattern.md) — Testing Livewire components

---

## External Resources

| Resource | URL |
|----------|-----|
| Livewire Official Docs | https://livewire.laravel.com/docs |
| Livewire API Reference | https://livewire.laravel.com/docs/api |
| Livewire Discord | https://discord.gg/livewire |