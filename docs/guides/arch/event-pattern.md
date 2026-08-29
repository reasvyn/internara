# Events, Listeners & Notifications — Domain Events, EDA & Observer Pattern

## Description

This pattern governs how Internara dispatches **domain events**, registers **listeners**, sends **notifications**, and integrates with **SmartLogger**. It synthesizes global industry standards — **Domain Events** (Eric Evans, DDD), **Observer Pattern** (Gang of Four), **Event-Driven Architecture** (Martin Fowler), **Event-Carried State Transfer** — into enforceable rules tied to Internara's stack: `BaseEvent`, `EventServiceProvider` (config-driven), `ShouldQueue`, `CustomDatabaseChannel`, and SmartLogger dual-channel logging.

Without it, side effects scatter across Actions, cross-module coupling increases, and the audit trail becomes incomplete. With it, events are domain-first, listeners are isolated, and every significant state change is traceable.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Events exist only when a listener exists.** Do NOT create events preemptively. Add an event only when a listener needs to react (cache invalidation, cross-module notification, logging beyond `$this->log()`). An event without a listener registration in `config/event.php` is dead code. Before adding an event, ask: "Is there a listener that will react to this?" If no → skip the event.

2. **Events are past-tense domain facts.** Event names MUST be past-tense: `{Entity}Created`, `{Login}Failed`, `{Student}Registered`. They represent something that **already happened** and cannot be undone. This is the **Domain Event** concept (Eric Evans): "something that happened in the domain that domain experts care about."

3. **Events are `final` classes.** No inheritance on events. Properties are `public` with typed constructor promotion. Only scalar types, Model instances, and objects with `toArray()` are allowed as properties.

4. **Deferred dispatch inside transactions.** Inside Command/Process Actions, use `$this->dispatchEvent()` to defer dispatch until the transaction commits. This prevents listeners from seeing uncommitted data — the **Read Committed** isolation level principle. Use `event()` only after `transaction()` returns.

5. **Listeners performing I/O MUST implement `ShouldQueue`.** Email, cache clear, API calls, database writes — all push to the queue worker. Synchronous listeners only for microsecond operations (cache forget, in-memory state). This keeps Action responses fast.

6. **Events registered in `config/event.php`.** Centralized event-listener mapping in one file. `EventServiceProvider` reads this config in `boot()`. An event without a listener registration is dead code.

7. **Domain Events vs Model Events — know the difference.** Model events (`creating`, `created`, `updating`) fire on every save/delete regardless of cause. Domain Events fire only when the business action explicitly dispatches them. If a listener should behave differently depending on *why* the row changed, you want Domain Events.

---

## How to Apply

### 1. Domain Events — Eric Evans DDD

Domain Events are first-class domain objects representing "something meaningful that happened in the business domain." Named in the ubiquitous language. Past tense. Immutable. Multiple consumers. Fire-and-forget.

**Event vs Command (Evans/Vernon):**

| | Event | Command |
|---|-------|---------|
| Tense | Past ("OrderPlaced") | Imperative ("PlaceOrder") |
| Can be rejected? | No — it already happened | Yes — handler may refuse |
| Consumers | Multiple subscribers | Single addressed handler |
| Origin | Domain service / aggregate root | User action / API endpoint |
| Coupling | Loose choreography | Tight orchestration |

**Rule of thumb:** Use events for communication *between* domain services. Use commands for communication with *technical/infrastructure* services.

### 2. Observer Pattern — GoF Behavioral

The Observer Pattern defines a one-to-many dependency: when the subject (Event) changes state, all dependents (Listeners) are notified automatically. In Laravel: events are dispatched, listeners handle side effects.

**Key distinction:** Model events fire on every save/delete. Domain Events fire only when explicitly dispatched. If a listener should behave differently depending on *why* the row changed, use Domain Events.

### 3. Event Architecture — BaseEvent Contract

All events extend `BaseEvent`:

| Trait / Method | Purpose |
|---------------|---------|
| `Dispatchable` | Static `dispatch()` via Laravel |
| `SerializesModels` | Safe queue serialization |
| `eventName(): string` | Abstract — dot-notation key for SmartLogger |
| `toPayload(): array` | Extracts public properties for logging |
| `broadcastOn(): array` | Returns `[]` (override to enable) |
| `shouldQueue(): bool` | Returns `false` (override to queue the event itself) |

### 4. Event Naming Conventions

**Pattern:** `{Entity}{PastTenseAction}`

