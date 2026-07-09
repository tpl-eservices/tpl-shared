# BiblioCommons Integration Guide

Complete guide for integrating BiblioCommons SSO authentication and template system into Laravel applications.

## Overview

TPL Shared package provides two main BiblioCommons integration features:

1. **SSO Authentication** - Authenticate users via BiblioCommons API
2. **Template Integration** - Use BiblioCommons headers, footers, and styles

---

## Quick Start (Authentication)

### 1. Automated Setup (Recommended)

```bash
# Run the automated install command
php artisan tpl-shared:install
```

This automatically configures:

- ✅ BiblioCommons API settings in `config/services.php`
- ✅ Authentication guard and provider in `config/auth.php`
- ✅ Middleware alias in `bootstrap/app.php`
- ✅ User model for stateless authentication
- ✅ Environment variables in `.env`

### 2. Update Environment Variables

Edit `.env` and replace placeholder values:

```env
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com
BIBLIOCOMMONS_API_KEY=your-actual-api-key
BIBLIOCOMMONS_LIBRARY_ID=tpl
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
```

### 3. Protect Routes

```php
// routes/web.php
Route::middleware('biblio.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

### 4. Use in Controllers

```php
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::guard('biblio')->user();

        return view('dashboard', [
            'user' => $user,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
```

That's it! 🎉 Users with valid BiblioCommons sessions will be automatically authenticated.

---

## Quick Start (Templates)

### 1. Configure API URL

Add to `config/services.php` (done automatically by install command):

```php
'bibliocommons' => [
    'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
],
```

### 2. Use Layout Components

```blade
<!-- Static pages -->
<x-tpl-shared::static-layout>
    <div class="py-12">
        <h1>Welcome to Our Library</h1>
        <p>Your content here...</p>
    </div>
</x-tpl-shared::static-layout>

<!-- Inertia.js pages -->
<x-tpl-shared::layout>
    @inertia
</x-tpl-shared::layout>
```

The package automatically:

- ✅ Fetches BiblioCommons templates
- ✅ Caches for 24 hours
- ✅ Injects header/footer/CSS/JS
- ✅ Handles API failures gracefully

---

## Authentication System

### Architecture

The authentication system consists of three components:

1. **`BiblioGuard`** - Reads BiblioCommons session from cookies
2. **`BiblioUserProvider`** - Fetches user data from BiblioCommons API
3. **`AuthenticateBiblioCommons`** - Middleware for automatic authentication

### Stateless Authentication

**Important:** This authentication system is **stateless**:

- Users are **not stored in database**
- User data is fetched fresh from BiblioCommons API on each request
- Session management is handled entirely by BiblioCommons

### Authentication Flow

```
1. User visits BiblioCommons and logs in
2. BiblioCommons sets cookie (biblioSession) in browser
3. User visits protected route in your app
4. Middleware reads cookie using CookieUtils
5. Guard validates session via BiblioCommons API
6. Provider fetches user profile from API
7. Transient User object created (not persisted)
8. User authenticated via standard Laravel Auth facade
```

### Configuration

#### Complete `config/auth.php`

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    // BiblioCommons guard
    'biblio' => [
        'driver' => 'biblio',
        'provider' => 'biblio',
        'session_cookie' => env('BIBLIO_SESSION_COOKIE', 'bc_session'),
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],

    // BiblioCommons provider
    'biblio' => [
        'driver' => 'biblio',
        'model' => App\Models\User::class,
    ],
],
```

#### User Model Setup

Your User model must be configured for stateless authentication:

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

### Manual Usage

#### Direct API Access

```php
use Tpl\Shared\Services\BiblioSsoService;

// Get session from cookie
$sessionId = getRawCookie('biblioSession');

// Validate and fetch user profile
$biblioSso = app(BiblioSsoService::class);
$profile = $biblioSso->fetchUserProfile($sessionId);

if ($profile) {
    // Create/update user and log them in
    $user = User::updateOrCreate(
        ['email' => $profile['borrower']['email']],
        ['name' => $profile['borrower']['name']]
    );

    Auth::login($user);
}
```

#### Cookie Utilities

Read raw (unencrypted) cookies from external systems:

```php
// Global helper function
$sessionId = getRawCookie('biblioSession');

// Advanced usage
use Tpl\Shared\Utils\CookieUtils;

// Check if cookie exists
if (CookieUtils::hasRaw('biblioSession', $request)) {
    $value = CookieUtils::getRaw('biblioSession', $request);
}

// Get multiple cookies
$cookies = CookieUtils::getRawMany(['session', 'user_id'], $request);
```

**Why needed?** Laravel encrypts cookies by default. External systems like BiblioCommons set cookies that aren't encrypted, so we need to read them raw.

---

## Template System

### How It Works

1. **BiblioCommonsTemplateService** fetches template parts from API
2. **BiblioCommonsComposer** injects data into views
3. **Layout Components** render templates with your content

### Configuration

```env
# .env
BIBLIOCOMMONS_API_URL=https://your-library.bibliocommons.com/api/external-templates
```

```php
// config/services.php
'bibliocommons' => [
    'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
],
```

### Available Layouts

#### Static Layout

```blade
<x-tpl-shared::static-layout>
    <!-- Static page content -->
</x-tpl-shared::static-layout>
```

#### Dynamic Layout (with Inertia)

```blade
<x-tpl-shared::layout>
    @inertia
</x-tpl-shared::layout>
```

### Customization

#### Publish Views

```bash
php artisan vendor:publish --tag=tpl-shared-views
```

Edit published files:

```
resources/views/vendor/tpl-shared/components/static-layout.blade.php
resources/views/vendor/tpl-shared/components/layout.blade.php
```

#### Clear Cache

```bash
php artisan tinker
>>> app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class)->clearCache()
```

---

## Advanced Usage

### Custom User Provider

Extend the provider for custom user data mapping:

```php
namespace App\Auth;

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
```

Register in `AppServiceProvider`:

```php
use App\Auth\CustomBiblioUserProvider;
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

### Custom Middleware

Create middleware with additional logic:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomBiblioAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check BiblioCommons authentication
        if (!Auth::guard('biblio')->check()) {
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

---

## Testing

### Authentication Tests

```php
use Illuminate\Support\Facades\Http;
use Tpl\Shared\Services\BiblioSsoService;

it('authenticates user with valid session', function () {
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'user' => [
                'id' => '123',
                'name' => 'testuser',
                'borrowers' => ['tpl' => '456'],
            ],
        ], 200),
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'successful' => true,
            'borrower' => [
                'id' => '456',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
        ], 200),
    ]);

    $service = app(BiblioSsoService::class);
    $profile = $service->fetchUserProfile('test-session-id');

    expect($profile['borrower']['name'])->toBe('Test User');
});
```

### Template Tests

```php
it('renders layout with bibliocommons data', function () {
    Http::fake([
        '*' => Http::response([
            'header' => '<header>Library Header</header>',
            'footer' => '<footer>Library Footer</footer>',
            'css' => '',
            'js' => '',
            'screen_reader_navigation' => '',
        ], 200),
    ]);

    $response = $this->get('/');

    $response->assertSee('Library Header', false);
    $response->assertSee('Library Footer', false);
});
```

---

## Troubleshooting

### Common Issues

#### Authentication Not Working

```php
// Check if cookie exists
$sessionId = getRawCookie('biblioSession');
dd($sessionId); // Should show session ID

