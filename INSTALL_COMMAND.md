# TPL Shared Installation Command Guide

## Overview

The `tpl-shared:install` and `tpl-shared:uninstall` commands provide automated setup and removal of the TPL Shared package in Laravel host applications.

---

## Installation Command

### Basic Usage

```bash
php artisan tpl-shared:install
```

### With Force Flag

```bash
php artisan tpl-shared:install --force
```

---

## What It Does

The install command automatically:

1. ✅ **Modifies `config/services.php`** - Adds BiblioCommons API configuration
2. ✅ **Modifies `config/auth.php`** - Adds `biblio` guard and provider
3. ✅ **Modifies `bootstrap/app.php`** - Registers `biblio.auth` middleware alias
4. ✅ **Modifies `app/Models/User.php`** - Adds stateless authentication properties
5. ✅ **Updates `.env`** - Appends BiblioCommons environment variables
6. ✅ **Creates backups** - All modified files backed up to `storage/backups/tpl-shared/{timestamp}/`
7. ✅ **Tracks installation** - Creates `config/tpl-shared-installed.php` with status
8. ✅ **Creates `.env.tpl-shared.example`** - Example environment configuration

---

## Idempotent Behavior

The command is **idempotent** - running it multiple times is safe:

- ✅ Detects existing configuration
- ✅ Skips already-installed components
- ✅ Shows status for each component
- ✅ Only modifies what's needed

### Example Output

```
✅ Configuring config/services.php ............... ⏭️ Already installed
✅ Configuring config/auth.php ................... ⏭️ Already installed
✅ Configuring bootstrap/app.php ................. ⏭️ Already installed
✅ Configuring app/Models/User.php ............... ⏭️ Already installed
✅ Configuring .env file ......................... ⏭️ Already installed
```

---

## Installation Process

### Step 1: Check Installation Status

The command first checks if TPL Shared is already installed by looking for:
- `config/tpl-shared-installed.php` file

If found and `--force` is not used, it shows current status and asks for confirmation.

### Step 2: Create Backups

All files are backed up before modification:
- Location: `storage/backups/tpl-shared/{timestamp}/`
- Preserves original directory structure
- Timestamped for easy identification

### Step 3: Modify Configuration Files

#### config/services.php
Adds BiblioCommons configuration block:

```php
// TPL Shared - BiblioCommons Configuration
'bibliocommons' => [
    'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    'api_base_url' => env('BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com'),
    'api_key' => env('BIBLIOCOMMONS_API_KEY'),
    'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),
],
```

#### config/auth.php
Adds BiblioCommons guard and provider:

```php
'guards' => [
    // ...existing guards

    // TPL Shared - BiblioCommons Guard
    'biblio' => [
        'driver' => 'biblio',
        'provider' => 'biblio',
        'session_cookie' => env('BIBLIO_SESSION_COOKIE', 'bc_session'),
    ],
],

'providers' => [
    // ...existing providers

    // TPL Shared - BiblioCommons Provider
    'biblio' => [
        'driver' => 'biblio',
        'model' => App\Models\User::class,
    ],
],
```

#### bootstrap/app.php
Adds middleware alias:

```php
->withMiddleware(function (Middleware $middleware) {
    // TPL Shared - BiblioCommons Middleware
    $middleware->alias([
        'biblio.auth' => \Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons::class,
    ]);
})
```

#### app/Models/User.php
Adds stateless authentication properties:

```php
class User extends Authenticatable
{
    // TPL Shared - Stateless Authentication Properties
    // These properties support BiblioCommons stateless authentication
    // Users are not stored in database - data is fetched from API on each request
    public $id;
    public $name;
    public $email;
    public $password;
    public $email_verified_at;

    // Mark as existing to prevent save attempts
    public $exists = true;

    // ...rest of your User model
}
```

### Step 4: Update .env File

Appends BiblioCommons configuration variables with placeholders:

```env
# TPL Shared - BiblioCommons Configuration
# Update these values with your actual BiblioCommons credentials and URLs

# BiblioCommons API base URL
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com

# Your BiblioCommons API key (required)
BIBLIOCOMMONS_API_KEY=your-api-key-here

# Your library ID (e.g., tpl, nypl, etc.)
BIBLIOCOMMONS_LIBRARY_ID=tpl

# BiblioCommons external templates API URL (for header/footer)
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates

# BiblioCommons session cookie name (default: bc_session)
BIBLIO_SESSION_COOKIE=bc_session
```

