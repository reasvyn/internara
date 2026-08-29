# Rules: Cryptography

> OWASP: A02 (Cryptographic Failures)
> CWE: CWE-327 (Broken/Risky Crypto Algorithm), CWE-338 (PRNG)
> Applicability: All applications handling sensitive data

## Core Principles

1. **Don't roll your own crypto** — Use established libraries/frameworks
2. **Use strong algorithms** — bcrypt/argon2 for passwords, AES-256 for encryption
3. **Use cryptographically secure randomness** — `random_bytes()`, not `rand()`
4. **Never hardcode keys** — Use environment variables
5. **Encrypt sensitive data at rest** — PII, tokens, secrets

## What to Check

### 1. Password Hashing

```php
// BAD — weak algorithms
md5($password);
sha1($password);
password_hash($password, PASSWORD_DEFAULT); // Default is bcrypt, OK
crypt($password, $salt);

// GOOD — explicit strong algorithm
Hash::make($password);  // Laravel default: bcrypt
Hash::make($password, 'argon2id');  // Alternative

// Check verification
Hash::check($password, $user->password);
```

### 2. Encryption

```php
// BAD — custom encryption
$encrypted = base64_encode($data);  // NOT encryption!
$encrypted = openssl_encrypt($data, 'aes-128-ecb', $key);  // Weak mode

// GOOD — Laravel encryption
$encrypted = encrypt($data);
$decrypted = decrypt($encrypted);

// GOOD — explicit encryption with strong algorithm
$encrypted = Crypt::encryptString($data);
$decrypted = Crypt::decryptString($encrypted);
```

### 3. Random Number Generation

```php
// BAD — not cryptographically secure
$token = md5(uniqid(mt_rand(), true));
$token = rand(100000, 999999);
$token = str_random(32); // Laravel 4 helper (deprecated)

// GOOD — cryptographically secure
$token = Str::random(32);  // Laravel helper (uses random_bytes internally)
$token = bin2hex(random_bytes(32));
$token = base64_encode(random_bytes(32));

// GOOD — for one-time codes
$code = random_int(100000, 999999);  // PHP 7+ secure
```

### 4. Key Management

```php
// BAD — hardcoded key
$key = 'my-secret-key-123';
$encrypted = encrypt($data, $key);

// GOOD — environment variable
// .env
APP_KEY=base64:...
ENCRYPTION_KEY=base64:...

// config/app.php
'key' => env('APP_KEY'),
```

### 5. APP_KEY

```php
// Check:
// - APP_KEY is set and strong (32+ bytes, base64 encoded)
// - APP_KEY is not committed to version control
// - APP_KEY is unique per environment
// - APP_KEY was generated with php artisan key:generate
```

### 6. Token Generation

```php
// BAD — predictable tokens
$token = md5($userId . time());
$token = substr(md5($email), 0, 16);

// GOOD — random tokens
$token = Str::random(60);  // For API tokens
$token = Str::random(32);  // For password reset
$token = bin2hex(random_bytes(32));  // For email verification
```

## Detection

```bash
# Find weak hashing
grep -rn "md5(\|sha1(\|crypt(" app/ --include="*.php"

# Find base64 used as "encryption"
grep -rn "base64_encode\|base64_decode" app/ --include="*.php"

# Find weak randomness
grep -rn "mt_rand\|rand(" app/ --include="*.php"

# Find hardcoded keys
grep -rn "key.*=.*['\"]" config/ --include="*.php" | grep -v "env("

# Find APP_KEY
grep "APP_KEY" .env 2>/dev/null
```

## Severity Classification

| Finding | Severity |
|---------|----------|
| Passwords hashed with MD5/SHA1 | Critical |
| APP_KEY missing or weak | Critical |
| Hardcoded encryption keys | Critical |
| `mt_rand()` for security tokens | High |
| `rand()` for security tokens | High |
| `base64_encode()` used as encryption | High |
| ECB mode encryption | High |
| Custom encryption implementation | High |
| No encryption for PII at rest | Medium |
| Token predictable (time-based) | High |
