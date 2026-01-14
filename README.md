# TPL Shared Package

A comprehensive shared Laravel package for TPL projects, providing common components, views, assets, and integrations across multiple Laravel applications.

## 📦 What's Included

- **BiblioCommons Integration** - SSO authentication and user profile management
- **Cookie Utilities** - Read raw cookies from external systems
- **Blade Components** - Reusable UI components with BiblioCommons templates
- **React Components** - Inertia.js powered frontend components
- **Frontend Assets** - Shared CSS and JavaScript
- **Database Migrations** - Common database structures
- **Configuration** - Centralized settings management

## 🎯 Requirements

- **PHP** 8.4+
- **Laravel** 12.x
- **Node.js** 20+
- **Tailwind CSS** v4

## 🚀 Quick Start

For complete installation instructions, see [INSTALL.md](INSTALL.md) or [QUICK_START.md](QUICK_START.md).

### 1. Configure Composer (run once on your machine):**
 ```bash
 composer config --global github-oauth.github.com YOUR_TOKEN_HERE
 ```

### 2. Add Repository to composer.json

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

### 3. Install Package

```bash
composer require tpl/shared:^0.1
```

### 4. Run Automated Install Command ✨ NEW

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

See [INSTALL_COMMAND.md](INSTALL_COMMAND.md) for complete documentation.

### 5. Update Environment Variables

Edit `.env` and replace placeholder values:

```env
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com
BIBLIOCOMMONS_API_KEY=your-actual-api-key
BIBLIOCOMMONS_LIBRARY_ID=tpl
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
```

> **Note:** The install command already added these to your `.env` file with placeholders. Update them with your actual values.

## 📚 Documentation

### Getting Started
- **[INSTALL.md](INSTALL.md)** - Detailed installation instructions
- **[INSTALL_COMMAND.md](INSTALL_COMMAND.md)** - **NEW** Automated install command guide ✨
- **[QUICK_START.md](QUICK_START.md)** - Fast setup guide for team members
- **[SETUP_COMPLETE.md](SETUP_COMPLETE.md)** - Post-installation verification

### Core Features

#### BiblioCommons Integration
- **[BIBLIOSSO_USAGE.md](BIBLIOSSO_USAGE.md)** - Complete BiblioCommons SSO usage guide
- **[BIBLIOSSO_IMPLEMENTATION.md](BIBLIOSSO_IMPLEMENTATION.md)** - Implementation details and architecture
- **[BIBLIOCOMMONS.md](BIBLIOCOMMONS.md)** - BiblioCommons general documentation
- **[BIBLIOCOMMONS_QUICK_REF.md](BIBLIOCOMMONS_QUICK_REF.md)** - Quick reference guide
- **[HOST_APP_BIBLIOCOMMONS_EXAMPLE.md](HOST_APP_BIBLIOCOMMONS_EXAMPLE.md)** - Host app integration examples

#### Frontend & Inertia
- **[INERTIA_USAGE.md](INERTIA_USAGE.md)** - Inertia.js integration guide

### Development

#### Package Development
- **[PACKAGE_DEV_NOTES.md](PACKAGE_DEV_NOTES.md)** - Notes for package developers
- **[PUBLISHING.md](PUBLISHING.md)** - Asset publishing guide

#### Build & Version Management
- **[MAKEFILE_GUIDE.md](MAKEFILE_GUIDE.md)** - Complete Makefile usage guide (Unix/Linux/Mac)
- **[WINDOWS_BUILD_GUIDE.md](WINDOWS_BUILD_GUIDE.md)** - **NEW** Windows build scripts guide ✨
- **[MAKEFILE_QUICK_REF.md](MAKEFILE_QUICK_REF.md)** - Quick reference for Makefile commands
- **[VERSION_MANAGEMENT.md](VERSION_MANAGEMENT.md)** - Version management workflow

#### Troubleshooting
- **[TROUBLESHOOTING_VERSIONS.md](TROUBLESHOOTING_VERSIONS.md)** - Fix version and tagging issues
- **[VENDOR_PUBLISH_FIX.md](VENDOR_PUBLISH_FIX.md)** - Resolve asset publishing problems

### Project Information
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and changes

## 🔧 Key Features

### BiblioCommons SSO Service

Authenticate users via BiblioCommons API with a simple, Laravel-style API:

