# Component Registration Fixed - Host App Guide

## ✅ Problem Solved

**Error in Host App:**
```
InvalidArgumentException
Unable to locate a class or view for component [tpl-shared-static-layout].
```

**Root Cause:** 
Components weren't properly registered with Laravel's Blade namespace system, so host applications couldn't resolve them.

## Solution Applied

Changed from individual component registration to namespace registration in `SharedServiceProvider`:

**Before (didn't work in host apps):**
```php
Blade::component('tpl-shared-layout', Layout::class);
Blade::component('tpl-shared-static-layout', StaticLayout::class);
```

**After (works in host apps):**
```php
Blade::componentNamespace('Tpl\\Shared\\View\\Components', 'tpl-shared');
```

This tells Laravel: "When you see `<x-tpl-shared::something>`, look for the component class in the `Tpl\Shared\View\Components` namespace."

---

## BREAKING CHANGE: Component Syntax Updated

### New Syntax (Use This)

```blade
{{-- Static layout --}}
<x-tpl-shared::static-layout>
    Your content
</x-tpl-shared::static-layout>

{{-- Dynamic layout --}}
<x-tpl-shared::layout>
    @inertia
</x-tpl-shared::layout>
```

### Old Syntax (Don't Use)

```blade
{{-- This won't work anymore --}}
<x-tpl-shared-static-layout>
<x-tpl-shared-layout>
```

**Why the change?** 
Laravel's `Blade::componentNamespace()` requires the `::` (double colon) syntax to properly map to the component namespace. This is the standard Laravel convention for package components.

---

## How to Use in Your Host Application

### Step 1: Update Package

```bash
composer update tpl/shared
```

Or if using symlink:

```bash
composer dump-autoload
```

### Step 2: Update Your Views

**For Inertia.js apps (`resources/views/app.blade.php`):**

```blade
<x-tpl-shared::static-layout>
  <x-slot:head>
    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    @inertiaHead
  </x-slot:head>

  @inertia
</x-tpl-shared::static-layout>
```

**For static pages:**

```blade
<x-tpl-shared::static-layout>
  <div class="py-12">
    <h1>Welcome to Toronto Public Library</h1>
  </div>
</x-tpl-shared::static-layout>
```

### Step 3: Clear Caches

```bash
php artisan view:clear
php artisan config:clear
composer dump-autoload
```

### Step 4: Test

```bash
php artisan serve
```

Visit your page - components should now load without errors!

---

## Component Features

### Static Layout Component

**Syntax:**
```blade
<x-tpl-shared::static-layout class="max-w-6xl mx-auto">
  <!-- Your content -->
</x-tpl-shared::static-layout>
```

**Props:**
- `class` (optional) - Container CSS classes, default: `max-w-4xl mx-auto`

**Slots:**
- `head` - Add content to `<head>` section
- `scripts` - Add scripts before `</body>`
- Default slot - Your page content

**Example with all features:**
```blade
<x-tpl-shared::static-layout class="max-w-7xl mx-auto px-4">
  <x-slot:head>
    <meta name="description" content="Library portal">
    @vite('resources/css/custom.css')
  </x-slot:head>

  <div class="py-12">
    <h1>My Page</h1>
  </div>

  <x-slot:scripts>
    <script>
      console.log('Page loaded');
    </script>
  </x-slot:scripts>
</x-tpl-shared::static-layout>
```

### Layout Component (for Inertia)

**Syntax:**
```blade
<x-tpl-shared::layout :center="false">
  @inertia
</x-tpl-shared::layout>
```

**Props:**
- `center` (optional, boolean) - Center content, default: `false`

**Slots:**
- `head` - Add content to `<head>` section
- `scripts` - Add scripts before `</body>`
- Default slot - Usually `@inertia`

---

## What's Included Automatically

When you use these components, you automatically get:

✅ **BiblioCommons Integration**
- Header with navigation, search, account menu
- Footer with links and copyright
- CSS stylesheets
- JavaScript functionality

✅ **Required Dependencies**
- Handlebars.js (for BiblioCommons templates)
- jQuery (for BiblioCommons functionality)

✅ **Responsive Design**
- Mobile-friendly layout
- Accessible navigation
- Screen reader support

✅ **Performance Optimizations**
- 24-hour caching of BiblioCommons templates
- Graceful fallback if API fails
- Minimal overhead

---

## Complete Example: Inertia.js Application

### 1. Configure BiblioCommons API

**`config/services.php`:**
```php
return [
    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    ],
];
```

**`.env`:**
```env
BIBLIOCOMMONS_API_URL=https://torontopubliclibrary.bibliocommons.com/api/external-templates
```

### 2. Create App Layout

**`resources/views/app.blade.php`:**
```blade
<x-tpl-shared::static-layout class="max-w-6xl mx-auto px-4">
  <x-slot:head>
    <meta name="description" content="Toronto Public Library Portal">
    
    @viteReactRefresh
    @vite([
        'resources/css/app.css',
        'resources/js/app.tsx',
        "resources/js/pages/{$page['component']}.tsx"
    ])
    @inertiaHead
  </x-slot:head>

  @inertia

  <x-slot:scripts>
    @env('production')
    <script src="https://analytics.example.com/script.js"></script>
    @endenv
  </x-slot:scripts>
</x-tpl-shared::static-layout>
```

### 3. Configure Inertia

**`app/Http/Middleware/HandleInertiaRequests.php`:**
```php
public function rootView(Request $request): string
{
    return 'app'; // Points to resources/views/app.blade.php
}
```

### 4. Create React Pages

**`resources/js/pages/Dashboard.tsx`:**
```tsx
import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard - TPL" />
            
            <div className="py-12">
                <h1 className="text-4xl font-bold mb-8">
                    Welcome to Your Dashboard
                </h1>
                
                <div className="grid md:grid-cols-3 gap-6">
                    <div className="p-6 bg-white rounded-lg shadow">
                        <h2 className="text-xl font-semibold">Checked Out</h2>
                        <p className="text-3xl font-bold text-blue-600">5</p>
                    </div>
                    
                    <div className="p-6 bg-white rounded-lg shadow">
                        <h2 className="text-xl font-semibold">On Hold</h2>
                        <p className="text-3xl font-bold text-green-600">2</p>
                    </div>
                    
                    <div className="p-6 bg-white rounded-lg shadow">
                        <h2 className="text-xl font-semibold">Saved</h2>
                        <p className="text-3xl font-bold text-purple-600">12</p>
                    </div>
                </div>
            </div>
        </>
    );
}
```

### 5. Define Routes

**`routes/web.php`:**
```php
use Inertia\Inertia;

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');
```

### 6. Start Development

```bash
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Vite
pnpm dev
```

Visit `http://localhost:8000/dashboard` - you should see:
- ✅ BiblioCommons header
- ✅ Your dashboard content
- ✅ BiblioCommons footer
- ✅ No errors!

---

## Troubleshooting

### Still Getting "Unable to locate component"

**1. Clear all caches:**
```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

**2. Verify package is installed:**
```bash
composer show tpl/shared
```

Should show the package with latest version.

**3. Check component syntax:**
- ✅ `<x-tpl-shared::static-layout>` (correct)
- ❌ `<x-tpl-shared-static-layout>` (wrong)
- ❌ `<x-static-layout>` (wrong)

**4. Restart development server:**
```bash
# Kill existing server
# Then start fresh
php artisan serve
```

### BiblioCommons content not showing

**Check configuration:**
```bash
php artisan tinker
>>> config('services.bibliocommons.external_templates_url')
```

Should return your API URL.

**Check cache:**
```bash
php artisan tinker
>>> Cache::get('bibliocommons_templates')
```

Should return array with header, footer, etc.

**Clear BiblioCommons cache:**
```bash
php artisan tinker
>>> app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class)->clearCache()
```

### Vite assets not loading

**Check .env:**
```env
APP_URL=http://localhost
VITE_DEV_SERVER_URL=http://localhost:5173
```

**Ensure Vite is running:**
```bash
pnpm dev
```

---

## Migration Guide

If you were using the old syntax, here's how to migrate:

### Search and Replace

In your host application, search for:
- `<x-tpl-shared-static-layout>` → Replace with `<x-tpl-shared::static-layout>`
- `</x-tpl-shared-static-layout>` → Replace with `</x-tpl-shared::static-layout>`
- `<x-tpl-shared-layout>` → Replace with `<x-tpl-shared::layout>`
- `</x-tpl-shared-layout>` → Replace with `</x-tpl-shared::layout>`

### VS Code Find & Replace

1. Press `Cmd+Shift+F` (Mac) or `Ctrl+Shift+F` (Windows)
2. Search: `tpl-shared-static-layout`
3. Replace: `tpl-shared::static-layout`
4. Click "Replace All"

Repeat for `tpl-shared-layout`.

---

## Why This Syntax?

Laravel's `Blade::componentNamespace()` method requires the `::` syntax because:

1. **Namespace Resolution**: The `::` tells Laravel this is a namespaced component
2. **Auto-Discovery**: Laravel can automatically find component classes in the registered namespace
3. **Convention**: This matches Laravel's standard for package components
4. **Clarity**: Clear separation between package prefix and component name

Examples from other packages:
- `<x-filament::section>` (Filament)
- `<x-livewire::component>` (Livewire)
- `<x-tpl-shared::layout>` (Your package)

---

## Summary

✅ **Fixed:** Component registration now works in host applications  
✅ **Syntax:** Use `<x-tpl-shared::static-layout>` and `<x-tpl-shared::layout>`  
✅ **Method:** Using `Blade::componentNamespace()` instead of `Blade::component()`  
✅ **Docs:** All documentation updated with correct syntax  

The component will now resolve properly in your host Laravel application!

## Need Help?

Check these docs:
- **BIBLIOCOMMONS.md** - BiblioCommons integration guide
- **INERTIA_USAGE.md** - Inertia.js specific examples
- **README.md** - Package overview
- **QUICK_START.md** - Quick reference

