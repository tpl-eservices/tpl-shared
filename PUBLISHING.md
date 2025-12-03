# Asset Publishing for tpl-shared

## How to Publish tpl-shared Assets in Your Host Project

To use the CSS and JS assets from `tpl-shared` in your host Laravel project, follow these steps:

### 1. Install tpl-shared as a Composer Dependency

Add `tpl-shared` to your host project's `composer.json`:

```bash
composer require tpl/shared
```

### 2. Register the Service Provider (if not auto-discovered)

If your Laravel version does not auto-discover service providers, add this to `config/app.php`:

```php
'providers' => [
    // ...existing providers...
    Tpl\Shared\SharedServiceProvider::class,
],
```

### 3. Publish the Assets

Run the following Artisan command in your host project:

```bash
php artisan vendor:publish --tag=tpl-shared-assets
```

This will copy the built assets from `tpl-shared/public/build` to your host project's `public/vendor/tpl-shared/build` directory.

### 4. Reference the Assets in Your Blade Layout

Add the following to your Blade layout to include the CSS:

```blade
<link rel="stylesheet" href="{{ asset('vendor/tpl-shared/build/assets/app.css') }}">
```

### 5. Re-publish After Updates

Whenever you update `tpl-shared` or rebuild its assets, re-run the publish command:

```bash
php artisan vendor:publish --tag=tpl-shared-assets --force
```

This ensures your host project always has the latest assets.

---

## Notes
- Do **not** symlink or copy assets manually; always use the publish command.
- If you use Vite, configure your host project to reference the published assets as needed.
- For more advanced usage, see the `SharedServiceProvider` for additional publishable resources (views, JS, CSS).

