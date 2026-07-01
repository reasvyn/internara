> **Last updated:** 2026-07-01
> **Changes:** extracted from SKILL.md as dedicated reference — lifecycle hooks, validation, loading states, URL query string, events, nested components, file uploads, computed properties, pagination, route binding, testing

# Patterns — Livewire Implementation Reference

Reference for detailed Livewire implementation patterns. Used by [SKILL.md](../SKILL.md).

## Component Lifecycle Hooks

Execution order in a full request cycle:

```
boot()              → called once at start of every request
mount()             → called once on initial render (not on subsequent updates)
hydrate()           → called every request after state is hydrated
booted()            → called once after boot + mount complete
updating($name, $v) → called before a specific property updates
updated($name, $v)  → called after a specific property updates
dehydrate()         → called every request before sending response to browser
```

### `mount()` — Initialization

Use for: loading initial data, setting default state, resolving route model binding.

```php
public function mount(?string $userId = null): void
{
    $this->user = $userId ? User::findOrFail($userId) : null;
    $this->startDate = now()->format('Y-m-d');
}
```

**Rules:**
- `mount()` receives parameters from `Livewire::mount()` or route binding
- Do NOT trigger side effects here — use a Command Action in a dedicated method
- `mount()` is NOT called on subsequent updates (AJAX round-trips)

### `boot()` — Authorization & Setup

Use for: Gate checks, early abort conditions.

```php
public function boot(): void
{
    Gate::authorize('view', Internship::class);
}
```

### `updating()` / `updated()` — React to Property Changes

Use for: cascading updates, resetting dependent state.

```php
public function updatedDepartmentId(string $value): void
{
    $this->programId = '';
    $this->availablePrograms = Program::where('department_id', $value)->get();
}
```

The `updated{Property}()` naming convention auto-magically hooks into Livewire's lifecycle. `BaseRecordManager` uses `updatedSearch()` and `updatedFilters()` to reset pagination.

## Real-time Validation

### Full Form Validation

```php
public function save(): void
{
    $validated = $this->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
    ]);
}
```

### Per-Field Validation (on blur/keydown)

```php
public function updatedEmail(): void
{
    $this->validateOnly('email', [
        'email' => ['required', 'email', 'unique:users,email'],
    ]);
}
```

```blade
<input wire:model.blur="email" type="email" />
@error('email') <span class="text-error text-sm">{{ $message }}</span> @enderror
```

### `wire:model` Modifiers

| Modifier | Behavior |
|----------|----------|
| `wire:model` | Updates on every input event (debounced) |
| `wire:model.blur` | Updates on blur/unfocus |
| `wire:model.live` | Updates on every keystroke (Livewire 4) |
| `wire:model.debounce.500ms` | Custom debounce delay |
| `wire:model.lazy` | Updates on change event (Legacy — prefer `.blur`) |

### Custom Error Bag Manipulation

```php
public function addCustomError(string $field, string $message): void
{
    $this->addError('custom_field', __('validation.custom_error'));
}

public function clearFieldError(string $field): void
{
    $this->resetValidation($field);
}
```

### Server-Side Validation with Live Feedback

Wrap Action calls and display `ValidationFailedException` or `RejectedException` messages:

```php
public function save(CreateUserAction $action): void
{
    $this->validate();

    try {
        $action->execute($this->form->toArray());
        flash()->success(__('users.created'));
    } catch (ValidationFailedException $e) {
        foreach ($e->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }
}
```

## Loading States & Spinners

### Basic Loading Indicator

```blade
<button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
    <span wire:loading.remove>{{ __('common.save') }}</span>
    <span wire:loading>{{ __('common.saving') }}...</span>
</button>
```

### Spinner for Specific Target

```blade
<button wire:click="deleteUser('{{ $user->id }}')" class="btn btn-error btn-sm">
    {{ __('common.delete') }}
</button>
<span wire:loading wire:target="deleteUser" class="loading loading-spinner loading-sm"></span>
```

### DaisyUI Spinner Patterns

