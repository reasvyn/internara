# TallStackUI v4 — Complete Component Reference

## Description

TallStackUI is a comprehensive suite of 80+ Blade components for TALL-stack applications
(Tailwind, Alpine, Laravel, Livewire). This guide covers the complete component API, from
form inputs to complex interactions, aligned with Internara's UI system.

---


## Prerequisites

See [Installation](../installation.md#prerequisites) for full server requirements and verification commands.

## Steps

This document is reference-oriented. For installation and setup procedures, see:

1. [Installation](../installation.md) — server preparation and CLI provisioning
2. [Setup Wizard](../setup-wizard.md) — browser-based initial configuration
3. [Post-Setup](../post-setup.md) — initial data population after wizard completion

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

Set to `ts-` so components become `<x-ts-alert />`. Default (no prefix): `<x-ts-modal />`, `<x-ts-input />`.

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

### Input (`<x-ts-input>`)

```blade
<x-ts-input /> {{-- type=text --}}
<x-ts-input email /> {{-- type=email --}}
<x-ts-input label="Name" hint="Insert your name" />
<x-ts-input label="Name *" hint="Insert your name" /> {{-- asterisk indicator --}}

{{-- Icon --}}
<x-ts-input label="Name" icon="users" />
<x-ts-input label="Name" icon="cog" position="right" />

{{-- Prefix / Suffix --}}
<x-ts-input prefix="https://" label="Domain" />
<x-ts-input suffix="@gmail.com" label="E-mail" />
<x-ts-input>
    <x-slot:prefix>Prefix</x-slot:prefix>
    <x-slot:suffix>Suffix</x-slot:suffix>
</x-ts-input>

{{-- Buttons in prefix/suffix (addon mode) --}}
<x-ts-input label="Search">
    <x-slot:suffix button>
        <x-ts-button text="Go" sm />
    </x-slot:suffix>
</x-ts-input>

<x-ts-input value="TallStackUI" clearable />
<x-ts-input strip-zeros />
<x-ts-input label="Name" invalidate />
<x-ts-input label="Readonly" value="Readonly" readonly />
<x-ts-input label="Disabled" value="Disabled" disabled />

```

### Select — Three Variants

**Native (`<x-ts-select.native>`)** — plain `<select>`:

```blade
<x-ts-select.native :options="[1,2,3]" />
<x-ts-select.native label="Plan" hint="…" :options="[1,2,3]" />
<x-ts-select.native :options="[['label'=>'TALL','value'=>1], ['label'=>'LIVT','value'=>2]]" />
<x-ts-select.native :options="[['name'=>'TALL','id'=>1]]" select="label:name|value:id" />

```

**Styled (`<x-ts-select.styled>`)** — custom Alpine floating UI:

```blade
<x-ts-select.styled :options="[1,2,3]" />
<x-ts-select.styled label="Select One" placeholder="Custom" hint="…" :options="[1,2,3]" />
<x-ts-select.styled :options="[1,2,3]" required />
<x-ts-select.styled :options="[1,2,3,4,5,6]" multiple />

{{-- Disabled options --}}
<x-ts-select.styled :options="[ ['label'=>'TALL','value'=>1,'disabled'=>true], ['label'=>'LIVT','value'=>2] ]" />

{{-- Grouped options --}}
<x-ts-select.styled :options="[ ['label'=>'Brazil','description'=>'SA','value'=>[ ['label'=>'São Paulo','value'=>4], ['label'=>'Rio','value'=>5] ]], ['label'=>'USA','value'=>[ ['label'=>'NY','value'=>7], ['label'=>'LA','value'=>8] ]] ]" />

<x-ts-select.styled :limit="2" :options="[…]" multiple />     {{-- multi-limit --}}

{{-- Image + description --}}
<x-ts-select.styled :options="[
    ['label'=>'Taylor','value'=>1,'image'=>'https://unavatar.io/github/taylorotwell'],
    ['label'=>'Nuno','value'=>2,'description'=>'Creator of PestPHP'],
]" />

<x-ts-select.styled :options="[…]" searchable />
<x-ts-select.styled :options="[…30 items]" lazy="10" />     {{-- lazy load ≥ 10 --}}

<x-ts-select.styled :options="[…]">
    <x-slot:after>
        <x-ts-button x-on:click="show = false; $dispatch('confirmed', { term: search })">
            <span x-html="`Create user <b>${search}</b>`"></span>
        </x-ts-button>
    </x-slot:after>
</x-ts-select.styled>

{{-- Events --}}
<x-ts-select.styled :options="[…]" multiple x-on:select="alert(`Select: ${JSON.stringify($event.detail.select)}`)"
    x-on:remove="alert(`Remove: ${JSON.stringify($event.detail.select)}`)" />

```

Events: `select` (option picked, detail: `{ select }`), `remove` (option removed, detail: `{ select }`).

**Styled API (`<x-ts-select.styled :request="…">`)** — fetches options from a URL:

```blade
<x-ts-select.styled :request="route('api.users')" />
<x-ts-select.styled :request="route('api.users')" indicator="spinner" />
<x-ts-select.styled :request="route('api.users')" indicator="spinner.bars" />
<x-ts-select.styled :request="route('api.users')" unfiltered />
<x-ts-select.styled :request="[
    'url'    => route('api.users'),
    'method' => 'get',
    'params' => ['library' => 'TallStackUi'],
]" />
<x-ts-select.styled :request="route('api.users')" recycle />

```

Backend contract — the route must return JSON `[{label, value}]` (or use `select="label:col|value:col"` to remap).

### Textarea (`<x-ts-textarea>`)

```blade
<x-ts-textarea />
<x-ts-textarea label="Name" hint="Insert the description" />
<x-ts-textarea label="Description *" />
<x-ts-textarea resize />
<x-ts-textarea resize-auto />
<x-ts-textarea maxlength="10" count />
<x-ts-textarea count />       {{-- shows character count --}}
<x-ts-textarea label="Readonly" value="…" readonly />
<x-ts-textarea label="Disabled" value="…" disabled />

```

### Checkbox (`<x-ts-checkbox>` + `<x-ts-checkbox.group>`)

**Single:**

```blade
<x-ts-checkbox />
<x-ts-checkbox label="Receive Alert" />
<x-ts-checkbox label="Receive Alert" position="left" />
<x-ts-checkbox label="Readonly" checked readonly />
<x-ts-checkbox label="Disabled" checked disabled />
<x-ts-checkbox>
    <x-slot:label>I agree to the <a href="#">terms</a></x-slot:label>
</x-ts-checkbox>
<x-ts-checkbox xs|sm|md|lg />
<x-ts-checkbox color="red" label="Red" />

```

**Group (`<x-ts-checkbox.group>`):**

```blade
<x-ts-checkbox.group label="Features" :options="[
    ['label'=>'Newsletter','value'=>'newsletter','description'=>'Weekly digest'],
    ['label'=>'Alerts','value'=>'alerts','description'=>'Real time'],
    ['label'=>'Reports','value'=>'reports','description'=>'Monthly'],
]" />

<x-ts-checkbox.group list|card|panel|inline :options="$features" />
<x-ts-checkbox.group card :columns="3" :options="$features" />
<x-ts-checkbox.group position="right" :options="$features" />
<x-ts-checkbox.group xs|sm|md|lg :options="$features" />
<x-ts-checkbox.group color="green" :options="$features" />

{{-- Remap array keys --}}
<x-ts-checkbox.group select="label:name|value:id|description:note" :options="$features" />

{{-- Custom body with @interact --}}
<x-ts-checkbox.group card :options="$addons">
    @interact('option', $option)
        <span class="font-semibold">{{ $option['name'] }}</span>
        <span class="font-mono">${{ $option['price'] }}</span>
    @endinteract
</x-ts-checkbox.group>

```

### Radio (`<x-ts-radio>` + `<x-ts-radio.group>`)

Same API as checkbox:

```blade
<x-ts-radio.group label="Plan" :options="[
    ['label'=>'Startup','value'=>'startup','description'=>'5 jobs','aside'=>'$29/mo'],
    ['label'=>'Business','value'=>'business','description'=>'25 jobs','aside'=>'$99/mo'],
    ['label'=>'Enterprise','value'=>'enterprise','description'=>'Unlimited','aside'=>'$249/mo'],
]" />

<x-ts-radio.group list|card|panel|inline :options="$plans" />
<x-ts-radio.group card :columns="3" :options="$plans" />
<x-ts-radio.group position="right" :options="$plans" />

```

### Toggle (`<x-ts-toggle>`)

```blade
<x-ts-toggle />
<x-ts-toggle label="Receive Alert" />
<x-ts-toggle label="Receive Alert" position="left" />
<x-ts-toggle label="Readonly" checked readonly />
<x-ts-toggle label="Disabled" checked disabled />
<x-ts-toggle xs|sm|md|lg />
<x-ts-toggle color="red" label="Red" />
<x-ts-toggle>
    <x-slot:label start>Align on Start</x-slot:label>
</x-ts-toggle>

```

### Date (`<x-ts-date>`)

Format tokens (Day.js): `YYYY MM MMM MMMM D DD d dd ddd dddd [escaped]` plus time tokens.
Backend always receives `YYYY-MM-DD`.

```blade
<x-ts-date />
<x-ts-date label="Date" hint="Select your DoB" />
<x-ts-date label="Readonly" value="2026-08-13" readonly />
<x-ts-date format="YYYY-MM-DD" />
<x-ts-date format="DD [of] MMMM [of] YYYY" />
<x-ts-date helpers />
<x-ts-date :min-date="now()->subWeek()" :max-date="now()->addWeek()" />
<x-ts-date :min-year="2020" :max-year="2024" />

{{-- Disable dates --}}
<x-ts-date :disable="['2020-01-01','2020-01-02']" />
<x-ts-date :disable="[ ['2020-01-01','2020-01-03'], ['2020-01-04','2020-01-06'] ]" />

{{-- Disable specific weekdays --}}
<x-ts-date only="3" /> {{-- 0=Sunday, …, 6=Saturday --}}
<x-ts-date weekdays />
<x-ts-date weekends />

<x-ts-date range wire:model="date" /> {{-- public array $date = ['2021-01-01', '2021-01-31'] --}}
<x-ts-date multiple wire:model="date" />
<x-ts-date start="1" />          {{-- first day of week --}}
<x-ts-date month-year-only />

{{-- Events --}}
<x-ts-date x-on:select="alert(`Selected: ${$event.detail.date}`)"
    x-on:clear="alert('Cleaned!')" />

```

### Time (`<x-ts-time>`)

```blade
<x-ts-time />
<x-ts-time label="Time" hint="Select the hour" />
<x-ts-time label="Readonly" value="10:00 AM" readonly />
<x-ts-time :step-minute="5" />
<x-ts-time format="24" />
<x-ts-time :min-hour="5" :max-hour="10" />
<x-ts-time :min-minute="30" :max-minute="45" />
<x-ts-time required />
<x-ts-time helper />                                 {{-- "current time" button --}}
<x-ts-time :step-hour="3" :step-minute="15" />
<x-ts-time>
    <x-slot:footer>Footer Slot</x-slot:footer>
</x-ts-time>

<x-ts-time x-on:hour="alert(`Hour: ${$event.detail.hour}`)"
    x-on:minute="alert(`Min: ${$event.detail.minute}`)"
    x-on:interval="alert(`Interval: ${$event.detail.interval}`)" />

```

### Upload (`<x-ts-upload>`)

Uses Livewire's normal `WithFileUploads` mechanism:

```blade
<x-ts-upload />
<x-ts-upload label="Screenshot" hint="…" tip="Drag and drop your screenshot here" />
<x-ts-upload close-after-upload />
<x-ts-upload delete />                    {{-- requires deleteUpload() method --}}
<x-ts-upload delete delete-method="deleting" />
<x-ts-upload multiple />                  {{-- property must be array --}}
<x-ts-upload accept="application/pdf" />
<x-ts-upload>
    <x-slot:footer><x-ts-button class="w-full">Save</x-ts-button></x-slot:footer>
</x-ts-upload>
<x-ts-upload>
    <x-slot:footer when-uploaded>
        <x-ts-button class="w-full" wire:click="store">Save</x-ts-button>
    </x-slot:footer>
</x-ts-upload>

<x-ts-upload x-on:upload="console.log($event.detail.files)" />
<x-ts-upload delete x-on:remove="console.log($event.detail.file)" />

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
| `<x-ts-password>` | Text input with show/hide eye toggle |
| `<x-ts-number>` | Numeric input with up/down controls |
| `<x-ts-currency>` | Formatted currency input |
| `<x-ts-color>` | Color picker |
| `<x-ts-pin>` | OTP/PIN digit input |
| `<x-ts-range>` | Slider |
| `<x-ts-tag>` | Multi-tag entry |
| `<x-autocomplete>` | Searchable suggestions |
| `<x-ts-upload.async>` | Resumable-style uploads with chunked progress |

---

## UI Components

### Modal (`<x-ts-modal>`)

```blade
<x-ts-modal>TallStackUI</x-ts-modal>

<x-ts-modal>
    <x-slot:title>TallStackUI</x-slot:title>
    TallStackUI
</x-ts-modal>

{{-- or via attrs --}}
<x-ts-modal title="TallStackUI">TallStackUI</x-ts-modal>
<x-ts-modal footer="TallStackUI">…</x-ts-modal>

<x-ts-modal scrollable>Long content…</x-ts-modal>
<x-ts-modal blur>…</x-ts-modal> {{-- sm/md/lg/xl blur --}}

{{-- Sizes: sm md lg xl 2xl 3xl 4xl 5xl 6xl 7xl full --}}
<x-ts-modal size="2xl">…</x-ts-modal>

<x-ts-modal center>…</x-ts-modal>                                {{-- desktop only --}}
<x-ts-modal center="md">…</x-ts-modal>                           {{-- bottom sheet< md, centered ≥ md --}}
<x-ts-modal persistent>…</x-ts-modal>                            {{-- no outside-click close --}}
<x-ts-modal handle>…</x-ts-modal>                                {{-- mobile drag handle --}}
<x-ts-modal paddingless>…</x-ts-modal>
<x-ts-modal wire>…</x-ts-modal>                                  {{-- boolean prop $modal --}}
<x-ts-modal wire="tallstackui">…</x-ts-modal>                    {{-- custom prop --}}

<x-ts-modal id="modal-id">…</x-ts-modal>
<x-ts-button x-on:click="$tsui.open.modal('modal-id')">Open</x-ts-button>
<x-ts-button x-on:click="$tsui.close.modal('modal-id')">Close</x-ts-button>

<x-ts-modal x-on:open="alert('Opened!')" x-on:close="alert('Closed!')">…</x-ts-modal>

{{-- Focus helper --}}
<x-ts-modal id="modal-id" x-on:open="$tsui.focus('email')">
    <x-ts-input label="Email" id="email" />
</x-ts-modal>

{{-- Footer alignment --}}
<x-ts-modal>
    TallStackUI
    <x-slot:footer between>
        <x-ts-button color="red">Delete</x-ts-button>
        <x-ts-button>Save</x-ts-button>
    </x-slot:footer>
</x-ts-modal>

```

Events: `open` (modal opens), `close` (modal closes).

### Dropdown (`<x-ts-dropdown>`)

```blade
<x-ts-dropdown text="Menu" position="bottom-end">
    <x-ts-dropdown.items text="Settings" />
    <x-ts-dropdown.items text="Logout" separator />
</x-ts-dropdown>

<x-ts-dropdown text="Open when hover" position="bottom-end" hover>…</x-ts-dropdown>

{{-- Positions: bottom[-start|-end], top[-start|-end], left[-start|-end], right[-start|-end] --}}
<x-ts-dropdown text="Menu" position="bottom-start">…</x-ts-dropdown>

<x-ts-dropdown icon="chevron-down" position="bottom-end">
    <a href="https://google.com.br" target="_blank"><x-ts-dropdown.items text="Google" /></a>
</x-ts-dropdown>

<x-ts-dropdown icon="chevron-down">…</x-ts-dropdown>
<x-ts-dropdown icon="ellipsis-vertical" static>…</x-ts-dropdown> {{-- no rotate animation --}}

<x-ts-dropdown text="Account" xs|sm|md|lg>…</x-ts-dropdown>

{{-- Widths: xxs xs sm md lg xl 2xl --}}
<x-ts-dropdown text="xl" width="xl">…</x-ts-dropdown>

<x-ts-dropdown text="Menu">
    <x-slot:header><x-ts-theme-switch block /></x-slot:header>
    <x-ts-dropdown.items icon="cog" text="Settings" />
    <x-ts-dropdown.items icon="arrow-left-on-rectangle" text="Logout" separator />
</x-ts-dropdown>

<x-ts-dropdown text="Menu" position="bottom-end">
    <x-ts-dropdown.items text="PHP" />
    <x-ts-dropdown.submenu text="Second Level">
        <x-ts-dropdown.items text="JavaScript" />
        <x-ts-dropdown.submenu text="Third Level" position="left-start">
            <x-ts-dropdown.items text="C++" />
            <x-ts-dropdown.items text="Golang" />
        </x-ts-dropdown.submenu>
    </x-ts-dropdown.submenu>
</x-ts-dropdown>

<x-ts-dropdown>
    <x-slot:action>
        <x-ts-button x-on:click="show = !show" sm outline>Open</x-ts-button>
    </x-slot:action>
    <x-ts-dropdown.items icon="cog" text="Settings" />
</x-ts-dropdown>

<x-ts-dropdown x-on:open="alert(…)" x-on:select="alert('Selected')">…</x-ts-dropdown>

```

Events: `open {status}` (open/close), `select` (item clicked).

### Tooltip (`<x-ts-tooltip>`)

Dropped Tippy.js in v4 → smaller bundle.

```blade
<x-ts-tooltip text="TallStackUI" />
<x-ts-tooltip text="Top" position="top" icon="exclamation-circle" />
<x-ts-tooltip text="TallStackUI" xs|sm|md|lg />
<x-ts-tooltip><b>Tall</b><i>Stack</i><u>Ui</u></x-ts-tooltip>

{{-- Color applies to icon; balloon paints the balloon --}}
<x-ts-tooltip text="TallStackUI" color="red" />
<x-ts-tooltip text="TallStackUI" balloon="red" />

<x-ts-tooltip text="TallStackUI" delay="flash" />           {{-- slow | fast | faster | flash --}}
<x-ts-tooltip text="TallStackUI" scale="lg" />              {{-- sm | md | lg; balloon sizes --}}

{{-- On any HTML element via x-tooltip directive --}}
<span x-data x-tooltip="TallStackUI" data-tooltip-delay="faster"></span>

{{-- Disable reactively --}}
<div x-data="{ disabled: false }">
    <x-ts-toggle x-model="disabled" label="Disable the tooltip" />
    <span x-data x-tooltip="TallStackUI" x-bind:data-tooltip-disabled="disabled">
        Hover me
    </span>
</div>

```

### Card (`<x-ts-card>`)

```blade
<x-ts-card>TallStackUI</x-ts-card>

<x-ts-card header="TallStackUI">…</x-ts-card>
<x-ts-card footer="TallStackUI">…</x-ts-card>

<x-ts-card header="TallStackUI" minimize>…</x-ts-card>
<x-ts-card header="TallStackUI" minimize="mount">…</x-ts-card>     {{-- start minimized --}}

<x-ts-card image="https://picsum.photos/750/300">…</x-ts-card>
<x-ts-card position="bottom" image="…">…</x-ts-card>

{{-- Round: bare flag → default rounded-lg; xs sm md lg xl 2xl --}}
<x-ts-card round="sm">…</x-ts-card>

<x-ts-card header="TallStackUI" color="primary">…</x-ts-card>
<x-ts-card header="TallStackUI" color="primary" light>…</x-ts-card>
<x-ts-card header="TallStackUI" color="primary" accent>…</x-ts-card>   {{-- colored top border --}}

<x-ts-card shadowless>…</x-ts-card>
<x-ts-card bordered>…</x-ts-card>
<x-ts-card shadowless bordered>…</x-ts-card>
<x-ts-card header="…" paddingless>…</x-ts-card>

<x-ts-card>
    TallStackUI
    <x-slot:footer between>
        <x-ts-button color="red">Delete</x-ts-button>
        <x-ts-button>Save</x-ts-button>
    </x-slot:footer>
</x-ts-card>

<x-ts-card skeleton /> {{-- 3 body lines --}}
<x-ts-card skeleton="5" header="…" footer image round="xl" />

<x-ts-card loading>…</x-ts-card>
<x-ts-card loading="save" delay="longest">…</x-ts-card>

<x-ts-card header="TallStackUI" minimize
    x-on:minimize="alert('Minimized!')"
    x-on:maximize="alert('Maximized!')"
    x-on:close="alert('Closed!')">…</x-ts-card>

```

Events: `minimize`, `maximize`, `close`. Used inside `#[Lazy]` component `placeholder()` for skeleton.

### Table (`<x-ts-table>`)

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
<x-ts-table :$headers :$rows />
<x-ts-table :$headers :$rows headerless />
<x-ts-table :$headers :$rows striped />

{{-- Column alignment --}}
<x-ts-table :headers="[
    ['index' => 'name',     'label' => 'Product'],
    ['index' => 'quantity', 'label' => 'Quantity', 'align' => 'center'],
    ['index' => 'price',    'label' => 'Price',    'align' => 'right'],
]" :rows="[…]" />

