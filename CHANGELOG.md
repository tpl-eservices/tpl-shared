# Changelog

All notable changes to `tpl/shared` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Mock Authentication Service** ⭐ NEW
  - `FakeBiblioSsoService` for local development and testing without real API
  - Enable with `BIBLIOCOMMONS_MOCK_ENABLED=true` in `.env`
  - Configurable mock user data via `services.bibliocommons.mock.*` settings
  - Automatic security block in production environments
  - Logged with `[MOCK]` prefix for visibility
- **PHPStan Level 8 Static Analysis**
  - Added larastan for Laravel-aware static analysis
  - All code passes PHPStan at strictest level (level 8)
  - CI workflow step for continuous static analysis
- **Expanded Test Coverage**
  - 106 tests with 217 assertions (all passing)
  - New tests for BiblioGuard, BiblioUserProvider, and middleware
  - Comprehensive auth component coverage
- **Developer Experience**
  - Enforce pnpm as package manager (npm/yarn blocked with helpful message)
  - Added `packageManager` field for Corepack compatibility
  - Claude rules and repo instructions for AI-assisted development

### Changed
- **CI Workflow Consolidation**
  - Merged duplicate workflows into single `ci.yml`
  - Removed redundant `lint.yml` and `tests.yml`
  - pnpm version now read from `package.json` packageManager field

### Fixed
- **Windows Path Compatibility** - Fixed `tpl-shared:install` command failing on Windows with "mkdir(): No such file or directory" error
  - Properly normalize Windows path separators (backslashes) to forward slashes when creating backup directories
  - Added test coverage for Windows path handling
- **PHPUnit Configuration** - Removed reference to deleted `tests/Unit` directory

### Removed
- Boilerplate app tests that don't apply to this package
- `package-lock.json` in favor of `pnpm-lock.yaml`
- Duplicate CI workflow files

## [0.1.x] - Previous Releases

### Added
- **BiblioCommons SSO Integration**
  - `BiblioSsoService` for authenticating users via BiblioCommons API
  - Methods: `validateSession()`, `fetchBorrowerInfo()`, `fetchUserProfile()`
  - Configuration-driven with retry logic and error handling
  - Registered as singleton service
- **Cookie Utilities**
  - `CookieUtils` class for reading raw (unencrypted) cookies
  - Static methods: `getRaw()`, `hasRaw()`, `getRawMany()`
  - Global helper function: `getRawCookie()`
  - Essential for reading cookies from external systems
- **Authentication Provider & Guard**
  - `BiblioUserProvider` - Custom Laravel user provider for BiblioCommons
  - `BiblioGuard` - Custom authentication guard that reads cookies
  - Automatic user creation/update in local database
  - Works with Laravel's standard `Auth` facade
  - Zero-code authentication for host apps
- **Comprehensive Documentation**
  - `BIBLIOSSO_USAGE.md` - Complete BiblioCommons SSO usage guide
  - `BIBLIOSSO_IMPLEMENTATION.md` - Implementation details and architecture
  - `AUTH_PROVIDER_GUIDE.md` - Authentication provider setup and usage
  - Updated README with complete documentation index

## [0.1.0] - 2025-12-02

### Added
- Initial package structure
- Service provider with auto-discovery
- Configuration file publishing
- Views and Blade components
- Frontend assets (React components, CSS)
- Database migrations
- Route definitions
- Comprehensive test suite with Pest
- Laravel Pint for code formatting
- GitHub Actions CI/CD workflow
- README with installation and usage instructions

### Infrastructure
- PHP 8.4+ support
- Laravel 12.x compatibility
- React 19 with Inertia v2
- Tailwind CSS v4
- TypeScript support
- ESLint and Prettier configuration

