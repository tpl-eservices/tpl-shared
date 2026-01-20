---
paths:
  - "app/Console/Commands/**"
  - "routes/console.php"
  - "tests/**/Console/**"
---

# Artisan Commands

Create commands with `php artisan make:command ToolName --no-interaction`.

## Naming Conventions

- **Signatures**: Use namespaced format `domain:action` (e.g., `icons:generate`, `cache:clear`)
- **Classes**: Match signature action in PascalCase + "Command" (e.g., `GenerateIconCommand`)
- **Descriptions**: Clear, actionable sentence starting with a verb

## Signature Design

### Short Option Aliases (Required)

Always provide short aliases for common options:

```php
protected $signature = 'icons:generate
    {name? : Icon name to generate}
    {--l|list : List available icons}
    {--s|search= : Search for icons by name}
    {--f|force : Overwrite existing files}
    {--d|dry-run : Show what would be done without making changes}';
```

### Help Text

Include inline descriptions for all arguments and options (shown above with `: description`).

## Laravel Prompts

Use Laravel Prompts for interactive input instead of basic `$this->ask()`:

```php
use function Laravel\Prompts\{text, select, confirm, search, progress, spin, table, info, warning, error};

// Text input with validation
$name = text('What is the icon name?', required: true);

// Selection from options
$format = select('Output format?', ['svg', 'png', 'webp'], default: 'svg');

// Searchable selection (great for large lists)
$icon = search(
    label: 'Search for an icon:',
    options: fn (string $value) => $this->searchIcons($value),
);

// Confirmation
if (confirm('Generate component file?', default: true)) {
    // ...
}

// Progress bar with callback
$results = progress('Generating icons...', $icons, fn ($icon) => $this->generate($icon));

// Spinner for async operations
$data = spin(fn () => $this->fetchRemoteData(), 'Fetching data...');

// Display tabular data
table(['Name', 'Status'], $rows);
```

## Non-Interactive Mode

### PromptsForMissingInput

Implement for auto-prompting when arguments are missing:

```php
use Illuminate\Contracts\Console\PromptsForMissingInput;

class GenerateIconCommand extends Command implements PromptsForMissingInput
{
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => fn () => search(
                label: 'Which icon?',
                options: fn ($value) => $this->searchIcons($value),
            ),
        ];
    }
}
```

### Force Flag

Use `--force` to skip confirmations in scripts/CI:

```php
if (!$this->option('force') && !confirm('Proceed?')) {
    return Command::SUCCESS;
}
```

### No-Interaction Handling

Symfony's `--no-interaction` (`-n`) is automatic. Check with `$this->input->isInteractive()`.

## Output & Exit Codes

```php
public function handle(): int
{
    // Use Laravel Prompts for styled output
    info('Processing...');
    warning('File already exists');
    error('Generation failed');

    // Always return explicit exit codes
    return Command::SUCCESS; // or Command::FAILURE
}
```

## Testability

### Delegate to Services

Keep commands thin - delegate logic to service/support classes:

```php
public function __construct(private IconGenerator $generator) {}

public function handle(): int
{
    $this->generator->generate($this->argument('name'));
    return Command::SUCCESS;
}
```

### Testing Commands

```php
use function Pest\Laravel\artisan;

it('generates an icon', function () {
    artisan('icons:generate', ['name' => 'check', '--force' => true])
        ->assertSuccessful()
        ->expectsOutput('Icon generated successfully');
});

it('prompts for missing arguments', function () {
    artisan('icons:generate')
        ->expectsSearch('Which icon?', search: 'che', answer: 'check')
        ->assertSuccessful();
});

it('lists available icons', function () {
    artisan('icons:generate', ['--list' => true])
        ->assertSuccessful()
        ->expectsTable(['Name', 'Category'], $expectedRows);
});
```
