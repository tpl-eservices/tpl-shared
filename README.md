# TPL Shared Package

Shared Laravel package for TPL projects providing BiblioCommons SSO authentication, shared layouts, and common components across multiple Laravel applications.

**This is a public repository:** Do not include sensitive information, credentials, or proprietary code here.

## What's Included

- **BiblioCommons SSO** - Authentication via BiblioCommons API (session validation, user profiles, automatic retries)
- **BiblioCommons Templates** - Fetches and caches TPL header/footer/CSS/JS from BiblioCommons
- **Blade Components** - Shared layouts (`<x-tpl-shared::layout>`, `<x-tpl-shared::static-layout>`)
- **Cookie Utilities** - Read raw (unencrypted) cookies from external systems like BiblioCommons
- **DX Services Integration** - Library card renewal and membership services
- **Database Migrations** - Common database structures
- **Artisan Commands** - Install wizard, cache clearing, diagnostics

## Requirements

- PHP 8.4+
- Laravel 12.x or 13.x

## Installation

### 1. Install the package

```bash
composer require tpl/shared:^0.1
```

### 2. Run the install command

```bash
php artisan tpl-shared:install
```

This configures `config/services.php`, `config/auth.php`, middleware, and `.env` variables automatically (with backups of all modified files).

### 3. Set your environment variables

The install command adds placeholders to `.env`. Update them with real values:

```env
BIBLIOCOMMONS_API_KEY=your-actual-api-key
BIBLIOCOMMONS_TITLES_API_KEY=your-titles-api-key
```

See [INSTALL.md](INSTALL.md) for detailed installation instructions.

## Usage

### Layouts

```blade
<x-tpl-shared::layout title="Page Title">
    <div>Your content here</div>
</x-tpl-shared::layout>
```

### SSO Authentication

```php
use Tpl\Shared\Services\BiblioSsoService;

$profile = $biblioSso->fetchUserProfile($sessionId);
```

### Cookie Utilities

```php
// Read raw cookies that bypass Laravel encryption
$sessionId = getRawCookie('biblioSession');
```

### Mock Authentication (Local Dev)

Enable mock mode for development without API access:

```env
BIBLIOCOMMONS_MOCK_ENABLED=true
```

Mock mode is automatically blocked in production.

## Artisan Commands

```bash
php artisan tpl-shared:install          # Guided setup wizard
php artisan bibliocommons:diagnose      # Check configuration and connectivity
php artisan tpl-shared:clear-cache      # Clear template cache
php artisan vendor:publish --tag=tpl-shared-assets   # Publish frontend assets
php artisan vendor:publish --tag=tpl-shared-config   # Publish config
```

## Release Management

Releases are handled via the `Makefile` (Unix/Mac; see `docs/development/VERSION_MANAGEMENT.md` for Windows equivalents).

```bash
make release      # Full release: format, build, tag-patch, push
```

Individual commands:

| Command               | Purpose                                              |
| ----------------------| ---------------------------------------------------- |
| `make build`          | Build frontend assets with Vite                      |
| `make clean`          | Remove `vendor`, `node_modules`, and cached files     |
| `make format`         | Format code with Laravel Pint                        |
| `make help`           | List all available commands                          |
| `make install`        | Install Composer and pnpm dependencies               |
| `make push`           | Push commits and tags to GitHub                      |
| `make release`        | All-inclusive command to make a new release          |
| `make status`         | Show current version, git status, recent commits     |
| `make tag-major`      | Bump major version (0.1.0 -> 1.0.0)                  |
| `make tag-minor`      | Bump minor version (0.1.0 -> 0.2.0)                  |
| `make tag-patch`      | Bump patch version (0.1.0 -> 0.1.1)                  |
| `make test`           | Run the test suite                                   |
| `make update-version` | Sync `composer.json`/`package.json` from latest tag  |
| ----------------------| ---------------------------------------------------- |

See [Version Management](docs/development/VERSION_MANAGEMENT.md) for full release workflows and troubleshooting.

## Documentation

- [Installation Guide](docs/installation/README.md)
- [BiblioCommons Integration](docs/features/bibliocommons.md)
- [Development Guide](docs/development/README.md)
- [Version Management](docs/development/VERSION_MANAGEMENT.md)
- [Troubleshooting](docs/troubleshooting/README.md)
- [Changelog](CHANGELOG.md)

## Development

This project uses **pnpm** for frontend assets (npm/yarn are blocked).

```bash
composer install && pnpm install    # Install dependencies
composer test                       # Run tests
composer analyse                    # PHPStan level 8
composer format                     # Laravel Pint
pnpm dev                            # Vite dev server
pnpm build                          # Build frontend assets
```

### Version Management

```bash
make tag-patch    # 0.1.0 -> 0.1.1
make tag-minor    # 0.1.0 -> 0.2.0
make tag-major    # 0.1.0 -> 1.0.0
make push         # Push tags to GitHub
make release      # All-in-one command
```
