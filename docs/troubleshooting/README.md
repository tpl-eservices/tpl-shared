# Troubleshooting Guide

Complete guide for diagnosing and fixing common issues with the TPL Shared package.

## Quick Diagnosis

### Run Diagnostic Command

```bash
# Comprehensive package health check
php artisan bibliocommons:diagnose

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Check Installation Status

```bash
# Verify package is installed correctly
php artisan tpl-shared:install --dry-run

# Check if install was completed
cat config/tpl-shared-installed.php
```

---

## Installation Issues

### Issue: "Authentication required (github.com)"

**Error:**

```
Could not authenticate against github.com
Authentication required (github.com)
```

**Cause:** GitHub Personal Access Token not configured or expired.

**Solution:**

1. Create new GitHub Personal Access Token:
    - Go to https://github.com/settings/tokens
    - Click "Generate new token (classic)"
    - Select scope: `repo`
    - Copy token immediately

2. Configure Composer:

```bash
composer config --global github-oauth.github.com YOUR_NEW_TOKEN
```

3. Verify configuration:

```bash
composer config --global github-oauth.github.com
```

### Issue: "Git Repository is empty"

**Error:**

```
The "https://api.github.com/repos/tpl-eservices/tpl-shared/git/refs/heads" file could not be downloaded (HTTP/2 409):
{"message":"Git Repository is empty."}
```

**Cause:** GitHub repository exists but has no commits pushed.

**Solution:**
Package maintainer needs to push commits:

```bash
cd /path/to/tpl-shared
git push origin main
git push origin v0.1.0
```

### Issue: "Could not find package tpl/shared"

**Error:**

```
Could not find package tpl/shared at any version for your minimum-stability
```

**Cause:** Repository not added to composer.json.

**Solution:**
Add repository to your host app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/tpl-eservices/tpl-shared.git"
        }
    ]
}
```

### Issue: Install Command Fails on Windows

**Error:**

```
mkdir(): No such file or directory
```

**Cause:** Windows path separator issue in backup directory creation (FIXED in v0.1.25+).

**Solution:**

```bash
# Update to latest version
composer update tpl/shared

# If still occurring, check permissions:
icacls storage/backups /grant "Everyone:(OI)(CI)F"
```

---

## Authentication Issues

### Issue: BiblioCommons Authentication Not Working

**Symptoms:**

- Users redirected to login even with valid BiblioCommons session
- `Auth::guard('biblio')->check()` returns false
- No user data available

**Diagnosis Steps:**

1. **Check Cookie Exists:**

```php
// Add to routes temporarily
Route::get('/debug-cookie', function (Request $request) {
    return [
        'biblioSession' => getRawCookie('biblioSession', $request),
        'all_cookies' => $_COOKIE,
    ];
});
```

2. **Check API Configuration:**

```bash
php artisan tinker
>>> config('services.bibliocommons.api_base_url')
>>> config('services.bibliocommons.api_key')
>>> config('services.bibliocommons.library_id')
```

3. **Test BiblioCommons API Directly:**

```php
// Add to routes temporarily
Route::get('/debug-api', function () {
    $biblioSso = app(BiblioSsoService::class);
    $sessionId = getRawCookie('biblioSession');

    $session = $biblioSso->validateSession($sessionId);
    $profile = $biblioSso->fetchUserProfile($sessionId);

    return [
        'session_valid' => $session !== null,
        'session_data' => $session,
        'profile' => $profile,
    ];
});
```

**Common Solutions:**

| Problem          | Solution                                 |
| ---------------- | ---------------------------------------- |
| Missing API key  | Add `BIBLIOCOMMONS_API_KEY` to `.env`    |
| Wrong library ID | Set correct `BIBLIOCOMMONS_LIBRARY_ID`   |
| Cookie not set   | Check BiblioCommons domain configuration |
| API timeout      | Verify network connectivity              |

### Issue: Infinite Redirect Loop

**Symptoms:**

- Browser shows "too many redirects" error
- Page keeps refreshing without loading

**Cause:** Middleware can't authenticate and keeps redirecting.