```php
use Tpl\Shared\Services\BiblioSsoService;

Route::get('/auth/callback', function (BiblioSsoService $biblioSso) {
    // Get session from cookie
    $sessionId = getRawCookie('biblioSession');
    
    // Validate and fetch user profile
    $profile = $biblioSso->fetchUserProfile($sessionId);
    
    if ($profile) {
        // Create/update user and log them in
        $user = User::updateOrCreate(
            ['email' => $profile['borrower']['email']],
            ['name' => $profile['borrower']['name']]
        );
        
        Auth::login($user);
        return redirect()->route('dashboard');
    }
    
    return redirect()->route('login')->with('error', 'Authentication failed');
});
```

**Features:**
- ✅ Session validation
- ✅ Borrower info retrieval
- ✅ Complete user profile fetching
- ✅ Automatic retry logic (2 retries, 500ms delay)
- ✅ Comprehensive error handling and logging
- ✅ Registered as singleton for efficiency

### Cookie Utilities

Read raw (unencrypted) cookies from external systems:

```php
// Simple global helper
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

### Blade Components with BiblioCommons Templates

Use BiblioCommons layouts in your views:

```blade
<x-tpl-shared::layout title="Page Title">
    <div>Your content here</div>
</x-tpl-shared::layout>
```

The package automatically fetches and caches BiblioCommons templates (header, footer, CSS, JS).

### Global Helper Functions

```php
// Get hashed asset from package manifest
tplSharedAsset('css'); // Returns: /vendor/tpl-shared/build/app-[hash].css

// Get raw cookie value
getRawCookie('cookieName'); // Bypasses Laravel encryption
```

## 📋 Available Commands

```bash
 Diagnose BiblioCommons configuration and connectivity
php artisan bibliocommons:diagnose

# Clear BiblioCommons template cache
php artisan tpl-shared:clear-cache

# Publish package assets
php artisan vendor:publish --tag=tpl-shared-assets
php artisan vendor:publish --tag=tpl-shared-config
php artisan vendor:publish --tag=tpl-shared-views
```

## 🧪 Testing

The package includes comprehensive test coverage:

```bash
# Run all tests
composer test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --filter=BiblioSso
```

**Test Statistics:**
- BiblioSsoService: 8 tests
- CookieUtils: 10 tests
- Integration: 4 tests
- Total: 22+ tests (all passing)

## 🛠️ Development Workflow

### For Package Developers

```bash
# Install dependencies
composer install
npm install

# Run tests
composer test

# Format code
composer format

# Build assets
npm run build

# Development mode
npm run dev
```

### Version Management

Use the Makefile for proper version management:

```bash
# Bump patch version (0.1.0 → 0.1.1)
make tag-patch

# Bump minor version (0.1.0 → 0.2.0)
make tag-minor

# Bump major version (0.1.0 → 1.0.0)
make tag-major

# Push tags to GitHub
make push
```

See [MAKEFILE_GUIDE.md](MAKEFILE_GUIDE.md) for complete details.

## 🔒 Private Repository Access

This is a private repository. Configure authentication:

```bash
# One-time setup on your machine
composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN
```

Get your token at: https://github.com/settings/tokens (requires `repo` scope)

## 📦 Package Structure

```
tpl-shared/
├── src/                          # Package source code
│   ├── Auth/                     # Authentication components
│   │   ├── BiblioUserProvider.php
│   │   └── BiblioGuard.php
│   ├── Services/                 # Service classes
│   │   ├── BiblioCommonsTemplateService.php
│   │   └── BiblioSsoService.php
│   ├── Utils/                    # Utility classes
│   │   └── CookieUtils.php
│   ├── Console/                  # Artisan commands
│   ├── View/                     # Blade components & composers
│   └── SharedServiceProvider.php # Main service provider
├── app/                          # Helper functions
│   └── helpers.php
├── config/                       # Configuration files
│   └── shared.php
├── resources/                    # Frontend assets & views
│   ├── css/
│   ├── js/
│   └── views/
├── routes/                       # Package routes
│   └── web.php
├── tests/                        # Test suite
│   ├── Feature/
│   └── Unit/
└── database/                     # Migrations & seeders
    └── migrations/
```

## 🤝 Contributing

### Code Style

The package uses Laravel Pint for code formatting:

```bash
# Format all files
vendor/bin/pint

# Check formatting without fixing
vendor/bin/pint --test
```

### Pull Request Guidelines

1. Create a feature branch from `main`
2. Write tests for new features
3. Ensure all tests pass: `composer test`
4. Format code: `vendor/bin/pint`
5. Update CHANGELOG.md
6. Submit pull request

## 📝 Configuration

### Package Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=tpl-shared-config
```

Edit `config/shared.php`:

