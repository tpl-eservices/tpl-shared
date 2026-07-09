# Development Guide - TPL Shared Package

Complete guide for developing, building, and releasing the TPL Shared package.

## Prerequisites

- **PHP** 8.4+
- **Laravel** 12.x
- **Node.js** 20+
- **pnpm** (recommended) or npm
- **Git** with access to `tpl-eservices/tpl-shared`

---

## Development Setup

### Clone Repository

```bash
git clone https://github.com/tpl-eservices/tpl-shared.git
cd tpl-shared
```

### Install Dependencies

```bash
# Unix/Linux/Mac
make install

# Windows
.\build.ps1 install

# Or manually
composer install
pnpm install  # or npm install
```

### Development Server

```bash
# Start frontend development with hot reload
npm run dev  # or pnpm dev

# Start PHP development server (in separate terminal)
php artisan serve
```

---

## Code Quality

### PHP Code Formatting

```bash
# Format all PHP files
composer format

# Check formatting without fixing
vendor/bin/pint --test

# Using build scripts
make format      # Unix
.\build.ps1 format  # Windows
```

### JavaScript/TypeScript Code Quality

```bash
# Run ESLint with auto-fix
npm run lint

# Type checking
npm run types

# Format code with Prettier
npm run format

# Check formatting without fixing
npm run format:check
```

### Testing

```bash
# Run all tests
composer test

# Run with coverage
php artisan test --coverage

# Using build scripts
make test      # Unix
.\build.ps1 test  # Windows
```

---

## Build System

This package supports three build systems:

### 1. Makefile (Unix/Linux/Mac)

```bash
make help          # Show all commands
make status        # Check current state
make test          # Run tests
make format        # Format PHP code
make build         # Build assets
make release       # Full release workflow
make clean         # Clean artifacts
```

### 2. PowerShell Script (Windows)

```powershell
.\build.ps1 help
.\build.ps1 status
.\build.ps1 test
.\build.ps1 format
.\build.ps1 build
.\build.ps1 release
.\build.ps1 clean
```

### 3. Batch Script (Windows)

```cmd
build help
build status
build test
build format
build build
build release
build clean
```

### Building Assets

```bash
# Development build with hot reload
npm run dev

# Production build
npm run build

# SSR build (if needed)
npm run build:ssr

# Using build scripts
make build      # Unix
.\build.ps1 build  # Windows
```

---

## Version Management