```blade
{{-- Button spinner with daisyUI --}}
<button wire:click="export" class="btn btn-ghost" wire:loading.class="btn-disabled">
    <x-mary-icon name="o-document-arrow-down" wire:loading.remove.delay />
    <span wire:loading wire:target="export" class="loading loading-spinner loading-xs"></span>
    {{ __('common.export') }}
</button>
```

### `wire:dirty` — Unsaved Changes Indicator

```blade
<span wire:dirty class="text-warning text-sm">
    {{ __('common.unsaved_changes') }}
</span>
```

### Delay Classes for UX

```blade
{{-- Show loading after 500ms (prevents flash on fast operations) --}}
<div wire:loading.delay.500ms class="text-sm text-gray-500">
    {{ __('common.searching') }}...
</div>
```

## URL Query String

### `#[Url]` Attribute (Livewire 4)

Persist component state in the URL query string:

```php
use Livewire\Attributes\Url;

class UserManager extends BaseRecordManager
{
    #[Url]
    public string $search = '';

    #[Url(as: 'status')]
    public ?string $filterStatus = null;

    #[Url(history: true)]
    public string $tab = 'active';
}
```

### Options

| Option | Effect |
|--------|--------|
| `as:` | Custom query parameter alias |
| `history:` | Use browser history push (default `false` — uses `replace`) |
| `keep:` | Array of keys to persist even when null |
| `except:` | Array of keys to exclude from URL |

### Navigation Modes

| Mode | Behavior |
|------|----------|
| `history: false` (default) | `replaceState()` — no back-button entry |
| `history: true` | `pushState()` — creates back-button entry |

### Legacy `$queryString` Property (Livewire 3)

```php
protected $queryString = [
    'search' => ['except' => ''],
    'filterStatus' => ['as' => 'status', 'except' => null],
];
```

Prefer `#[Url]` for new code.

## Event System

### Dispatching Events

```php
// From component
$this->dispatch('user-saved', userId: $user->id)->to('admin.user.user-manager');
$this->dispatch('refresh-table')->self();
$this->dispatch('notify', type: 'success', message: 'Done!')->to('sysadmin.announcement.announcement-manager');
```

| Method | Scope |
|--------|-------|
| `$dispatch()` | To named component(s) or global |
| `$dispatchSelf()` | Only to self |
| `$dispatchTo('alias')` | Specific component by alias |
| `$dispatchGlobal()` | All listeners (including Alpine) |

### Listening with `#[On]` Attribute

```php
use Livewire\Attributes\On;

class UserManager extends BaseRecordManager
{
    #[On('user-saved')]
    public function refreshUserList(array $params): void
    {
        $this->resetPage();
    }

    #[On(['user-saved', 'user-deleted'])]
    public function refreshOnAnyChange(): void
    {
        $this->resetPage();
    }
}
```

### Client-Side Listening (Alpine.js `x-on`)

```blade
<div x-on:user-saved.window="console.log('User saved', $event.detail)">
    {{-- Reacts to $dispatchGlobal('user-saved') --}}
</div>
```

```blade
{{-- Via wire:on (Livewire 4 shorthand) --}}
<button wire:click="save" wire:on:user-saved="showSuccess">
    {{ __('common.save') }}
</button>
```

### Passing Event Parameters

```php
// Dispatch with named parameters
$this->dispatch('user-updated', userId: $user->id, action: 'status-change');

// Listen with typed payload
#[On('user-updated')]
public function handleUserUpdated(int $userId, string $action): void
{
    // $userId = 123, $action = 'status-change'
}
```

## Nested Components

### Embedding with `@livewire()` in Blade

```blade
{{-- Parent view --}}
<div>
    <h1>{{ __('users.user_detail') }}</h1>
    @livewire('user.profile.profile-editor', ['userId' => $user->id], key($user->id))
</div>
```

### Programmatic Rendering

```php
// In a controller or another component
$html = Livewire::mount('user.profile.profile-editor', ['userId' => $user->id])->html();
```

### Passing Props to Children

Child component declares `#[Reactive]` property for automatic reactiviy from parent:

```php
use Livewire\Attributes\Reactive;

class ProfileEditor extends Component
{
    #[Reactive]
    public string $userId;

    public function mount(): void
    {
        $this->user = User::findOrFail($this->userId);
    }
}
```

