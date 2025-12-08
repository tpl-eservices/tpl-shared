# BiblioCommons Authentication Provider Guide

## Overview

The TPL Shared package includes a complete Laravel authentication system for BiblioCommons SSO. This allows host applications to authenticate users via BiblioCommons with minimal configuration—just add a few lines to your `config/auth.php` file!

**Important:** This authentication system is **stateless** - users are **not stored in the database**. User data is fetched fresh from BiblioCommons API on each request. Session management is handled entirely by BiblioCommons.

## What's Included

- **`BiblioUserProvider`** - Custom user provider that fetches user data from BiblioCommons API on every request
- **`BiblioGuard`** - Custom guard that reads BiblioCommons session from cookies and validates with API
- **No Database Storage** - Users are transient objects, fresh data on every request
- **Zero Code Required** - Works with Laravel's standard `Auth` facade

---

## Quick Start (3 Minutes)

### 1. User Model Setup

Your User model must be configured to work as a **transient model** (no database persistence):

```php
// app/Models/User.php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // No fillable needed - not saving to database
    protected $fillable = [];
    
    // These properties are set dynamically from API
    public $id;
    public $name;
    public $email;
    public $password;
    public $email_verified_at;
    
    // Mark as existing to prevent save attempts
    public $exists = true;

    // ...rest of your model
}
```

**Note:** No migration needed! Users are fetched from BiblioCommons API only.

### 2. Configure Authentication

Add to your `config/auth.php`:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    
    // Add BiblioCommons guard
    'biblio' => [
        'driver' => 'biblio',
        'provider' => 'biblio',
        'session_cookie' => 'biblioSession', // Optional: customize cookie name
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    
    // Add BiblioCommons provider
    'biblio' => [
        'driver' => 'biblio',
        'model' => App\Models\User::class,
    ],
],
```

### 4. Create Authentication Routes

```php
// routes/web.php
use Illuminate\Support\Facades\Auth;

// BiblioCommons callback route
Route::get('/auth/biblio/callback', function () {
    // The guard automatically reads the biblioSession cookie
    $user = Auth::guard('biblio')->user();
    
    if ($user) {
        // Log the user in to the regular session guard
        Auth::login($user);
        
        return redirect()->route('dashboard')
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }
    
    return redirect()->route('login')
        ->with('error', 'BiblioCommons authentication failed');
});

// Logout route
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    
    return redirect('/');
})->name('logout');
```

### 5. That's It!

Users visiting `/auth/biblio/callback` with a valid `biblioSession` cookie will be automatically authenticated!

---

## How It Works

### Authentication Flow (Stateless)

1. **User visits BiblioCommons** and logs in there
2. **BiblioCommons sets cookie** (`biblioSession`) in the user's browser
3. **User visits your app** at any protected route
4. **BiblioGuard reads cookie** using `CookieUtils::getRaw()`
5. **BiblioGuard validates session** via `BiblioSsoService::validateSession()`
6. **BiblioUserProvider fetches profile** from BiblioCommons API using borrower ID
7. **Transient User object created** from API data (not persisted to database)
8. **User is authenticated** via Laravel's standard `Auth` facade

### Architecture

```
Request → BiblioGuard → Validate Session → Get Borrower ID
              ↓              ↓                    ↓
        Read Cookie   BiblioSsoService    BiblioCommons API
              ↓              ↓                    ↓
    BiblioUserProvider → Fetch Borrower Info → Create Transient User
              ↓                                   ↓
         Auth::user() ← Transient User Object ← API Data (no DB)
```

**Key Points:**
- ✅ No database queries
- ✅ Fresh data from BiblioCommons API every request
- ✅ User objects exist only for the duration of the request
- ✅ Session managed entirely by BiblioCommons

---

## Advanced Usage

### Customizing User Data Mapping

If you need to customize how API data maps to your User model, extend the `BiblioUserProvider`:

```php
namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Tpl\Shared\Auth\BiblioUserProvider as BaseProvider;

