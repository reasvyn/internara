# Rules: Input Validation

> CWE: CWE-20 (Improper Input Validation)
> ISO 25010 Mapping: Functional Suitability (Correctness), Security (Integrity)
> Applicability: All applications accepting user input

## Overview

All user input is untrusted until validated. Validation must happen server-side, regardless
of client-side validation.

## Core Principles

1. **Validate on the server** — Client-side validation is UX, not security
2. **Whitelist, don't blacklist** — Allow known-good, reject everything else
3. **Validate type, length, format, and range** — Not just presence
4. **Reject invalid input early** — Before processing
5. **Use type coercion carefully** — `filter_var()` with strict mode

## What to Check

### 1. All Inputs Validated

```php
// BAD — no validation
$name = $request->input('name');
User::create(['name' => $name]);

// GOOD — validated via FormRequest
class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}

// GOOD — inline validation
$validated = $request->validate([
    'name' => 'required|string|max:255',
]);
```

### 2. Type Validation

```php
// BAD — no type check
$page = $request->input('page'); // Could be anything
$users = User::paginate(20, '*', 'page', $page); // May error or inject

// GOOD — type validated
$page = (int) $request->input('page', 1);
$users = User::paginate(20, '*', 'page', $page);

// BETTER — FormRequest handles this
'page' => ['nullable', 'integer', 'min:1'],
```

### 3. Email Validation

```php
// BAD — basic regex
filter_var($email, FILTER_VALIDATE_EMAIL);

// GOOD — proper validation
'email' => ['required', 'email', 'max:255'],

// BETTER — with DNS check
'email' => ['required', 'email:rfc,dns', 'max:255'],
```

### 4. File Upload Validation

```php
// BAD — no validation
$request->file('avatar')->store('avatars');

// GOOD — comprehensive validation
$request->validate([
    'avatar' => [
        'required',
        'file',
        'max:2048', // 2MB max
        'mimes:jpg,jpeg,png', // Allowed types
        'dimensions:max_width=1000,max_height=1000', // Size limit
    ],
]);

// GOOD — additional server-side check
$file = $request->file('avatar');
$ mimeType = $file->getMimeType();
$allowed = ['image/jpeg', 'image/png'];
if (!in_array($mimeType, $allowed)) {
    abort(422, 'Invalid file type.');
}
```

### 5. Array Input Validation

```php
// BAD — array not validated
$tags = $request->input('tags'); // Could contain anything
foreach ($tags as $tag) {
    Tag::create(['name' => $tag]); // SQL injection risk
}

// GOOD — array validated
'tags' => ['required', 'array', 'max:10'],
'tags.*' => ['required', 'string', 'max:50'],
```

### 6. Date/Time Validation

```php
// BAD — no validation
$date = $request->input('start_date');
$carbon = Carbon::parse($date); // May throw

// GOOD — validated
'start_date' => ['required', 'date', 'after:today'],
'end_date' => ['required', 'date', 'after:start_date'],
```

### 7. Numeric Validation

```php
// BAD — string comparison
$quantity = $request->input('quantity');
if ($quantity > 0) { // String comparison, "9" > "10"

// GOOD — integer validation
'quantity' => ['required', 'integer', 'min:1', 'max:100'],
```

### 8. Boolean Validation

```php
// BAD — no type safety
$active = $request->input('active'); // "false" is truthy in PHP!

// GOOD — boolean validation
'active' => ['required', 'boolean'],
```

### 9. JSON/HTML Input Sanitization

```php
// BAD — raw HTML from user
$content = $request->input('content'); // May contain <script>

// GOOD — strip HTML tags
$content = strip_tags($request->input('content'));

// GOOD — use HTMLPurifier for rich content
$clean = $purifier->purify($request->input('content'));

// GOOD — use markdown with sanitization
$clean = Str::markdown($request->input('content'));
// Must be sanitized: strip_tags or HTMLPurifier on output
```

### 10. Mass Assignment Protection

```php
// BAD — $request->all() passes all input
User::create($request->all());

// GOOD — validated + explicit fillable
User::create($request->validated());

// GOOD — guarded model
class User extends Model
{
    protected $guarded = ['id', 'role'];
}
```

## Validation in Laravel

### FormRequest (Preferred for Complex Validation)

```php
class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'avatar' => ['nullable', 'file', 'image', 'max:2048'],
        ];
    }
}
```

### Inline Validation (Simple Cases)

```php
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'body' => 'required|string',
]);
```

### Custom Validation Rules

```php
// GOOD — reusable custom rule
class ValidNISN extends Rule
{
    public function passes($attribute, $value)
    {
        return preg_match('/^\d{10}$/', $value) === 1;
    }
    
    public function message()
    {
        return 'The :attribute must be a valid 10-digit NISN.';
    }
}
```

## Severity Classification

| Finding | Severity |
|---------|----------|
| No validation on mutation endpoint | High |
| Missing type validation (string vs int) | Medium |
| File upload without type/size check | High |
| Array input not validated | Medium |
| Date input not validated | Low |
| Email not validated | Medium |
| Mass assignment possible | High |
| User HTML rendered without sanitization | High |