The package follows [Semantic Versioning](https://semver.org/spec/v2.0.0/):

- **Patch (0.1.0 → 0.1.1):** Bug fixes, small improvements
- **Minor (0.1.0 → 0.2.0):** New features, backward-compatible changes
- **Major (0.1.0 → 1.0.0):** Breaking changes, major refactoring

### Quick Release (Recommended)

```bash
# Unix/Linux/Mac
make release

# Windows
.\build.ps1 release
```

This automatically:

1. ✅ Formats code with Laravel Pint
2. ✅ Commits formatting changes
3. ✅ Builds frontend assets
4. ✅ Creates patch version tag
5. ✅ Pushes to GitHub

### Manual Version Control

```bash
# Format code
make format

# Run tests
make test

# Create specific version
make tag-patch    # 0.1.0 → 0.1.1
make tag-minor    # 0.1.0 → 0.2.0
make tag-major    # 0.1.0 → 1.0.0

# Push to GitHub
make push
```

### Version Files

- `composer.json` - PHP package version
- `package.json` - Node.js package version
- Git tags - Release versions

---

## Project Structure

```
tpl-shared/
├── src/                          # Package source code
│   ├── Auth/                     # Authentication components
│   │   ├── BiblioUserProvider.php
│   │   └── BiblioGuard.php
│   ├── Services/                 # Service classes
│   │   ├── BiblioCommonsTemplateService.php
│   │   └── BiblioSsoService.php
│   ├── Utils/                    # Utility classes
│   │   └── CookieUtils.php
│   ├── Console/                  # Artisan commands
│   ├── Http/                     # HTTP middleware
│   ├── View/                     # Blade components & composers
│   └── SharedServiceProvider.php  # Main service provider
├── app/                          # Helper functions
│   └── helpers.php
├── config/                       # Configuration files
│   └── shared.php
├── resources/                    # Frontend assets & views
│   ├── css/
│   ├── js/
│   └── views/
├── routes/                       # Package routes
│   └── web.php
├── tests/                        # Test suite
│   ├── Feature/
│   └── Unit/
├── database/                     # Migrations & seeders
│   └── migrations/
├── public/                       # Built assets (generated)
├── node_modules/                 # Node dependencies
├── vendor/                      # Composer dependencies
├── Makefile                      # Unix build commands
├── build.ps1                     # PowerShell build script
└── build.bat                     # Batch build script
```

---

## Development Workflow

### 1. Local Development

```bash
# Make changes
# ... edit files ...

# Format code
make format

# Run tests
make test

# Check types
npm run types

# Lint code
npm run lint
```

### 2. Testing Changes

```bash
# Run full test suite
composer test

# Run specific test
php artisan test --filter=BiblioSsoTest

# Run with coverage
php artisan test --coverage
```

### 3. Building for Release

```bash
# Quick release (recommended)
make release

# Or manual process
make format
make test
make build
make tag-patch
make push
```

---

## Frontend Development

### Tech Stack

- **React** 19 with TypeScript
- **Inertia.js** v2 for routing
- **Tailwind CSS** v4 for styling
- **Vite** for bundling

### Available Scripts

```bash
npm run dev          # Start development server
npm run build        # Build for production
npm run build:ssr     # Build SSR version
npm run lint         # Run ESLint
npm run types        # TypeScript type checking
npm run format       # Format with Prettier
npm run format:check # Check formatting only
```

### Component Development

```typescript
// resources/js/components/ExampleComponent.tsx
import React from 'react';
import { cn } from '@/lib/utils';

interface ExampleProps {
    title: string;
    className?: string;
}

export default function ExampleComponent({ title, className }: ExampleProps) {
    return (
        <div className={cn('p-4 border rounded', className)}>
            <h2>{title}</h2>
        </div>
    );
}
```

### Using Components in Host Apps

```tsx
// After publishing assets
import { ExampleComponent } from '@/vendor/tpl-shared/js/components/ExampleComponent';

export default function Page() {
    return <ExampleComponent title="Hello World" />;
}
```

---

## Package Development Best Practices

### 1. Code Style

- Use Laravel Pint for PHP formatting
- Use ESLint + Prettier for JavaScript/TypeScript
- Follow PSR-12 standards for PHP
- Use TypeScript strict mode

### 2. Testing

- Write tests for all new features
- Aim for high test coverage
- Use Pest for readable tests
- Test both happy paths and error cases

### 3. Documentation

- Update documentation for API changes
- Include code examples in docs
- Update CHANGELOG.md for releases
- Document breaking changes

### 4. Version Management

- Use semantic versioning
- Update CHANGELOG.md with every release
- Tag releases in Git
- Push tags to GitHub

---

## Testing Strategies

### Unit Tests

Test individual classes and methods:

```php
use Tpl\Shared\Services\BiblioSsoService;

it('validates session correctly', function () {
    $service = new BiblioSsoService();
    $result = $service->validateSession('valid-session');

    expect($result)->toBeArray();
});
```

### Feature Tests

Test complete workflows:

```php
it('authenticates user via BiblioCommons', function () {
    $response = $this->get('/auth/biblio/callback');

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});
```

### Integration Tests

Test with external services:

```php
it('fetches BiblioCommons templates', function () {
    Http::fake([
        'api.bibliocommons.com/*' => Http::response([
            'header' => '<header>Test</header>',
            'footer' => '<footer>Test</footer>',
        ]),
    ]);

    $service = app(BiblioCommonsTemplateService::class);
    $templates = $service->getTemplateParts();

    expect($templates['header'])->toBe('<header>Test</header>');
});
```

---

## Troubleshooting Development Issues

### Common Issues

#### Build Failures

```bash
# Clear and reinstall
make clean
make install

# Check Node version
node --version  # Should be 20+

# Clear npm cache
npm cache clean --force
```

#### Test Failures

```bash
# Run tests with verbose output
composer test -v

# Check database configuration
php artisan config:cache

# Clear caches
php artisan cache:clear
php artisan config:clear
```

#### Asset Build Issues

```bash
# Check Vite configuration
cat vite.config.ts

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install

# Check TypeScript errors
npm run types
```

#### Version Conflicts

```bash
# Check current version
make status

# Update version files
make update-version

# Fix version mismatch
make tag-patch
```

### Debug Commands

```bash
# Show current status
make status

# Diagnose BiblioCommons
php artisan bibliocommons:diagnose

# Check environment
php artisan env
```

---

## Advanced Development

### Custom Service Provider

Extend the package functionality:

```php
namespace App\Providers;

use Tpl\Shared\SharedServiceProvider as BaseProvider;

class CustomSharedServiceProvider extends BaseProvider
{
    public function register()
    {
        parent::register();

        // Register custom services
        $this->app->singleton('custom.service', function ($app) {
            return new CustomService();
        });
    }
}
```

### Custom Commands

Create package-specific commands:

```php
namespace Tpl\Shared\Console;

use Illuminate\Console\Command;

class CustomCommand extends Command
{
    protected $signature = 'tpl-shared:custom';
    protected $description = 'Custom command description';

    public function handle()
    {
        $this->info('Custom command executed!');
    }
}
```

### Package Testing in Host Apps

#### Symlink Method (Development)

In host app's `composer.json`:

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

```bash
composer require tpl/shared:@dev
```

#### Local Repository Method

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "/path/to/local/tpl-shared"
        }
    ]
}
```

---

## Performance Optimization

### Frontend Optimization

```bash
# Build with analysis
npm run build -- --analyze

