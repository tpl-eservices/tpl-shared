# AGENTS.md - Development Guidelines for TPL Shared Package

This file contains guidelines and commands for agentic coding agents working on the TPL Shared Laravel package.

## Project Overview

TPL Shared is a **distributable Laravel package** that provides common components, views, assets, and integrations for TPL (Toronto Public Library) projects. This package is designed to be **shared across multiple Laravel 12 host applications** and includes BiblioCommons SSO integration, React components with Inertia.js, and shared frontend assets.

**Important:** This is a package, not a standalone Laravel application. All code must be developed with package distribution in mind.

## Documentation Structure

The package documentation has been reorganized into `/docs/`:

```
docs/
├── README.md                    # Complete documentation index
├── installation/
│   └── README.md               # Installation and setup
├── features/
│   └── bibliocommons.md        # BiblioCommons integration
├── development/
│   ├── README.md               # Development workflow
│   └── VERSION_MANAGEMENT.md   # Version management
└── troubleshooting/
    └── README.md               # Issue resolution
```

**Primary Documentation:** [docs/README.md](docs/README.md)

**Tech Stack:**

- PHP 8.4+ with Laravel 12.x
- React 19 with TypeScript
- Inertia.js v2 for frontend routing
- Tailwind CSS v4 for styling
- Vite for asset bundling
- ESLint + Prettier for code formatting

## Build Commands

### Development

```bash
# Start development server with hot reload
npm run dev

# Build assets for production
npm run build

# Build SSR version (if needed)
npm run build:ssr
```

### Package Development (Artisan Commands)

```bash
# Complete release workflow
php artisan tpl-shared:build release

# Individual version management
php artisan tpl-shared:build tag-patch    # 0.1.0 → 0.1.1
php artisan tpl-shared:build tag-minor    # 0.1.0 → 0.2.0
php artisan tpl-shared:build tag-major    # 0.1.0 → 1.0.0

# Development commands
php artisan tpl-shared:build test         # Run tests
php artisan tpl-shared:build format       # Format PHP code
php artisan tpl-shared:build build        # Build frontend assets
php artisan tpl-shared:build status       # Check current state
php artisan tpl-shared:build push         # Push to GitHub
php artisan tpl-shared:build clean        # Clean dependencies
php artisan tpl-shared:build install      # Install dependencies

# Show help
php artisan tpl-shared:build help
```

### Legacy Build Scripts (Deprecated)

The following scripts have been replaced by the Artisan command and are deprecated:

- Unix/Linux: `make` commands → `php artisan tpl-shared:build`
- Windows PowerShell: `.\build.ps1` → `php artisan tpl-shared:build`
- Windows Batch: `build.bat` → `php artisan tpl-shared:build`

The Artisan command provides identical functionality with better cross-platform compatibility.

### Code Quality

```bash
# Run ESLint with auto-fix
npm run lint

# Type checking with TypeScript
npm run types

# Format code with Prettier
npm run format

# Check formatting without fixing
npm run format:check
```

### PHP Code Quality

```bash
# Format PHP code using Laravel Pint
composer format

# Run PHP tests
composer test

# Check formatting without fixing
vendor/bin/pint --test
```

### Testing

```bash
# Run all tests
composer test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --filter=BiblioSso

# Test coverage includes:
# - BiblioSsoService: 8 tests
# - CookieUtils: 10 tests
# - Integration: 4 tests
# - Total: 22+ tests (all passing)
```

## Code Style Guidelines

### TypeScript/JavaScript

#### Imports

- Use named imports for better tree-shaking: `import { cn } from '@/lib/utils'`
- Group imports: external libraries first, then internal modules
- Use `@/` alias for resources/js directory

```typescript
// ✅ Good
import { Head, Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { useIsMobile } from '@/hooks/use-mobile';

// ❌ Avoid default imports unless necessary
import React from 'react';
```

#### Component Structure

- Use functional components with TypeScript interfaces for props
- Export components as default: `export default function ComponentName()`
- Use React 19+ automatic JSX runtime (no React import needed for JSX)