| Convention | Example |
|-----------|---------|
| Entity + Created | `{Entity}Created` |
| Entity + Activated | `{Entity}Activated` |
| Entity + Deleted | `{Entity}Deleted` |
| Entity + Failed | `{Entity}Failed` |
| Entity + Finalized | `{Entity}Finalized` |

The `eventName()` method returns a dot-notation key (`{entity}.{action}`) that doubles as the log translation key.

### 5. Event Dispatch Patterns

| Method | Behaviour | When |
|--------|-----------|------|
| `$this->dispatchEvent(BaseEvent)` | Deferred — dispatched after transaction commits | Inside `transaction()` callback |
| `event($event)` / `Event::dispatch()` | Immediate dispatch | After `transaction()` returns |
| `SmartLogger::event($baseEvent)->save()` | Auto-dispatch + log payload merge | Combined logging + event |

### 6. Listener Naming and Registration

Listeners named by what they **do**: `{Action}{Entity}`. Registered in `config/event.php`:

```php
'listen' => [
    {Entity}{Action}::class => [
        {Listener}::class,
    ],
],
```

### 7. ShouldQueue for Async Listeners

```php
class {Listener} implements ShouldQueue
{
    public function handle({Entity}{Action} $event): void
    {
        // I/O-bound work
    }
}
```

**Rule of thumb:** If the listener does anything slower than a cache `forget()`, it should be queued.

### 8. Notification Architecture

Notifications extend `Illuminate\Notifications\Notification` directly. Channel strategy: `mail` + `broadcast` + `CustomDatabaseChannel`. Every notification implements `ShouldQueue` + `use Queueable`.

### 9. SmartLogger Integration

Events integrate with SmartLogger in two ways:
- `BaseAction::log()` — event name as log key (does NOT dispatch a BaseEvent)
- `SmartLogger::event($baseEvent)->save()` — dispatches event + merges payload

### 10. Testing Events and Listeners

| What | How | Tool |
|------|-----|------|
| Event was dispatched | `Event::fake([...])` + `assertDispatched()` | Laravel |
| Payload correctness | Closure in `assertDispatched()` | Laravel |
| Listener side effect | Dispatch event + assert state change | Manual |
| SmartLogger event integration | `Event::fake()` + assert both event and log | SmartLogger |
| BaseEvent contract | Module unit tests on `toPayload()`, `dispatch()` | Pest |

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| Event class with no listener registered | Add event only when listener exists | YAGNI — dead code |
| `event(new EntityCreated())` preemptively | Add event only when listener is implemented | Premature dispatch |
| `$this->dispatchEvent()` outside `$this->transaction()` | Inside transaction callback | Events may fire before commit |
| `event($e)` inside `transaction()` without `dispatchEvent()` | Use `$this->dispatchEvent()` for deferred dispatch | Immediate dispatch may see uncommitted data |
| Listener with `handle()` doing email + cache + API | Split into separate listeners, each one responsibility | SRP violation in listener |
| Listener doing synchronous I/O without `ShouldQueue` | `implements ShouldQueue` + `use Queueable` | Slow Action response |
| Event registered in `config/event.php` but class doesn't exist | Remove or create the listener | Dead registration |
| `EntityCreated` (present tense) | `EntityCreated` (past tense) | Domain Event naming convention |
| Business logic in listener (state mutation) | Listener does side effects only; business logic in Action | Listener is not a domain rule executor |
| Model event (`creating`, `created`) for business logic | Domain Event dispatched from Action | Model events fire on every save, not just business actions |

---

## Quick References

- `action-pattern.md` §8 Event Dispatch — `dispatchEvent()` vs `event()` inside transactions
- `logging-pattern.md` — SmartLogger dual-channel, PII masking
- `modular-pattern.md` §14 Notification Patterns — multi-channel, naming
- [Eric Evans — Domain Events](https://www.domainlanguage.com/ddd/) — "something that happened in the domain"
- [Microsoft — Domain Events: Design and Implementation](https://learn.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/domain-events-design-implementation) — when to use, event vs command
- [Martin Fowler — Event-Driven Architecture](https://martinfowler.com/articles/201701-event-driven.html) — four EDA patterns
- [GoF — Observer Pattern](https://en.wikipedia.org/wiki/Observer_pattern) — one-to-many dependency notification
- [ttulka — Events vs Commands in DDD](https://blog.ttulka.com/events-vs-commands-in-ddd/) — event = past tense, command = imperative
- [Laravel — Events](https://laravel.com/docs/events) — event dispatch, listeners, queuing