### Step 5: Save Installation Status

Creates `config/tpl-shared-installed.php` with:
- Installation timestamp
- Package version
- Backup location
- List of modified files
- Status of each component

---

## Stub Files (Fallback)

If automatic modification fails, the command creates stub files at:
- `config/examples/tpl-shared/services.php`
- `config/examples/tpl-shared/auth.php`
- `config/examples/tpl-shared/app.php`
- `config/examples/tpl-shared/User.php`

These contain example configurations you can manually merge into your files.

---

## Installation Summary

After installation, you'll see a detailed summary:

```
Installation Summary

┌─────────────────────────┬──────────────────────┐
│ Component               │ Status               │
├─────────────────────────┼──────────────────────┤
│ config/services.php     │ ✅ Modified          │
│ config/auth.php         │ ✅ Modified          │
│ bootstrap/app.php       │ ✅ Modified          │
│ app/Models/User.php     │ ✅ Modified          │
│ .env                    │ ✅ Modified          │
└─────────────────────────┴──────────────────────┘

Backups created at:
  storage/backups/tpl-shared/2024-01-15_143022

⚠️ IMPORTANT: Update these .env variables with your actual values:

┌───────────────────────────────┬──────────────────────────────────────────────────┬─────────────────────────────────┐
│ Variable                      │ Example Value                                     │ Description                     │
├───────────────────────────────┼──────────────────────────────────────────────────┼─────────────────────────────────┤
│ BIBLIOCOMMONS_API_BASE_URL    │ https://api.bibliocommons.com                    │ BiblioCommons API base URL      │
│ BIBLIOCOMMONS_API_KEY         │ your-api-key-here                                │ Your BiblioCommons API key      │
│ BIBLIOCOMMONS_LIBRARY_ID      │ tpl                                              │ Your library ID                 │
│ BIBLIOCOMMONS_API_URL         │ https://tpl.bibliocommons.com/api/external-...  │ Templates API URL               │
│ BIBLIO_SESSION_COOKIE         │ bc_session                                       │ BiblioCommons session cookie    │
└───────────────────────────────┴──────────────────────────────────────────────────┴─────────────────────────────────┘

Next Steps:
  1. Update .env variables with your actual values
  2. Publish package assets: php artisan vendor:publish --provider="Tpl\Shared\SharedServiceProvider"
  3. Use BiblioCommons layouts: <x-tpl-shared::layout>
  4. Protect routes with middleware: Route::middleware('biblio.auth')

📚 Documentation:
  • AUTH_PROVIDER_GUIDE.md - Laravel authentication setup
  • MIDDLEWARE_GUIDE.md - Middleware usage
  • BIBLIOCOMMONS.md - Template integration
  • DOCUMENTATION_INDEX.md - Complete documentation hub

✅ Installation complete!
```

---

## Uninstallation Command

### Basic Usage

```bash
php artisan tpl-shared:uninstall
```

### Dry Run (Preview Changes)

```bash
php artisan tpl-shared:uninstall --dry-run
```

---

## What Uninstall Does

The uninstall command:

1. ✅ **Shows uninstall plan** - Preview what will be removed
2. ✅ **Creates final backup** - Backs up current state before removal
3. ✅ **Removes from `config/services.php`** - Deletes BiblioCommons config
4. ✅ **Removes from `config/auth.php`** - Deletes guard and provider
5. ✅ **Removes from `bootstrap/app.php`** - Deletes middleware alias
6. ✅ **Cleans `.env`** - Removes BiblioCommons variables (with confirmation)
7. ✅ **Deletes installation status** - Removes `config/tpl-shared-installed.php`
8. ✅ **Shows manual cleanup steps** - Lists files requiring manual cleanup

### Manual Cleanup Required

The following require manual cleanup:

1. **User Model Properties** - `app/Models/User.php`
   - Remove stateless authentication properties
   - Look for: `// TPL Shared - Stateless Authentication Properties`

2. **Published Views** - `resources/views/vendor/tpl-shared/`
   ```bash
   rm -rf resources/views/vendor/tpl-shared
   ```

3. **Example Files**
   - `.env.tpl-shared.example`
   - `config/examples/tpl-shared/`

4. **Published Assets** - `public/vendor/tpl-shared/`
   ```bash
   rm -rf public/vendor/tpl-shared
   ```