```typescript
interface WelcomeProps {
    canRegister?: boolean;
}

export default function Welcome({ canRegister = true }: WelcomeProps) {
    // Component logic
}
```

#### Types

- Use explicit return types for functions
- Prefer interfaces over types for object shapes
- Use generic types where appropriate

```typescript
interface User {
    id: number;
    name: string;
    email: string;
}

function fetchUser(id: number): Promise<User> {
    // Implementation
}
```

### CSS/Styling

#### Tailwind CSS v4

- Use Tailwind v4 CSS-first configuration with `@theme` directive
- Import Tailwind with `@import "tailwindcss"` instead of `@tailwind` directives
- Use gap utilities for spacing instead of margins in flex/grid layouts
- Support dark mode with `dark:` prefixes when existing components do

```css
/* ✅ Tailwind v4 */
@import 'tailwindcss';

@theme {
    --color-brand: oklch(0.72 0.11 178);
}
```

#### Component Styling

- Use the `cn()` utility from `@/lib/utils` for conditional classes
- Follow existing component patterns for consistency
- Remove redundant classes and leverage Tailwind's utility-first approach

```typescript
import { cn } from '@/lib/utils';

const buttonClasses = cn(
    'inline-block rounded-sm border px-5 py-1.5 text-sm',
    'hover:border-[#1915014a]',
    'dark:border-[#3E3E3A] dark:hover:border-[#62605b]',
);
```

### File Organization

#### Frontend Structure

```
resources/js/
├── app.tsx                 # Main Inertia app entry
├── pages/                  # Inertia page components
├── components/             # Reusable React components
├── hooks/                  # Custom React hooks
├── lib/                    # Utility functions
├── types/                  # TypeScript type definitions
└── routes.ts              # Frontend route definitions
```

#### Naming Conventions

- Use PascalCase for components: `UserProfile.tsx`
- Use camelCase for functions and variables: `useIsMobile()`
- Use kebab-case for file names (except components): `user-utils.ts`
- Use descriptive names: `isRegisteredForDiscounts` not `discount()`

### Error Handling

#### Frontend

- Use try-catch blocks for async operations
- Provide meaningful error messages to users
- Log errors appropriately for debugging

```typescript
try {
    const response = await fetch('/api/user');
    const data = await response.json();
    return data;
} catch (error) {
    console.error('Failed to fetch user:', error);
    throw new Error('Unable to load user data');
}
```

#### Backend (PHP)

- Use proper exception handling with try-catch blocks
- Return meaningful error responses
- Log errors for debugging while avoiding sensitive data exposure

### React/Inertia.js Best Practices

#### Navigation

- Use `<Link>` component for client-side navigation
- Use `router.visit()` for programmatic navigation
- Import routes from `@/routes` for type safety

```typescript
import { Link } from '@inertiajs/react';
import { dashboard, login } from '@/routes';

<Link href={dashboard()}>Dashboard</Link>
```

#### Forms

- Use Inertia's `<Form>` component for form submissions
- Handle loading states and errors properly
- Use proper form validation

```typescript
import { Form } from '@inertiajs/react';

<Form action="/users" method="post">
    {({ processing, errors }) => (
        // Form content
    )}
</Form>
```

#### State Management

- Use React hooks for local state
- Use Inertia's shared props for global state
- Create custom hooks for complex logic

### Package-Specific Guidelines

#### Package Development Principles

- **Distributable Architecture**: All code must work as a standalone package that can be installed via Composer in multiple host applications
- **Namespace Isolation**: Use the `Tpl\Shared` namespace for all PHP classes to avoid conflicts with host applications
- **Asset Publishing**: Frontend assets must be publishable to host applications using Laravel's vendor:publish command
- **Configuration**: Package settings should be configurable through published config files and environment variables
- **Service Provider**: All package services must be registered through the SharedServiceProvider

#### Automated Installation (NEW)

