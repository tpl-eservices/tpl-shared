# BiblioCommons Authentication Middleware

## Overview

The `AuthenticateBiblioCommons` middleware automatically handles BiblioCommons SSO authentication before requests reach your controllers. It validates sessions, authenticates users, and redirects to BiblioCommons login when needed.

---

## Features

- ✅ **Automatic Authentication** - Checks and validates BiblioCommons session
- ✅ **Cookie Reading** - Uses `CookieUtils` to read external cookies
- ✅ **Guard Integration** - Works with `BiblioGuard` for authentication
- ✅ **Auto-Redirect** - Redirects to BiblioCommons login when unauthenticated
- ✅ **Comprehensive Logging** - Detailed logs for debugging
- ✅ **Zero Configuration** - Works out of the box with sensible defaults
- ✅ **Optional Session Login** - Can log into default session guard automatically

---

## Installation

### 1. Register Middleware

In your `bootstrap/app.php`:

```php
use Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        // Register BiblioCommons auth middleware
        $middleware->alias([
            'biblio.auth' => AuthenticateBiblioCommons::class,
        ]);
    })
    // ...
```

### 2. Use in Routes

```php
// Protect route group
Route::middleware('biblio.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/settings', [SettingsController::class, 'index']);
});

// Protect individual route
Route::get('/protected', function () {
    return view('protected', ['user' => Auth::user()]);
})->middleware('biblio.auth');

// In controller constructor
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('biblio.auth');
    }
}
```

---

## How It Works

### Authentication Flow

```
1. Request arrives at middleware
   ↓
2. Check: Is user already authenticated?
   ├─ Yes → Continue to controller
   └─ No → Continue to step 3
   ↓
3. Check: Does bc_session cookie exist?
   ├─ No → Redirect to BiblioCommons login
   └─ Yes → Continue to step 4
   ↓
4. Validate session via BiblioGuard
   ↓
5. Authentication successful?
   ├─ Yes → Continue to controller
   └─ No → Redirect to BiblioCommons login
```

### Step-by-Step Process

1. **Check Existing Authentication**
   ```php
   if (Auth::guard('biblio')->check()) {
       // User already authenticated, continue
   }
   ```

2. **Read Session Cookie**
   ```php
   $sessionId = CookieUtils::getRaw('bc_session', $request);
   ```

3. **Attempt Authentication**
   ```php
   $user = Auth::guard('biblio')->user();
   // Guard validates session and fetches user from API
   ```

4. **Optional Session Login**
   ```php
   if (config('auth.biblio_auto_login_session')) {
       Auth::login($user); // Log into default guard
   }
   ```

5. **Redirect if Failed**
   ```php
   return redirect('https://tpl.bibliocommons.com/user/login?destination=...');
   ```

---

## Configuration

### Required Configuration

Ensure these are set in your `config/auth.php`:

```php
'guards' => [
    'biblio' => [
        'driver' => 'biblio',
        'provider' => 'biblio_users',
        'session_cookie' => env('BIBLIO_SESSION_COOKIE', 'bc_session'),
    ],
],

'providers' => [
    'biblio_users' => [
        'driver' => 'biblio',
        'model' => App\Models\User::class,
    ],
],
```

And in `config/services.php`:

```php
'bibliocommons' => [
    'api_base_url' => env('BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com'),
    'api_key' => env('BIBLIOCOMMONS_API_KEY'),
    'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),
],
```

### Optional Configuration

#### Custom Cookie Name

Change the cookie name in `config/auth.php`:

```php
'guards' => [
    'biblio' => [
        'session_cookie' => 'my_custom_cookie',
    ],
],
```

Or via environment:

```env
BIBLIO_SESSION_COOKIE=my_custom_cookie
```

#### Auto-Login to Session Guard

Enable automatic login to Laravel's default session guard:

```php
// config/auth.php
'biblio_auto_login_session' => env('BIBLIO_AUTO_LOGIN_SESSION', false),
```

```env
BIBLIO_AUTO_LOGIN_SESSION=true
```

When enabled, users authenticated via BiblioCommons will also be logged into the default session guard, allowing them to persist across requests without requiring the BiblioCommons cookie on every request.

#### Custom Library ID

```env
BIBLIOCOMMONS_LIBRARY_ID=your-library-id
```

