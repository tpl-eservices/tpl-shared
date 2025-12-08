# BiblioCommons Authentication Issues - tpl-stacks

## Diagnostic Results

After examining the host app (`tpl-stacks`), I've identified several issues preventing BiblioCommons authentication from working properly.

---

## ✅ What's Correctly Configured

1. **config/auth.php** ✅
   - `biblio` guard configured correctly
   - `biblio` provider configured correctly
   - `session_cookie` set to `bc_session`

2. **config/services.php** ✅
   - BiblioCommons API configuration present
   - Library ID set to `tpl`

3. **bootstrap/app.php** ✅
   - Cookie exception for `bc_session` (won't be encrypted)
   - Middleware aliases registered:
     - `'auth'` → custom `App\Http\Middleware\Authenticate`
     - `'biblio.auth'` → package middleware `AuthenticateBiblioCommons`

4. **.env** ✅
   - API credentials configured
   - Library ID set

---

## ❌ Issues Found

### Issue 1: User Model Not Set Up for Stateless Auth ⚠️ **CRITICAL**

**File:** `app/Models/User.php`

**Problem:**
The User model still expects database persistence, but the BiblioCommons auth is stateless (no database).

**Current:**
```php
class User extends Authenticatable
{
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

**Should Be:**
```php
class User extends Authenticatable
{
    // No database - make properties public for dynamic assignment
    public $id;
    public $name;
    public $email;
    public $password;
    public $email_verified_at;
    
    // Mark as existing to prevent save attempts
    public $exists = true;
    
    // No fillable needed for transient objects
    protected $fillable = [];
    
    // Disable incrementing for API-provided IDs
    public $incrementing = false;
    protected $keyType = 'string';
}
```

---

### Issue 2: Custom Authenticate Middleware Has Authentication Commented Out ⚠️ **CRITICAL**

**File:** `app/Http/Middleware/Authenticate.php`

**Problem:**
The authentication check is commented out, so **no authentication is happening**:

```php
public function handle(Request $request, Closure $next): Response
{
//  if (! Auth::check()) {  // ← COMMENTED OUT!
        $currentUrl = urlencode($request->fullUrl());
        $loginUrl = config('services.bibliocommons.login_url');
        Log::info($_COOKIE['bc_session'] ?? 'No bc_session cookie found');
//      return redirect("{$loginUrl}?destination={$currentUrl}");
//  }

    return $next($request);  // ← Always allows through!
}
```

**Solution:** Either:
1. Use the package middleware instead: Change routes to use `middleware('biblio.auth')`
2. Fix the custom middleware to actually authenticate

---

### Issue 3: Routes Using Wrong Middleware ⚠️ **IMPORTANT**

**File:** `routes/web.php`

**Problem:**
Routes use `middleware('auth')` which points to the broken custom middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/', [StacksController::class, 'index'])->name('home');
    Route::post('/', [StacksController::class, 'store'])->name('stacks.store');
});
```

**Should Use:**
```php
Route::middleware('biblio.auth')->group(function () {
    Route::get('/', [StacksController::class, 'index'])->name('home');
    Route::post('/', [StacksController::class, 'store'])->name('stacks.store');
});
```

---

### Issue 4: Auth Guard Not Specified 🔍 **MINOR**

When accessing authenticated user in controllers, should specify the biblio guard:

**Current (likely):**
```php
$user = Auth::user(); // Uses default 'web' guard
```

**Should Be:**
```php
$user = Auth::guard('biblio')->user(); // Uses biblio guard
```

---

## 🔧 Fixes Required

### Fix 1: Update User Model

Replace `app/Models/User.php` with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Public properties for dynamic assignment from API.
     * No database persistence - transient objects only.
     */
    public $id;
    public $name;
    public $email;
    public $password;
    public $email_verified_at;

    /**
     * Mark as existing to prevent save attempts.
     */
    public $exists = true;

    /**
     * No fillable needed - not persisting to database.
     */
    protected $fillable = [];

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
}
```

### Fix 2: Update Routes to Use Correct Middleware

Replace `routes/web.php`:

```php
<?php

use App\Http\Controllers\StacksController;
use Illuminate\Support\Facades\Route;

// Use biblio.auth middleware (from package)
Route::middleware('biblio.auth')->group(function () {
    Route::get('/', [StacksController::class, 'index'])->name('home');
    Route::post('/', [StacksController::class, 'store'])->name('stacks.store');
});
```

### Fix 3: Update Controllers to Use Biblio Guard

In your controllers (e.g., `StacksController.php`):

```php
use Illuminate\Support\Facades\Auth;

public function index()
{
    // Get user from biblio guard
    $user = Auth::guard('biblio')->user();
    
    return view('stacks.index', compact('user'));
}
```

### Fix 4: Optional - Remove or Fix Custom Middleware

Since you're using the package middleware (`biblio.auth`), you can:

**Option A:** Delete `app/Http/Middleware/Authenticate.php` (not needed)

**Option B:** Keep it for reference but remove from bootstrap:

```php
// bootstrap/app.php
$middleware->alias([
    // Remove this if not using custom middleware
    // 'auth' => \App\Http\Middleware\Authenticate::class,
    'biblio.auth' => AuthenticateBiblioCommons::class,
]);
```

---

## 🧪 Testing After Fixes

### 1. Test Cookie Reading

```php
// Add temporary route
Route::get('/debug-cookie', function () {
    return [
        'bc_session' => getRawCookie('bc_session'),
        'all_cookies' => $_COOKIE,
    ];
});
```

### 2. Test Authentication

```php
Route::get('/debug-auth', function () {
    return [
        'is_authenticated' => Auth::guard('biblio')->check(),
        'user' => Auth::guard('biblio')->user(),
    ];
});
```

### 3. Test Protected Route

Visit `http://localhost/` with a valid `bc_session` cookie:
- Should authenticate and show content
- Check logs: `tail -f storage/logs/laravel.log | grep BiblioCommons`

Without cookie:
- Should redirect to BiblioCommons login

---

## 📊 Summary of Issues

| Issue | Severity | Status | File |
|-------|----------|--------|------|
| User model expects database | ⚠️ CRITICAL | Needs fix | `app/Models/User.php` |
| Auth commented out in middleware | ⚠️ CRITICAL | Needs fix | `app/Http/Middleware/Authenticate.php` |
| Routes using wrong middleware | ⚠️ IMPORTANT | Needs fix | `routes/web.php` |
| Auth guard not specified | 🔍 MINOR | Optional | Controllers |

---

## 🚀 Quick Fix Steps

1. **Update User model** - Make it transient (no database)
2. **Update routes** - Change `auth` to `biblio.auth`
3. **Update controllers** - Use `Auth::guard('biblio')->user()`
4. **Test** - Visit protected routes with bc_session cookie

After these fixes, BiblioCommons authentication should work correctly!

---

## 📝 Expected Behavior After Fixes

1. User visits protected route
2. Middleware checks for `bc_session` cookie
3. Middleware validates session with BiblioCommons API
4. Middleware fetches user data from API
5. Transient User object created (not saved to DB)
6. User is authenticated and can access route
7. Fresh data from API on every request

---

**Next Steps:** Would you like me to create the fixed files for you?