- Package includes automated install command: `php artisan tpl-shared:install`
- Automatically configures services.php, auth.php, middleware, and User model
- Creates backups of all modified files during installation
- Tracks installation status for proper uninstallation

#### Cookie Utilities for External Systems

- Use CookieUtils for reading raw (unencrypted) cookies from external systems like BiblioCommons
- Laravel encrypts cookies by default - bypass encryption for external cookies
- Global helper: `getRawCookie('cookieName')` available

#### BiblioCommons Integration

- Use the BiblioSsoService for authentication
- Handle BiblioCommons template caching properly
- Use CookieUtils for reading external cookies

```php
use Tpl\Shared\Services\BiblioSsoService;

$profile = $biblioSso->fetchUserProfile($sessionId);
```

#### Component Development

- Check for existing components before creating new ones
- Follow established patterns from sibling files
- Use proper TypeScript interfaces for props
- **Blade Components**: Use the `x-tpl-shared::` prefix for all Blade components to avoid namespace conflicts
- **React Components**: Ensure components are self-contained and don't rely on host application state

#### Asset Management

- Use Vite for asset bundling and optimization
- Reference assets using the manifest when needed
- Build assets before committing if changes require it
- **Asset Publishing**: All CSS/JS assets must be configured for publishing to host applications
- **Versioned Assets**: Use hashed asset names for cache busting in distributed environments

## Development Workflow

1. **Before Making Changes**
    - Read existing code to understand patterns
    - Check for similar components/functionality to reuse
    - Run existing tests to ensure baseline

2. **During Development**
    - Use `npm run dev` for hot reloading
    - Run `npm run types` frequently to catch type errors
    - Use `npm run lint` to maintain code quality

3. **Before Committing**
    - Run `npm run build` to ensure assets compile
    - Run `npm run lint` and `npm run types`
    - Test your changes thoroughly
    - Update documentation if needed

## Configuration Files

### Key Config Files

- `vite.config.ts` - Vite bundler configuration with React and Tailwind
- `tsconfig.json` - TypeScript configuration with strict mode enabled
- `eslint.config.js` - ESLint configuration with React and TypeScript rules
- `package.json` - Dependencies and scripts

### Path Aliases

- `@/` maps to `resources/js/` for clean imports

## Testing Guidelines

When writing tests:

- Follow existing test patterns in the codebase
- Test both happy paths and error scenarios
- Use descriptive test names
- Mock external dependencies appropriately

## Common Patterns

### Utility Functions

```typescript
// Use the cn() utility for conditional classes
import { cn } from '@/lib/utils';

const className = cn(
    'base-class',
    condition && 'conditional-class',
    props.variant === 'primary' && 'primary-styles',
);
```

### Custom Hooks

```typescript
// Follow the useMobile hook pattern
export function useCustomHook() {
    const [state, setState] = useState(initialState);

    // Hook logic

    return state;
}
```

### API Calls

```typescript
// Use proper error handling and typing
interface ApiResponse<T> {
    data: T;
    message?: string;
}

async function fetchData<T>(url: string): Promise<T> {
    const response = await fetch(url);

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return response.json();
}
```

## Cursor/Copilot Rules Integration

This project follows Laravel Boost guidelines with specific focus on:

- Laravel 12 conventions and structure
- Inertia.js v2 best practices
- React 19 with TypeScript patterns
- Tailwind CSS v4 utility-first styling
- Modern ESLint/Prettier code formatting

## Package Development Guidelines

### Laravel Package Best Practices

#### Service Provider Registration

- All package functionality must be registered in `SharedServiceProvider`
- Use conditional registration to avoid conflicts with host applications
- Register package-specific middleware, views, and routes

#### Configuration Management

- Provide sensible defaults for all configuration options
- Use environment variables for sensitive data
- Allow host applications to override configuration through published config files

#### Asset Publishing Strategy

- Tag all publishable assets appropriately (css, js, views, config)
- Use versioned asset names for cache busting
- Provide clear documentation for asset publishing in host applications

#### Database Considerations

- Package migrations should be optional and clearly documented
- Use table prefixes to avoid conflicts with host application tables
- Provide migration rollback capabilities