`#[Reactive]` means the child re-renders whenever the parent updates the prop — no manual `$dispatch()` needed.

### Child-to-Parent Communication

Child dispatches up via `$dispatch()->to()` targeting the parent's alias, or via `$dispatchGlobal()`:

```php
// In child component
$this->dispatch('profile-updated', userId: $this->userId)->to('user.user-manager');
```

### `wire:key` — Mandatory in Loops

Every `@foreach` rendering Livewire components must have a unique `wire:key`:

```blade
@foreach($users as $user)
    @livewire('user.profile.profile-editor', ['userId' => $user->id], key('profile-' . $user->id))
@endforeach
```

## File Uploads

### Validation

```php
public $photo;

public function save(): void
{
    $this->validate([
        'photo' => ['required', 'image', 'max:2048'],           // 2MB image
        'document' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB PDF
    ]);

    // $this->photo is automatically a Livewire\Features\SupportFileUploads\TemporaryUploadedFile
}
```

### Temporary Preview URL

```php
public function updatedPhoto(): void
{
    $this->photoPreview = $this->photo->temporaryUrl();
}
```

```blade
@if ($photoPreview)
    <img src="{{ $photoPreview }}" class="w-32 h-32 object-cover rounded" />
@endif
```

### Upload Progress Tracking

```blade
<label class="btn btn-primary" wire:loading.class="btn-disabled">
    <input type="file" wire:model="photo" class="hidden" />
    {{ __('common.upload') }}
</label>

{{-- Progress bar --}}
<div wire:loading wire:target="photo" class="w-full bg-gray-200 rounded-full h-2 mt-2">
    <div class="bg-primary h-2 rounded-full" style="width: 25%"></div>
</div>

{{-- File name after upload --}}
@if ($photo)
    <p class="text-sm mt-1">{{ $photo->getClientOriginalName() }}</p>
@endif
```

### Handling Upload Failures

```php
public function updatedPhoto(): void
{
    try {
        $this->validateOnly('photo');
    } catch (\Livewire\Features\SupportFileUploads\FileUploadException $e) {
        flash()->error(__('common.upload_failed'));
        $this->photo = null;
    }
}
```

### Clearing a File Bag

```php
$this->clearFileBag('photo');
```

### Delegating to MediaLibrary Action

After validating the upload, pass the `TemporaryUploadedFile` to a Command Action:

```php
public function save(UploadUserPhotoAction $action): void
{
    $this->validate();

    try {
        $action->execute(auth()->user(), $this->photo);
        flash()->success(__('users.photo_updated'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }

    $this->clearFileBag('photo');
}
```

## Computed Properties

### `#[Computed]` Attribute

```php
use Livewire\Attributes\Computed;

class UserDashboard extends Component
{
    #[Computed]
    public function activeInternships(): Collection
    {
        return Internship::where('status', InternshipStatus::ACTIVE)->get();
    }

    #[Computed(persist: true)]
    public function statistics(): array
    {
        return [
            'total' => Internship::count(),
            'active' => Internship::where('status', InternshipStatus::ACTIVE)->count(),
        ];
    }
}
```

### Usage in Blade

```blade
{{-- Access as property --}}
@foreach($this->activeInternships as $internship)
    <p>{{ $internship->name }}</p>
@endforeach

<p>Active: {{ $this->statistics['active'] }}</p>
```

### Rules

| Aspect | Guidance |
|--------|----------|
| **Cache behavior** | Recalculates once per render cycle by default |
| **`persist: true`** | Caches across requests until the component is destroyed |
| **When to use** | Expensive queries, derived data that combines multiple sources |
| **When NOT to use** | Trivial getters (`return $this->user->name`) — use public properties |
| **vs Read Action** | Read Action for cross-component or complex business queries; `#[Computed]` for component-specific derived state |

### Clearing Computed Cache

```php
unset($this->activeInternships); // Forces re-evaluation on next access
```

## Pagination

### WithPagination Trait

Applied automatically in `BaseRecordManager`:

```php
use Livewire\WithPagination;

class UserManager extends BaseRecordManager
{
    // $perPage, rows() with paginate() built in
}
```

### Pagination Methods

