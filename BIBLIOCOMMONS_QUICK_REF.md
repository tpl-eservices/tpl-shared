# BiblioCommons Quick Reference

## Host App Setup (3 Steps)

### 1. Configure API URL

**`config/services.php`:**
```php
'bibliocommons' => [
    'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
],
```

**`.env`:**
```env
BIBLIOCOMMONS_API_URL=https://your-library.bibliocommons.com/api/external-templates
```

### 2. Use Layout Component

```blade
<x-tpl-shared-static-layout>
    <div class="py-12">
        <h1>Your Page Content</h1>
    </div>
</x-tpl-shared-static-layout>
```

### 3. Done! ✅

The package automatically:
- ✅ Fetches BiblioCommons templates
- ✅ Caches for 24 hours
- ✅ Injects header/footer/CSS/JS
- ✅ Handles API failures gracefully

---

## Available Layouts

### Static Layout
```blade
<x-tpl-shared-static-layout>
    <!-- Static page content -->
</x-tpl-shared-static-layout>
```

### Dynamic Layout (with Inertia)
```blade
<x-tpl-shared-layout>
    @inertia
</x-tpl-shared-layout>
```

---

## Common Tasks

### Clear Cache
```bash
php artisan tinker
>>> app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class)->clearCache()
```

### Check API Config
```bash
php artisan tinker
>>> config('services.bibliocommons.external_templates_url')
```

### Check Cached Data
```bash
php artisan tinker
>>> Cache::get('bibliocommons_templates')
```

### View Logs
```bash
tail -f storage/logs/laravel.log | grep BiblioCommons
```

---

## Optional: Publish & Customize

```bash
# Publish views
php artisan vendor:publish --tag=tpl-shared-views

# Edit published files
resources/views/vendor/tpl-shared/components/static-layout.blade.php
```

---

## What Gets Injected

The `$bibliocommons` variable contains:
- `css` - Stylesheet link tags
- `screen_reader_navigation` - Skip navigation
- `header` - Library header HTML
- `footer` - Library footer HTML  
- `js` - JavaScript script tags

Plus required dependencies:
- Handlebars.js
- jQuery

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| No header/footer shows | Check `BIBLIOCOMMONS_API_URL` in `.env` |
| API errors | Check logs: `tail -f storage/logs/laravel.log` |
| Cache not updating | Clear: `Cache::forget('bibliocommons_templates')` |
| CSS conflicts | Publish views and adjust styles |

---

See **BIBLIOCOMMONS.md** for complete documentation.

