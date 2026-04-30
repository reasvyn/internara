# Session Documentation: Internara

## 1. Overview

Internara uses Laravel's session management for maintaining user state across requests. The system supports multiple session drivers with **Database** as the default for production and **Array** for testing.

### Configuration
- **Config File**: `config/session.php`
- **Environment Variables**: `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_COOKIE`
- **Default Driver**: Database (`env('SESSION_DRIVER', 'database')`)

## 2. Session Drivers

### Available Drivers
| Driver | Description | Use Case |
|--------|-------------|---------|
| `database` | Database table storage | Production default |
| `file` | File-based storage | Simple deployments |
| `redis` | Redis key-value store | High-performance production |
| `memcached` | Memcached distributed memory | High-traffic applications |
| `array` | In-memory array (non-persistent) | Testing |
| `cookie` | Encrypted cookies | Stateless (not recommended for auth) |
| `null` | No session storage | API-only applications |

### Default Configuration
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_COOKIE=internara_session
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTPONLY=true
SESSION_SAME_SITE=lax
```

## 3. Session Table (`sessions`)

### Migration (in `2026_04_29_092750_create_users_table.php`)
```php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignUuid('user_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

### Table Structure
| Column | Type | Description |
|--------|------|-------------|
| `id` | varchar(255) | Session ID (primary key) |
| `user_id` | uuid (nullable) | Authenticated user (foreign key) |
| `ip_address` | varchar(45) | Client IP address |
| `user_agent` | text | Browser user agent |
| `payload` | longText | Serialized session data |
| `last_activity` | integer | Timestamp of last activity |

## 4. Session Configuration

### Lifetime & Expiration
```env
SESSION_LIFETIME=120  # 120 minutes (2 hours)
SESSION_EXPIRE_ON_CLOSE=false  # Keep session until expiration
```

### Security Settings
```env
SESSION_ENCRYPT=false        # Encrypt session data?
SESSION_SECURE_COOKIE=true  # Only send over HTTPS
SESSION_HTTPONLY=true       # Prevent JavaScript access
SESSION_SAME_SITE=lax         # CSRF protection (lax/strict/none)
```

### Cookie Settings
```env
SESSION_COOKIE=internara_session
SESSION_PATH=/
SESSION_DOMAIN=null  # Auto-detect domain
```

## 5. Session Usage Patterns

### Basic Session Operations

#### Store Data
```php
use Illuminate\Support\Facades\Session;

// Store single value
Session::put('key', 'value');
session(['key' => 'value']); // Helper function

// Store multiple values
Session::put(['key1' => 'value1', 'key2' => 'value2']);

// Flash data (available only for next request)
Session::flash('message', 'Action successful!');
Session::flash('errors', $errors);

// Flash all data for next request
Session::reflash();

// Keep specific flash data for another request
Session::keep(['message', 'errors']);
```

#### Retrieve Data
```php
// Get value
$value = Session::get('key');
$value = Session::get('key', 'default'); // With default

// Get and remove (pull)
$value = Session::pull('key'); // Retrieves and deletes

// Check if exists
if (Session::has('key')) { }
if (Session::exists('key')) { } // Same as has()

// Get all session data
$data = Session::all();
```

#### Delete Data
```php
// Remove specific key
Session::forget('key');
Session::forget(['key1', 'key2']); // Multiple keys

// Remove all session data (but keep session alive)
Session::flush();

// Regenerate session ID (security - prevent fixation)
Session::regenerate(); // With optional: regenerate(true) to delete old file

// Invalidate session (logout)
Session::invalidate(); // Flush + regenerate
```

## 6. Real-World Usage in Internara

### Example 1: Setup Wizard (`app/Services/Setup/SetupService.php`)
```php
class SetupService
{
    private const SESSION_PREFIX = 'setup.';
    
    public function getCurrentStep(): int
    {
        return (int) Session::get(self::SESSION_PREFIX.'current_step', 1);
    }
    
    public function setCurrentStep(int $step): void
    {
        Session::put(self::SESSION_PREFIX.'current_step', max(1, min(6, $step)));
    }
    
    public function completeStep(string $step): void
    {
        $steps = $this->getCompletedSteps();
        
        if (! in_array($step, $steps)) {
            $steps[] = $step;
            Session::put(self::SESSION_PREFIX.'completed_steps', $steps);
        }
    }
    
    public function getCompletedSteps(): array
    {
        return Session::get(self::SESSION_PREFIX.'completed_steps', []);
    }
    
    public function storeEntityId(string $key, string $id): void
    {
        Session::put(self::SESSION_PREFIX.'entity.'.$key, $id);
    }
    
    public function clearSession(): void
    {
        Session::forget(self::SESSION_PREFIX.'token');
        Session::forget(self::SESSION_PREFIX.'token_expires_at');
        Session::forget(self::SESSION_PREFIX.'current_step');
        Session::forget(self::SESSION_PREFIX.'completed_steps');
        Session::forget(self::SESSION_PREFIX.'entity');
    }
}
```

### Example 2: Login (Livewire Component)
```php
class Login extends Component
{
    public function login(): void
    {
        // ... authentication logic ...
        
        // Regenerate session to prevent fixation
        session()->regenerate();
        
        // Redirect to intended URL
        return $this->redirect(session()->pull('url.intended', '/dashboard'));
    }
}
```

### Example 3: Middleware (ProtectSetupRoute)
```php
class ProtectSetupRoute
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->session()->get('setup.token_input');
        
        if (! $token || ! $this->setupService->validateToken($token)) {
            return redirect()->route('setup.wizard');
        }
        
        return $next($request);
    }
}
```

## 7. Session Drivers Configuration

### Database Driver (Default)
```php
// config/session.php
'database' => [
    'driver' => 'database',
    'connection' => env('SESSION_CONNECTION'),
    'table' => 'sessions',
],
```

**Pros**:
- Persistent across server restarts
- No additional infrastructure
- Works with existing database
- User association via `user_id`

**Cons**:
- Slower than in-memory stores
- Adds database load
- Needs cleanup for expired sessions

### Redis Driver (High Performance)
```env
SESSION_DRIVER=redis
REDIS_SESSION_CONNECTION=session
```

```php
// config/session.php
'redis' => [
    'driver' => 'redis',
    'connection' => env('REDIS_SESSION_CONNECTION', 'session'),
],
```

**Pros**:
- Very fast (in-memory)
- Automatic expiration
- Atomic operations

**Cons**:
- Requires Redis server
- Additional infrastructure

### File Driver (Simple)
```env
SESSION_DRIVER=file
SESSION_FILES=storage/framework/sessions
```

**Pros**:
- No database needed
- Simple setup

**Cons**:
- Slow for many sessions
- Can't scale across multiple servers

## 8. Session Security

### S1 - Secure: Session Fixation Protection
```php
// Always regenerate session after authentication
Session::regenerate(); // In LoginController/Livewire

// Or with logout
Auth::logout();
Session::invalidate(); // Flush + regenerate
```

### S1 - Secure: HTTPS Only
```env
SESSION_SECURE_COOKIE=true  # Only send cookie over HTTPS
```

### S1 - Secure: HttpOnly Flag
```env
SESSION_HTTPONLY=true  # Prevent JavaScript access to cookie
```

### S1 - Secure: SameSite Protection
```env
SESSION_SAME_SITE=lax  # Protects against CSRF
# Options: 'lax' (recommended), 'strict' (paranoid), 'none' (API only)
```

### S1 - Secure: Encrypt Session Data
```env
SESSION_ENCRYPT=true  # Encrypt session payload
```

⚠️ **Warning**: Encryption adds overhead. Only use if storing sensitive data in session.

## 9. Session Testing

### PHPUnit/Pest Testing
```php
use Illuminate\Support\Facades\Session;

test('session stores data', function () {
    Session::put('test_key', 'test_value');
    
    expect(Session::get('test_key'))->toBe('test_value');
    
    Session::forget('test_key');
    expect(Session::has('test_key'))->toBeFalse();
});

test('session regeneration', function () {
    $oldId = session()->getId();
    session()->regenerate();
    $newId = session()->getId();
    
    expect($oldId)->not->toBe($newId);
});
```

### Testing with Array Driver
Tests automatically use `array` driver (configured in `phpunit.xml`):
```xml
<env name="SESSION_DRIVER" value="array"/>
```

**Benefits**:
- No database needed for session tests
- Session is isolated per test
- Fast execution

## 10. Session Maintenance

### Cleanup Expired Sessions

#### Database Driver
```bash
# Manually clean expired sessions
php artisan session:table  # If you need to recreate table
```

Laravel doesn't automatically clean expired sessions in database. You can:
```sql
-- Delete expired sessions (last_activity + lifetime < current time)
DELETE FROM sessions 
WHERE last_activity < (UNIX_TIMESTAMP() - 120);  # 120 = SESSION_LIFETIME
```

#### Automated Cleanup (Optional)
Create a scheduled job:
```php
// app/Console/Kernel.php (or routes/console.php)
Schedule::call(function () {
    DB::table('sessions')
        ->where('last_activity', '<', now()->subMinutes(120)->timestamp)
        ->delete();
})->daily();
```

### Monitor Active Sessions
```sql
-- Count active sessions
SELECT COUNT(*) FROM sessions;

-- Find sessions for specific user
SELECT * FROM sessions WHERE user_id = 'uuid-here';

-- Find expired sessions
SELECT * FROM sessions 
WHERE last_activity < (UNIX_TIMESTAMP() - 120);
```

## 11. Session & Authentication

### Laravel Auth Integration
Internara uses Laravel's authentication with session-based guards:

```env
# config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],
```

### Remember Me (Remember Token)
- Table: `users` (column: `remember_token`)
- Cookie: Stored separately from session
- Lifetime: 5 years (Laravel default)
- Usage: "Remember me" checkbox in login form

### Logout (In Laravel Livewire)
```php
public function logout(): void
{
    Auth::guard('web')->logout();
    
    Session::invalidate();  // Flush session + regenerate
    Session::regenerateToken(); // Regenerate CSRF token
    
    $this->redirect('/login');
}
```

## 12. Performance Considerations

### Session Driver Performance
| Driver | Read Speed | Write Speed | Scalability | Infrastructure |
|--------|------------|-------------|--------------|---------------|
| `array` | ⚡⚡⚡ Very Fast | ⚡⚡⚡ Very Fast | ❌ None | None |
| `file` | ⚡⚡ Fast | ⚡⚡ Fast | ⚠️ Limited | File system |
| `database` | ⚡ Moderate | ⚡ Moderate | ✅ Good | Database |
| `redis` | ⚡⚡⚡ Very Fast | ⚡⚡⚡ Very Fast | ✅✅ Excellent | Redis server |
| `memcached` | ⚡⚡⚡ Very Fast | ⚡⚡⚡ Very Fast | ✅✅ Excellent | Memcached server |

### When to Use Sessions
✅ **Good candidates**:
- User authentication state
- Flash messages (success/error)
- Multi-step wizards (like Setup)
- CSRF tokens
- Temporary form data

❌ **Bad candidates**:
- Large data storage (use cache instead)
- Sensitive data (use encrypted database)
- High-frequency writes (consider stateless JWT)

### Session Size Limits
- **Cookie driver**: ~4KB (browser limit)
- **Database/File/Redis**: Limited by storage, but keep < 64KB per session

## 13. Troubleshooting

### Session Not Persisting
1. Check driver: `php artisan about`
2. Verify cookie settings: Check browser DevTools > Application > Cookies
3. Check domain/path: Ensure cookie matches your URL
4. HTTPS issues: Ensure `SESSION_SECURE_COOKIE` matches your protocol

### "Session Expired" Errors
1. Check `SESSION_LIFETIME` (default 120 minutes)
2. Verify `SESSION_EXPIRE_ON_CLOSE` setting
3. Check server time synchronization
4. Look for session garbage collection

### Multiple Session Conflicts
- Ensure `SESSION_COOKIE` is unique per application
- Use different `SESSION_DOMAIN` if running multiple apps on same domain
- Check for cookie path conflicts

---

**Last Updated**: April 30, 2026  
**Default Driver**: Database  
**Test Driver**: Array  
**Session Table**: `sessions`  
**Lifetime**: 120 minutes (2 hours)