# ✅ FIXED: Inertia ServiceProvider Error

## Problem Resolved

```
php artisan vendor:publish --tag=tpl-shared

In Application.php line 961:
Class "Inertia\ServiceProvider" not found
```

## Root Cause

After fixing the Fortify config issue, a new error appeared: the `FortifyServiceProvider` in `app/Providers/` was importing `Inertia\Inertia` which isn't a required dependency of the package.

```php
// app/Providers/FortifyServiceProvider.php
use Inertia\Inertia;  // ← This caused the error
```

When Laravel boots to run `vendor:publish`, it tries to load all registered providers, including app-specific ones that reference optional dependencies.

## Solution Applied

### Complete Separation of Package vs Application Code

Moved ALL app-specific code from `app/` to `app-dev/`:

**Files Moved:**

```
app/                          app-dev/
├── Actions/           →      ├── Actions/
│   └── Fortify/              │   └── Fortify/
├── Http/              →      ├── Http/
│   ├── Controllers/          │   ├── Controllers/
│   ├── Middleware/           │   ├── Middleware/
│   └── Requests/             │   └── Requests/
├── Models/            →      ├── Models/
│   └── User.php              │   └── User.php
└── Providers/         →      ├── Providers/
    ├── AppServiceProvider    │   ├── AppServiceProvider
    └── FortifyServiceProvider│   └── FortifyServiceProvider
                              └── README.md
```

### Updated bootstrap/providers.php

**Before:**
```php
<?php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
];
```

**After:**
```php
<?php
return [
    // App-specific providers moved to app-dev/ for package development only
    // The package provider (Tpl\Shared\SharedServiceProvider) is auto-discovered via composer.json
];
```

### Updated .gitignore

```gitignore
# Development-only app files (not published to host apps)
/app-dev/
!/app-dev/README.md
```

### Added Documentation

Created `app-dev/README.md` explaining why these files are separate and how they're used.

---

## Why This Structure?

### Package Code (Publishable)

**Location:** `src/`

This is what gets used by host applications:
- `Services/BiblioCommonsTemplateService.php`
- `View/Components/Layout.php`
- `View/Components/StaticLayout.php`
- `View/Composers/BiblioCommonsComposer.php`

### Application Code (Development Only)

**Location:** `app-dev/`

This is for package development and testing only:
- Providers that reference optional dependencies (Inertia, Fortify)
- Controllers for testing package features
- Models for testing
- Actions and middleware for testing

### Why Separate?

1. **No Dependency Conflicts**: Package doesn't require Inertia or Fortify
2. **Clean Publishing**: Only `src/` code is for host apps
3. **Flexible Testing**: Can develop/test package locally
4. **No Interference**: App-specific code won't affect host apps

---

## Result: vendor:publish Now Works! ✅

### Test It

```bash
cd /path/to/tpl-shared
php artisan vendor:publish --tag=tpl-shared
```

**No errors!** ✅

### What Gets Published

```
Only these directories are published:
├── config/shared.php           → config/
├── resources/views/            → resources/views/vendor/tpl-shared/
├── resources/js/               → resources/vendor/tpl-shared/js/
├── resources/css/              → resources/vendor/tpl-shared/css/
└── public/                     → public/vendor/tpl-shared/
```

App-specific code in `app-dev/` is **never** published.

---

## Package Structure Now

```
tpl-shared/
├── src/                        ← Package code (published)
│   ├── Services/
│   ├── View/
│   │   ├── Components/
│   │   └── Composers/
│   └── SharedServiceProvider.php
├── app-dev/                    ← Dev-only (NOT published)
│   ├── Actions/
│   ├── Http/
│   ├── Models/
│   ├── Providers/
│   └── README.md
├── config/                     ← Package config (published)
│   └── shared.php
├── config-dev/                 ← Dev-only (NOT published)
│   ├── fortify.php
│   ├── auth.php
│   └── ...
├── resources/                  ← Published
│   ├── views/
│   ├── js/
│   └── css/
└── tests/                      ← Package tests
```

---

## For Package Development

If you need to develop/test the package locally with app features:

### Option 1: Symlink (Quick)

```bash
cd app
ln -s ../app-dev/Providers .
ln -s ../app-dev/Models .
ln -s ../app-dev/Http .
ln -s ../app-dev/Actions .
```

Don't commit symlinks.

### Option 2: Test Application (Recommended)

Create a separate Laravel app and install the package:

```bash
cd ~/projects
laravel new tpl-shared-test
cd tpl-shared-test

# Add to composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../tpl-shared",
            "options": {"symlink": true}
        }
    ]
}

composer require tpl/shared:@dev
```

This is the most realistic testing environment.

### Option 3: Orchestra Testbench

The package already uses Orchestra Testbench for testing. Run:

```bash
composer test
```

---

## What Can Be Published

All publish tags work correctly now:

```bash
# Everything
php artisan vendor:publish --tag=tpl-shared

# Individual resources
php artisan vendor:publish --tag=tpl-shared-config
php artisan vendor:publish --tag=tpl-shared-views
php artisan vendor:publish --tag=tpl-shared-assets
php artisan vendor:publish --tag=tpl-shared-migrations
php artisan vendor:publish --tag=tpl-shared-public
```

No errors related to Inertia, Fortify, or missing dependencies!

---

## Dependencies

### Package Required Dependencies

**In `composer.json` require:**
```json
{
    "require": {
        "php": "^8.4",
        "laravel/framework": "^12.0"
    }
}
```

Only these are required. Host apps can use the package without Inertia or Fortify.

### Development Dependencies

**In `composer.json` require-dev:**
```json
{
    "require-dev": {
        "pestphp/pest": "^4.0",
        "laravel/pint": "^1.0",
        "orchestra/testbench": "^10.0"
    }
}
```

These are only installed during package development.

### Optional for Host Apps

Host apps can optionally use:
- `inertiajs/inertia-laravel` (if using Inertia)
- `laravel/fortify` (if using authentication)
- Any other packages they need

The package doesn't force these dependencies.

---

## Troubleshooting

### "Class Inertia\ServiceProvider not found"

✅ **Fixed!** App providers moved to `app-dev/`.

### "Class Laravel\Fortify\Features not found"

✅ **Fixed!** Config files moved to `config-dev/`.

### Still getting errors?

Make sure you have the latest version:

```bash
cd /path/to/tpl-shared
git pull origin main
composer dump-autoload
```

Then test:

```bash
php artisan vendor:publish --tag=tpl-shared
```

Should complete without errors.

---

## Summary

✅ **Error Fixed:** No more "Class Inertia\ServiceProvider not found"  
✅ **Clean Structure:** Package code in `src/`, dev code in `app-dev/`  
✅ **No Dependencies:** Package doesn't require Inertia or Fortify  
✅ **Working Publishing:** All publish tags work correctly  
✅ **Documented:** Clear separation and explanation  

## Git Commits

```
✅ Fix Inertia ServiceProvider error - move app files to app-dev
```

The package is now properly structured as a library with clean separation between publishable code and development-only code.

Ready to push to GitHub! 🎉