// Check if guard works
$user = Auth::guard('biblio')->user();
dd($user); // Should show User model or null

// Check BiblioCommons API directly
$biblioSso = app(BiblioSsoService::class);
$profile = $biblioSso->fetchUserProfile($sessionId);
dd($profile); // Should show borrower data
```

#### Templates Not Loading

```bash
# Check API configuration
php artisan tinker
>>> config('services.bibliocommons.external_templates_url')

# Check cached data
php artisan tinker
>>> Cache::get('bibliocommons_templates')

# View logs
tail -f storage/logs/laravel.log | grep BiblioCommons
```

#### Vite Development Issues

```typescript
// vite.config.ts
export default defineConfig({
    server: {
        host: 'localhost',
        hmr: { host: 'localhost' },
    },
});
```

```env
# .env
APP_URL=http://localhost
VITE_DEV_SERVER_URL=http://localhost:5173
```

### Debug Commands

```bash
# Diagnose BiblioCommons configuration
php artisan bibliocommons:diagnose

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Clear BiblioCommons cache only
php artisan tpl-shared:clear-cache
```

### Common Errors

| Issue                     | Solution                                            |
| ------------------------- | --------------------------------------------------- |
| "Authentication required" | Check BiblioCommons API credentials in `.env`       |
| "Template not found"      | Verify `BIBLIOCOMMONS_API_URL` is correct           |
| "Cookie not found"        | Ensure BiblioCommons cookie domain matches your app |
| "API timeout"             | Check network connectivity and API rate limits      |

---

## API Reference

### BiblioSsoService Methods

#### `validateSession(string $sessionId): ?array`

Validates a BiblioCommons session ID.

```php
$sessionData = $biblioSso->validateSession($sessionId);
// Returns: ['user' => ['id' => '2412321', 'name' => 'exampleuser', 'borrowers' => ['tpl' => '12345']]]
```

#### `fetchBorrowerInfo(string $borrowerId): ?array`

Fetches detailed borrower information.

```php
$borrower = $biblioSso->fetchBorrowerInfo('123456');
// Returns: ['successful' => true, 'borrower' => ['id' => '123456', 'name' => 'John Doe', 'email' => 'john@example.com']]
```

#### `fetchUserProfile(string $sessionId): ?array`

Validates session and fetches complete profile in one call.

```php
$profile = $biblioSso->fetchUserProfile($sessionId);
// Returns: Same as fetchBorrowerInfo() or null if invalid
```

### CookieUtils Methods

#### `getRaw(string $name, Request $request = null): ?string`

Get a single raw cookie value.

#### `hasRaw(string $name, Request $request = null): bool`

Check if a raw cookie exists.

#### `getRawMany(array $names, Request $request = null): array`

Get multiple cookies at once.

---

## Security Considerations

### Authentication Security

- ✅ Session validated with BiblioCommons API on every request
- ✅ No credentials stored in local database
- ✅ Passwordless authentication
- ✅ HTTPS recommended for production

### Cookie Security

- ✅ External cookies only read, never written
- ✅ BiblioCommons sets HttpOnly cookies
- ✅ No sensitive data exposed in logs
- ✅ Session IDs not logged

### Template Security

- ✅ API responses cached for 24 hours
- ✅ Graceful fallback on API failures
- ✅ No user-generated content in templates
- ✅ External content properly escaped

---

## Available Commands

```bash
# Authentication & Installation
php artisan tpl-shared:install          # Automated setup
php artisan tpl-shared:uninstall        # Clean removal
php artisan bibliocommons:diagnose     # Test configuration

