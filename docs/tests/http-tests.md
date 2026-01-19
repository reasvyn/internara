# HTTP Testing: Endpoint Verification

While Livewire handles most user interactions, Internara still utilizes standard HTTP routes for
redirections, API endpoints, and media access. HTTP tests verify that our routing, middleware, and
response structures are correct.

---

## 1. Testing Route Protection

The most common HTTP test case is ensuring that middleware (like `auth` or `role`) is correctly
applied to a route.

### 1.1 Redirect Guest

```php
it('redirects guests to login', function () {
    get('/admin/dashboard')->assertRedirect('/login');
});
```

### 1.2 Status Verification

```php
it('allows teachers to access the workspace', function () {
    $teacher = User::factory()->create()->assignRole('teacher');

    actingAs($teacher)->get('/teacher/dashboard')->assertStatus(200);
});
```

---

## 2. Testing Media & Signed URLs

For sensitive documents like internship certificates, we use **Signed URLs**.

```php
it('allows access via signed URL', function () {
    $url = URL::signedRoute('certificate.verify', ['id' => $id]);

    get($url)->assertStatus(200)->assertSee('Authenticated Certificate');
});
```

---

## 3. Best Practices

1.  **Test for "Forbidden"**: Always include tests that verify unauthorized roles receive a `403`
    response.
2.  **Assert Redirects**: When a user is moved after an action (e.g., Logout), verify the
    destination.
3.  **JSON APIs**: If building API endpoints, use `assertJsonStructure` to ensure the contract is
    respected.

---

_HTTP tests ensure that the "gates" of our application are secure. They are essential for verifying
cross-cutting concerns like authentication and localization._
