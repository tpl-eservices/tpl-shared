# BiblioCommons Fix Checklist for tpl-apps

## ✅ Step-by-Step Checklist

Copy this checklist and check off each step as you complete it:

---

### 📋 Pre-Flight Check

- [ ] Package `tpl/shared` is installed and up to date
- [ ] You have access to modify `AppServiceProvider.php`
- [ ] You have access to create/edit config files
- [ ] You have access to modify `.env` file

---

### 🔧 Implementation Steps

#### Step 1: Remove Incorrect View Composer
- [ ] Open `app/Providers/AppServiceProvider.php`
- [ ] Remove this line (if present):
  ```php
  View::composer(['app', 'components/layout', 'components/static-layout'], BiblioCommonsComposer::class);
  ```
- [ ] Save the file
- [ ] Verify no other BiblioCommons composer registrations exist

#### Step 2: Create Services Configuration
- [ ] Create file `config/services.php` (if it doesn't exist)
- [ ] Add BiblioCommons configuration:
  ```php
  <?php
  return [
      'bibliocommons' => [
          'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
      ],
  ];
  ```
- [ ] Save the file

#### Step 3: Configure Environment Variable
- [ ] Open `.env` file
- [ ] Add this line:
  ```env
  BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
  ```
- [ ] Save the file
- [ ] Verify the URL is correct for your environment

#### Step 4: Update Blade Views
- [ ] Identify all views using layouts
- [ ] Update to use package layout:
  ```blade
  <x-tpl-shared::static-layout>
      <div>Your content</div>
  </x-tpl-shared::static-layout>
  ```
- [ ] Or for dynamic layouts:
  ```blade
  <x-tpl-shared::layout>
      <div>Your content</div>
  </x-tpl-shared::layout>
  ```

#### Step 5: Clear Caches
- [ ] Run: `php artisan config:clear`
- [ ] Run: `php artisan view:clear`
- [ ] Run: `php artisan cache:clear`
- [ ] Or run all at once: `php artisan optimize:clear`

#### Step 6: Run Diagnostic
- [ ] Run: `php artisan bibliocommons:diagnose`
- [ ] Check for any errors or warnings
- [ ] Verify all checks pass (green checkmarks)
- [ ] Follow any recommendations provided

#### Step 7: Test the Application
- [ ] Start the development server (if not running)
- [ ] Visit your application in browser
- [ ] Verify BiblioCommons header appears at top
- [ ] Verify BiblioCommons footer appears at bottom
- [ ] Check browser console for JavaScript errors
- [ ] Verify navigation links work
- [ ] Test on multiple pages

---

### 🔍 Verification Checklist

- [ ] BiblioCommons header is visible
- [ ] BiblioCommons footer is visible
- [ ] BiblioCommons CSS is loading (check page styles)
- [ ] BiblioCommons JavaScript is loading (check browser console)
- [ ] No 404 errors in browser console
- [ ] No PHP errors in Laravel logs
- [ ] Navigation works correctly
- [ ] Page content displays properly

---

### 🐛 If Something Doesn't Work

#### Quick Troubleshooting
- [ ] Run diagnostic again: `php artisan bibliocommons:diagnose`
- [ ] Check configuration in tinker:
  ```bash
  php artisan tinker
  >>> config('services.bibliocommons.external_templates_url')
  ```
- [ ] Check if templates are loading:
  ```bash
  php artisan tinker
  >>> app(\Tpl\Shared\Services\BiblioCommonsTemplateService::class)->getTemplateParts()
  ```
- [ ] Check Laravel logs: `tail -f storage/logs/laravel.log`
- [ ] Check browser console for errors (F12)

#### Need More Help?
- [ ] Read: [QUICK_FIX_BIBLIOCOMMONS.md](QUICK_FIX_BIBLIOCOMMONS.md)
- [ ] Read: [FIX_HOST_APP_SETUP.md](FIX_HOST_APP_SETUP.md)
- [ ] Read: [TROUBLESHOOTING_HOST_APP.md](TROUBLESHOOTING_HOST_APP.md)
- [ ] Contact package maintainers

---

### 📝 Notes Section

**Environment:** (Development / Staging / Production)

**Date Implemented:** _______________

**Implemented By:** _______________

**Issues Encountered:**
- 
- 
- 

**Resolution:**
- 
- 
- 

**Additional Configuration:**
- 
- 
- 

---

### ✅ Final Sign-Off

- [ ] All steps completed
- [ ] All verifications passed
- [ ] Documentation reviewed
- [ ] Team notified of changes
- [ ] Ready for code review/merge

**Completed By:** _______________

**Date:** _______________

**Approved By:** _______________

---

## 🎉 Success Criteria

Your implementation is successful when:

✅ BiblioCommons header and footer appear on all pages  
✅ No errors in browser console  
✅ No errors in Laravel logs  
✅ `php artisan bibliocommons:diagnose` shows all green checks  
✅ Navigation and links work properly  
✅ Application functions as expected  

---

## 📞 Support

If you need help:
- Run `php artisan bibliocommons:diagnose` first
- Check the troubleshooting guides (links above)
- Review Laravel logs
- Contact package maintainers with diagnostic output

---

**Remember:** The package already handles the view composer registration. You just need to configure the API URL and use the package views! 🚀

