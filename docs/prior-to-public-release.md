# Prior to Public Release

This document outlines changes required before releasing this package publicly on Packagist.

## Critical Issues

### 1. Hardcoded TPL URL

**File:** `src/Services/DXServicesService.php` (line ~137)

```php
'message' => "Your library card is valid for renewal. Please renew your membership at https://membership.tpl.ca before attempting to {$action}.",
```

**Fix:** Make the membership renewal URL configurable:

```php
// config/services.php
'dxservices' => [
    // ... existing config ...
    'renewal_url' => env('DXSERVICES_RENEWAL_URL', 'https://membership.tpl.ca'),
],

// In DXServicesService.php
$renewalUrl = config('services.dxservices.renewal_url');
'message' => "Your library card is valid for renewal. Please renew your membership at {$renewalUrl} before attempting to {$action}.",
```

### 2. Missing LICENSE File

The `composer.json` declares `"license": "proprietary"` but no LICENSE file exists.

**Options:**
- **Open source:** Create `LICENSE` with MIT license text, update composer.json to `"license": "MIT"`
- **Proprietary:** Create `LICENSE` defining proprietary terms and usage restrictions

### 3. Incomplete composer.json Metadata

Add these fields for Packagist discoverability:

```json
{
    "homepage": "https://github.com/tpl-eservices/tpl-shared",
    "keywords": ["laravel", "bibliocommons", "sso", "library", "authentication"],
    "authors": [
        {
            "name": "Toronto Public Library",
            "email": "eservices@tpl.ca"
        }
    ]
}
```

## Optional Improvements

### Package Name

Current: `tpl/shared`

Consider a more descriptive name for public release:
- `tpl-eservices/shared`
- `tpl/bibliocommons-laravel`

Note: Changing the package name is a breaking change for existing consumers.

### Test File URLs

**File:** `tests/Feature/BiblioCommonsServiceTest.php`

Contains TPL-specific test data (`torontopubliclibrary.ca`). Consider using generic mock URLs for cleaner public appearance, though this is not a security concern.

## Security Checklist (Verified)

- [x] No hardcoded API keys in source code
- [x] All secrets managed via environment variables
- [x] Only `.env.example` exists (no actual credentials)
- [x] Proper `.gitignore` excludes sensitive files
- [x] SSL verification only disabled in non-production

## After Making Changes

1. Run full test suite: `composer test`
2. Run static analysis: `composer analyse`
3. Update CHANGELOG.md
4. Tag new version
5. Register on Packagist at https://packagist.org/packages/submit