{{-- Search --}}
<x-ts-table :$headers :$rows searchable />

{{-- Loading --}}
<x-ts-table :$headers :$rows loading />

{{-- Empty state --}}
<x-ts-table :$headers :$rows>
    <x-slot:empty>No records found</x-slot:empty>
</x-ts-table>

{{-- Actions column --}}
<x-ts-table :$headers :$rows>
    <x-slot:actions>
        <x-ts-button sm>Edit</x-ts-button>
    </x-slot:actions>
</x-ts-table>

```

### Pagination (`<x-ts-pagination>`)

```blade
<x-ts-pagination :paginator="$users" />
<x-ts-pagination :paginator="$users" simple />

```

### Badge (`<x-ts-badge>`)

```blade
<x-ts-badge text="Active" />
<x-ts-badge text="Active" color="green" />
<x-ts-badge text="Active" xs|sm|md|lg />
<x-ts-badge text="Active" round />
<x-ts-badge text="Active" light />
<x-ts-badge text="Active" outline />
<x-ts-badge icon="check" text="Verified" />

```

### Alert (`<x-ts-alert>`)

```blade
<x-ts-alert>Default alert</x-ts-alert>
<x-ts-alert color="green">Success message</x-ts-alert>
<x-ts-alert color="red">Error message</x-ts-alert>
<x-ts-alert color="yellow">Warning message</x-ts-alert>
<x-ts-alert color="blue">Info message</x-ts-alert>

