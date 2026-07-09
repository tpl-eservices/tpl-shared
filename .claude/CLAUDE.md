<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance satisfaction building Laravel applications.

## Foundational Context
This is a **Laravel package** (not a standalone application). Key dependencies:

- php - ^8.4
- laravel/framework - v12
- larastan/larastan - v3
- laravel/pint - v1
- pestphp/pest - v4
- tailwindcss - v4 (frontend assets)

## Conventions
- Follow existing code conventions. Check sibling files for structure, approach, naming.
- Do not create new base folders or change dependencies without approval.

## Git Commits
- **Atomic commits**: One commit per logical change for easy tracing/reverting.
- **Commit before big refactors**: Especially files likely to be touched.
- **Conventional Commits** format: `type: subject`
  - Types: `feat`, `fix`, `test`, `refactor`, `docs`, `style`, `perf`, `chore`, `ci`, `build`, `revert`
  - Subject: lowercase start, no period, <52 chars
  - Body (when non-trivial): bullet points explaining key changes and rationale

**Never** add Claude ad a Coauthor for commits

Run `php artisan test -p` to quickly run parallel tests before committing nontrivial changes (especially on main).

## Frontend Bundling
- This project uses **pnpm** (npm/yarn are blocked)
- If the user doesn't see a frontend change reflected in the UI, they may need to run `pnpm build` or `pnpm dev`


=== boost rules ===

## Laravel Boost (if available)
If Laravel Boost MCP server is connected, use its tools:
- `search-docs` - Search Laravel ecosystem documentation
- `tinker` - Execute PHP to debug code or query models
- `list-artisan-commands` - Check available Artisan command parameters

Search documentation before making code changes to ensure the correct approach.


=== php rules ===

## PHP

### Static Analysis (PHPStan Level 8)
- All code must pass `vendor/bin/phpstan analyse` at level 8.
- Use PHPDoc for types that can't be expressed natively: `@return array{name: string, count: int}`, `@param Collection<int, User> $users`.
- Prefer constructor property promotion. No empty zero-parameter constructors.

### Comments
- Prefer PHPDoc over inline comments. Only use inline comments for complex logic or counterintuitive code needing rationale.


=== testing policy ===

## Testing Requirements

- **TDD encouraged**: Write or update tests for all changes, especially critical path functionality.
- All tests use Pest. Never remove tests without approval.
- Run minimal tests during development; offer to run full suite before finalizing.
- Ensure all tests pass before committing changes.


=== laravel/core rules ===

## Package Structure

This is a Laravel **package**, not an application:
- Source code lives in `src/` (not `app/`)
- Commands are in `src/Console/Commands/`
- Service provider: `src/SharedServiceProvider.php`
- Tests use Orchestra Testbench

### Creating New Classes
- For package classes, create them manually in `src/`
- Pass `--no-interaction` to Artisan commands when needed

### Vite Error
- If you receive a ViteException about manifest, run `pnpm build` or `pnpm dev`


=== laravel/v12 rules ===

## Laravel 12 Compatibility

This package targets Laravel 12.x host applications.

### Laravel 12 Gotchas
- **Migration columns**: When modifying a column, include ALL previous attributes or they'll be dropped.
- **Model casts**: Use `casts()` method, not `$casts` property. Follow existing model conventions.



=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== tailwind ===

## Tailwind CSS

This project uses **Tailwind v4.1+**. Do not use v3 syntax or deprecated utilities (e.g., `bg-opacity-*`, `!bg-red` prefix). Use `search-docs` for current patterns.

</laravel-boost-guidelines>
