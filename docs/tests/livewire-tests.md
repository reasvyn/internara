# Livewire Testing: Interactive Verification

Since Internara is built on the TALL stack, Livewire components are our primary interface layer.
Testing these components ensures that user interactions, reactive states, and business logic
delegation behave as expected.

> **Spec Alignment:** Livewire tests must verify the **Mobile-First** responsiveness and
> **Multi-Language** compliance of all UI components.

---

## 1. Testing UI State & Localization

Always verify that the UI renders the correct localized strings.

```php
it('renders localized welcome message in indonesian', function () {
    app()->setLocale('id');

    Livewire::test(Dashboard::class)->assertSee(__('core::ui.welcome'));
});
```

---

## 2. Functional Verification

Verify that user actions correctly interact with the **Service Layer**.

### 2.1 Form Submissions

```php
it('delegates user creation to the service layer', function () {
    Livewire::test(CreateUser::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
});
```

---

## 3. Authorization & Roles

Verify that UI elements are hidden according to the User Roles defined in
**[Internara Specs](../internal/internara-specs.md)**.

```php
it('denies students from accessing the delete action', function () {
    $student = User::factory()->create()->assignRole('student');

    actingAs($student)->livewire(UserList::class)->assertForbidden();
});
```

---

## 4. Mobile-First Considerations

While functional tests don't check pixels, ensure your component handles data density well:

- Test that tables have "Card View" alternatives or horizontal scroll states if required.
- Test that navigation components (Drawers/Sidebars) respond correctly to state changes.

---

_Effective Livewire tests are the frontline of our quality assurance, ensuring a consistent and
localized experience for all users._
