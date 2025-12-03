# Host App Setup Example

## Complete Step-by-Step Guide

### 1. Install the Package

```bash
composer require tpl/shared:^0.1.0
```

### 2. Configure Services

**Edit `config/services.php`:**
```php
<?php

return [
    // ...existing services like 'mailgun', 'postmark', etc.

    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    ],
];
```

**Edit `.env`:**
```env
# Add this line (replace with your actual BiblioCommons API URL)
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
```

### 3. Update Your Views

**Before (old layout):**
```blade
{{-- resources/views/welcome.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Welcome</h1>
        <p>Content here</p>
    </div>
@endsection
```

**After (with package layout):**
```blade
{{-- resources/views/welcome.blade.php --}}
<x-tpl-shared::static-layout>
    <div class="py-12">
        <h1>Welcome</h1>
        <p>Content here</p>
    </div>
</x-tpl-shared::static-layout>
```

### 4. Test It

**Start your dev server:**
```bash
php artisan serve
```

**Visit your page:**
```
http://localhost:8000
```

**You should see:**
- ✅ BiblioCommons header (navigation, search, etc.)
- ✅ Your page content
- ✅ BiblioCommons footer (links, copyright, etc.)

### 5. Verify BiblioCommons Data

```bash
php artisan tinker
```

```php
// Check config
config('services.bibliocommons.external_templates_url')
// Should output: "https://tpl.bibliocommons.com/..."

// Check cached data
Cache::get('bibliocommons_templates')
// Should output array with 'header', 'footer', 'css', 'js', etc.

// Manually fetch (for testing)
app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class)->getTemplateParts()
```

---

## Example Route + Controller + View

### Route
```php
// routes/web.php
Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', function () {
    return view('about');
});
```

### View: Welcome Page
```blade
{{-- resources/views/welcome.blade.php --}}
<x-tpl-shared::static-layout>
    <div class="space-y-12 py-16">
        {{-- Hero Section --}}
        <div class="text-center">
            <h1 class="text-5xl font-bold text-gray-900 mb-4">
                Welcome to Toronto Public Library
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Discover thousands of books, magazines, eBooks, audiobooks, and more.
            </p>
        </div>

        {{-- Features Grid --}}
        <div class="grid md:grid-cols-3 gap-8">
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-2xl font-bold mb-3">Browse Catalog</h2>
                <p class="text-gray-600">Search our extensive collection</p>
            </div>
            
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-2xl font-bold mb-3">Events</h2>
                <p class="text-gray-600">Join our programs and workshops</p>
            </div>
            
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-2xl font-bold mb-3">Digital Resources</h2>
                <p class="text-gray-600">Access eBooks and audiobooks</p>
            </div>
        </div>
    </div>
</x-tpl-shared::static-layout>
```

### View: About Page (with custom width)
```blade
{{-- resources/views/about.blade.php --}}
<x-tpl-shared::static-layout class="max-w-6xl mx-auto px-4">
    <article class="prose lg:prose-xl py-12">
        <h1>About Us</h1>
        
        <p>
            Toronto Public Library is one of the world's busiest public library 
            systems, serving millions of visitors each year.
        </p>

        <h2>Our Mission</h2>
        <p>
            We empower Torontonians to thrive by providing inclusive access to 
            spaces, services, collections and cultural experiences.
        </p>

        <h2>Our History</h2>
        <p>
            Founded in 1883, TPL has grown from a single library to 100 branches 
            across the city.
        </p>
    </article>
</x-tpl-shared::static-layout>
```

---

## Example with Inertia.js

### App Layout
```blade
{{-- resources/views/app.blade.php --}}
<x-tpl-shared::layout>
    @inertia
</x-tpl-shared::layout>
```

### React Page Component
```tsx
// resources/js/pages/Welcome.tsx
import { Head } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            
            <div className="py-12">
                <h1 className="text-4xl font-bold">Welcome to TPL</h1>
                <p className="mt-4 text-lg text-gray-600">
                    Your Inertia.js content here
                </p>
            </div>
        </>
    );
}
```

---

## Customization Examples

### Example 1: Full-Width Hero Section

```blade
<x-tpl-shared::static-layout class="">
    {{-- Full-width hero --}}
    <div class="w-full bg-blue-600 text-white py-24 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl font-bold">Welcome</h1>
        </div>
    </div>

    {{-- Constrained content --}}
    <div class="max-w-4xl mx-auto px-4 py-12">
        <p>Your content here...</p>
    </div>
</x-tpl-shared::static-layout>
```