<x-ts-alert close>Closable alert</x-ts-alert>
<x-ts-alert icon="check-circle" color="green">With icon</x-ts-alert>

```

### Loading (`<x-ts-loading>`)

```blade
<x-ts-loading />
<x-ts-loading text="Loading…" />
<x-ts-loading color="primary" />
<x-ts-loading spinner="bars" />

```

### Button (`<x-ts-button>`)

```blade
<x-ts-button>Default</x-ts-button>
<x-ts-button text="Save" />
<x-ts-button color="green">Success</x-ts-button>
<x-ts-button color="red">Danger</x-ts-button>
<x-ts-button color="yellow">Warning</x-ts-button>
<x-ts-button color="blue">Info</x-ts-button>

<x-ts-button sm>Small</x-ts-button>
<x-ts-button lg>Large</x-ts-button>

<x-ts-button outline>Outline</x-ts-button>
<x-ts-button light>Light</x-ts-button>
<x-ts-button circle>+</x-ts-button>

<x-ts-button icon="plus" />
<x-ts-button icon="pencil" sm>Edit</x-ts-button>

<x-ts-button loading>Submit</x-ts-button>
<x-ts-button disabled>Disabled</x-ts-button>

<x-ts-button wire:click="save">Save</x-ts-button>
<x-ts-button wire:loading wire:target="save">Saving…</x-ts-button>