### Host Application Integration

#### Installation Requirements

- Document minimum Laravel version requirements (12.x)
- Specify required PHP extensions and dependencies
- Provide clear installation instructions for host applications

#### Private Repository Setup

This is a private package requiring GitHub authentication:

```bash
# One-time GitHub authentication setup
composer config --global github-oauth.github.com YOUR_TOKEN_HERE

# Add repository to host application composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/tpl-eservices/tpl-shared.git"
        }
    ]
}

# Install package in host application
composer require tpl/shared:^0.1.0
```

#### Automated Installation Process

Host applications can use the automated install command:

```bash
php artisan tpl-shared:install
```

This automatically configures:

- `config/services.php` with BiblioCommons settings
- `config/auth.php` with biblio guard and provider
- Middleware registration in `bootstrap/app.php`
- User model updates for stateless authentication
- Environment variables in `.env`
- Backups of all modified files
- Installation status tracking

#### Configuration Steps

- List all required environment variables for host applications
- Document service provider registration steps
- Provide examples of configuration file customization

#### Usage Examples

- Show how to use package components in host application views
- Demonstrate proper service injection and usage patterns
- Include examples of common integration scenarios

## Important Notes

- This is a private package requiring GitHub authentication
- Always check existing components before creating new ones
- Follow Laravel package development conventions
- Maintain backward compatibility when possible
- Use semantic versioning for releases
- **Never assume host application structure** - package must be self-contained
- **Test package installation** in fresh Laravel applications before releases
- **Document all breaking changes** in CHANGELOG.md with migration instructions

## Quick Reference

```bash
# Development
npm run dev              # Start dev server
npm run build            # Build for production
npm run lint             # Fix linting issues
npm run types            # Check TypeScript types
npm run format           # Format code with Prettier

# Common patterns
import { cn } from '@/lib/utils';           # Utility for classes
import { Link } from '@inertiajs/react';    # Navigation
import { dashboard } from '@/routes';        # Route imports
```

## Available Commands for Host Applications

```bash
# Diagnose BiblioCommons configuration and connectivity
php artisan bibliocommons:diagnose

# Clear BiblioCommons template cache
php artisan tpl-shared:clear-cache

# Publish package assets
php artisan vendor:publish --tag=tpl-shared-assets
php artisan vendor:publish --tag=tpl-shared-config
php artisan vendor:publish --tag=tpl-shared-views

# Build management (for package development)
php artisan tpl-shared:build help        # Show all build commands
```

## Environment Variables

Host applications must configure these environment variables:

```env
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com
BIBLIOCOMMONS_API_KEY=your-actual-api-key
BIBLIOCOMMONS_LIBRARY_ID=tpl
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates
```

## Package Development Checklist

### Before Publishing New Version

- [ ] Test package installation in fresh Laravel application
- [ ] Verify all assets publish correctly
- [ ] Test configuration file publishing
- [ ] Ensure all migrations work independently
- [ ] Update CHANGELOG.md with breaking changes
- [ ] Update version number in package.json
- [ ] Build and commit assets if needed

### Version Management Workflow

```bash
# Using Artisan command (recommended)
php artisan tpl-shared:build tag-patch    # 0.1.0 → 0.1.1
php artisan tpl-shared:build tag-minor    # 0.1.0 → 0.2.0
php artisan tpl-shared:build tag-major    # 0.1.0 → 1.0.0

# Legacy scripts (deprecated)
# make tag-patch (Unix/Linux)  → php artisan tpl-shared:build tag-patch
# .\build.ps1 tag-patch (Windows) → php artisan tpl-shared:build tag-patch
```

### Host Application Testing

- [ ] Install package in clean Laravel project
- [ ] Run `php artisan vendor:publish --tag=tpl-shared-assets`
- [ ] Test all published components work correctly
- [ ] Verify configuration options function
- [ ] Test BiblioCommons integration (if applicable)
- [ ] Check for namespace conflicts with host application