class CustomBiblioUserProvider extends BaseProvider
{
    protected function createUserFromApiData(array $data): Authenticatable
    {
        $class = '\\'.ltrim($this->model, '\\');
        $user = new $class;

        // Custom mapping logic
        $user->id = $data['id'];
        $user->name = $data['first_name'] . ' ' . $data['last_name'];
        $user->email = $data['email'] ?? '';
        $user->custom_field = $data['custom_field'] ?? null;
        $user->exists = true;

        return $user;
    }
}
```;

class CustomBiblioUserProvider extends BaseProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (! isset($credentials['biblio_session_id'])) {
            return null;
        }

        $profile = $this->biblioSso->fetchUserProfile($credentials['biblio_session_id']);

        if (! $profile || ! isset($profile['borrower'])) {
            return null;
        }

        $borrower = $profile['borrower'];

        // Custom user creation logic
        return $this->model::firstOrCreate(
            ['biblio_id' => $borrower['id']],
            [
                'name' => $borrower['name'] ?? '',
                'email' => $borrower['email'] ?? '',
                'is_biblio_user' => true, // Custom field
                'biblio_library_id' => config('services.bibliocommons.library_id'),
            ]
        );
    }
}
```

Then register it in `config/auth.php`:

```php
'providers' => [
    'biblio' => [
        'driver' => 'biblio',
        'model' => App\Models\User::class,
    ],
],
```

And in your `AppServiceProvider`:

```php
use App\Auth\CustomBiblioUserProvider;
use Illuminate\Support\Facades\Auth;
use Tpl\Shared\Services\BiblioSsoService;

public function boot()
{
    Auth::provider('biblio', function ($app, array $config) {
        return new CustomBiblioUserProvider(
            $app->make(BiblioSsoService::class),
            $config['model']
        );
    });
}
```

### Using Middleware

The package includes a built-in middleware that automatically authenticates users via BiblioCommons before requests reach your controllers.

#### Built-in Middleware: `AuthenticateBiblioCommons`

**Location:** `Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons`

This middleware:
- ✅ Checks for BiblioCommons session cookie
- ✅ Validates session with BiblioCommons API
- ✅ Authenticates user via BiblioGuard
- ✅ Redirects to BiblioCommons login if not authenticated
- ✅ Comprehensive logging for debugging

#### Register the Middleware

In your `bootstrap/app.php`:

```php
use Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons;

->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'biblio.auth' => AuthenticateBiblioCommons::class,
    ]);
})
```

#### Use in Routes

```php
// Protect specific routes
Route::middleware('biblio.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});

// Or on individual routes
Route::get('/protected', [Controller::class, 'method'])
    ->middleware('biblio.auth');
```

#### Configuration

The middleware uses these configuration values:

```php
// config/auth.php
'guards' => [
    'biblio' => [
        'session_cookie' => env('BIBLIO_SESSION_COOKIE', 'bc_session'),
    ],
],

// config/services.php
'bibliocommons' => [
    'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),
],

// Optional: Auto-login to session guard
// config/auth.php
'biblio_auto_login_session' => env('BIBLIO_AUTO_LOGIN_SESSION', false),
```

#### How It Works

```php
1. Request arrives at middleware
2. Check if already authenticated (Auth::guard('biblio')->check())
3. If yes → continue to controller
4. If no → check for bc_session cookie
5. If cookie exists → attempt authentication via BiblioGuard
6. If authentication succeeds → continue to controller
7. If authentication fails → redirect to BiblioCommons login
8. If no cookie → redirect to BiblioCommons login
```

#### Automatic Session Login

If you want users to also be logged into Laravel's default session guard (for persistent sessions across requests), enable this in your config:

```php
// config/auth.php
'biblio_auto_login_session' => true,
```

Then in the middleware, after BiblioCommons authentication succeeds, the user will also be logged into the session guard:

```php
Auth::login($user); // Happens automatically when enabled
```

#### Custom Middleware (Optional)

