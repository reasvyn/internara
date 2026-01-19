# Livewire Testing: Interactive Verification

Since Internara is built on the TALL stack, Livewire components are our primary interface layer.
Testing these components ensures that user interactions, reactive states, and business logic
delegation behave as expected.

---

## 1. Testing UI State

Use the Livewire testing API to simulate user actions and verify component state.

### 1.1 Standard Interaction

```php
it('can search for users', function () {
    Livewire::test(UserList::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});
```

### 1.2 Form Submissions

Always verify that forms trigger the correct Service methods.

```php
it('can create a new user', function () {
    Livewire::test(CreateUser::class)
        ->set('name', 'New User')
        ->set('email', 'new@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/users');
});
```

---

## 2. Authorization in Tests

Verifying that UI elements are correctly hidden or protected is a core requirement.

### 2.1 The "Forbidden" Section

```php
it('hides the delete button for students', function () {
    $student = User::factory()->create()->assignRole('student');

    actingAs($student)->livewire(UserList::class)->assertDontSee('Delete');
});
```

---

## 3. Best Practices

1.  **Don't Over-Assert**: Focus on behavior (e.g., "Was the record created?") rather than
    implementation details (e.g., "Is the variable `$user` an object?").
2.  **Use Component Aliases**: Test components using their modular aliases if possible.
3.  **Clean State**: Always use the `RefreshDatabase` concern to ensure each test runs in isolation.

---

_Effective Livewire tests allow us to iterate on our design system without breaking core
functionality. They are the frontline of our quality assurance._
