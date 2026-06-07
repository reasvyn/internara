# Livewire JavaScript Integration

## What It Covers

Livewire v4's JavaScript interceptor system allows hooking into component message processing and
HTTP request lifecycle. Three main integrations exist: intercepting messages (component-level
communication), intercepting requests (HTTP-level), and Alpine.js co-existence.

## Interceptor System

`Livewire.interceptMessage()` provides hooks for component message lifecycle: `onFinish()` runs
after response received but before DOM processing, `onSuccess()` has access to the component
snapshot and DOM diff payload, `onError()` handles server errors.

`Livewire.interceptRequest()` provides hooks for the raw HTTP request lifecycle: `onResponse()` for
raw response access, `onSuccess()` for 2xx responses, `onError()` for 4xx/5xx (with
`preventDefault()` to suppress default handling), and `onFailure()` for network-level failures.

Component-scoped interceptors use `$intercept` within Blade:
`this.$intercept('actionName', { onSuccess: ... })` — scoped to a specific component instance and
action.

## Alpine.js Integration

Alpine.js and Livewire co-exist naturally. Alpine manages client-side state (`x-data`, `x-show`,
`x-transition`) while Livewire manages server state (`wire:model`, `wire:click`). They communicate
through `$wire` (access component properties and methods from Alpine) and `$errors` (access
validation errors from JavaScript).

## When to Use

- Interceptors: for analytics, logging, custom error handling, or DOM manipulation after Livewire
  updates
- Alpine.js: for client-side-only UI behavior (expanding panels, tab switching, dropdown menus) that
  doesn't need server state
- `$wire`: for hybrid interactions where Alpine reads or writes Livewire component state

Exceptions: Most application code will not need interceptors. Prefer Livewire's built-in loading
states (`wire:loading`) and flash messages for user feedback.