If you need custom logic, you can create your own middleware:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomBiblioAuth
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('Custom BiblioCommons auth middleware');

        // Check BiblioCommons authentication
        if (! Auth::guard('biblio')->check()) {
            // Custom redirect logic
            return redirect()->route('custom.login')
                ->with('error', 'Please log in via BiblioCommons');
        }

        // Custom logic after authentication
        $user = Auth::guard('biblio')->user();
        // ... your custom logic ...

        return $next($request);
    }
}
```


### Checking Authentication Status

```php
// Check if user is authenticated via BiblioCommons
if (Auth::guard('biblio')->check()) {
    $user = Auth::guard('biblio')->user();
    echo "Hello, {$user->name}!";
}

// Validate credentials programmatically
$isValid = Auth::guard('biblio')->validate([
    'biblio_session_id' => 'session-id-here',
]);
```

### Custom Cookie Name

By default, the guard looks for a cookie named `biblioSession`. You can customize this:

```php
'guards' => [
    'biblio' => [
        'driver' => 'biblio',
        'provider' => 'biblio',
        'session_cookie' => 'my_custom_cookie_name', // Custom name
    ],
],
```

### Multiple Guards

You can use BiblioCommons authentication alongside standard Laravel authentication:

```php
// Standard login
Auth::attempt(['email' => $email, 'password' => $password]);

// BiblioCommons login
Auth::guard('biblio')->user();

// Check which guard is active
if (Auth::guard('biblio')->check()) {
    echo "Logged in via BiblioCommons";
} elseif (Auth::check()) {
    echo "Logged in via standard auth";
}
```

---

## Testing

### Unit Testing the Provider

```php
use Illuminate\Support\Facades\Http;
use Tpl\Shared\Auth\BiblioUserProvider;
use Tpl\Shared\Services\BiblioSsoService;

it('creates user from BiblioCommons profile', function () {
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'user' => ['id' => '123', 'name' => 'Test User', 'borrowers' => ['tpl' => '456']],
        ], 200),
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'successful' => true,
            'borrower' => ['id' => '456', 'name' => 'Test User', 'email' => 'test@example.com'],
        ], 200),
    ]);

    $provider = new BiblioUserProvider(
        app(BiblioSsoService::class),
        User::class
    );

    $user = $provider->retrieveByCredentials(['biblio_session_id' => 'test-session']);

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});
```

### Integration Testing

```php
it('authenticates user via BiblioCommons guard', function () {
    $_COOKIE['biblioSession'] = 'test-session-id';

    Http::fake([
        'api.bibliocommons.com/*' => Http::response([
            'successful' => true,
            'borrower' => ['id' => '123', 'name' => 'Test User', 'email' => 'test@example.com'],
        ], 200),
    ]);

    $response = $this->get('/auth/biblio/callback');

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    unset($_COOKIE['biblioSession']);
});
```

---

## Troubleshooting

### Issue: User not authenticating

**Check:**
1. BiblioCommons session cookie is present: `getRawCookie('biblioSession')`
2. BiblioCommons API credentials are configured in `.env`
3. User model has `biblio_id` column
4. Guard and provider are registered in `config/auth.php`

**Debug:**
```php
// Check if cookie exists
$sessionId = getRawCookie('biblioSession');
dd($sessionId); // Should show the session ID

// Check if guard works
$user = Auth::guard('biblio')->user();
dd($user); // Should show User model or null

// Check BiblioCommons API directly
$biblioSso = app(BiblioSsoService::class);
$profile = $biblioSso->fetchUserProfile($sessionId);
dd($profile); // Should show borrower data
```

### Issue: Database error when creating user

**Solution:** Ensure your `users` table has all required columns:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('biblio_id')->unique()->nullable();
    $table->string('email')->nullable()->change(); // Make email nullable if needed
});
```

### Issue: Cookie not found

**Check:**
1. Cookie name matches configuration
2. Cookie is set by BiblioCommons with the correct domain
3. Your app and BiblioCommons are on compatible domains (for cookie sharing)

---

## Configuration Reference