```

### Avatar (`<x-ts-avatar>`)

```blade
<x-ts-avatar :model="$user" />
<x-ts-avatar :model="$user" color="primary" />
<x-ts-avatar :model="$user" xs|sm|md|lg|xl />
<x-ts-avatar :model="$user" round />
<x-ts-avatar label="AB" /> {{-- Initials --}}

```

---

## Interaction Components

### Dialog (`<x-ts-dialog>`)

Confirmation dialogs with async support:

```blade
<x-ts-dialog z-index="z-50" blur="sm">
    <x-ts-dialog.button color="red" wire:click="delete({{ $id }})">
        Delete
    </x-ts-dialog.button>
</x-ts-dialog>

{{-- Or programmatically --}}
<x-ts-button x-on:click="$tsui.interaction('dialog')
    ?.confirm('Are you sure?', 'This will delete the record', {
        accept: { label: 'Yes, delete', method: 'delete', params: {{ $id }} },
        reject: { label: 'Cancel' },
    })">
    Delete
</x-ts-button>

```

### Slide (`<x-ts-slide>`)

Side panel for forms or details:

```blade
<x-ts-slide id="slide-id" title="Details">
    Content here
</x-ts-slide>

<x-ts-button x-on:click="$tsui.open.slide('slide-id')">Open</x-ts-button>
<x-ts-button x-on:click="$tsui.close.slide('slide-id')">Close</x-ts-button>