```php
return [
    'domain' => null, // Domain for package routes
    'publish_assets' => true, // Toggle asset publishing
];
```

### Services Configuration

Add to your `config/services.php`:

```php
'bibliocommons' => [
    'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    'api_base_url' => env('BIBLIOCOMMONS_API_BASE_URL'),
    'api_key' => env('BIBLIOCOMMONS_API_KEY'),
    'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),
],
```

## 🐛 Troubleshooting

### BiblioCommons Not Loading

**⚡ Quick Fix:** See [QUICK_FIX_BIBLIOCOMMONS.md](QUICK_FIX_BIBLIOCOMMONS.md) for a 5-minute solution.

**Run diagnostic:**
```bash
php artisan bibliocommons:diagnose
```

**Detailed guides:**
- [FIX_HOST_APP_SETUP.md](FIX_HOST_APP_SETUP.md) - Step-by-step fix guide
- [TROUBLESHOOTING_HOST_APP.md](TROUBLESHOOTING_HOST_APP.md) - Comprehensive troubleshooting

### Composer Can't Find New Versions

See [TROUBLESHOOTING_VERSIONS.md](TROUBLESHOOTING_VERSIONS.md) for detailed solutions.

**Quick fix:**
```bash
composer clear-cache
composer update tpl/shared
```

### Asset Publishing Issues

See [VENDOR_PUBLISH_FIX.md](VENDOR_PUBLISH_FIX.md) for solutions.

**Quick fix:**
```bash
php artisan vendor:publish --tag=tpl-shared-assets --force
```

### Vite ENOTFOUND Errors

Update your `vite.config.ts`:

```typescript
export default defineConfig({
    server: {
        host: 'localhost',
        hmr: { host: 'localhost' },
    },
});
```

## 📊 Package Stats

- **Version:** 0.1.13
- **PHP Version:** 8.4+
- **Laravel Version:** 12.x
- **Test Coverage:** 22+ tests passing
- **Documentation:** 15+ guides
- **License:** Proprietary

## 🔗 Links

- **Repository:** https://github.com/tpl-eservices/tpl-shared
- **Issues:** https://github.com/tpl-eservices/tpl-shared/issues
- **Documentation:** See [docs](#-documentation) section above

## 📄 License

Proprietary. All rights reserved.

---

## Quick Links by Task

### I want to...

- **Install the package** → [INSTALL.md](INSTALL.md)
- **Use BiblioCommons SSO** → [BIBLIOSSO_USAGE.md](BIBLIOSSO_USAGE.md)
- **Set up Laravel Auth** → [AUTH_PROVIDER_GUIDE.md](AUTH_PROVIDER_GUIDE.md) ⭐
- **Use middleware** → [MIDDLEWARE_GUIDE.md](MIDDLEWARE_GUIDE.md)
- **Read external cookies** → See [Cookie Utilities](#cookie-utilities) above
- **Publish assets** → [PUBLISHING.md](PUBLISHING.md)
- **Create a new version** → [MAKEFILE_GUIDE.md](MAKEFILE_GUIDE.md)
- **Fix version issues** → [TROUBLESHOOTING_VERSIONS.md](TROUBLESHOOTING_VERSIONS.md)
- **Develop the package** → [PACKAGE_DEV_NOTES.md](PACKAGE_DEV_NOTES.md)
- **See what changed** → [CHANGELOG.md](CHANGELOG.md)

---

**Built with ❤️ for Toronto Public Library**
```

### Option 2: SSH Keys

Ensure your SSH key is added to GitHub and your SSH agent is running:

```bash
ssh-add ~/.ssh/id_rsa
```

## Usage

### BiblioCommons Integration

This package includes BiblioCommons header/footer integration for TPL library applications.

**Quick Start:**
1. Configure API URL in `config/services.php`
2. Use `<x-tpl-shared::static-layout>` in your views
3. Done! Templates are fetched and cached automatically.

See [BIBLIOCOMMONS.md](BIBLIOCOMMONS.md) for complete documentation or [BIBLIOCOMMONS_QUICK_REF.md](BIBLIOCOMMONS_QUICK_REF.md) for quick reference.

### Views

Use package views in your Blade templates:

```blade
@include('tpl-shared::example')
```

### Components

After publishing assets, import components in your React/Inertia pages:

```tsx
import { SomeComponent } from '@/vendor/tpl-shared/js/components/SomeComponent'
```

### Routes

The package automatically registers routes from `routes/web.php`. Check registered routes:

```bash
php artisan route:list
```