**Solution:**

1. **Check Logs:**

```bash
tail -f storage/logs/laravel.log | grep BiblioCommons
```

2. **Verify Cookie Domain:**
    - Ensure BiblioCommons cookie domain matches your application
    - Check cookie is set with correct domain/path

3. **Test with Valid Session:**
    - Log in to BiblioCommons first
    - Verify `biblioSession` cookie exists

### Issue: User Not Created/Updated

**Symptoms:**

- Authentication succeeds but user data not in database
- User properties missing or incorrect

**Solution:**

1. **Check User Model:**

```php
// Ensure User model has these properties
class User extends Authenticatable
{
    public $id;
    public $name;
    public $email;
    public $password;
    public $email_verified_at;
    public $exists = true; // Important for stateless auth
}
```

2. **Check Database Schema:**

```php
// Ensure users table has required columns
Schema::table('users', function (Blueprint $table) {
    $table->string('biblio_id')->unique()->nullable();
    $table->string('email')->nullable()->change();
});
```

3. **Check Provider Configuration:**

```php
// config/auth.php
'providers' => [
    'biblio' => [
        'driver' => 'biblio',
        'model' => App\Models\User::class,
    ],
],
```

---

## Template Integration Issues

### Issue: BiblioCommons Header/Footer Not Showing

**Symptoms:**

- Layout components render without BiblioCommons styling
- No header or footer content
- Missing CSS/JS assets

**Diagnosis:**

1. **Check API Configuration:**

```bash
php artisan tinker
>>> config('services.bibliocommons.external_templates_url')
```

2. **Check Cached Data:**

```bash
php artisan tinker
>>> Cache::get('bibliocommons_templates')
```

3. **Check View Composer:**

```bash
# Verify composer is registered
php artisan route:list --name=bibliocommons
```

**Solutions:**

| Problem        | Solution                                                |
| -------------- | ------------------------------------------------------- |
| Wrong API URL  | Update `BIBLIOCOMMONS_API_URL` in `.env`                |
| Cache stale    | Clear cache: `Cache::forget('bibliocommons_templates')` |
| API timeout    | Check network connectivity to BiblioCommons             |
| Missing config | Add configuration to `config/services.php`              |

### Issue: CSS/JS Conflicts

**Symptoms:**

- Page styling broken after adding BiblioCommons templates
- JavaScript errors in console
- Layout alignment issues

**Solution:**

1. **Publish Views for Customization:**

```bash
php artisan vendor:publish --tag=tpl-shared-views
```

2. **Isolate BiblioCommons Content:**

```blade
<!-- In published layout file -->
<div class="bibliocommons-wrapper">
    {!! $bibliocommons['header'] !!}
</div>

<main>
    @yield('content')
</main>

<div class="bibliocommons-wrapper">
    {!! $bibliocommons['footer'] !!}
</div>
```

3. **Add Custom CSS:**

```css
.bibliocommons-wrapper {
    /* Isolate BiblioCommons styles */
}
```

---

## Frontend Development Issues

### Issue: Vite ENOTFOUND Errors

**Error:**

```
Internal server error: ENOTFOUND tpl-shared.tpl.ca
```

**Cause:** Vite trying to resolve package with wrong domain.

**Solution:**

1. **Update vite.config.ts:**

```typescript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
    ],
    server: {
        host: 'localhost',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
});
```

2. **Update .env:**

```env
APP_URL=http://localhost
VITE_DEV_SERVER_URL=http://localhost:5173
```

### Issue: Assets Not Loading in Production

**Symptoms:**

- 404 errors for CSS/JS files
- Missing package assets after deployment

**Solution:**

1. **Publish Assets:**

```bash
php artisan vendor:publish --tag=tpl-shared-assets --force
```

2. **Build Assets:**

```bash
npm run build
```

3. **Check Asset Manifest:**

```bash
cat public/build/manifest.json
```

4. **Verify Asset Paths:**

```blade
<!-- Use helper function -->
<link rel="stylesheet" href="{{ tplSharedAsset('css') }}">
<script src="{{ tplSharedAsset('js') }}"></script>
```