### Example 2: Multiple Sections with Different Widths

```blade
<x-tpl-shared::static-layout class="">
    {{-- Wide section --}}
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-4 gap-6">
            {{-- 4-column grid --}}
        </div>
    </div>

    {{-- Narrow section --}}
    <div class="max-w-3xl mx-auto px-4 py-12">
        <article class="prose">
            {{-- Article content --}}
        </article>
    </div>
</x-tpl-shared::static-layout>
```

### Example 3: With Custom Scripts

```blade
<x-tpl-shared::static-layout>
    <div class="py-12">
        <h1>Page with Custom JS</h1>
        <button id="my-button">Click Me</button>
    </div>

    <x-slot:scripts>
        <script>
            document.getElementById('my-button').addEventListener('click', () => {
                alert('Hello from custom script!');
            });
        </script>
    </x-slot:scripts>
</x-tpl-shared::static-layout>
```

---

## Troubleshooting Your Setup

### Issue: "Class BiblioCommonsTemplateService not found"

**Solution:** The package is not installed or autoload not dumped.
```bash
composer dump-autoload
```

### Issue: Config not found

**Solution:** Clear config cache
```bash
php artisan config:clear
```

### Issue: Views not found

**Solution:** Clear view cache
```bash
php artisan view:clear
```

### Issue: BiblioCommons header/footer not showing

**Check 1:** Is the API URL configured?
```bash
php artisan tinker
>>> config('services.bibliocommons.external_templates_url')
```

**Check 2:** Can you reach the API?
```bash
php artisan tinker
>>> Http::get(config('services.bibliocommons.external_templates_url'))
```

**Check 3:** Check the logs
```bash
tail -f storage/logs/laravel.log
```

### Issue: Getting empty templates

This is normal if:
- API is not responding (service falls back gracefully)
- First load (cache building)
- API returned empty data

**Check cache:**
```bash
php artisan tinker
>>> Cache::get('bibliocommons_templates')
```

**Force refresh:**
```bash
php artisan tinker
>>> app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class)->clearCache()
```

---

## Production Deployment Checklist

- [ ] `BIBLIOCOMMONS_API_URL` set in production `.env`
- [ ] Config cached: `php artisan config:cache`
- [ ] Views cached: `php artisan view:cache`
- [ ] Routes cached: `php artisan route:cache`
- [ ] Test BiblioCommons header/footer appear
- [ ] Check logs for any API errors
- [ ] Verify cache is working (check response times)

---

## Performance Tips

### Tip 1: Extend Cache Duration

The default is 24 hours. To change:

```php
// app/Services/CustomBiblioCommonsService.php
namespace App\Services;

use Tpl\Shared\Services\BiblioCommonsTemplateService as BaseService;
use Illuminate\Support\Facades\Cache;

class CustomBiblioCommonsService extends BaseService
{
    public function getTemplateParts(): array
    {
        return Cache::remember('bibliocommons_templates', now()->addDays(7), function () {
            return parent::getTemplateParts();
        });
    }
}
```

Then bind in `AppServiceProvider`:
```php
$this->app->singleton(
    \Tpl\Shared\Services\BiblioCommonsTemplateService::class,
    \App\Services\CustomBiblioCommonsService::class
);
```

### Tip 2: Warm the Cache

Add to a scheduled command:
```php
// app/Console/Commands/WarmBiblioCommonsCache.php
$service = app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class);
$service->getTemplateParts(); // Fetches and caches
```

Schedule it:
```php
// routes/console.php
Schedule::command('bibliocommons:warm')->daily();
```

### Tip 3: Monitor API Health

```php
// In a health check endpoint
public function health()
{
    $templates = Cache::get('bibliocommons_templates');
    
    return response()->json([
        'bibliocommons' => !empty($templates['header']) ? 'ok' : 'degraded',
    ]);
}
```

---

## Next Steps

1. ✅ Configure `services.bibliocommons` and `.env`
2. ✅ Replace your layouts with `<x-tpl-shared::static-layout>`
3. ✅ Test locally
4. ✅ Deploy to staging
5. ✅ Verify production works

Need help? Check **BIBLIOCOMMONS.md** for complete documentation!