| Method | Use Case |
|--------|----------|
| `paginate($perPage)` | Standard pagination with page count (default) |
| `simplePaginate($perPage)` | "Prev / Next" only — no page numbers |
| `cursorPaginate($perPage)` | Infinite scroll — uses cursor, scalable on large datasets |

```php
// In a non-BaseRecordManager component
use Livewire\WithPagination;

class InternshipList extends Component
{
    use WithPagination;

    public string $search = '';

    public function render(): View
    {
        return view('program.internship.internship-list', [
            'internships' => Internship::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->paginate(10),
        ]);
    }
}
```

### Page Reset on Filter/Search

```php
// When using WithPagination directly (not BaseRecordManager):
public function updatedSearch(): void
{
    $this->resetPage();
}

public function updatedFilters(): void
{
    $this->resetPage();
}
```

`BaseRecordManager` handles this automatically via `updatedSearch()`, `updatedFilters()`, and `updatedPerPage()`.

### Paginator State

```php
public $paginators = []; // Holds page per named paginator (for multiple paginated lists)
```

```blade
{{-- Default pagination links --}}
{{ $internships->links() }}

{{-- With daisyUI --}}
<div class="mt-4">
    {{ $internships->links('livewire::bootstrap') }}
</div>
```

## URL Parameters & Route Binding

### `#[Url]` for Route Parameters

```php
#[Url(as: 'user')]
public ?string $userId = null;

#[Locked]
public string $section = 'profile';
```

| Attribute | Purpose |
|-----------|---------|
| `#[Url]` | Syncs property with URL query string |
| `#[Locked]` | Property is set from URL/route and never changes — not reactive to user input |

### Route Model Binding in `mount()`

```php
// routes/web/program.php
Route::get('/internships/{internship}', InternshipDetail::class)->name('internships.show');

// Component
class InternshipDetail extends Component
{
    public Internship $internship;

    public function mount(Internship $internship): void
    {
        $this->internship = $internship;
    }
}
```

### Immutable Parameters with `#[Locked]`

```php
use Livewire\Attributes\Locked;

class InternshipDetail extends Component
{
    #[Locked]
    public string $internshipId;

    public function mount(string $internshipId): void
    {
        $this->internshipId = $internshipId;
    }
}
```

`#[Locked]` prevents the property from being updated via `wire:model` or `$set()` from the frontend — security measure against tampering.

## Testing Lifecycle

### Basic Component Test

```php
it('renders the user manager page', function (): void {
    Livewire::test(UserManager::class)
        ->assertOk()
        ->assertSeeHtml('wire:model="search"');
});
```

### Setting Properties

```php
Livewire::test(UserManager::class)
    ->set('search', 'John')
    ->assertSet('search', 'John')
    ->assertSee('John');
```

### Calling Actions

```php
Livewire::test(UserManager::class)
    ->call('deleteUser', $user->id)
    ->assertDispatched('user-deleted');
```

### Assertions Reference

| Method | What It Checks |
|--------|----------------|
| `->assertOk()` | HTTP 200 status |
| `->assertSee('text')` | Rendered output contains text |
| `->assertSeeHtml('<div>')` | Rendered output contains raw HTML |
| `->assertSet('prop', $value)` | Property matches expected value |
| `->assertNotSet('prop', $value)` | Property does NOT match |
| `->assertDispatched('event')` | Event dispatched |
| `->assertNotDispatched('event')` | Event NOT dispatched |
| `->assertHasErrors('field')` | Validation errors on field |
| `->assertHasNoErrors('field')` | No validation errors |
| `->assertFileUploaded('file')` | File was uploaded |
| `->assertFileExists('path')` | File exists on disk |
| `->assertRedirect($route)` | Redirected to route |

### Testing File Uploads

```php
use Livewire\Livewire;

it('uploads a photo', function (): void {
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    Livewire::test(ProfileEditor::class)
        ->set('photo', $file)
        ->call('save')
        ->assertHasNoErrors('photo')
        ->assertDispatched('profile-updated');
});

it('rejects invalid file type', function (): void {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    Livewire::test(ProfileEditor::class)
        ->set('photo', $file)  // Expects image, not PDF
        ->call('save')
        ->assertHasErrors('photo');
});
```
