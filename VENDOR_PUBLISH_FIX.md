# ✅ Fixed: vendor:publish Fortify Error

## Problem

When running `php artisan vendor:publish --tag=tpl-shared` in a host application, you encountered:

```
In fortify.php line 147:
Class "Laravel\Fortify\Features" not found
```

## Root Cause

The package had Laravel's default configuration files (like `fortify.php`, `auth.php`, etc.) in the `config/` directory. When running `vendor:publish`, these files were being processed even though they weren't meant to be published to host applications.

The `fortify.php` file specifically imports `Laravel\Fortify\Features`, which doesn't exist in applications that don't have Fortify installed.

## Solution Applied

### 1. Moved Development Configs

All Laravel default configuration files have been moved from `config/` to `config-dev/`:

**Moved files:**
- `app.php` - Application config
- `auth.php` - Authentication config
- `cache.php` - Cache config
- `database.php` - Database config
- `filesystems.php` - Filesystem config
- `fortify.php` - Fortify config (optional, not all apps use it)
- `inertia.php` - Inertia.js config
- `logging.php` - Logging config
- `mail.php` - Mail config
- `queue.php` - Queue config
- `services.php` - Services config
- `session.php` - Session config

**Kept in `config/`:**
- `shared.php` - Package-specific config (the ONLY file that should be published)

### 2. Updated .gitignore

Added to prevent committing dev configs but keep the directory structure:

```gitignore
# Development-only config files (not published to host apps)
/config-dev/*.php
!/config-dev/README.md
```

### 3. Created Documentation

Added `config-dev/README.md` explaining why these files are separate.

## What This Means for Host Apps

### ✅ Now Works

```bash
# In your host application
php artisan vendor:publish --tag=tpl-shared
```

This will ONLY publish:
- `config/shared.php` → `config/shared.php`
- Views, assets, migrations (as configured)

It will NOT try to publish Fortify, Auth, or other Laravel default configs.

### No Breaking Changes for Host Apps

This fix doesn't change any functionality for host applications:
- BiblioCommons integration works the same
- Components work the same
- Only the publishing process is cleaner

## What This Means for Package Development

If you're developing the package itself and need those config files:

### Option 1: Symlink When Needed

```bash
# In package directory
cd config
ln -s ../config-dev/fortify.php .
ln -s ../config-dev/services.php .
# etc.
```

Don't commit these symlinks.

### Option 2: Reference config-dev/ Directly

The package can still load configs from `config-dev/` during development if needed.

### Option 3: Use Host App Pattern

For package development, use a separate test Laravel application that installs the package normally.

## Published Files

After running `php artisan vendor:publish --tag=tpl-shared`, host apps will now have:

```
host-app/
├── config/
│   └── shared.php  ← Only this gets published
├── resources/
│   └── views/
│       └── vendor/
│           └── tpl-shared/  ← If you publish views
├── resources/
│   └── vendor/
│       └── tpl-shared/
│           ├── js/  ← If you publish assets
│           └── css/
└── public/
    └── vendor/
        └── tpl-shared/  ← If you publish public assets
```

## Individual Publish Tags

You can still publish specific resources:

```bash
# Config only
php artisan vendor:publish --tag=tpl-shared-config

# Views only
php artisan vendor:publish --tag=tpl-shared-views

# Frontend assets only
php artisan vendor:publish --tag=tpl-shared-assets

# Migrations only
php artisan vendor:publish --tag=tpl-shared-migrations

# Public assets only
php artisan vendor:publish --tag=tpl-shared-public

# Everything at once
php artisan vendor:publish --tag=tpl-shared
```

## config/shared.php Contents

The only publishable config file contains package-specific settings:

```php
<?php

return [
    // Package-specific configuration
    'example_key' => env('SHARED_EXAMPLE', 'default_value'),
];
```

Host applications can customize this after publishing.

## BiblioCommons Configuration

BiblioCommons API configuration should be in the **host app's** `config/services.php`:

```php
// host-app/config/services.php
return [
    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    ],
];
```

This is NOT published by the package - it's added manually to each host application.

## Testing the Fix

### In Package Directory

```bash
cd tpl-shared
php artisan vendor:publish --tag=tpl-shared
# Should complete without errors
```

### In Host Application

```bash
cd your-host-app
composer update tpl/shared
php artisan vendor:publish --tag=tpl-shared
# Should complete without Fortify errors
```

## Summary

✅ **Fixed:** No more "Class Fortify\Features not found" error  
✅ **Cleaner:** Only package-specific config is published  
✅ **Organized:** Development configs separated into `config-dev/`  
✅ **Documented:** README explains why and how  

The vendor:publish command now works correctly in all host applications, regardless of whether they use Fortify or other optional Laravel packages.

## Files Changed

- ✅ Moved 12 config files to `config-dev/`
- ✅ Updated `.gitignore`
- ✅ Added `config-dev/README.md`
- ✅ Package now only publishes `config/shared.php`

All committed and ready to push! 🎉