# Template Management
php artisan tpl-shared:clear-cache      # Clear BiblioCommons cache

# Asset Publishing
php artisan vendor:publish --tag=tpl-shared-assets
php artisan vendor:publish --tag=tpl-shared-views
php artisan vendor:publish --tag=tpl-shared-config
```

---

## Benefits

### Before TPL Shared

```php
// Manual authentication - 20+ lines of code
Route::get('/auth/callback', function () {
    $sessionId = $_COOKIE['biblioSession'] ?? null;
    if (!$sessionId) return redirect('/login');

    $response = Http::get("https://api.bibliocommons.com/v1/sessions/$sessionId");
    if (!$response->successful()) return redirect('/login');

    $userData = $response->json();
    $borrowerId = $userData['user']['borrowers']['tpl'] ?? null;
    if (!$borrowerId) return redirect('/login');

    $response = Http::get("https://api.bibliocommons.com/v1/libraries/tpl/borrowers/$borrowerId");
    if (!$response->successful()) return redirect('/login');

    $borrower = $response->json()['borrower'];
    $user = User::firstOrCreate(['email' => $borrower['email']], $borrower);
    Auth::login($user);

    return redirect('/dashboard');
});
```

### After TPL Shared

```php
// Automatic authentication - 2 lines of code
Route::middleware('biblio.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

**Advantages:**

- ✅ 90% code reduction
- ✅ Zero boilerplate
- ✅ Standard Laravel patterns
- ✅ Built-in error handling
- ✅ Comprehensive logging
- ✅ Easy to test
- ✅ Production-ready

---

## Need Help?

- **Documentation:** Check other guides in [../](../)
- **Issues:** https://github.com/tpl-eservices/tpl-shared/issues
- **Support:** Contact TPL development team
- **Diagnosis:** Run `php artisan bibliocommons:diagnose`

---

**Built with ❤️ for Toronto Public Library**