<x-ts-slide position="right" size="lg">…</x-ts-slide>
<x-ts-slide persistent>…</x-ts-slide> {{-- no outside-click close --}}

```

### Toast (`<x-ts-toast>`)

Notification messages:

```blade
<x-ts-toast position="top-right" />

{{-- Programmatically --}}
<x-ts-button x-on:click="$tsui.interaction('toast')
    ?.success('Success!', 'Record created successfully')">
    Save
</x-ts-button>

<x-ts-button x-on:click="$tsui.interaction('toast')
    ?.error('Error!', 'Something went wrong')">
    Delete
</x-ts-button>

```

### Error (`<x-ts-error>`)

Form error display:

```blade
<x-ts-error />
<x-ts-error :errors="$errors" />
<x-ts-error :errors="$errors" title="Please fix the following errors:" />
<x-ts-error :errors="$errors" color="red" />

```

---

## Icon System

### Default Icons (Heroicons)

TallStackUI uses Heroicons by default:

```blade
<x-ts-input icon="users" />
<x-ts-button icon="plus">Add</x-ts-button>
<x-ts-dropdown.items icon="cog" text="Settings" />

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
<x-ts-input label="Name" wire:model="name" />
<x-ts-input label="Email" wire:model.live="email" />
<x-ts-select.styled wire:model="role" :options="$roles" />
<x-ts-checkbox label="Active" wire:model="active" />
<x-ts-toggle label="Notifications" wire:model="notifications" />