---

## Command Options

### Install Command Options

| Option    | Description                                    |
|-----------|------------------------------------------------|
| `--force` | Force reinstallation even if already installed |

### Uninstall Command Options

| Option      | Description                              |
|-------------|------------------------------------------|
| `--dry-run` | Show what would be removed without doing it |

---

## Troubleshooting

### Issue: Installation fails with "mkdir(): No such file or directory"

**Status:** ✅ **FIXED** (as of v0.1.25)

**Cause:** Windows path separator issue in backup directory creation.

**Solution:** Update to the latest version of tpl-shared. The issue has been resolved by properly normalizing Windows paths (backslashes) to forward slashes when creating backup directories.

If you're still experiencing this issue:
1. Ensure you're using the latest version: `composer update tpl/shared`
2. Check that you have write permissions to the `storage/backups` directory

### Issue: Installation fails on one file

**Solution:** The command continues with other files and creates stub files for failed modifications. Check:
1. Stub files in `config/examples/tpl-shared/`
2. Backup files in `storage/backups/tpl-shared/{timestamp}/`
3. Manually merge stub content into your files

### Issue: Files already modified

**Behavior:** Command detects existing configuration and skips it. Use `--force` to override.

### Issue: Want to restore from backup

**Solution:**
```bash
# Find backup directory
ls storage/backups/tpl-shared/

# Restore specific file
cp storage/backups/tpl-shared/2024-01-15_143022/config/services.php config/services.php
```

### Issue: Need to reinstall

**Solution:**
```bash
# Uninstall first
php artisan tpl-shared:uninstall

# Then install again
php artisan tpl-shared:install
```

Or use force flag:
```bash
php artisan tpl-shared:install --force
```

---

## Post-Installation Steps

### 1. Update Environment Variables

Edit `.env` and replace placeholder values:

```env
# Replace these with actual values from BiblioCommons
BIBLIOCOMMONS_API_KEY=your-actual-api-key
BIBLIOCOMMONS_LIBRARY_ID=your-library-id
BIBLIOCOMMONS_API_URL=https://your-library.bibliocommons.com/api/external-templates
```

### 2. Publish Package Assets (Optional)

```bash
# Publish views (optional - for customization)
php artisan vendor:publish --provider="Tpl\Shared\SharedServiceProvider"

# Or publish specific assets
php artisan vendor:publish --tag=tpl-shared-assets
```

### 3. Use BiblioCommons Features

#### Template Integration
```blade
<x-tpl-shared::layout>
    <h1>Your Page Content</h1>
</x-tpl-shared::layout>
```

#### Authentication
```php
// In routes/web.php
Route::middleware('biblio.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// In controllers
$user = Auth::guard('biblio')->user();
```

#### Direct API Access
```php
use Tpl\Shared\Services\BiblioSsoService;

$biblioSso = app(BiblioSsoService::class);
$profile = $biblioSso->fetchUserProfile($sessionId);
```

---

## Best Practices

### 1. Always Review Backups
Check backup files before deleting them:
```bash
ls -la storage/backups/tpl-shared/
```

### 2. Test After Installation
Run your application's test suite:
```bash
php artisan test
```

### 3. Commit Changes
Commit the changes made by the install command:
```bash
git add .
git commit -m "Install TPL Shared package"
```

### 4. Document Environment Variables
Add `.env.tpl-shared.example` to your repository:
```bash
git add .env.tpl-shared.example
git commit -m "Add TPL Shared environment example"
```

---

## Security Considerations

### 1. Protect API Keys
- Never commit actual API keys to version control
- Keep `.env` in `.gitignore`
- Use `.env.example` for documentation only

### 2. Review Modified Files
- Check all modified files before deploying
- Ensure no conflicts with existing code
- Test authentication flow

### 3. Backup Management
- Keep backups secure
- Delete old backups periodically
- Don't expose backup directory publicly

---

## See Also

- **AUTH_PROVIDER_GUIDE.md** - Complete authentication guide
- **MIDDLEWARE_GUIDE.md** - Middleware usage and configuration
- **BIBLIOCOMMONS.md** - Template integration guide
- **DOCUMENTATION_INDEX.md** - Complete documentation hub

---

**Package:** tpl/shared  
**Commands:** `tpl-shared:install`, `tpl-shared:uninstall`  
**Status:** Production Ready

