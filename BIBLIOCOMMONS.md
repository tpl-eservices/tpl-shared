# BiblioCommons Integration Guide

This package provides BiblioCommons header/footer integration for TPL library applications.

## Overview

The package includes:
- **BiblioCommonsTemplateService** - Fetches and caches BiblioCommons template parts
- **BiblioCommonsComposer** - View composer that injects template data into layouts
- **Layout Components** - Blade components with BiblioCommons integration

## Package Setup (Already Configured)

The package automatically:
1. ✅ Registers `BiblioCommonsTemplateService` as a singleton
2. ✅ Binds `BiblioCommonsComposer` to layout views
3. ✅ Provides views with BiblioCommons components

No additional setup needed in the package itself.

---

## Host Application Setup

### Step 1: Configure BiblioCommons API

Add the BiblioCommons API URL to your host app's `config/services.php`:

```php
<?php

return [
    // ...existing services

    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    ],
];
```

Add to your `.env`:

```env
BIBLIOCOMMONS_API_URL=https://your-library.bibliocommons.com/api/external-templates
```

### Step 2: Use Package Layout Components

The package provides two layout components with BiblioCommons integration:

#### Option A: Use Package Views Directly (Recommended)

```blade
{{-- In your host app's Blade files --}}
<x-tpl-shared-layout>
    <div>Your content here</div>
</x-tpl-shared-layout>
```

Or for static layouts:

```blade
<x-tpl-shared-static-layout>
    <div>Your content here</div>
</x-tpl-shared-static-layout>
```

#### Option B: Publish and Customize Views

If you need to customize the layouts:

```bash
php artisan vendor:publish --tag=tpl-shared-views
```

This publishes views to `resources/views/vendor/tpl-shared/`.

Then use them:

```blade
{{-- Using published views --}}
<x-tpl-shared-layout>
    <div>Your content here</div>
</x-tpl-shared-layout>
```

### Step 3: Register View Composer in Your AppServiceProvider (Optional)

If you publish the views and want to use them without the `tpl-shared::` namespace, you can register the composer in your host app's `AppServiceProvider`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Tpl\Shared\View\Composers\BiblioCommonsComposer;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // If you published views and moved them to your app's views folder
        View::composer([
            'components.layout',           // Your app's layout
            'components.static-layout',    // Your app's static layout
        ], BiblioCommonsComposer::class);
    }
}
```

---

## How It Works

### 1. BiblioCommonsTemplateService

The service fetches template parts from the BiblioCommons API and caches them for 24 hours:

```php
// Service automatically injected via view composer
$templateService->getTemplateParts();

// Returns:
[
    'css' => '<link rel="stylesheet" href="...">',
    'screen_reader_navigation' => '<nav>...</nav>',
    'header' => '<header>...</header>',
    'footer' => '<footer>...</footer>',
    'js' => '<script src="..."></script>',
]
```

**Features:**
- ✅ 24-hour caching for performance
- ✅ Graceful fallback if API fails
- ✅ Automatic timeout handling (10 seconds)
- ✅ Error logging for debugging

### 2. BiblioCommonsComposer

The view composer automatically injects `$bibliocommons` data into layout views:

```blade
{{-- In layout.blade.php --}}
@if (!empty($bibliocommons['header']))
    {!! $bibliocommons['header'] !!}
@endif
```

**The composer is already registered for:**
- `tpl-shared::components.layout`
- `tpl-shared::components.static-layout`

### 3. Layout Components

Both layout components include:
- BiblioCommons CSS in `<head>`
- Screen reader navigation
- Header
- Your content in `<main>`
- Footer
- Required JavaScript (Handlebars, jQuery, BiblioCommons JS)

---

## Usage Examples

### Example 1: Basic Page with BiblioCommons Layout

```blade
{{-- resources/views/welcome.blade.php --}}
<x-tpl-shared-static-layout>
    <div class="py-12">
        <h1 class="text-4xl font-bold">Welcome to Our Library</h1>
        <p>Your content here...</p>
    </div>
</x-tpl-shared-static-layout>
```

### Example 2: Custom Container Width

```blade
<x-tpl-shared::static-layout class="max-w-7xl mx-auto px-4">
    <div>Wide container content</div>
</x-tpl-shared-static-layout>
```

### Example 3: With Inertia.js (if using layout component)

```blade
{{-- resources/views/components/layout.blade.php (published) --}}
<x-tpl-shared-layout>
    @inertia
</x-tpl-shared-layout>
```

### Example 4: Clearing Cache

If you need to refresh BiblioCommons templates:

```php
// In a controller or command
use Tpl\Shared\Services\BiblioCommonsTemplateService;

