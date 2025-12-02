# Development Configuration Files

This directory contains Laravel configuration files used **only for package development**.

These files are **NOT published** to host applications and should **NOT** be moved back to the `config/` directory.

## Why These Are Separate

When developing this package as a standalone Laravel application (for testing, etc.), Laravel needs these config files. However, when the package is installed in a host application:

1. The host app has its own config files
2. Publishing package config would overwrite host app settings
3. Some configs (like Fortify) may not be used by the host app

## Files in This Directory

- `app.php` - Application configuration (development only)
- `auth.php` - Authentication configuration
- `cache.php` - Cache configuration
- `database.php` - Database configuration
- `filesystems.php` - Filesystem configuration
- `fortify.php` - Laravel Fortify (optional, not all apps use this)
- `inertia.php` - Inertia.js configuration
- `logging.php` - Logging configuration
- `mail.php` - Mail configuration
- `queue.php` - Queue configuration
- `services.php` - Third-party services configuration
- `session.php` - Session configuration

## What IS Published

Only `config/shared.php` is published to host applications via:

```bash
php artisan vendor:publish --tag=tpl-shared-config
```

## For Package Development

If you need these configs for local package development:

1. Keep them in `config-dev/`
2. Symlink them when needed:
   ```bash
   ln -s ../config-dev/fortify.php config/fortify.php
   ```
3. Don't commit the symlinks to git

Or create a bootstrap/app.php that loads from config-dev/ during development.

## Important

Never move these files back to `config/` as they will be automatically published to host applications and cause conflicts/errors.

