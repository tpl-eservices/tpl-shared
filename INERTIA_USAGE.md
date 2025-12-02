# Using TPL Shared Components with Inertia.js

## App Layout for Inertia Applications

The package includes an `app.blade.php` file that demonstrates how to integrate with Inertia.js applications.

### Package's app.blade.php

Located at `resources/views/app.blade.php`:

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

This serves as a reference implementation showing how to:
- Use the package's BiblioCommons-integrated layout
- Add Inertia.js specific head elements
- Load React/Vue components via Vite

## Using in Your Host Application

### Option 1: Create Your Own app.blade.php

**Recommended for most projects:**

Create `resources/views/app.blade.php` in your host application:

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

Or use the dynamic layout with center option:

```blade
<x-tpl-shared-layout :center="false">
  <x-slot:head>
    @viteReactRefresh
    @vite('resources/js/app.tsx')
    @inertiaHead
  </x-slot:head>

  @inertia
</x-tpl-shared::layout>
```

### Option 2: Publish and Customize

Publish the package views:

```bash
php artisan vendor:publish --tag=tpl-shared-views
```

Then customize `resources/views/vendor/tpl-shared/app.blade.php` or create your own.

### Option 3: Reference from Package

You can reference the package view directly in your Inertia middleware:

```php
// In your Inertia middleware or HandleInertiaRequests
return $this->rootView ?? 'tpl-shared::app';
```

## Component Features

### Head Slot

Both layouts support a `head` slot for adding content to `<head>`:

```blade
<x-tpl-shared::static-layout>
  <x-slot:head>
    {{-- Your meta tags, scripts, styles --}}
    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
    @inertiaHead
    
    <meta name="description" content="My library app">
    <script src="https://example.com/analytics.js"></script>
  </x-slot:head>

  @inertia
</x-tpl-shared::static-layout>
```

### Scripts Slot

Add scripts at the end of `<body>`:

```blade
<x-tpl-shared::static-layout>
  @inertia
  
  <x-slot:scripts>
    <script>
      console.log('Custom script');
    </script>
  </x-slot:scripts>
</x-tpl-shared::static-layout>
```

### Custom Container Class

Change the main content container width:

```blade
<x-tpl-shared-static-layout class="max-w-7xl mx-auto px-4">
  @inertia
</x-tpl-shared::static-layout>
```

Default: `max-w-4xl mx-auto`

## Complete Example

**`resources/views/app.blade.php` in your host application:**

```blade
<x-tpl-shared-static-layout class="max-w-6xl mx-auto px-4">
  <x-slot:head>
    <meta name="description" content="{{ config('app.name') }} - Library Portal">
    
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
    @if (config('app.env') === 'production')
    <script src="https://analytics.example.com/script.js"></script>
    @endif
  </x-slot:scripts>
</x-tpl-shared::static-layout>
```

## Inertia Configuration

Ensure your `config/inertia.php` points to the correct root view:

```php
<?php

return [
    'ssr' => [
        'enabled' => true,
        'url' => 'http://127.0.0.1:13714/render',
    ],
];
```

Your Inertia middleware should handle the root view:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function rootView(Request $request): string
{
    return 'app'; // Points to resources/views/app.blade.php
}
```

## React Page Example

**`resources/js/pages/Dashboard.tsx`:**

```tsx
import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <>
            <Head>
                <title>Dashboard - Toronto Public Library</title>
                <meta name="description" content="Your library dashboard" />
            </Head>

            <div className="py-12">
                <h1 className="text-4xl font-bold mb-8">
                    Welcome to Your Dashboard
                </h1>
                
                <div className="grid md:grid-cols-3 gap-6">
                    <div className="p-6 bg-white rounded-lg shadow">
                        <h2 className="text-xl font-semibold mb-2">Checked Out</h2>
                        <p className="text-3xl font-bold text-blue-600">5</p>
                    </div>
                    
                    <div className="p-6 bg-white rounded-lg shadow">
                        <h2 className="text-xl font-semibold mb-2">On Hold</h2>
                        <p className="text-3xl font-bold text-green-600">2</p>
                    </div>
                    
                    <div className="p-6 bg-white rounded-lg shadow">
                        <h2 className="text-xl font-semibold mb-2">Saved</h2>
                        <p className="text-3xl font-bold text-purple-600">12</p>
                    </div>
                </div>
            </div>
        </>
    );
}
```

## What You Get

When using the package layouts with Inertia:

✅ **BiblioCommons Integration**
- Header with navigation, search, account menu
- Footer with links and branding
- CSS and JavaScript automatically included

✅ **Inertia.js Support**
- `@inertia` directive for mounting your app
- `@inertiaHead` for dynamic meta tags
- SSR-compatible structure

✅ **Vite Integration**
- Hot Module Replacement (HMR)
- React Fast Refresh
- Automatic asset bundling

✅ **Responsive Layout**
- Mobile-friendly design
- Accessible navigation
- Dark mode support (if configured)

## Troubleshooting

### "Unknown Blade component"

If you see this error, ensure:

1. **Package is installed:**
   ```bash
   composer require tpl/shared:^0.1.0
   ```

2. **Autoload is updated:**
   ```bash
   composer dump-autoload
   ```

3. **Using correct syntax:**
   - ✅ `<x-tpl-shared::static-layout>`
   - ❌ `<x-static-layout>` (won't work without publishing)

### BiblioCommons content not showing

Check your configuration:

```bash
php artisan tinker
>>> config('services.bibliocommons.external_templates_url')
```

Should return a valid URL.

### Vite assets not loading

Ensure your `.env` has:

```env
APP_URL=http://localhost
VITE_DEV_SERVER_URL=http://localhost:5173
```

And run:

```bash
pnpm dev  # or npm run dev
```

## Summary

- ✅ Use `<x-tpl-shared::static-layout>` or `<x-tpl-shared::layout>` in your app.blade.php
- ✅ Add Inertia/Vite directives in the `head` slot
- ✅ Use `@inertia` directive for mounting your React/Vue app
- ✅ BiblioCommons header/footer automatically included
- ✅ Fully compatible with Inertia.js SSR

For more details, see:
- [BIBLIOCOMMONS.md](BIBLIOCOMMONS.md) - BiblioCommons integration
- [README.md](README.md) - Package overview

