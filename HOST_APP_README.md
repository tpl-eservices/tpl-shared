# 🚨 BiblioCommons Not Loading? Fix in 5 Minutes!

> **For `tpl-apps` host application team**

## The Problem

BiblioCommons header and footer aren't showing up in your application.

## The Fix (7 Simple Steps)

### 1️⃣ Run This Command First

```bash
php artisan bibliocommons:diagnose
```

This will tell you exactly what's wrong! Follow its recommendations.

---

### 2️⃣ Fix Your AppServiceProvider

**Open:** `app/Providers/AppServiceProvider.php`

**Remove this line** (if you see it):
```php
View::composer(['app', 'components/layout', 'components/static-layout'], BiblioCommonsComposer::class);
```

**Your file should look like this:**
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

---

### 3️⃣ Create Config File

**Create:** `config/services.php` (if it doesn't exist)

**Add this:**
```php
<?php

return [
    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
    ],
];
```

---

### 4️⃣ Update .env File

**Add this line to your `.env` file:**
```env
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
```

---

### 5️⃣ Use Package Views

**In your Blade files, use:**
```blade
<x-tpl-shared::static-layout>
    <div>
        Your page content here
    </div>
</x-tpl-shared::static-layout>
```

**Or for dynamic layouts:**
```blade
<x-tpl-shared::layout>
    <div>
        Your page content here
    </div>
</x-tpl-shared::layout>
```

---

### 6️⃣ Clear Caches

```bash
php artisan optimize:clear
```

---

### 7️⃣ Test!

```bash
# Run diagnostic again
php artisan bibliocommons:diagnose

# Visit your app in browser
# You should see BiblioCommons header and footer! 🎉
```

---

## ✅ Verify It Works

**Check these:**
- [ ] BiblioCommons header appears at top of page
- [ ] BiblioCommons footer appears at bottom of page
- [ ] No errors in browser console (F12)
- [ ] No errors in Laravel logs
- [ ] `php artisan bibliocommons:diagnose` shows all green checks

---

## 🐛 Still Not Working?

### Quick Checks

**1. Check configuration:**
```bash
php artisan tinker
>>> config('services.bibliocommons.external_templates_url')
# Should show: https://tpl.bibliocommons.com/api/external-templates
```

**2. Check if templates are loading:**
```bash
php artisan tinker
>>> app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class)->getTemplateParts()
# Should show array with 'header', 'footer', etc.
```

**3. Check logs:**
```bash
tail -f storage/logs/laravel.log | grep BiblioCommons
```

---

## 📚 Need More Help?

**Guides in the package (from quick to detailed):**

1. **⚡ QUICK_FIX_BIBLIOCOMMONS.md** - This information in more detail
2. **✅ HOST_APP_FIX_CHECKLIST.md** - Step-by-step checklist to print/follow
3. **📘 FIX_HOST_APP_SETUP.md** - Detailed guide with 2 different approaches
4. **🔍 TROUBLESHOOTING_HOST_APP.md** - Comprehensive troubleshooting

**All guides are in the `tpl-shared` package directory.**

---

## 💡 Why Did This Happen?

### The Issue
Your `AppServiceProvider` was trying to register a view composer for BiblioCommons, but:
1. The views it referenced didn't exist or weren't namespaced correctly
2. The package **already registers the composer** for you
3. You just needed to configure the API URL

### The Solution
- Remove the duplicate composer registration
- Configure the API URL
- Use the package views with `<x-tpl-shared::*>` prefix

**The package handles everything else automatically!** 🎉

---

## 🎯 Key Takeaway

**You don't need to register the view composer!**

The package's `SharedServiceProvider` already registers `BiblioCommonsComposer` for:
- `tpl-shared::components.layout`
- `tpl-shared::components.static-layout`
- `tpl-shared::components.inertia-layout`

You just need to:
1. Configure the API URL
2. Use the package views

That's it! 🚀

---

## 📞 Support

**Before asking for help:**
1. Run `php artisan bibliocommons:diagnose`
2. Check the troubleshooting guides above
3. Review Laravel logs

**When reporting issues, include:**
- Output from `php artisan bibliocommons:diagnose`
- Any error messages from Laravel logs
- What you've tried so far

---

**Last Updated:** December 15, 2025  
**Package Version:** 0.1.13+  
**Status:** ✅ Ready to Implement

