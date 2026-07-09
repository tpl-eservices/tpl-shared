# Installation Guide - TPL Shared Package

Complete guide for installing and configuring the TPL Shared package in Laravel applications.

## Quick Start (5 Minutes)

### Prerequisites

- **PHP** 8.4+
- **Laravel** 12.x
- **Node.js** 20+
- Private repository access to `tpl-eservices/tpl-shared`

### Step 1: GitHub Authentication (One-Time Setup)

Since this is a **private repository**, you need to authenticate with GitHub:

1. **Create a Personal Access Token:**
    - Go to https://github.com/settings/tokens
    - Click **"Generate new token (classic)"**
    - Give it a name: `Composer - TPL Shared`
    - Select scope: **`repo`** (Full control of private repositories)
    - Click **"Generate token"**
    - **Copy the token immediately** (you won't see it again!)

2. **Configure Composer (run once on your machine):**
    ```bash
    composer config --global github-oauth.github.com YOUR_TOKEN_HERE
    ```

### Step 2: Install Package

```bash
# Add repository to composer.json
composer repo add shared vcs https://github.com/tpl-eservices/tpl-shared.git

# Install the package
composer require tpl/shared:^0.1.0
```

### Step 3: Run Automated Install Command ✨

```bash
php artisan tpl-shared:install
```

This automated command will:

- ✅ Configure `config/services.php` with BiblioCommons settings
- ✅ Configure `config/auth.php` with biblio guard and provider
- ✅ Register middleware in `bootstrap/app.php`
- ✅ Update User model for stateless authentication
- ✅ Add environment variables to `.env`
- ✅ Create backups of all modified files
- ✅ Track installation status

### Step 4: Update Environment Variables

Edit `.env` and replace placeholder values:

```env
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com
BIBLIOCOMMONS_API_KEY=your-actual-api-key
BIBLIOCOMMONS_LIBRARY_ID=tpl
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
```

### Step 5: Start Development

```bash
# Publish package assets (optional)
php artisan vendor:publish --tag=tpl-shared-assets

# Start development server
php artisan serve
npm run dev  # or pnpm dev
```

That's it! 🎉 The package is now installed and ready to use.

---

## Manual Installation (Alternative)

If you prefer to configure everything manually instead of using the automated install command:

### 1. Configure Services

Add to `config/services.php`:

```php
// TPL Shared - BiblioCommons Configuration
'bibliocommons' => [
    'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    'api_base_url' => env('BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com'),
    'api_key' => env('BIBLIOCOMMONS_API_KEY'),
    'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),
],
```

### 2. Configure Authentication

Add to `config/auth.php`:

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

### 3. Register Middleware

Add to `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    // TPL Shared - BiblioCommons Middleware
    $middleware->alias([
        'biblio.auth' => \Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons::class,
    ]);
})
```

### 4. Update User Model

Add to `app/Models/User.php`:

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

### 5. Add Environment Variables

Add to `.env`:

```env
# TPL Shared - BiblioCommons Configuration
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com
BIBLIOCOMMONS_API_KEY=your-api-key-here
BIBLIOCOMMONS_LIBRARY_ID=tpl
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
BIBLIO_SESSION_COOKIE=bc_session
```

---

## Package Development Setup

### For Package Developers

If you're actively developing the package alongside a host application:

#### Local Development with Symlink

In your host app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../tpl-shared",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

Install with dev version:

```bash
composer require tpl/shared:@dev
```

#### Making Changes

```bash
cd tpl-shared

# PHP changes
composer format    # Format code
composer test      # Run tests

# Frontend changes
pnpm install
pnpm lint
pnpm build         # Build assets

# Commit changes
git add -A
git commit -m "Your changes"
```

#### Creating a Release

```bash
# Unix/Linux/Mac
make release

# Windows
.\build.ps1 release
```

---

## Post-Installation Usage

### BiblioCommons Template Integration

Use the provided layout components:

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

### Authentication

Protect routes with BiblioCommons authentication:

```php
// In routes/web.php
Route::middleware('biblio.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});

// In controllers
$user = Auth::guard('biblio')->user();
```

### Direct API Access

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

---

## Available Commands

### Package Management Commands

```bash
# Automated installation/removal
php artisan tpl-shared:install
php artisan tpl-shared:install --force    # Force reinstall
php artisan tpl-shared:uninstall
php artisan tpl-shared:uninstall --dry-run  # Preview changes

# Diagnose BiblioCommons configuration
php artisan bibliocommons:diagnose

# Clear BiblioCommons template cache
php artisan tpl-shared:clear-cache

# Publish package assets
php artisan vendor:publish --tag=tpl-shared-assets
php artisan vendor:publish --tag=tpl-shared-config
php artisan vendor:publish --tag=tpl-shared-views
```

### Development Commands (Package Developers)

```bash
# Unix/Linux/Mac
make help          # Show all commands
make status        # Check current state
make test          # Run tests
make format        # Format PHP code
make release       # Create new release
make clean         # Clean artifacts

# Windows
.\build.ps1 help
.\build.ps1 status
.\build.ps1 test
.\build.ps1 format
.\build.ps1 release
.\build.ps1 clean
```

---

## Troubleshooting

### Common Issues

#### "Authentication required (github.com)"

You need to set up your GitHub token (see Step 1 above).

#### "Could not find package tpl/shared"

Make sure the repository is added to `composer.json`.

#### Vite Error: "ENOTFOUND tpl-shared.tpl.ca"

Use `localhost` in your vite.config.ts and .env:

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

#### "Class not found" errors

Run `composer dump-autoload` in your host application.

#### Assets not loading

Publish the assets: `php artisan vendor:publish --tag=tpl-shared-assets`

#### Install command fails with "mkdir(): No such file or directory"

This was a Windows path issue that's been fixed. Update to the latest version:

```bash
composer update tpl/shared
```

### Getting Help

- **Installation Issues:** Check the troubleshooting section above
- **Documentation:** See [docs/](../) for complete documentation
- **GitHub Issues:** https://github.com/tpl-eservices/tpl-shared/issues
- **Support:** Contact the TPL development team

---

## Version Management

The package follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html):

- **Patch (0.1.0 → 0.1.1):** Bug fixes, small improvements
- **Minor (0.1.0 → 0.2.0):** New features, backward-compatible changes
- **Major (0.1.0 → 1.0.0):** Breaking changes, major refactoring

### Updating Package

```bash
# Update to latest version
composer update tpl/shared

# Update to specific version
composer require tpl/shared:^0.2.0

# After updating, run install command if needed
php artisan tpl-shared:install --force
```

---

## What's Included

The package provides:

- ✅ **BiblioCommons Integration** - SSO authentication and template integration
- ✅ **Cookie Utilities** - Read raw cookies from external systems
- ✅ **Blade Components** - Reusable UI components
- ✅ **React Components** - Inertia.js powered frontend components
- ✅ **Frontend Assets** - Shared CSS and JavaScript
- ✅ **Database Migrations** - Common database structures
- ✅ **Configuration** - Centralized settings management

---

## Next Steps

1. **Review the Documentation:** Check [../features/](../features/) for feature-specific guides
2. **Test Integration:** Use BiblioCommons layouts in your views
3. **Set Up Authentication:** Protect routes with `biblio.auth` middleware
4. **Explore Features:** Read about cookie utilities and API services
5. **Customize as Needed:** Publish views and assets for customization

---

**Ready to go!** 🎉 The TPL Shared package is now integrated into your Laravel application.

**Package:** tpl/shared  
**Version:** v0.1.0+  
**Repository:** https://github.com/tpl-eservices/tpl-shared  
**License:** Proprietary