class RefreshBiblioCommonsCommand extends Command
{
    public function handle(BiblioCommonsTemplateService $service): void
    {
        $service->clearCache();
        $this->info('BiblioCommons cache cleared!');
    }
}
```

Or via Tinker:

```bash
php artisan tinker
>>> app(Tpl\Shared\Services\BiblioCommonsTemplateService::class)->clearCache()
```

---

## API Response Format

The BiblioCommons API should return JSON in this format:

```json
{
    "css": "<link rel=\"stylesheet\" href=\"https://...\">",
    "screen_reader_navigation": "<nav aria-label=\"Skip links\">...</nav>",
    "header": "<header>...</header>",
    "footer": "<footer>...</footer>",
    "js": "<script src=\"https://...\"></script>"
}
```

---

## Customization

### Customize Cache Duration

Extend the service in your host app:

```php
<?php

namespace App\Services;

use Tpl\Shared\Services\BiblioCommonsTemplateService as BaseService;

class CustomBiblioCommonsService extends BaseService
{
    public function getTemplateParts(): array
    {
        return Cache::remember('bibliocommons_templates', now()->addHours(1), function () {
            return parent::getTemplateParts();
        });
    }
}
```

Then bind it in your `AppServiceProvider`:

```php
$this->app->singleton(
    \Tpl\Shared\Services\BiblioCommonsTemplateService::class,
    \App\Services\CustomBiblioCommonsService::class
);
```

### Customize Template Structure

Publish the views and modify them:

```bash
php artisan vendor:publish --tag=tpl-shared-views
```

Edit `resources/views/vendor/tpl-shared/components/static-layout.blade.php`:

```blade
{{-- Add your customizations --}}
<header>
    {{-- Your custom header --}}
    @if (!empty($bibliocommons['header']))
        {!! $bibliocommons['header'] !!}
    @endif
</header>
```

---

## Troubleshooting

### BiblioCommons content not showing

**Check API configuration:**
```bash
php artisan tinker
>>> config('services.bibliocommons.external_templates_url')
```

**Check cache:**
```bash
php artisan tinker
>>> Cache::get('bibliocommons_templates')
```

**Check logs:**
```bash
tail -f storage/logs/laravel.log | grep BiblioCommons
```

### API timeout or errors

The service automatically falls back to empty templates. Check your logs:

```bash
php artisan log:tail | grep BiblioCommons
```

### CSS/JS conflicts

If BiblioCommons styles conflict with your app:

1. Publish the views
2. Wrap BiblioCommons content in isolated containers
3. Adjust CSS specificity or use namespacing

### Cache not clearing

Manually clear:

```bash
php artisan cache:clear
```

Or programmatically:

```php
Cache::forget('bibliocommons_templates');
```

---

## Testing

### Unit Test for Service

```php
<?php

use Tpl\Shared\Services\BiblioCommonsTemplateService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

test('fetches bibliocommons templates', function () {
    Http::fake([
        '*' => Http::response([
            'css' => '<link>',
            'header' => '<header>Test</header>',
            'footer' => '<footer>Test</footer>',
            'screen_reader_navigation' => '<nav>Test</nav>',
            'js' => '<script></script>',
        ], 200),
    ]);

    $service = app(BiblioCommonsTemplateService::class);
    $templates = $service->getTemplateParts();

    expect($templates['header'])->toBe('<header>Test</header>');
});

test('returns default template on api failure', function () {
    Http::fake(['*' => Http::response([], 500)]);

    $service = app(BiblioCommonsTemplateService::class);
    Cache::forget('bibliocommons_templates');
    
    $templates = $service->getTemplateParts();

    expect($templates['header'])->toBe('');
});
```

### Feature Test for Layout

```php
<?php

test('layout renders with bibliocommons data', function () {
    Http::fake([
        '*' => Http::response([
            'header' => '<header>Library Header</header>',
            'footer' => '<footer>Library Footer</footer>',
            'css' => '',
            'js' => '',
            'screen_reader_navigation' => '',
        ], 200),
    ]);

    $response = $this->get('/');

    $response->assertSee('Library Header', false);
    $response->assertSee('Library Footer', false);
});
```

---

## Summary

✅ **Package automatically provides:**
- BiblioCommons service registration
- View composer for layouts
- Ready-to-use layout components

✅ **Host app needs to:**
1. Configure API URL in `config/services.php`
2. Use `<x-tpl-shared-static-layout>` or `<x-tpl-shared-layout>`
3. Optionally publish views for customization

✅ **Benefits:**
- Automatic caching (24 hours)
- Graceful fallback on errors
- No manual composer setup required
- Works out of the box after configuration

---

## Quick Start Checklist

- [ ] Add BiblioCommons API URL to `config/services.php`
- [ ] Add `BIBLIOCOMMONS_API_URL` to `.env`
- [ ] Use `<x-tpl-shared-static-layout>` in your Blade files
- [ ] Test that header/footer appear
- [ ] Clear cache if needed: `Cache::forget('bibliocommons_templates')`

That's it! The package handles everything else automatically.