# Optimize bundle size
npm run build -- --minify

# Generate service worker (if using)
npm run build:ssr
```

### PHP Optimization

```bash
# Optimize autoloader
composer dump-autoload --optimize

# Cache configuration
php artisan config:cache
php artisan route:cache
```

### Testing Performance

```bash
# Run tests in parallel
php artisan test --parallel

# Profile tests
php artisan test --profile
```

---

## Security Considerations

### Development Security

1. **Environment Variables** - Never commit `.env` files
2. **API Keys** - Use placeholder values in docs
3. **Dependencies** - Regularly audit for vulnerabilities
4. **Code Review** - All changes should be reviewed

### Production Security

1. **HTTPS** - Always use HTTPS in production
2. **CORS** - Configure proper CORS headers
3. **Rate Limiting** - Implement API rate limits
4. **Input Validation** - Validate all user inputs

---

## Contributing Guidelines

### 1. Fork and Clone

```bash
git clone https://github.com/your-username/tpl-shared.git
cd tpl-shared
```

### 2. Create Feature Branch

```bash
git checkout -b feature/your-feature-name
```

### 3. Make Changes

- Follow code style guidelines
- Write comprehensive tests
- Update documentation
- Keep commits atomic

### 4. Test Changes

```bash
make test
make format
make build
```

### 5. Submit Pull Request

```bash
git push origin feature/your-feature-name
# Create pull request on GitHub
```

### Pull Request Requirements

- [ ] All tests pass
- [ ] Code is formatted
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Breaking changes documented

---

## Deployment

### Pre-Release Checklist

- [ ] All tests passing
- [ ] Code formatted
- [ ] Assets built
- [ ] Version updated
- [ ] Documentation updated
- [ ] CHANGELOG.md updated

### Release Process

```bash
# Quick release
make release

# Verify release
git tag -l
make status
```

### Post-Release

- [ ] Update GitHub release notes
- [ ] Notify team of changes
- [ ] Update external documentation
- [ ] Monitor for issues

---

## Tools and Resources

### Development Tools

- **PHPStorm/VSCode** - IDE with Laravel support
- **Laravel Telescope** - Debugging assistant
- **Laravel Debugbar** - Debug toolbar
- **Postman** - API testing
- **Git** - Version control

### Useful Commands

```bash
# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models

# Clear all caches
make clean

# Rebuild everything
make clean && make install

# Check dependencies
composer outdated
npm outdated
```

### Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev/)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)

---

## Need Help?

### Internal Resources

- **Documentation:** [../](../) directory
- **Change Log:** `CHANGELOG.md`
- **Issues:** GitHub issues page
- **Team:** Contact development team

### External Resources

- **Laravel Support:** Laravel Discord, forums
- **Package Issues:** GitHub issues
- **Community:** Laravel community channels

---

**Happy coding! 🚀**

Built with ❤️ for Toronto Public Library
