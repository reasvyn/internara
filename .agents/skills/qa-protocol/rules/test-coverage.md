# Rules: Test Coverage

> ISO 25010 Mapping: Functional Suitability, Maintainability (Testability)
> Applicability: All applications with test suites

## Overview

Test coverage is not just a number — it's about what's tested and how. Meaningful tests
cover business logic, edge cases, and failure paths, not just happy paths.

## 1. Coverage Targets

| Layer | Target | Rationale |
|-------|--------|-----------|
| Business Logic (Services, Actions) | ≥ 90% | Core correctness |
| Domain Rules (Entities, Value Objects) | 100% | Rules change; tests catch drift |
| HTTP Handlers (Controllers, Livewire) | ≥ 80% | Input handling is critical |
| Data Layer (Models, Repositories) | ≥ 80% | Query correctness matters |
| Views (Blade templates) | ≥ 50% | Rendering correctness |
| Utilities (Helpers, Traits) | ≥ 90% | Reused everywhere |

## 2. What Makes a Good Test

### Arrange-Act-Assert (AAA)

```php
// GOOD — clear structure
it('calculates total price with tax', function () {
    // Arrange
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 100,
        'quantity' => 2,
    ]);
    
    // Act
    $total = $order->calculateTotal();
    
    // Assert
    expect($total)->toBe(220.00); // 200 + 10% tax
});
```

### Test Behavior, Not Implementation

```php
// BAD — testing implementation details
it('calls the database', function () {
    Mockery::mock(DB::class)->shouldReceive('table')->once();
    $service->process();
});

// GOOD — testing behavior
it('creates an order when valid data is provided', function () {
    $service->process($validData);
    $this->assertDatabaseHas('orders', ['status' => 'pending']);
});
```

### Test Edge Cases

```php
// BAD — only happy path
it('creates a user', function () {
    $user = User::factory()->create();
    expect($user)->not->toBeNull();
});

// GOOD — happy path + edge cases
it('creates a user with valid data', function () { ... });
it('rejects user with duplicate email', function () { ... });
it('rejects user with invalid email format', function () { ... });
it('rejects user with password less than 8 characters', function () { ... });
it('handles user creation with maximum length fields', function () { ... });
it('creates user with unicode characters in name', function () { ... });
```

### Test Failure Paths

```php
// BAD — only testing success
it('processes payment', function () {
    $result = $payment->process($validCard);
    expect($result->success)->toBeTrue();
});

// GOOD — testing both success and failure
it('processes payment with valid card', function () {
    $result = $payment->process($validCard);
    expect($result->success)->toBeTrue();
});

it('rejects payment with expired card', function () {
    $result = $payment->process($expiredCard);
    expect($result->success)->toBeFalse();
    expect($result->error)->toBe('Card expired');
});

it('rejects payment with insufficient funds', function () {
    $result = $payment->process($lowBalanceCard);
    expect($result->success)->toBeFalse();
});

it('handles payment gateway timeout', function () {
    $gateway->shouldReceive('charge')->andThrow(new TimeoutException());
    $result = $payment->process($validCard);
    expect($result->success)->toBeFalse();
});
```

## 3. Test Patterns by Type

### Unit Tests

```php
// Test classes in isolation
// No database, no HTTP, no framework dependencies
it('validates email format', function () {
    $validator = new EmailValidator();
    expect($validator->isValid('user@example.com'))->toBeTrue();
    expect($validator->isValid('invalid'))->toBeFalse();
    expect($validator->isValid(''))->toBeFalse();
});
```

### Feature Tests

```php
// Test full request lifecycle
it('allows authenticated user to create post', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/posts', ['title' => 'Test', 'body' => 'Content']);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('posts', ['title' => 'Test']);
});

it('prevents unauthenticated user from creating post', function () {
    $response = $this->post('/posts', ['title' => 'Test']);
    $response->assertRedirect('/login');
});
```

### Integration Tests

```php
// Test multiple components working together
it('processes order end-to-end', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id]);
    
    $result = $orderService->process($order);
    
    expect($result->success)->toBeTrue();
    expect($order->status)->toBe('processed');
    expect($item->stock)->toBe(0);
    $this->assertDatabaseHas('order_logs', ['order_id' => $order->id]);
});
```

## 4. Test Anti-Patterns

### Don't Test Framework Code

```php
// BAD — testing that Eloquent works
it('can save a model', function () {
    $user = new User();
    $user->name = 'Test';
    $user->save();
    expect($user->id)->not->toBeNull();
});

// GOOD — testing your logic, not Eloquent
it('generates username from email', function () {
    $user = User::factory()->create(['email' => 'john@example.com']);
    expect($user->username)->toBe('john');
});
```

### Don't Use Shared State

```php
// BAD — tests depend on each other
$sharedUser = null;

it('creates a user', function () use (&$sharedUser) {
    $sharedUser = User::factory()->create();
});

it('updates the user', function () use (&$sharedUser) {
    // Depends on previous test!
    $sharedUser->update(['name' => 'Updated']);
});

// GOOD — each test is independent
it('creates a user', function () {
    $user = User::factory()->create();
    expect($user->name)->not->toBeNull();
});

it('updates a user name', function () {
    $user = User::factory()->create();
    $user->update(['name' => 'Updated']);
    expect($user->name)->toBe('Updated');
});
```

### Don't Mock Too Much

```php
// BAD — mocking everything defeats the purpose
it('processes order', function () {
    Mockery::mock(Order::class);
    Mockery::mock(PaymentService::class);
    Mockery::mock(InventoryService::class);
    Mockery::mock(NotificationService::class);
    // What's left to test?
});

// GOOD — use real objects, mock only external services
it('processes order', function () {
    $order = Order::factory()->create();
    // Use real DB, real services
    // Mock only: payment gateway, email service, external APIs
    Http::fake();
    Mail::fake();
    
    $result = $orderService->process($order);
    expect($result->success)->toBeTrue();
});
```

## 5. Coverage Measurement

```bash
# Run with coverage
vendor/bin/pest --coverage --min=80

# Generate HTML report
vendor/bin/pest --coverage --coverage-html=coverage

# Check coverage for specific module
vendor/bin/pest --coverage --testsuite=ModuleName
```

## 6. Test Health Indicators

| Symptom | Diagnosis | Fix |
|---------|-----------|-----|
| Test passes alone, fails in suite | Shared state, ordering issue | Isolate tests, use factories |
| Tests take > 10 minutes | Too many integration tests | Add more unit tests |
| Flaky tests (sometimes pass) | Race conditions, missing DB cleanup | Add RefreshDatabase, fix timing |
| High coverage but bugs persist | Testing wrong things | Test behavior, not implementation |
| Tests break on refactoring | Testing implementation details | Test public API only |

## Severity Classification

| Finding | Severity |
|---------|----------|
| No tests for business logic | High |
| Only happy-path tests | Medium |
| Tests use shared state | Medium |
| Coverage < 50% for business logic | High |
| No tests for error/edge cases | Medium |
| Tests mock everything | Low |
| Test suite takes > 10 min | Low |
| Flaky tests | Medium |
