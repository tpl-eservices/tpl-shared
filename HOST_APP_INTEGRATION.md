# Host App Integration Guide

This guide helps you integrate the `tpl/shared` package into your Laravel host application and resolve common configuration issues.

## Fixing Vite ENOTFOUND Errors

The error `ENOTFOUND tpl-shared.tpl.ca` occurs when Vite tries to resolve a custom domain that doesn't exist in your DNS or hosts file.

### Solution 1: Use localhost (Recommended)

Update your host app's `vite.config.ts`:

```typescript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
            ],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        host: 'localhost',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
});
```

Update your `.env`:

```env
APP_URL=http://localhost
VITE_DEV_SERVER_URL=http://localhost:5173
```

### Solution 2: Add to /etc/hosts

If you need to use custom domains, add them to your `/etc/hosts` file:

```bash
sudo nano /etc/hosts
```

Add:

```
127.0.0.1 tpl-shared.tpl.ca
127.0.0.1 your-host-app.tpl.ca
```

Then update your `.env`:

```env
APP_URL=http://tpl-shared.tpl.ca
VITE_DEV_SERVER_URL=http://tpl-shared.tpl.ca:5173
```

### Solution 3: Use Laravel Herd/Valet Domains

If using Laravel Herd or Valet, they automatically set up `.test` domains:

```env
APP_URL=http://tpl-shared.test
VITE_DEV_SERVER_URL=http://tpl-shared.test:5173
```

## Wayfinder Configuration

If your host app uses Wayfinder, ensure it doesn't conflict with the package's Wayfinder setup:

```typescript
// Host app's vite.config.ts
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    plugins: [
        // ...other plugins
        wayfinder({
            formVariants: true,
            // Include package routes if needed
            exclude: [
                // Optionally exclude package routes
                // 'vendor/tpl/shared/**',
            ],
        }),
    ],
});
```

## Package Asset Integration

### Option 1: Publish and Import

Publish package assets:

```bash
php artisan vendor:publish --tag=tpl-shared-assets
```

Then import in your host app:

```tsx
// resources/js/app.tsx
import '@/vendor/tpl-shared/css/app.css';
import { SomeComponent } from '@/vendor/tpl-shared/js/components/SomeComponent';
```

### Option 2: Direct Vendor Reference

If you don't publish, you can reference directly:

```tsx
import { SomeComponent } from '../../../vendor/tpl/shared/resources/js/components/SomeComponent';
```

Add this to your `vite.config.ts` for better resolution:

```typescript
export default defineConfig({
    resolve: {
        alias: {
            '@tpl-shared': '/vendor/tpl/shared/resources/js',
        },
    },
});
```

## TypeScript Configuration

Update your host app's `tsconfig.json`:

```json
{
    "compilerOptions": {
        "paths": {
            "@/*": ["./resources/js/*"],
            "@tpl-shared/*": [
                "./vendor/tpl/shared/resources/js/*",
                "./resources/vendor/tpl-shared/js/*"
            ]
        }
    }
}
```

## Troubleshooting

### Assets Not Loading

1. Clear Laravel caches:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

2. Rebuild frontend assets:
   ```bash
   pnpm install
   pnpm build
   ```

### Vite HMR Not Working

Ensure your `.env` has the correct Vite URL:

```env
VITE_DEV_SERVER_URL="${APP_URL}:5173"
```

Restart Vite:

```bash
pnpm dev
```

### Package Routes Not Registering

Check that the service provider is loaded:

```bash
php artisan about
```

Look for `Tpl\Shared\SharedServiceProvider` in the providers list.

### Composer Autoload Issues

Regenerate autoload files:

```bash
composer dump-autoload
```

## Development Workflow

When developing the package alongside your host app:

1. Use path repository with symlink:
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

2. Install with `@dev`:
   ```bash
   composer require tpl/shared:@dev
   ```

3. Changes to package PHP files reflect immediately
4. For frontend changes, rebuild:
   ```bash
   cd ../tpl-shared
   pnpm build
   ```

## Production Deployment

For production, always use a tagged version:

```bash
composer require tpl/shared:^0.1.0
```

Ensure your CI/CD has proper authentication for private repositories (see main README).