---

## Version Management Issues

### Issue: "Working directory is not clean"

**Error:**

```
Error: Working directory is not clean. Commit or stash changes first.
```

**Solution:**

```bash
# Check what's uncommitted
git status

# Commit changes
git add -A
git commit -m "Your commit message"

# Or stash changes
git stash

# Then try again
make tag-patch
```

### Issue: Version Mismatch

**Symptoms:**

- Different versions in composer.json and package.json
- Git tags don't match version files

**Solution:**

```bash
# Update version files to match git tags
make update-version

# Or manually fix versions
# Edit composer.json and package.json
git add -A
git commit -m "Fix version numbers"
```

### Issue: Composer Won't Update

**Symptoms:**

- `composer update tpl/shared` shows old version
- Host application can't get latest package

**Solution:**

```bash
# Clear Composer cache
composer clear-cache

# Update specific package
composer update tpl/shared --with-all-dependencies

# Remove and reinstall
composer remove tpl/shared
composer require tpl/shared:^0.2.0

# Check configured repository
composer config --list | grep tpl-shared
```

---

## Performance Issues

### Issue: Slow Page Loads

**Symptoms:**

- Pages take 5+ seconds to load
- BiblioCommons templates delay rendering

**Solutions:**

1. **Check BiblioCommons API Performance:**

```bash
# Test API response time
curl -w "@curl-format.txt" -o /dev/null -s "https://api.bibliocommons.com/v1/sessions/test"
```

2. **Implement Template Caching:**

```php
// Custom template service
class FastBiblioCommonsService extends BiblioCommonsTemplateService
{
    public function getTemplateParts(): array
    {
        return Cache::remember('bibliocommons_templates', now()->addHours(12), function () {
            return parent::getTemplateParts();
        });
    }
}
```

3. **Enable HTTP Caching:**

```php
// In controller
public function dashboard()
{
    return response()->view('dashboard')
        ->header('Cache-Control', 'public, max-age=3600');
}
```

### Issue: High Memory Usage

**Symptoms:**

- PHP memory errors
- Slow performance with many users

**Solutions:**

1. **Optimize User Provider:**

```php
// Use minimal user data
protected function createUserFromApiData(array $data): Authenticatable
{
    $user = new $this->model;
    $user->id = $data['id'];
    $user->name = $data['name'];
    // Only load required properties
    $user->exists = true;

    return $user;
}
```

2. **Enable PHP Opcache:**

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

---

## Development Environment Issues

### Issue: Class Not Found

**Error:**

```
Class 'Tpl\Shared\Services\BiblioSsoService' not found
```

**Solution:**

```bash
# Dump autoloader
composer dump-autoload

# Check package is installed
composer show tpl/shared

# Verify service provider is registered
php artisan config:cache
```

### Issue: Route Not Defined

**Error:**

```
Route [dashboard] not defined
```

**Solution:**

```bash
# Check registered routes
php artisan route:list

# Clear route cache
php artisan route:clear
php artisan route:cache
```

### Issue: Tests Failing

**Common Test Issues:**

1. **Environment Variables Missing:**

```php
// In tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();

    config(['services.bibliocommons.api_key' => 'test-key']);
}
```

2. **HTTP Mocking Issues:**

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'api.bibliocommons.com/*' => Http::response([
        'user' => ['id' => '123', 'name' => 'Test User'],
    ], 200),
]);
```

3. **Cookie Testing:**

```php
$_COOKIE['biblioSession'] = 'test-session-id';

// Don't forget to clean up
unset($_COOKIE['biblioSession']);
```

---

## Debugging Tools

### Debug Routes

Add temporary debug routes to your application:

```php
Route::get('/debug/package', function () {
    return [
        'package_version' => config('shared.version'),
        'services_config' => config('services.bibliocommons'),
        'auth_config' => config('auth.guards.biblio'),
        'install_status' => file_exists(config_path('tpl-shared-installed.php')),
    ];
});

