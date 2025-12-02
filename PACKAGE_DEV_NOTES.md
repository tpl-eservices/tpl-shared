# Package Development Notes

## Vite Development Server

This package includes frontend assets (React components, CSS) but doesn't run a standalone Vite dev server since it's a package, not an application.

### For Package Development

When developing the package:

1. **Build assets**: Use `pnpm build` to compile frontend assets
2. **Test in host app**: Symlink the package to a host Laravel app for live development
3. **Run tests**: Use `composer test` for PHP/Laravel tests

### Vite Configuration

The `vite.config.ts` is included for:
- Building distributable frontend assets
- Documentation/reference for host apps
- IDE support and type generation

To build the package assets:

```bash
pnpm install
pnpm build
```

### Testing Frontend Changes

To test frontend components in development:

1. Use a path repository in your host app:
   ```json
   {
       "repositories": [
           {
               "type": "path",
               "url": "../tpl-shared",
               "options": {
                   "symlink": true
               }
           }
       ]
   }
   ```

2. Install in host app:
   ```bash
   composer require tpl/shared:@dev
   ```

3. Publish assets to host app:
   ```bash
   php artisan vendor:publish --tag=tpl-shared-assets
   ```

4. Run Vite from the **host app**, not the package:
   ```bash
   # In host app directory
   pnpm dev
   ```

The host app's Vite will watch and compile both its own assets and the published package assets.

## Wayfinder in Packages

The Wayfinder plugin in `vite.config.ts` generates TypeScript types for Laravel routes. Since this is a package:

- Wayfinder will try to run `php artisan wayfinder:generate` during Vite startup
- This requires a full Laravel application context (not available in packages)
- Host applications using this package should include Wayfinder in their own config

### Solution for Host Apps

Host apps should configure Wayfinder to include package routes:

```typescript
// Host app's vite.config.ts
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        wayfinder({
            formVariants: true,
            // Package routes will be auto-discovered from vendor
        }),
    ],
});
```

## CI/CD Notes

The GitHub Actions workflow:
- Runs PHP tests (working)
- Checks code style with Pint (working)
- Runs TypeScript type checking (requires build setup)
- Builds frontend assets (optional for package)

You may want to adjust the frontend CI steps based on whether you distribute pre-built assets or expect host apps to build them.