This affects the redirect URL: `https://{library_id}.bibliocommons.com/user/login`

---

## Usage Examples

### Example 1: Protected Dashboard

```php
// routes/web.php
Route::middleware('biblio.auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::guard('biblio')->user();
        
        return view('dashboard', [
            'user' => $user,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    });
});
```

### Example 2: Controller with Middleware

```php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('biblio.auth');
    }
    
    public function index()
    {
        $user = Auth::guard('biblio')->user();
        
        return view('dashboard', compact('user'));
    }
}
```

### Example 3: Mixed Authentication

```php
// Some routes require BiblioCommons, others use standard auth
Route::middleware('biblio.auth')->group(function () {
    Route::get('/biblio-only', [BiblioController::class, 'index']);
});

Route::middleware('auth')->group(function () {
    Route::get('/standard-auth', [StandardController::class, 'index']);
});
```

### Example 4: Conditional Middleware

```php
// Apply middleware conditionally
Route::get('/content/{type}', [ContentController::class, 'show'])
    ->middleware(function ($request, $next) {
        if ($request->route('type') === 'premium') {
            return app('biblio.auth')->handle($request, $next);
        }
        return $next($request);
    });
```

---

## Logging

The middleware provides comprehensive logging for debugging:

```php
// When middleware is invoked
Log::info('BiblioCommons authentication middleware invoked');

// When user is already authenticated
Log::info('User already authenticated via BiblioCommons guard');

// When session cookie is found
Log::info('BiblioCommons session cookie found', [
    'cookie_name' => 'bc_session',
]);

// When authentication succeeds
Log::info('User authenticated via BiblioCommons', [
    'user_id' => $user->id,
    'user_name' => $user->name,
]);

// When authentication fails
Log::warning('BiblioCommons authentication failed', [
    'session_cookie' => 'bc_session',
]);

// When redirecting to login
Log::info('Redirecting to BiblioCommons login', [
    'login_url' => 'https://tpl.bibliocommons.com/user/login?destination=...',
]);
```

### Viewing Logs

```bash
# Tail Laravel logs
tail -f storage/logs/laravel.log

# Search for BiblioCommons logs
grep "BiblioCommons" storage/logs/laravel.log

# View last 50 authentication logs
grep "BiblioCommons" storage/logs/laravel.log | tail -50
```

---

## Troubleshooting

### Issue: Infinite Redirect Loop

**Symptom:** Browser shows "too many redirects" error

**Causes:**
1. BiblioCommons cookie not being set
2. Cookie domain mismatch
3. Session validation failing

**Solution:**
```php
// Check logs
tail -f storage/logs/laravel.log | grep BiblioCommons

// Verify cookie is being set
Route::get('/debug-cookie', function (Request $request) {
    return [
        'bc_session' => getRawCookie('bc_session', $request),
        'all_cookies' => $_COOKIE,
    ];
});
```

### Issue: User Not Authenticated

**Symptom:** Middleware redirects even with valid cookie

**Debug:**
```php
// Add to routes/web.php temporarily
Route::get('/debug-auth', function () {
    $sessionId = getRawCookie('bc_session');
    
    return [
        'cookie_exists' => $sessionId !== null,
        'cookie_value' => $sessionId,
        'guard_check' => Auth::guard('biblio')->check(),
        'user' => Auth::guard('biblio')->user(),
    ];
});
```

### Issue: Wrong Cookie Name

**Symptom:** Cookie exists but not being read

**Solution:** Check cookie name configuration
```php
// config/auth.php
'guards' => [
    'biblio' => [
        'session_cookie' => 'bc_session', // Must match actual cookie name
    ],
],
```

### Issue: BiblioCommons API Not Responding

**Symptom:** Logs show "BiblioCommons authentication failed"

**Check:**
1. API credentials configured
2. Network connectivity
3. API rate limits

```php
// Test API directly
Route::get('/test-api', function () {
    $biblioSso = app(BiblioSsoService::class);
    $sessionId = getRawCookie('bc_session');
    
    $session = $biblioSso->validateSession($sessionId);
    
    return [
        'session_valid' => $session !== null,
        'session_data' => $session,
    ];
});
```

---

## Advanced Usage

### Custom Middleware with Additional Logic

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons as BaseMiddleware;