Route::get('/debug/cookies', function (Request $request) {
    return [
        'biblioSession' => getRawCookie('biblioSession', $request),
        'all_cookies' => $_COOKIE,
        'request_cookies' => $request->cookie(),
    ];
});
```

### Logging

Add comprehensive logging:

```php
use Illuminate\Support\Facades\Log;

// Debug authentication flow
Log::debug('BiblioCommons auth check', [
    'cookie_exists' => getRawCookie('biblioSession') !== null,
    'cookie_value' => getRawCookie('biblioSession'),
    'auth_check' => Auth::guard('biblio')->check(),
    'user_data' => Auth::guard('biblio')->user(),
]);

// Debug API calls
Log::debug('BiblioCommons API call', [
    'url' => $url,
    'response_code' => $response->status(),
    'response_body' => $response->body(),
]);
```

### Environment Debugging

Check environment configuration:

```bash
# Show all environment variables
php artisan env

# Check specific values
php artisan tinker
>>> env('BIBLIOCOMMONS_API_KEY')
>>> config('services.bibliocommons')
```

---

## Quick Fixes by Issue

### 5-Minute Fixes

| Issue                         | Quick Fix                                                |
| ----------------------------- | -------------------------------------------------------- |
| **BiblioCommons not loading** | Run `php artisan bibliocommons:diagnose`                 |
| **Authentication failing**    | Check `BIBLIOCOMMONS_API_KEY` in `.env`                  |
| **Vite errors**               | Set `host: 'localhost'` in `vite.config.ts`              |
| **Assets not found**          | Run `php artisan vendor:publish --tag=tpl-shared-assets` |
| **Version issues**            | Run `composer clear-cache && composer update tpl/shared` |
| **Tests failing**             | Run `composer dump-autoload && php artisan config:cache` |

### Step-by-Step Troubleshooting

1. **Clear Everything:**

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
composer dump-autoload
```

2. **Check Configuration:**

```bash
php artisan bibliocommons:diagnose
php artisan tinker
>>> config('services.bibliocommons')
>>> config('auth.guards.biblio')
```

3. **Test Components Individually:**
    - Test BiblioCommons API directly
    - Test authentication middleware
    - Test template rendering
    - Test asset loading

4. **Check Logs:**

```bash
tail -f storage/logs/laravel.log | grep -E "(BiblioCommons|tpl-shared|Error)"
```

---

## Getting Help

### Automated Help

```bash
# Package-specific help
php artisan bibliocommons:diagnose
php artisan tpl-shared:install --help

# Development help
make help
.\build.ps1 help
```

### Documentation

- [Installation Guide](../installation/README.md) - Complete installation instructions
- [Features Guide](../features/) - Feature-specific documentation
- [Development Guide](../development/README.md) - Development workflow

### Support Channels

- **GitHub Issues:** https://github.com/tpl-eservices/tpl-shared/issues
- **TPL Development Team:** Contact directly for urgent issues
- **Community:** Laravel community channels

### Report Issues

When reporting issues, include:

1. **Package Version:** `composer show tpl/shared`
2. **Laravel Version:** `php artisan --version`
3. **PHP Version:** `php --version`
4. **Error Messages:** Full error stack traces
5. **Configuration:** Redacted configuration values
6. **Steps to Reproduce:** Detailed reproduction steps
7. **Expected vs Actual:** What you expected vs what happened

---

## Prevention Tips

### Before Installation

- [ ] Verify PHP 8.4+ and Laravel 12.x
- [ ] Set up GitHub authentication
- [ ] Check network connectivity to GitHub
- [ ] Backup existing application

### Before Releases

- [ ] Run full test suite
- [ ] Format all code
- [ ] Update CHANGELOG.md
- [ ] Verify version consistency
- [ ] Test in fresh environment

### Ongoing Maintenance

- [ ] Regularly update dependencies
- [ ] Monitor BiblioCommons API changes
- [ ] Keep documentation current
- [ ] Review error logs periodically

---

**Still stuck?**

Don't hesitate to reach out! We're here to help you succeed with the TPL Shared package.

---

Built with ❤️ for Toronto Public Library