```

### Wire Loading States

```blade
<x-ts-button wire:click="save">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving…</span>
</x-ts-button>

```

### Wire Events

```blade
<x-ts-button wire:click="edit({{ $id }})">Edit</x-ts-button>
<x-ts-button wire:click="delete({{ $id }})"
    x-on:click="$tsui.interaction('dialog')?.confirm('Delete?', 'Are you sure?', {
        accept: { label: 'Yes', method: 'delete', params: {{ $id }} },
        reject: { label: 'Cancel' },
    })">
    Delete
</x-ts-button>

```

### Form Validation

```blade
<x-ts-input label="Name" wire:model="name" :errors="$errors" />
<x-ts-input label="Name" wire:model="name" invalidate /> {{-- hide errors --}}

```

---

## Best Practices for Internara

### 1. Consistent Sizing

Use the same size across related components:

```blade
<x-ts-input sm label="Search" wire:model="search" />
<x-ts-button sm wire:click="filter">Filter</x-ts-button>

```

### 2. Semantic Colors

Use semantic colors for actions:

```blade
<x-ts-button color="green">Save</x-ts-button>
<x-ts-button color="red">Delete</x-ts-button>
<x-ts-button color="yellow">Edit</x-ts-button>

```

### 3. Loading States

Always provide loading feedback:

```blade
<x-ts-button wire:click="save">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving…</span>
</x-ts-button>

```

### 4. Confirmation Dialogs

Use dialogs for destructive actions:

```blade
<x-ts-button color="red"
    x-on:click="$tsui.interaction('dialog')?.confirm('Delete?', 'This cannot be undone', {
        accept: { label: 'Delete', method: 'delete', params: {{ $id }} },
        reject: { label: 'Cancel' },
    })">
    Delete
</x-ts-button>

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
<x-ts-error :errors="$errors" />

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
---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