class CustomBiblioAuth extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        // Run parent middleware first
        $response = parent::handle($request, $next);
        
        // Add custom logic after authentication
        if (Auth::guard('biblio')->check()) {
            $user = Auth::guard('biblio')->user();
            
            // Log user activity
            Log::info('User accessed route', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);
            
            // Set custom headers
            $response->headers->set('X-BiblioCommons-User', $user->id);
        }
        
        return $response;
    }
}
```

### Middleware Parameters

```php
// Create middleware that accepts parameters
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class BiblioAuthWithRole
{
    public function handle($request, Closure $next, $role = null)
    {
        // First, check BiblioCommons authentication
        if (! Auth::guard('biblio')->check()) {
            return redirect('https://tpl.bibliocommons.com/user/login');
        }
        
        // Then check role if specified
        if ($role && ! $request->user()->hasRole($role)) {
            abort(403, 'Insufficient permissions');
        }
        
        return $next($request);
    }
}

// Use in routes
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('biblio.auth.role:admin');
```

---

## Testing

### Unit Testing the Middleware

```php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons;

it('allows authenticated users through', function () {
    Http::fake([
        'api.bibliocommons.com/*' => Http::response([
            'user' => ['id' => '123'],
            'borrower' => ['id' => '123', 'name' => 'Test', 'email' => 'test@example.com'],
        ]),
    ]);
    
    $_COOKIE['bc_session'] = 'valid-session';
    
    $response = $this->get('/dashboard');
    
    $response->assertOk();
    $this->assertAuthenticated('biblio');
    
    unset($_COOKIE['bc_session']);
});

it('redirects unauthenticated users', function () {
    $response = $this->get('/dashboard');
    
    $response->assertRedirect();
    $response->assertRedirectContains('bibliocommons.com/user/login');
});
```

### Integration Testing

```php
it('authenticates via middleware and accesses protected route', function () {
    Http::fake([
        'api.bibliocommons.com/*' => Http::response([
            'user' => ['id' => '123'],
            'borrower' => ['id' => '123', 'name' => 'Test User', 'email' => 'test@tpl.ca'],
        ]),
    ]);
    
    $_COOKIE['bc_session'] = 'test-session-123';
    
    $response = $this->get('/dashboard');
    
    $response->assertOk();
    $response->assertSee('Test User');
    $this->assertAuthenticated('biblio');
    
    unset($_COOKIE['bc_session']);
});
```

---

## Security Considerations

### 1. Cookie Security
- Middleware only reads cookies, never writes them
- BiblioCommons sets HttpOnly cookies
- HTTPS recommended in production

### 2. Session Validation
- Every request validates session with BiblioCommons API
- No cached authentication state
- Fresh user data on every request

### 3. Redirect Security
- Destination URL is properly encoded
- No open redirect vulnerabilities
- Redirects only to configured BiblioCommons domain

### 4. Logging
- Sensitive data not logged
- Session IDs not exposed in logs
- User IDs logged for audit trail

---

## Best Practices

1. **Use on Protected Routes Only**
   - Don't apply to public routes
   - Don't apply to login/logout routes

2. **Enable Logging in Development**
   - Monitor authentication flow
   - Debug issues quickly

3. **Configure Auto-Session Login**
   - For web applications with sessions
   - Not needed for stateless APIs

4. **Test with Invalid Cookies**
   - Ensure proper redirect behavior
   - Test error scenarios

5. **Monitor API Rate Limits**
   - BiblioCommons API has rate limits
   - Consider caching if needed

---

## Summary

The `AuthenticateBiblioCommons` middleware provides:

- ✅ **Automatic authentication** before controllers
- ✅ **Zero boilerplate** in controllers
- ✅ **Comprehensive logging** for debugging
- ✅ **Flexible configuration** for different setups
- ✅ **Production-ready** with proper error handling

**Simply register the middleware and apply it to routes - authentication is handled automatically!**

---

**For more information:**
- [AUTH_PROVIDER_GUIDE.md](AUTH_PROVIDER_GUIDE.md) - Complete authentication guide
- [BIBLIOSSO_USAGE.md](BIBLIOSSO_USAGE.md) - BiblioSsoService usage
- [README.md](README.md) - Package overview

