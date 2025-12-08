# Changelog

All notable changes to `tpl/shared` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
- **Comprehensive Documentation**
  - `BIBLIOSSO_USAGE.md` - Complete BiblioCommons SSO usage guide
  - `BIBLIOSSO_IMPLEMENTATION.md` - Implementation details and architecture
  - Updated README with complete documentation index
- **Test Coverage**
  - 22 new tests for BiblioSSO and CookieUtils (all passing)
  - Integration tests showing real-world usage patterns

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

