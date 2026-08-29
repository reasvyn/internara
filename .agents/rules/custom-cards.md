# Pulse Custom Cards — Building Dashboard Extensions

Custom Pulse cards extend `Pulse\Livewire\Card` and add Internara-specific metrics to the dashboard.
They must be registered in `config/pulse.php`, live in the provider/component location the project
uses, and enforce their own authorization.

---

## Intent

Create the card class extending `Pulse\Livewire\Card`, register it under `dashboard.cards` in
`config/pulse.php`, and define authorization via `authorize()`. Cards live in
`app/Providers/PulseServiceProvider.php` or as standalone Livewire components.

## Rationale — What Fails Without It

- **Extending the wrong base** (`Component` instead of `Card`) means Pulse neither registers the card
  nor provides a `sample()` scope — the card either never appears or reads raw, non-Pulse data.
- **Not registering under `dashboard.cards`** leaves the class orphaned: `Card` exists, but the
  dashboard never renders it.
- **No `authorize()`** on the card re-opens finer-grained exposure; a card carrying privileged
  metrics (school PII counts, session info) is only as safe as its least-privileged route access.
- **Wrong file location** — cards in a random `Livewire/` folder without registration still render as
  plain components, silently losing Pulse's sampling context and the dashboard layout.

## How to Apply

### 1. Create the card class

```php
use Pulse\Livewire\Card;

final class InternPlacementCard extends Card
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole(['admin', 'superadmin']);
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        return view('pulse.intern-placement-card');
    }

    public function sample(): void
    {
        $this->sample(Trace::class);
    }
}
```

- **Extend `Card`** — it provides `sample()`, `interval`, the dashboard hook, and authorization
  integration.
- **Card files** live in `app/Providers/PulseServiceProvider.php` (provider-registered cards) or as
  standalone Livewire components under `app/{Module}/{SubModule}/Livewire/` — follow the existing
  convention; do not invent a third location.

### 2. Register in `config/pulse.php`

```php
'dashboard' => [
    'cards' => [
        'aliases' => [
            'intern-placement' => InternPlacementCard::class,
        ],
    ],
],
```

### 3. Define authorization via `authorize()`

- Same principle as the `viewPulse` Gate — the card must declare who may see it.
- Non-authorized users get the card hidden; do not leak its data through the render path.

## Anti-Patterns & Pitfalls

- A card class whose `render()` returns a Livewire component without `Card`'s lifecycle — no sample
  data, no interval, half a dashboard card.
- Registering the card class but forgetting the alias under `dashboard.cards` — it simply never shows.
- `authorize()` returning `true` unconditionally — the card bypasses `viewPulse` for raw data
  exposure (PII risk, matches the security rule scope).
- Reading data in `render()` instead of `sample()` — defeats Pulse's buffering and stairsteps the
  dashboard refresh.

## Verification

- Card extends `Card`, is registered in `config/pulse.php::dashboard.cards`, and passes
  `authorize()` for the admin/superadmin roles.
- Dashboard renders the card with sampled data; non-admin users do not see it.
- `docs/guides/system-observability.md` documents the new metric the card surfaces.
