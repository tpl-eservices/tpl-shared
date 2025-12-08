# BiblioCommons SSO Service Usage

The `BiblioSsoService` provides authentication and user profile management through the BiblioCommons API.

## Configuration

Add the following environment variables to your `.env` file:

```env
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com
BIBLIOCOMMONS_API_KEY=your-api-key-here
BIBLIOCOMMONS_LIBRARY_ID=tpl
```

Add the configuration to your `config/services.php`:

```php
'bibliocommons' => [
    'api_base_url' => env('BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com'),
    'api_key' => env('BIBLIOCOMMONS_API_KEY'),
    'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),
],
```

## Usage

The service is automatically registered as a singleton by the `SharedServiceProvider`.

### Basic Usage in Controllers

```php
<?php

namespace App\Http\Controllers;

use Tpl\Shared\Services\BiblioSsoService;

class AuthController extends Controller
{
    public function __construct(
        protected BiblioSsoService $biblioSso
    ) {
    }

    public function callback(Request $request)
    {
        $sessionId = $request->input('session_id');
        
        // Validate the session
        $sessionData = $this->biblioSso->validateSession($sessionId);
        
        if (!$sessionData) {
            return redirect()->route('login')->with('error', 'Invalid session');
        }
        
        // Get full user profile
        $profile = $this->biblioSso->fetchUserProfile($sessionId);
        
        if (!$profile) {
            return redirect()->route('login')->with('error', 'Could not fetch user profile');
        }
        
        // Create or update local user
        // ... your user creation/update logic here
        
        return redirect()->route('dashboard');
    }
}
```

### Using Dependency Injection

```php
use Tpl\Shared\Services\BiblioSsoService;

Route::get('/auth/biblio', function (BiblioSsoService $biblioSso) {
    $profile = $biblioSso->fetchUserProfile(request('session_id'));
    
    return response()->json($profile);
});
```

### Using the Service Container

```php
$biblioSso = app(Tpl\Shared\Services\BiblioSsoService::class);
$profile = $biblioSso->fetchUserProfile($sessionId);
```

## Available Methods

### `validateSession(string $sessionId): ?array`

Validates a BiblioCommons session ID and returns user information.

**Returns:**
```php
[
    'user' => [
        'id' => '2412321',
        'name' => 'exampleuser',
        'borrowers' => ['tpl' => '123456']
    ]
]
```

### `fetchBorrowerInfo(string $borrowerId): ?array`

Fetches detailed borrower information from the BiblioCommons API.

**Returns:**
```php
[
    'successful' => true,
    'borrower' => [
        'id' => '123456',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        // ... other borrower fields
    ]
]
```

### `fetchUserProfile(string $sessionId): ?array`

Validates the session and fetches the complete user profile in one call.

**Returns:** Same as `fetchBorrowerInfo()` or `null` if session is invalid.

## Error Handling

All methods return `null` on failure and log warnings/errors automatically. Check your logs for detailed error information:

```php
$profile = $biblioSso->fetchUserProfile($sessionId);

if (!$profile) {
    // Handle authentication failure
    Log::info('BiblioCommons authentication failed for session', ['session_id' => $sessionId]);
}
```

## Testing

The service uses Laravel's HTTP client, making it easy to fake responses in tests:

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

## Notes

- The service includes retry logic (2 retries with 500ms delay) for resilience
- SSL verification is disabled for local development (controlled via `withoutVerifying()`)
- All API calls have a 5-second timeout
- Errors are automatically logged with contextual information

---

## Cookie Utilities

The package includes a `CookieUtils` class for reading raw (unencrypted) cookies, which is essential for reading cookies set by external systems like BiblioCommons.

### Global Helper Function

The easiest way to get raw cookies:

```php
// Get a raw cookie value
$sessionId = getRawCookie('biblioSession');

// With explicit request
$sessionId = getRawCookie('biblioSession', $request);
```

### Using the CookieUtils Class

For more advanced usage:

```php
use Tpl\Shared\Utils\CookieUtils;

// Get a single raw cookie
$value = CookieUtils::getRaw('cookie_name', $request);

// Check if a raw cookie exists
if (CookieUtils::hasRaw('biblioSession', $request)) {
    // Cookie exists
}

// Get multiple cookies at once
$cookies = CookieUtils::getRawMany(['session', 'user_id', 'token'], $request);
// Returns: ['session' => 'value', 'user_id' => 'value', 'token' => null]
```

### Why Use Raw Cookies?

Laravel encrypts cookies by default for security. However, when integrating with external systems (like BiblioCommons SSO), you need to read cookies that were set by those systems without Laravel's encryption. This utility bypasses Laravel's cookie encryption to read raw cookie values.

### Example: BiblioCommons Integration

```php
use Tpl\Shared\Services\BiblioSsoService;

Route::get('/auth/callback', function (BiblioSsoService $biblioSso) {
    // Get the BiblioCommons session from a raw cookie
    $sessionId = getRawCookie('biblioSession');
    
    if (!$sessionId) {
        return redirect()->route('login')->with('error', 'No session found');
    }
    
    // Validate and fetch user profile
    $profile = $biblioSso->fetchUserProfile($sessionId);
    
    if (!$profile) {
        return redirect()->route('login')->with('error', 'Authentication failed');
    }
    
    // Create/update local user and log them in
    // ...
    
    return redirect()->route('dashboard');
});
```


