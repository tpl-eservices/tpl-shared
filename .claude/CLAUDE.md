<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- livewire/volt (VOLT) - v1
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

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
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Search Syntax
- Pass multiple queries at once: `queries=["authentication", "middleware"]`
- Multiple words use AND logic; quoted phrases match exactly: `middleware "rate limit"`


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

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Prefer `Model::query()` over `DB::`. Use eager loading to prevent N+1 problems.

### Model Creation
- When creating models, also create factories and seeders. Use `list-artisan-commands` to check `make:model` options.

### Controllers & Validation
- Use Form Request classes for validation (not inline). Check sibling Form Requests for array vs string rule format.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12+ Structure

- **No `app/Console/Kernel.php`** - use `bootstrap/app.php` or `routes/console.php`
- **No `app/Http/Middleware/`** - middleware registered in `bootstrap/app.php`
- **Commands auto-register** - files in `app/Console/Commands/` just work
- **Providers** in `bootstrap/providers.php`

## Laravel 12 Gotchas

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
