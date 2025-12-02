# TPL Shared Package

A shared Laravel package for TPL projects, providing common components, views, and assets across multiple Laravel applications.

## Requirements

- PHP 8.4+
- Laravel 12.x
- Node.js 20+

## Installation

### 1. Add the package repository to your host Laravel application

For **private repositories**, add this to your host app's `composer.json`:

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

For **local development** using a symlink:

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

### 2. Install the package

```bash
composer require tpl/shared
```

For local development with a specific version:

```bash
composer require tpl/shared:@dev
```

### 3. Publish package assets (optional)

Publish the configuration file:

```bash
php artisan vendor:publish --tag=tpl-shared-config
```

Publish frontend assets (JS/CSS):

```bash
php artisan vendor:publish --tag=tpl-shared-assets
```

Publish view files:

```bash
php artisan vendor:publish --tag=tpl-shared-views
```

Publish all at once:

```bash
php artisan vendor:publish --tag=tpl-shared
```

## Configuration for Private Repositories

If this repository is private, your host application needs proper GitHub authentication:

### Option 1: Personal Access Token (Recommended for CI/CD)

Create a GitHub Personal Access Token with `repo` scope and add it to your environment:

```bash
composer config --global --auth github-oauth.github.com YOUR_GITHUB_TOKEN
```

Or add to your project's `auth.json`:

```json
{
    "github-oauth": {
        "github.com": "YOUR_GITHUB_TOKEN"
    }
}
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

### Configuration

Access package configuration:

```php
config('shared.some_key')
```

## Vite Configuration for Host Apps

To avoid `ENOTFOUND` errors and properly integrate with the host app's Vite setup, configure your host app's `vite.config.ts`:

```typescript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: 'localhost', // Use localhost instead of custom domains
        hmr: {
            host: 'localhost',
        },
    },
});
```

Alternatively, update your `/etc/hosts` file to include custom domains if needed:

```
127.0.0.1 tpl-shared.tpl.ca
```

## Development

### Package Development

Since this is a package (not a standalone app), development workflow differs:

**For PHP/Laravel changes:**
```bash
# Run tests
composer test

# Format code
composer format
```

**For frontend changes:**
```bash
# Build assets (no dev server for packages)
pnpm install
pnpm build
```

**To test changes in a host app:**
1. Symlink the package (see Installation section)
2. Install with `composer require tpl/shared:@dev`
3. Publish assets with `php artisan vendor:publish --tag=tpl-shared-assets`
4. Run Vite **from the host app** (not the package)

> **Note**: Don't run `pnpm dev` in the package directory. Vite requires a full Laravel application context. Instead, run Vite from your host application after symlinking the package.

See [PACKAGE_DEV_NOTES.md](PACKAGE_DEV_NOTES.md) for detailed development workflow.

## CI/CD

This package includes GitHub Actions for automated testing and code quality checks. See `.github/workflows/ci.yml`.

## Versioning

This package follows [Semantic Versioning](https://semver.org/). 

Current version: `v0.1.0`

## License

Proprietary - Internal TPL use only.

