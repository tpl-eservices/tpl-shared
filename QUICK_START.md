# Quick Start Guide - TPL Shared Package

## 🚀 For Team Members Installing the Package

### 1. If Repository is Private - One-Time Setup

Create a GitHub Personal Access Token:
1. Go to https://github.com/settings/tokens
2. Generate new token (classic) with `repo` scope
3. Run this command **once** on your machine:

```bash
composer config --global github-oauth.github.com YOUR_TOKEN_HERE
```

### 2. Install in Your Laravel App

Add to your `composer.json`:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-org/tpl-shared.git"
        }
    ]
}
```

Install:
```bash
composer require tpl/shared:^0.1.0
```

### 3. Fix Vite ENOTFOUND Errors

Update your `vite.config.ts`:
```typescript
export default defineConfig({
    // ...existing plugins...
    server: {
        host: 'localhost',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
});
```

Update your `.env`:
```env
APP_URL=http://localhost
VITE_DEV_SERVER_URL=http://localhost:5173
```

### 4. Publish Assets (Optional)

```bash
# Publish everything
php artisan vendor:publish --tag=tpl-shared

# Or just what you need
php artisan vendor:publish --tag=tpl-shared-config
php artisan vendor:publish --tag=tpl-shared-assets
```

### 5. Start Development

```bash
pnpm dev  # or npm run dev
```

---

## 🔧 For Package Developers

### Local Development with Symlink

In your **host app's** `composer.json`:
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

Install:
```bash
composer require tpl/shared:@dev
```

### Making Changes to Package

```bash
cd tpl-shared

# PHP changes
composer format    # Format code
composer test      # Run tests

# Frontend changes
pnpm install
pnpm lint
pnpm build         # Build for distribution

# Commit changes
git add -A
git commit -m "Your changes"
```

### Creating a New Release

```bash
# Update version in composer.json and package.json
# Update CHANGELOG.md

git add -A
git commit -m "Release v0.2.0 - Description"
git tag -a v0.2.0 -m "Release v0.2.0"
git push origin main
git push origin v0.2.0
```

### Testing in Host App

With symlink active, changes take effect immediately:
- **PHP changes**: Instant (Laravel auto-reloads)
- **Frontend changes**: Run `pnpm build` in package, then `pnpm dev` in host app

---

## 📚 Available Documentation

- **README.md** - Full installation and usage guide
- **HOST_APP_INTEGRATION.md** - Detailed Vite/Wayfinder setup
- **PACKAGE_DEV_NOTES.md** - Technical development notes
- **CHANGELOG.md** - Version history

---

## ⚠️ Common Issues

### Error: "Route not defined"
The package doesn't require authentication routes by default. Make sure your host app has Fortify configured if using auth features.

### Error: "Class not found"
Run `composer dump-autoload` in your host app.

### Vite: "ENOTFOUND"
Use `localhost` in vite.config.ts and .env (see step 3 above).

### Assets not loading
Publish assets: `php artisan vendor:publish --tag=tpl-shared-assets`

---

## 🎯 What's Included

The package provides:
- ✅ Shared views and Blade components
- ✅ React/Inertia components
- ✅ Database migrations
- ✅ Routes (prefixed with `/tpl-shared`)
- ✅ Configuration file
- ✅ CSS/Tailwind utilities

### Using Package Views

```blade
@include('tpl-shared::example')
```

### Using Package Routes

```php
route('tpl-shared.ping')
```

### Using Package Config

```php
config('shared.some_key')
```

---

Need help? Check the full documentation or ask the team!

