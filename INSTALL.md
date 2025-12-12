# TPL Shared Package - Installation Instructions

## 🔒 Private Repository Information

**Repository:** https://github.com/tpl-eservices/tpl-shared.git  
**Type:** Private  
**Current Version:** v0.1.0

---

## 🚀 For Developers Installing the Package

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
   
   Replace `YOUR_TOKEN_HERE` with the token you just created.

### Step 2: Add Repository to Your Laravel App

```shell
composer repo add shared vcs https://github.com/tpl-eservices/tpl-shared.git
```

### Step 3: Install the Package

```bash
composer require tpl/shared:^0.1.0
```

### Step 4: Fix Vite Configuration

To avoid `ENOTFOUND` errors, update your `vite.config.ts`:

```typescript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        // ...other plugins
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

Update your `.env`:

```env
APP_URL=http://localhost
VITE_DEV_SERVER_URL=http://localhost:5173
```

### Step 5: Publish Assets (Optional)

```bash
# Publish everything
php artisan vendor:publish --tag=tpl-shared

# Or selectively:
php artisan vendor:publish --tag=tpl-shared-config
php artisan vendor:publish --tag=tpl-shared-assets
php artisan vendor:publish --tag=tpl-shared-views
```

### Step 6: Start Development

```bash
pnpm dev  # or npm run dev
```

---

## 🔧 For Package Developers

### Local Development with Symlink

If you're actively developing the package alongside a host application:

**In your host app's `composer.json`:**
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

**Install with dev version:**
```bash
composer require tpl/shared:@dev
```

Changes to PHP files will be reflected immediately. For frontend changes:
1. Build in package: `cd ../tpl-shared && pnpm build`
2. Restart Vite in host app: `pnpm dev`

### Making Changes to the Package

```bash
cd tpl-shared

# PHP development
composer format    # Format code with Pint
composer test      # Run Pest tests

# Frontend development  
pnpm install      # Install dependencies
pnpm lint         # Lint with ESLint
pnpm build        # Build assets

# Commit changes
git add -A
git commit -m "Your descriptive message"
git push origin main
```

### Creating a New Release

```bash
# 1. Update version in composer.json and package.json
# 2. Update CHANGELOG.md with changes

# 3. Commit, tag, and push
git add -A
git commit -m "Release v0.2.0 - Description of changes"
git tag -a v0.2.0 -m "Release v0.2.0"
git push origin main
git push origin v0.2.0
```

---

## 📦 What's Included in This Package

The package provides shared functionality across TPL applications:

- ✅ **Shared Views & Components** - Reusable Blade templates
- ✅ **React/Inertia Components** - Frontend components with TypeScript
- ✅ **Database Migrations** - Shared database schema
- ✅ **Routes** - Common routes (prefixed with `/tpl-shared`)
- ✅ **Configuration** - Shared config values
- ✅ **Fortify Actions** - Authentication features
- ✅ **CSS/Tailwind** - Shared styles and utilities

### Using Package Features

**Blade Views:**
```blade
@include('tpl-shared::example')
```

**Routes:**
```php
route('tpl-shared.ping')  // Test route
```

**Configuration:**
```php
config('shared.some_key')
```

**React Components (after publishing):**
```tsx
import { Component } from '@/vendor/tpl-shared/js/components/Component';
```

---

## ⚠️ Common Issues & Solutions

### "Git Repository is empty"

**Error:**
```
The "https://api.github.com/repos/tpl-eservices/tpl-shared/git/refs/heads?per_page=100" file could not be downloaded (HTTP/2 409):
{"message":"Git Repository is empty.","documentation_url":"...","status":"409"}
```

**Cause:** The GitHub repository exists but has no commits pushed yet.

**Solution:** The package maintainer needs to push commits to GitHub first:
```bash
cd /path/to/tpl-shared
git push origin main
git push origin v0.1.0
```

After that, you can install the package.

### "Authentication required (github.com)"
You need to set up your GitHub token (see Step 1 above).

### "Could not find package tpl/shared"
Make sure the repository is added to `composer.json` (see Step 2).

### Vite Error: "ENOTFOUND tpl-shared.tpl.ca"
Use `localhost` in your vite.config.ts and .env (see Step 4).

### "Class not found" errors
Run `composer dump-autoload` in your host application.

### Assets not loading
Publish the assets: `php artisan vendor:publish --tag=tpl-shared-assets`

---

## 🆘 Getting Help

- **Full Documentation:** See `README.md` in the package
- **Quick Reference:** See `QUICK_START.md`
- **Vite Issues:** See `HOST_APP_INTEGRATION.md`
- **Technical Details:** See `PACKAGE_DEV_NOTES.md`

---

## 📋 Team Checklist

Before you can use this package, make sure you have:

- [ ] GitHub account with access to `tpl-eservices/tpl-shared`
- [ ] Personal Access Token created with `repo` scope
- [ ] Token configured globally: `composer config --global github-oauth.github.com TOKEN`
- [ ] Repository added to your project's `composer.json`
- [ ] Package installed: `composer require tpl/shared:^0.1.0`
- [ ] Vite config updated to use `localhost`
- [ ] `.env` updated with `APP_URL=http://localhost`

---

## 🎉 Ready to Go!

Once you've completed the steps above, you're ready to use the TPL Shared package in your Laravel application.

**Current Version:** v0.1.0  
**Repository:** https://github.com/tpl-eservices/tpl-shared.git  
**Support:** Contact the TPL development team

