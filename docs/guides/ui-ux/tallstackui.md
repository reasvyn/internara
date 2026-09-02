# TallStackUI v4 — Complete Component Reference

## Description

TallStackUI is a comprehensive suite of 80+ Blade components for TALL-stack applications
(Tailwind, Alpine, Laravel, Livewire). This guide covers the complete component API, from
form inputs to complex interactions, aligned with Internara's UI system.

---

## Table of Contents

1. [Core Concepts & Architecture](#core-concepts--architecture)
2. [Installation & Setup](#installation--setup)
3. [Configuration](#configuration)
4. [Form Components](#form-components)
5. [UI Components](#ui-components)
6. [Interaction Components](#interaction-components)
7. [Icon System](#icon-system)
8. [Customization & Theming](#customization--theming)
9. [Integration with Livewire](#integration-with-livewire)
10. [Best Practices](#best-practices-for-internara)

---

## Core Concepts & Architecture

### What It Is

- A pure Blade component library — no JS framework of its own; relies on AlpineJS for interactivity
- Every component is a Blade Component class under `TallStackUi\View\Components\*`
- Built on **TailwindCSS v4** and **AlpineJS v3**

### Component Prefix

All components are namespaced with an optional prefix configurable globally:

```php
// config/tallstackui.php
'prefix' => env('TALLSTACKUI_PREFIX')
```

Set to `ts-` so components become `<x-ts-alert />`. Default (no prefix): `<x-modal />`, `<x-input />`.

### Component Anatomy

A TallStackUI component typically exposes:
- **HTML attributes** (label, hint, icon, color, size, etc.)
- **Slots** — `prefix`, `suffix`, `header`, `footer`, `title`, `action`, `after`, `before`, `left`, `right`, `text`, `empty`, `interact`, etc.
- **Events** — listened to via Alpine `x-on:event-name.window`
- **Wireable** flag — adds a `wire` prop binding to a Livewire boolean
- **Customize button** in every doc page → reveals named **blocks** of Tailwind classes

### AlpineJS Helper (`$tsui`)

A global Alpine magic provided by TallStackUI that exposes imperative helpers:

```blade
<button x-on:click="$tsui.open.modal('modal-id')">Open</button>
<button x-on:click="$tsui.close.modal('modal-id')">Close</button>
<button x-on:click="$tsui.open.select('languages')">Open</button>
<button x-on:click="$tsui.close.select('languages')">Close</button>
<button x-on:click="$tsui.open.slide('slide-id')">Open</button>
<button x-on:click="$tsui.focus('email')">Focus input</button>
```

### Size Shorthand Convention

Most components accept boolean size flags: `xs`, `sm`, `md` (default), `lg`, sometimes `xl`.

### Color Shorthand Convention

Components use Tailwind palette colors as the `color` prop:
`primary` (default), `secondary`, `slate`, `gray`, `zinc`, `neutral`, `stone`, `red`, `orange`,
`amber`, `yellow`, `lime`, `green`, `emerald`, `teal`, `cyan`, `sky`, `blue`, `indigo`, `violet`,
`purple`, `fuchsia`, `pink`, `rose`, plus extras `mauve`, `olive`, `mist`, `taupe`, `black`.

Variations: bare flag (filled), `light` (tinted bg), `outline` (border only).

---

## Installation & Setup

### Requirements

- PHP **8.1+**
- Laravel **10+**
- Livewire **4+**
- AlpineJS **3+**
- TailwindCSS **4+**

### Composer

```bash
composer require tallstackui/tallstackui:^4.0
```

### Base Layout

The TallStackUI script must be loaded **above** the `@vite` tag and **above** `@livewireStyles`:

```blade
<html>
<head>
    <!-- ... -->
    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
</html>
```

### Tailwind v4 — `resources/css/app.css`

```css
@import "tailwindcss";
@import '../../vendor/tallstackui/tallstackui/css/v4.css';

@plugin '@tailwindcss/forms';

@source '../../vendor/tallstackui/tallstackui/**/*.php';
@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
```

When using soft customization, also add:
```css
@source '../../app/Providers/*.php';
```

### Build & Clear

```bash
npm run build && php artisan optimize:clear
```

---

## Configuration

Publish with:
```bash
php artisan vendor:publish --tag=tallstackui.config
```

File: `config/tallstackui.php` — a flat array mapping components to classes and per-component settings.

| Key | Purpose |
|-----|---------|
| `prefix` | Component prefix (env `TALLSTACKUI_PREFIX`) |
| `color_classes_namespace` | Where to find user color classes |
| `invalidate_global` | Hide form validation errors everywhere by default |
| `floating.scroll_lock` | Lock page scroll while floating components are open |
| `spinner.type` | Default spinner variant for Loading/Select API |
| `top-on-mobile` | Move Toast position to top on mobile |
| `unfiltered` | Default for styled select API search |
| `recycle` | Default for styled API select to keep previous results |
| `card.round` | Default Card border radius |
| `date.start_day` | Default first day of week |
| `loading.indicator` | Default loading indicator icon |
| `icon.custom.guide` | Map internal icons to override default Heroicons |

### Translation

Locale files publish to `lang/{en,id}/tallstackui.php` for translatable strings inside components.

---

## Form Components

All form components share these conventions:
- Wire to a Livewire property with `wire:model="propName"`
- Global `invalidate` flag (or component-level `invalidate` attribute) hides validation error messages
- Most accept `label` (string OR `<x-slot:label>…</x-slot:label>`) and `hint`
- Size flags `xs | sm | md (default) | lg`
- Color variations on checkbox/radio/toggle: full Tailwind palette

### Input (`<x-input>`)

```blade
<x-input /> {{-- type=text --}}
<x-input email /> {{-- type=email --}}
<x-input label="Name" hint="Insert your name" />
<x-input label="Name *" hint="Insert your name" /> {{-- asterisk indicator --}}

{{-- Icon --}}
<x-input label="Name" icon="users" />
<x-input label="Name" icon="cog" position="right" />

{{-- Prefix / Suffix --}}
<x-input prefix="https://" label="Domain" />
<x-input suffix="@gmail.com" label="E-mail" />
<x-input>
    <x-slot:prefix>Prefix</x-slot:prefix>
    <x-slot:suffix>Suffix</x-slot:suffix>
</x-input>

{{-- Buttons in prefix/suffix (addon mode) --}}
<x-input label="Search">
    <x-slot:suffix button>
        <x-button text="Go" sm />
    </x-slot:suffix>
</x-input>

<x-input value="TallStackUI" clearable />
<x-input strip-zeros />
<x-input label="Name" invalidate />
<x-input label="Readonly" value="Readonly" readonly />
<x-input label="Disabled" value="Disabled" disabled />
```

### Select — Three Variants

**Native (`<x-select.native>`)** — plain `<select>`:
```blade
<x-select.native :options="[1,2,3]" />
<x-select.native label="Plan" hint="…" :options="[1,2,3]" />
<x-select.native :options="[['label'=>'TALL','value'=>1], ['label'=>'LIVT','value'=>2]]" />
<x-select.native :options="[['name'=>'TALL','id'=>1]]" select="label:name|value:id" />
```

**Styled (`<x-select.styled>`)** — custom Alpine floating UI:
```blade
<x-select.styled :options="[1,2,3]" />
<x-select.styled label="Select One" placeholder="Custom" hint="…" :options="[1,2,3]" />
<x-select.styled :options="[1,2,3]" required />
<x-select.styled :options="[1,2,3,4,5,6]" multiple />

{{-- Disabled options --}}
<x-select.styled :options="[ ['label'=>'TALL','value'=>1,'disabled'=>true], ['label'=>'LIVT','value'=>2] ]" />

{{-- Grouped options --}}
<x-select.styled :options="[ ['label'=>'Brazil','description'=>'SA','value'=>[ ['label'=>'São Paulo','value'=>4], ['label'=>'Rio','value'=>5] ]], ['label'=>'USA','value'=>[ ['label'=>'NY','value'=>7], ['label'=>'LA','value'=>8] ]] ]" />

<x-select.styled :limit="2" :options="[…]" multiple />     {{-- multi-limit --}}

{{-- Image + description --}}
<x-select.styled :options="[
    ['label'=>'Taylor','value'=>1,'image'=>'https://unavatar.io/github/taylorotwell'],
    ['label'=>'Nuno','value'=>2,'description'=>'Creator of PestPHP'],
]" />

<x-select.styled :options="[…]" searchable />
<x-select.styled :options="[…30 items]" lazy="10" />     {{-- lazy load ≥ 10 --}}

<x-select.styled :options="[…]">
    <x-slot:after>
        <x-button x-on:click="show = false; $dispatch('confirmed', { term: search })">
            <span x-html="`Create user <b>${search}</b>`"></span>
        </x-button>
    </x-slot:after>
</x-select.styled>

{{-- Events --}}
<x-select.styled :options="[…]" multiple x-on:select="alert(`Select: ${JSON.stringify($event.detail.select)}`)"
    x-on:remove="alert(`Remove: ${JSON.stringify($event.detail.select)}`)" />
```

Events: `select` (option picked, detail: `{ select }`), `remove` (option removed, detail: `{ select }`).

**Styled API (`<x-select.styled :request="…">`)** — fetches options from a URL:
```blade
<x-select.styled :request="route('api.users')" />
<x-select.styled :request="route('api.users')" indicator="spinner" />
<x-select.styled :request="route('api.users')" indicator="spinner.bars" />
<x-select.styled :request="route('api.users')" unfiltered />
<x-select.styled :request="[
    'url'    => route('api.users'),
    'method' => 'get',
    'params' => ['library' => 'TallStackUi'],
]" />
<x-select.styled :request="route('api.users')" recycle />
```

Backend contract — the route must return JSON `[{label, value}]` (or use `select="label:col|value:col"` to remap).

### Textarea (`<x-textarea>`)

```blade
<x-textarea />
<x-textarea label="Name" hint="Insert the description" />
<x-textarea label="Description *" />
<x-textarea resize />
<x-textarea resize-auto />
<x-textarea maxlength="10" count />
<x-textarea count />       {{-- shows character count --}}
<x-textarea label="Readonly" value="…" readonly />
<x-textarea label="Disabled" value="…" disabled />
```

### Checkbox (`<x-checkbox>` + `<x-checkbox.group>`)

**Single:**
```blade
<x-checkbox />
<x-checkbox label="Receive Alert" />
<x-checkbox label="Receive Alert" position="left" />
<x-checkbox label="Readonly" checked readonly />
<x-checkbox label="Disabled" checked disabled />
<x-checkbox>
    <x-slot:label>I agree to the <a href="#">terms</a></x-slot:label>
</x-checkbox>
<x-checkbox xs|sm|md|lg />
<x-checkbox color="red" label="Red" />
```

**Group (`<x-checkbox.group>`):**
```blade
<x-checkbox.group label="Features" :options="[
    ['label'=>'Newsletter','value'=>'newsletter','description'=>'Weekly digest'],
    ['label'=>'Alerts','value'=>'alerts','description'=>'Real time'],
    ['label'=>'Reports','value'=>'reports','description'=>'Monthly'],
]" />

<x-checkbox.group list|card|panel|inline :options="$features" />
<x-checkbox.group card :columns="3" :options="$features" />
<x-checkbox.group position="right" :options="$features" />
<x-checkbox.group xs|sm|md|lg :options="$features" />
<x-checkbox.group color="green" :options="$features" />

{{-- Remap array keys --}}
<x-checkbox.group select="label:name|value:id|description:note" :options="$features" />

{{-- Custom body with @interact --}}
<x-checkbox.group card :options="$addons">
    @interact('option', $option)
        <span class="font-semibold">{{ $option['name'] }}</span>
        <span class="font-mono">${{ $option['price'] }}</span>
    @endinteract
</x-checkbox.group>
```

### Radio (`<x-radio>` + `<x-radio.group>`)

Same API as checkbox:

```blade
<x-radio.group label="Plan" :options="[
    ['label'=>'Startup','value'=>'startup','description'=>'5 jobs','aside'=>'$29/mo'],
    ['label'=>'Business','value'=>'business','description'=>'25 jobs','aside'=>'$99/mo'],
    ['label'=>'Enterprise','value'=>'enterprise','description'=>'Unlimited','aside'=>'$249/mo'],
]" />

<x-radio.group list|card|panel|inline :options="$plans" />
<x-radio.group card :columns="3" :options="$plans" />
<x-radio.group position="right" :options="$plans" />
```

### Toggle (`<x-toggle>`)

```blade
<x-toggle />
<x-toggle label="Receive Alert" />
<x-toggle label="Receive Alert" position="left" />
<x-toggle label="Readonly" checked readonly />
<x-toggle label="Disabled" checked disabled />
<x-toggle xs|sm|md|lg />
<x-toggle color="red" label="Red" />
<x-toggle>
    <x-slot:label start>Align on Start</x-slot:label>
</x-toggle>
```

### Date (`<x-date>`)

Format tokens (Day.js): `YYYY MM MMM MMMM D DD d dd ddd dddd [escaped]` plus time tokens.
Backend always receives `YYYY-MM-DD`.

```blade
<x-date />
<x-date label="Date" hint="Select your DoB" />
<x-date label="Readonly" value="2026-08-13" readonly />
<x-date format="YYYY-MM-DD" />
<x-date format="DD [of] MMMM [of] YYYY" />
<x-date helpers />
<x-date :min-date="now()->subWeek()" :max-date="now()->addWeek()" />
<x-date :min-year="2020" :max-year="2024" />

{{-- Disable dates --}}
<x-date :disable="['2020-01-01','2020-01-02']" />
<x-date :disable="[ ['2020-01-01','2020-01-03'], ['2020-01-04','2020-01-06'] ]" />

{{-- Disable specific weekdays --}}
<x-date only="3" /> {{-- 0=Sunday, …, 6=Saturday --}}
<x-date weekdays />
<x-date weekends />

<x-date range wire:model="date" /> {{-- public array $date = ['2021-01-01', '2021-01-31'] --}}
<x-date multiple wire:model="date" />
<x-date start="1" />          {{-- first day of week --}}
<x-date month-year-only />

{{-- Events --}}
<x-date x-on:select="alert(`Selected: ${$event.detail.date}`)"
    x-on:clear="alert('Cleaned!')" />
```

### Time (`<x-time>`)

```blade
<x-time />
<x-time label="Time" hint="Select the hour" />
<x-time label="Readonly" value="10:00 AM" readonly />
<x-time :step-minute="5" />
<x-time format="24" />
<x-time :min-hour="5" :max-hour="10" />
<x-time :min-minute="30" :max-minute="45" />
<x-time required />
<x-time helper />                                 {{-- "current time" button --}}
<x-time :step-hour="3" :step-minute="15" />
<x-time>
    <x-slot:footer>Footer Slot</x-slot:footer>
</x-time>

<x-time x-on:hour="alert(`Hour: ${$event.detail.hour}`)"
    x-on:minute="alert(`Min: ${$event.detail.minute}`)"
    x-on:interval="alert(`Interval: ${$event.detail.interval}`)" />
```

### Upload (`<x-upload>`)

Uses Livewire's normal `WithFileUploads` mechanism:

```blade
<x-upload />
<x-upload label="Screenshot" hint="…" tip="Drag and drop your screenshot here" />
<x-upload close-after-upload />
<x-upload delete />                    {{-- requires deleteUpload() method --}}
<x-upload delete delete-method="deleting" />
<x-upload multiple />                  {{-- property must be array --}}
<x-upload accept="application/pdf" />
<x-upload>
    <x-slot:footer><x-button class="w-full">Save</x-button></x-slot:footer>
</x-upload>
<x-upload>
    <x-slot:footer when-uploaded>
        <x-button class="w-full" wire:click="store">Save</x-button>
    </x-slot:footer>
</x-upload>

<x-upload x-on:upload="console.log($event.detail.files)" />
<x-upload delete x-on:remove="console.log($event.detail.file)" />
```

Recommended `deleteUpload(array $content)` skeleton:
```php
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;

public function deleteUpload(array $content): void
{
    if (! $this->photo) return;
    $files = Arr::wrap($this->photo);
    $file = collect($files)->filter(fn (UploadedFile $item) =>
        $item->getFilename() === $content['temporary_name'])->first();
    rescue(fn () => $file->delete(), report: false);
    $collect = collect($files)->filter(fn (UploadedFile $item) =>
        $item->getFilename() !== $content['temporary_name']);
    $this->photo = is_array($this->photo) ? $collect->toArray() : $collect->first();
}
```

### Other Form Components

| Component | Description |
|-----------|-------------|
| `<x-password>` | Text input with show/hide eye toggle |
| `<x-number>` | Numeric input with up/down controls |
| `<x-currency>` | Formatted currency input |
| `<x-color>` | Color picker |
| `<x-pin>` | OTP/PIN digit input |
| `<x-range>` | Slider |
| `<x-tag>` | Multi-tag entry |
| `<x-autocomplete>` | Searchable suggestions |
| `<x-upload.async>` | Resumable-style uploads with chunked progress |

---

## UI Components

### Modal (`<x-modal>`)

```blade
<x-modal>TallStackUI</x-modal>

<x-modal>
    <x-slot:title>TallStackUI</x-slot:title>
    TallStackUI
</x-modal>

{{-- or via attrs --}}
<x-modal title="TallStackUI">TallStackUI</x-modal>
<x-modal footer="TallStackUI">…</x-modal>

<x-modal scrollable>Long content…</x-modal>
<x-modal blur>…</x-modal> {{-- sm/md/lg/xl blur --}}

{{-- Sizes: sm md lg xl 2xl 3xl 4xl 5xl 6xl 7xl full --}}
<x-modal size="2xl">…</x-modal>

<x-modal center>…</x-modal>                                {{-- desktop only --}}
<x-modal center="md">…</x-modal>                           {{-- bottom sheet< md, centered ≥ md --}}
<x-modal persistent>…</x-modal>                            {{-- no outside-click close --}}
<x-modal handle>…</x-modal>                                {{-- mobile drag handle --}}
<x-modal paddingless>…</x-modal>
<x-modal wire>…</x-modal>                                  {{-- boolean prop $modal --}}
<x-modal wire="tallstackui">…</x-modal>                    {{-- custom prop --}}

<x-modal id="modal-id">…</x-modal>
<x-button x-on:click="$tsui.open.modal('modal-id')">Open</x-button>
<x-button x-on:click="$tsui.close.modal('modal-id')">Close</x-button>

<x-modal x-on:open="alert('Opened!')" x-on:close="alert('Closed!')">…</x-modal>

{{-- Focus helper --}}
<x-modal id="modal-id" x-on:open="$tsui.focus('email')">
    <x-input label="Email" id="email" />
</x-modal>

{{-- Footer alignment --}}
<x-modal>
    TallStackUI
    <x-slot:footer between>
        <x-button color="red">Delete</x-button>
        <x-button>Save</x-button>
    </x-slot:footer>
</x-modal>
```

Events: `open` (modal opens), `close` (modal closes).

### Dropdown (`<x-dropdown>`)

```blade
<x-dropdown text="Menu" position="bottom-end">
    <x-dropdown.items text="Settings" />
    <x-dropdown.items text="Logout" separator />
</x-dropdown>

<x-dropdown text="Open when hover" position="bottom-end" hover>…</x-dropdown>

{{-- Positions: bottom[-start|-end], top[-start|-end], left[-start|-end], right[-start|-end] --}}
<x-dropdown text="Menu" position="bottom-start">…</x-dropdown>

<x-dropdown icon="chevron-down" position="bottom-end">
    <a href="https://google.com.br" target="_blank"><x-dropdown.items text="Google" /></a>
</x-dropdown>

<x-dropdown icon="chevron-down">…</x-dropdown>
<x-dropdown icon="ellipsis-vertical" static>…</x-dropdown> {{-- no rotate animation --}}

<x-dropdown text="Account" xs|sm|md|lg>…</x-dropdown>

{{-- Widths: xxs xs sm md lg xl 2xl --}}
<x-dropdown text="xl" width="xl">…</x-dropdown>

<x-dropdown text="Menu">
    <x-slot:header><x-theme-switch block /></x-slot:header>
    <x-dropdown.items icon="cog" text="Settings" />
    <x-dropdown.items icon="arrow-left-on-rectangle" text="Logout" separator />
</x-dropdown>

<x-dropdown text="Menu" position="bottom-end">
    <x-dropdown.items text="PHP" />
    <x-dropdown.submenu text="Second Level">
        <x-dropdown.items text="JavaScript" />
        <x-dropdown.submenu text="Third Level" position="left-start">
            <x-dropdown.items text="C++" />
            <x-dropdown.items text="Golang" />
        </x-dropdown.submenu>
    </x-dropdown.submenu>
</x-dropdown>

<x-dropdown>
    <x-slot:action>
        <x-button x-on:click="show = !show" sm outline>Open</x-button>
    </x-slot:action>
    <x-dropdown.items icon="cog" text="Settings" />
</x-dropdown>

<x-dropdown x-on:open="alert(…)" x-on:select="alert('Selected')">…</x-dropdown>
```

Events: `open {status}` (open/close), `select` (item clicked).

### Tooltip (`<x-tooltip>`)

Dropped Tippy.js in v4 → smaller bundle.

```blade
<x-tooltip text="TallStackUI" />
<x-tooltip text="Top" position="top" icon="exclamation-circle" />
<x-tooltip text="TallStackUI" xs|sm|md|lg />
<x-tooltip><b>Tall</b><i>Stack</i><u>Ui</u></x-tooltip>

{{-- Color applies to icon; balloon paints the balloon --}}
<x-tooltip text="TallStackUI" color="red" />
<x-tooltip text="TallStackUI" balloon="red" />

<x-tooltip text="TallStackUI" delay="flash" />           {{-- slow | fast | faster | flash --}}
<x-tooltip text="TallStackUI" scale="lg" />              {{-- sm | md | lg; balloon sizes --}}

{{-- On any HTML element via x-tooltip directive --}}
<span x-data x-tooltip="TallStackUI" data-tooltip-delay="faster"></span>

{{-- Disable reactively --}}
<div x-data="{ disabled: false }">
    <x-toggle x-model="disabled" label="Disable the tooltip" />
    <span x-data x-tooltip="TallStackUI" x-bind:data-tooltip-disabled="disabled">
        Hover me
    </span>
</div>
```

### Card (`<x-card>`)

```blade
<x-card>TallStackUI</x-card>

<x-card header="TallStackUI">…</x-card>
<x-card footer="TallStackUI">…</x-card>

<x-card header="TallStackUI" minimize>…</x-card>
<x-card header="TallStackUI" minimize="mount">…</x-card>     {{-- start minimized --}}

<x-card image="https://picsum.photos/750/300">…</x-card>
<x-card position="bottom" image="…">…</x-card>

{{-- Round: bare flag → default rounded-lg; xs sm md lg xl 2xl --}}
<x-card round="sm">…</x-card>

<x-card header="TallStackUI" color="primary">…</x-card>
<x-card header="TallStackUI" color="primary" light>…</x-card>
<x-card header="TallStackUI" color="primary" accent>…</x-card>   {{-- colored top border --}}

<x-card shadowless>…</x-card>
<x-card bordered>…</x-card>
<x-card shadowless bordered>…</x-card>
<x-card header="…" paddingless>…</x-card>

<x-card>
    TallStackUI
    <x-slot:footer between>
        <x-button color="red">Delete</x-button>
        <x-button>Save</x-button>
    </x-slot:footer>
</x-card>

<x-card skeleton /> {{-- 3 body lines --}}
<x-card skeleton="5" header="…" footer image round="xl" />

<x-card loading>…</x-card>
<x-card loading="save" delay="longest">…</x-card>

<x-card header="TallStackUI" minimize
    x-on:minimize="alert('Minimized!')"
    x-on:maximize="alert('Maximized!')"
    x-on:close="alert('Closed!')">…</x-card>
```

Events: `minimize`, `maximize`, `close`. Used inside `#[Lazy]` component `placeholder()` for skeleton.

### Table (`<x-table>`)

Two data modes: simple array or Eloquent paginator (recommended).

```php
// Volt / Class examples
use App\Models\User;
new class extends Component {
    public function with(): array {
        return [
            'headers' => [
                ['index' => 'id',   'label' => '#'],
                ['index' => 'name', 'label' => 'Member'],
                ['index' => 'role', 'label' => '<b>Role</b>', 'unescaped' => true],
            ],
            'rows' => User::all(),
        ];
    }
};
```

```blade
<x-table :$headers :$rows />
<x-table :$headers :$rows headerless />
<x-table :$headers :$rows striped />

{{-- Column alignment --}}
<x-table :headers="[
    ['index' => 'name',     'label' => 'Product'],
    ['index' => 'quantity', 'label' => 'Quantity', 'align' => 'center'],
    ['index' => 'price',    'label' => 'Price',    'align' => 'right'],
]" :rows="[…]" />

{{-- Search --}}
<x-table :$headers :$rows searchable />

{{-- Loading --}}
<x-table :$headers :$rows loading />

{{-- Empty state --}}
<x-table :$headers :$rows>
    <x-slot:empty>No records found</x-slot:empty>
</x-table>

{{-- Actions column --}}
<x-table :$headers :$rows>
    <x-slot:actions>
        <x-button sm>Edit</x-button>
    </x-slot:actions>
</x-table>
```

### Pagination (`<x-pagination>`)

```blade
<x-pagination :paginator="$users" />
<x-pagination :paginator="$users" simple />
```

### Badge (`<x-badge>`)

```blade
<x-badge text="Active" />
<x-badge text="Active" color="green" />
<x-badge text="Active" xs|sm|md|lg />
<x-badge text="Active" round />
<x-badge text="Active" light />
<x-badge text="Active" outline />
<x-badge icon="check" text="Verified" />
```

### Alert (`<x-alert>`)

```blade
<x-alert>Default alert</x-alert>
<x-alert color="green">Success message</x-alert>
<x-alert color="red">Error message</x-alert>
<x-alert color="yellow">Warning message</x-alert>
<x-alert color="blue">Info message</x-alert>

<x-alert close>Closable alert</x-alert>
<x-alert icon="check-circle" color="green">With icon</x-alert>
```

### Loading (`<x-loading>`)

```blade
<x-loading />
<x-loading text="Loading…" />
<x-loading color="primary" />
<x-loading spinner="bars" />
```

### Button (`<x-button>`)

```blade
<x-button>Default</x-button>
<x-button text="Save" />
<x-button color="green">Success</x-button>
<x-button color="red">Danger</x-button>
<x-button color="yellow">Warning</x-button>
<x-button color="blue">Info</x-button>

<x-button sm>Small</x-button>
<x-button lg>Large</x-button>

<x-button outline>Outline</x-button>
<x-button light>Light</x-button>
<x-button circle>+</x-button>

<x-button icon="plus" />
<x-button icon="pencil" sm>Edit</x-button>

<x-button loading>Submit</x-button>
<x-button disabled>Disabled</x-button>

<x-button wire:click="save">Save</x-button>
<x-button wire:loading wire:target="save">Saving…</x-button>
```

### Avatar (`<x-avatar>`)

```blade
<x-avatar :model="$user" />
<x-avatar :model="$user" color="primary" />
<x-avatar :model="$user" xs|sm|md|lg|xl />
<x-avatar :model="$user" round />
<x-avatar label="AB" /> {{-- Initials --}}
```

---

## Interaction Components

### Dialog (`<x-dialog>`)

Confirmation dialogs with async support:

```blade
<x-dialog z-index="z-50" blur="sm">
    <x-dialog.button color="red" wire:click="delete({{ $id }})">
        Delete
    </x-dialog.button>
</x-dialog>

{{-- Or programmatically --}}
<x-button x-on:click="$tsui.interaction('dialog')
    ?.confirm('Are you sure?', 'This will delete the record', {
        accept: { label: 'Yes, delete', method: 'delete', params: {{ $id }} },
        reject: { label: 'Cancel' },
    })">
    Delete
</x-button>
```

### Slide (`<x-slide>`)

Side panel for forms or details:

```blade
<x-slide id="slide-id" title="Details">
    Content here
</x-slide>

<x-button x-on:click="$tsui.open.slide('slide-id')">Open</x-button>
<x-button x-on:click="$tsui.close.slide('slide-id')">Close</x-button>

<x-slide position="right" size="lg">…</x-slide>
<x-slide persistent>…</x-slide> {{-- no outside-click close --}}
```

### Toast (`<x-toast>`)

Notification messages:

```blade
<x-toast position="top-right" />

{{-- Programmatically --}}
<x-button x-on:click="$tsui.interaction('toast')
    ?.success('Success!', 'Record created successfully')">
    Save
</x-button>

<x-button x-on:click="$tsui.interaction('toast')
    ?.error('Error!', 'Something went wrong')">
    Delete
</x-button>
```

### Error (`<x-error>`)

Form error display:

```blade
<x-error />
<x-error :errors="$errors" />
<x-error :errors="$errors" title="Please fix the following errors:" />
<x-error :errors="$errors" color="red" />
```

---

## Icon System

### Default Icons (Heroicons)

TallStackUI uses Heroicons by default:

```blade
<x-input icon="users" />
<x-button icon="plus">Add</x-button>
<x-dropdown.items icon="cog" text="Settings" />
```

### Custom Icon Mapping

```php
// config/tallstackui.php
'icon' => [
    'custom' => [
        'guide' => [
            'bars-4' => 'far.chart-bar',
            'users' => 'fas.users',
        ],
    ],
],
```

---

## Customization & Theming

### Soft Customization

Use the "Customize" button in TallStackUI docs to reveal named blocks:

```css
/* Customize input base */
.input.base {
    @apply border-gray-300 focus:ring-blue-500;
}

/* Customize button sizes */
.wrapper.sizes.sm {
    @apply px-3 py-1.5 text-sm;
}
```

### Color Classes

Create custom color classes in `App\View\Components\TallStackUi\Colors`:

```php
namespace App\View\Components\TallStackUi\Colors;

use TallStackUi\Foundation\Colors\ButtonColor;

class CustomButtonColor extends ButtonColor
{
    public array $classes = [
        'primary' => 'bg-blue-500 hover:bg-blue-600 text-white',
        'secondary' => 'bg-gray-500 hover:bg-gray-600 text-white',
    ];
}
```

---

## Integration with Livewire

### Wire Model Binding

```blade
<x-input label="Name" wire:model="name" />
<x-input label="Email" wire:model.live="email" />
<x-select.styled wire:model="role" :options="$roles" />
<x-checkbox label="Active" wire:model="active" />
<x-toggle label="Notifications" wire:model="notifications" />
```

### Wire Loading States

```blade
<x-button wire:click="save">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving…</span>
</x-button>
```

### Wire Events

```blade
<x-button wire:click="edit({{ $id }})">Edit</x-button>
<x-button wire:click="delete({{ $id }})"
    x-on:click="$tsui.interaction('dialog')?.confirm('Delete?', 'Are you sure?', {
        accept: { label: 'Yes', method: 'delete', params: {{ $id }} },
        reject: { label: 'Cancel' },
    })">
    Delete
</x-button>
```

### Form Validation

```blade
<x-input label="Name" wire:model="name" :errors="$errors" />
<x-input label="Name" wire:model="name" invalidate /> {{-- hide errors --}}
```

---

## Best Practices for Internara

### 1. Consistent Sizing

Use the same size across related components:

```blade
<x-input sm label="Search" wire:model="search" />
<x-button sm wire:click="filter">Filter</x-button>
```

### 2. Semantic Colors

Use semantic colors for actions:

```blade
<x-button color="green">Save</x-button>
<x-button color="red">Delete</x-button>
<x-button color="yellow">Edit</x-button>
```

### 3. Loading States

Always provide loading feedback:

```blade
<x-button wire:click="save">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving…</span>
</x-button>
```

### 4. Confirmation Dialogs

Use dialogs for destructive actions:

```blade
<x-button color="red"
    x-on:click="$tsui.interaction('dialog')?.confirm('Delete?', 'This cannot be undone', {
        accept: { label: 'Delete', method: 'delete', params: {{ $id }} },
        reject: { label: 'Cancel' },
    })">
    Delete
</x-button>
```

### 5. Toast Notifications

Provide feedback after actions:

```php
public function save()
{
    // …
    $this->dispatch('notify', type: 'success', message: 'Record created');
}
```

```blade
<div x-on:notify.window="
    $tsui.interaction('toast')
        ?.success($event.detail.message, 'Success')
" />
```

### 6. Error Display

Show validation errors consistently:

```blade
<x-error :errors="$errors" />
```

### 7. Accessibility

- Always provide `label` for form components
- Use `hint` for additional context
- Ensure color is not the only indicator (use icons + text)

---

## Related Documentation

- [UI/UX Index](./index.md) — Complete UI system overview
- [Livewire Guide](./livewire.md) — Reactive components
- [Tailwind CSS Guide](./tailwindcss.md) — Styling with Tailwind
- [Integration Guide](./integration.md) — How all UI technologies work together
- [UI Pattern](../arch/ui-pattern.md) — Project-specific UI patterns

---

## External Resources

| Resource | URL |
|----------|-----|
| TallStackUI Official Docs | https://tallstackui.com/docs |
| TallStackUI Components | https://tallstackui.com/docs/components |
| TallStackUI GitHub | https://github.com/tallstackui/tallstackui |
| Heroicons | https://heroicons.com/ |