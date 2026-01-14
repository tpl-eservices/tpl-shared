# Fix: BiblioCommons Not Loading in tpl-apps

## The Problem

The host app (`tpl-apps`) has registered a view composer for BiblioCommons in its `AppServiceProvider.php`, but:

1. ❌ No `config/services.php` with BiblioCommons API URL
2. ❌ Composer is registered for the wrong view names
3. ❌ The package views have their own composer already registered

## The Solution

You have **two options** - choose one:

---

## ✅ Option 1: Use Package Views Directly (Recommended)

This is the simplest approach and requires minimal changes.

### Step 1: Remove the Composer from AppServiceProvider

In `tpl-apps/app/Providers/AppServiceProvider.php`, **remove** this line:

```php
// DELETE THIS LINE:
View::composer(['app', 'components/layout', 'components/static-layout'], BiblioCommonsComposer::class);
```

Your `AppServiceProvider` should now look like this:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        URL::forceScheme('https');
    }
}
```

### Step 2: Create config/services.php

Create `tpl-apps/config/services.php`:

```php
<?php

return [
    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    ],
];
```

### Step 3: Update .env

Add to `tpl-apps/.env`:

```env
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
```

### Step 4: Use Package Views

In your Blade files, use the package layouts:

```blade
{{-- Instead of your local views --}}
<x-tpl-shared::static-layout>
    <div>Your content</div>
</x-tpl-shared::static-layout>
```

Or for dynamic layouts:

```blade
<x-tpl-shared::layout>
    <div>Your content</div>
</x-tpl-shared::layout>
```

### Step 5: Clear Caches

```bash
cd tpl-apps
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### Step 6: Test

Visit your app - the BiblioCommons header and footer should now appear!

---

## ⚙️ Option 2: Use Custom Views (More Work)

If you have custom layouts in your host app that you want to keep:

### Step 1: Publish Package Views

```bash
cd tpl-apps
php artisan vendor:publish --tag=tpl-shared-views
```

This creates views in `tpl-apps/resources/views/vendor/tpl-shared/`.

### Step 2: Create config/services.php

Create `tpl-apps/config/services.php`:

```php
<?php

return [
    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    ],
];
```

### Step 3: Update .env

Add to `tpl-apps/.env`:

```env
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
```

### Step 4: Fix AppServiceProvider

Update `tpl-apps/app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Tpl\Shared\View\Composers\BiblioCommonsComposer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        URL::forceScheme('https');

        // Use the correct view names with namespace
        View::composer([
            'tpl-shared::components.layout',
            'tpl-shared::components.static-layout',
        ], BiblioCommonsComposer::class);
    }
}
```

**Note:** Use `tpl-shared::components.layout` (dots), not `components/layout` (slashes).

### Step 5: Customize Your Published Views

Edit the published views in `tpl-apps/resources/views/vendor/tpl-shared/components/` as needed. They already include the `$bibliocommons` variable usage.

### Step 6: Clear Caches

```bash
cd tpl-apps
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### Step 7: Test

The BiblioCommons header and footer should now appear!

---

## Verification

### Quick Diagnostic Command

Run this command to check your BiblioCommons setup:

```bash
cd tpl-apps
php artisan bibliocommons:diagnose
```

This will check:
- ✓ Configuration (API URL)
- ✓ Cache status
- ✓ Template fetching
- ✓ View composer registration
- 💡 Recommendations for fixes

### Manual Test Configuration

```bash
cd tpl-apps
php artisan tinker
```

```php
// Should show your API URL
config('services.bibliocommons.external_templates_url')

// Should return array with header, footer, etc.
app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class)->getTemplateParts()
```

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep BiblioCommons
```

If you see warnings about "API URL not configured", go back to Steps 2-3.

### View Page Source

Visit your app and view source. You should see:
- BiblioCommons CSS `<link>` tags
- BiblioCommons header HTML
- BiblioCommons footer HTML
- BiblioCommons JavaScript

---

## Quick Summary

The core issue is that your `AppServiceProvider` had:

```php
// ❌ WRONG - Views don't exist or aren't namespaced correctly
View::composer(['app', 'components/layout', 'components/static-layout'], BiblioCommonsComposer::class);
```

**Fix Option 1:** Remove the composer registration entirely and use `<x-tpl-shared::layout>` (package handles the composer).

**Fix Option 2:** Fix the view names to use the namespace:

```php
// ✅ CORRECT
View::composer([
    'tpl-shared::components.layout',
    'tpl-shared::components.static-layout',
], BiblioCommonsComposer::class);
```

**Both options require:** `config/services.php` with BiblioCommons API URL.

---

## Need More Help?

See `TROUBLESHOOTING_HOST_APP.md` for comprehensive troubleshooting steps.