### Complete `config/auth.php` Example

```php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'biblio' => [
            'driver' => 'biblio',
            'provider' => 'biblio',
            'session_cookie' => env('BIBLIO_SESSION_COOKIE', 'biblioSession'),
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'biblio' => [
            'driver' => 'biblio',
            'model' => App\Models\User::class,
        ],
    ],

    // ... rest of auth config
];
```

### Environment Variables

```env
# BiblioCommons API Configuration
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com
BIBLIOCOMMONS_API_KEY=your-api-key-here
BIBLIOCOMMONS_LIBRARY_ID=tpl

# Optional: Custom cookie name
BIBLIO_SESSION_COOKIE=biblioSession
```

---

## Security Considerations

### 1. Cookie Security
- BiblioCommons sets the session cookie with HttpOnly flag
- Your app reads the cookie but never modifies it
- The raw cookie reading is secure—it bypasses Laravel encryption (which is intentional for external cookies)

### 2. Session Validation
- Every authentication attempt validates the session with BiblioCommons API
- Invalid or expired sessions return `null`
- No cached credentials are used

### 3. User Data
- User data is fetched fresh from BiblioCommons on each authentication
- Local database is updated with latest information
- No passwords are stored (passwordless authentication)

### 4. HTTPS Recommended
- Always use HTTPS in production
- BiblioCommons cookies should be Secure-flagged
- Prevents session hijacking

---

## Benefits Over Manual Implementation

### Before (Manual Implementation):
```php
Route::get('/auth/biblio/callback', function () {
    $sessionId = getRawCookie('biblioSession');
    
    if (!$sessionId) {
        return redirect()->route('login');
    }
    
    $biblioSso = app(BiblioSsoService::class);
    $profile = $biblioSso->fetchUserProfile($sessionId);
    
    if (!$profile) {
        return redirect()->route('login');
    }
    
    $user = User::firstOrCreate(
        ['biblio_id' => $profile['borrower']['id']],
        [
            'name' => $profile['borrower']['name'],
            'email' => $profile['borrower']['email'],
        ]
    );
    
    Auth::login($user);
    
    return redirect()->route('dashboard');
});
```

### After (With Auth Provider):
```php
Route::get('/auth/biblio/callback', function () {
    if ($user = Auth::guard('biblio')->user()) {
        Auth::login($user);
        return redirect()->route('dashboard');
    }
    
    return redirect()->route('login');
});
```

**Advantages:**
- ✅ Less code (70% reduction)
- ✅ Standard Laravel patterns
- ✅ Works with existing middleware
- ✅ Easier to test
- ✅ Centralized authentication logic
- ✅ No boilerplate in every route

---

## Summary

The BiblioCommons authentication provider gives you:

1. **Drop-in Laravel authentication** - Works with standard `Auth` facade
2. **Zero boilerplate** - Just configure `auth.php` and go
3. **Automatic user management** - Users created/updated automatically
4. **Cookie reading** - Built-in support for external cookies
5. **Extensible** - Easy to customize for your needs
6. **Well-tested** - Comprehensive test coverage included

---

## Quick Reference

### Authenticate User
```php
$user = Auth::guard('biblio')->user();
Auth::login($user);
```

### Check Authentication
```php
if (Auth::guard('biblio')->check()) {
    // User authenticated
}
```

### Validate Credentials
```php
$isValid = Auth::guard('biblio')->validate([
    'biblio_session_id' => $sessionId,
]);
```

### Get Current User
```php
$user = Auth::guard('biblio')->user();
```

---

## Support

For issues or questions:
- See [BIBLIOSSO_USAGE.md](BIBLIOSSO_USAGE.md) for service usage
- See [BIBLIOSSO_IMPLEMENTATION.md](BIBLIOSSO_IMPLEMENTATION.md) for architecture details
- Check [TROUBLESHOOTING_VERSIONS.md](TROUBLESHOOTING_VERSIONS.md) for common issues

---

**Built with ❤️ for Toronto Public Library**

