<?php

namespace Tpl\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallTplShared extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tpl-shared:install {--force : Force reinstallation even if already installed}';

    /**
     * The console command description.
     */
    protected $description = 'Install TPL Shared package - configures BiblioCommons templates, auth, middleware, and SSO';

    /**
     * Backup directory for modified files.
     */
    protected string $backupDir;

    /**
     * Installation timestamp.
     */
    protected string $timestamp;

    /**
     * Installation status tracking.
     */
    protected array $status = [
        'services_config' => false,
        'auth_config' => false,
        'bootstrap_app' => false,
        'user_model' => false,
        'env_file' => false,
    ];

    /**
     * Modified files list.
     */
    protected array $modifiedFiles = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->timestamp = now()->format('Y-m-d_His');
        $this->backupDir = storage_path("backups/tpl-shared/{$this->timestamp}");

        $this->components->info('TPL Shared Package Installation');
        $this->newLine();

        // Check if already installed
        if ($this->isAlreadyInstalled() && ! $this->option('force')) {
            $this->components->warn('TPL Shared is already installed!');
            $this->newLine();
            $this->showInstallationStatus();
            $this->newLine();

            if (! $this->components->confirm('Would you like to reinstall anyway?', false)) {
                $this->components->info('Installation cancelled.');

                return Command::SUCCESS;
            }
        }

        // Create backup directory
        File::ensureDirectoryExists($this->backupDir);

        $this->components->info('Starting installation...');
        $this->newLine();

        // Step 1: Modify config/services.php
        $this->installServicesConfig();

        // Step 2: Modify config/auth.php
        $this->installAuthConfig();

        // Step 3: Modify bootstrap/app.php
        $this->installBootstrapApp();

        // Step 4: Modify app/Models/User.php
        $this->installUserModel();

        // Step 5: Update .env file
        $this->installEnvVariables();

        // Save installation status
        $this->saveInstallationStatus();

        // Show results
        $this->newLine();
        $this->showInstallationSummary();

        return Command::SUCCESS;
    }

    /**
     * Check if package is already installed.
     */
    protected function isAlreadyInstalled(): bool
    {
        $statusFile = base_path('config/tpl-shared-installed.php');

        if (! File::exists($statusFile)) {
            return false;
        }

        $status = require $statusFile;

        return isset($status['installed']) && $status['installed'] === true;
    }

    /**
     * Install BiblioCommons configuration in config/services.php.
     */
    protected function installServicesConfig(): void
    {
        $configFile = base_path('config/services.php');
        $this->components->task('Configuring config/services.php', function () use ($configFile) {
            if (! File::exists($configFile)) {
                $this->createStubFile('services.config.stub', 'config/examples/tpl-shared/services.php');

                return false;
            }

            $content = File::get($configFile);

            // Check if already configured
            if (str_contains($content, "'bibliocommons'")) {
                $this->status['services_config'] = 'skipped';

                return true;
            }

            // Create backup
            $this->createBackup($configFile);

            // Insert BiblioCommons configuration
            $bibliocommonsConfig = $this->getBiblioCommonsConfigBlock();

            // Find the last item in the return array and add after it
            $pattern = '/(return\s*\[.*?)(\];)/s';
            if (preg_match($pattern, $content, $matches)) {
                $newContent = $matches[1]."\n".$bibliocommonsConfig."\n".$matches[2];
                File::put($configFile, $newContent);
                $this->modifiedFiles[] = $configFile;
                $this->status['services_config'] = 'modified';

                return true;
            }

            // Fallback to stub
            $this->createStubFile('services.config.stub', 'config/examples/tpl-shared/services.php');

            return false;
        });
    }

    /**
     * Install BiblioCommons auth configuration in config/auth.php.
     */
    protected function installAuthConfig(): void
    {
        $configFile = base_path('config/auth.php');
        $this->components->task('Configuring config/auth.php', function () use ($configFile) {
            if (! File::exists($configFile)) {
                $this->createStubFile('auth.config.stub', 'config/examples/tpl-shared/auth.php');

                return false;
            }

            $content = File::get($configFile);

            // Check if already configured
            if (str_contains($content, "'biblio'") && str_contains($content, "driver' => 'biblio'")) {
                $this->status['auth_config'] = 'skipped';

                return true;
            }

            // Create backup
            $this->createBackup($configFile);

            $modified = false;

            // Add guard
            if (! str_contains($content, "'biblio' =>") || $this->option('force')) {
                $guardConfig = $this->getBiblioGuardConfig();
                $content = $this->insertIntoArray($content, "'guards'", $guardConfig);
                $modified = true;
            }

            // Add provider
            if (! str_contains($content, "driver' => 'biblio'") || $this->option('force')) {
                $providerConfig = $this->getBiblioProviderConfig();
                $content = $this->insertIntoArray($content, "'providers'", $providerConfig);
                $modified = true;
            }

            if ($modified) {
                File::put($configFile, $content);
                $this->modifiedFiles[] = $configFile;
                $this->status['auth_config'] = 'modified';

                return true;
            }

            $this->status['auth_config'] = 'skipped';

            return true;
        });
    }

    /**
     * Install middleware registration in bootstrap/app.php.
     */
    protected function installBootstrapApp(): void
    {
        $bootstrapFile = base_path('bootstrap/app.php');
        $this->components->task('Configuring bootstrap/app.php', function () use ($bootstrapFile) {
            if (! File::exists($bootstrapFile)) {
                $this->createStubFile('bootstrap.app.stub', 'config/examples/tpl-shared/app.php');

                return false;
            }

            $content = File::get($bootstrapFile);

            // Check if already configured
            if (str_contains($content, 'biblio.auth') && str_contains($content, 'AuthenticateBiblioCommons')) {
                $this->status['bootstrap_app'] = 'skipped';

                return true;
            }

            // Create backup
            $this->createBackup($bootstrapFile);

            // Insert middleware alias
            $middlewareAlias = $this->getMiddlewareAliasBlock();

            // Find withMiddleware callback and insert alias
            if (str_contains($content, '->withMiddleware(')) {
                // Pattern to find withMiddleware callback
                $pattern = '/(->withMiddleware\(function\s*\(\$?middleware\)\s*(?::\s*void\s*)?\{)(.*?)(\}\))/s';

                if (preg_match($pattern, $content, $matches)) {
                    $callbackContent = $matches[2];

                    // Check if alias method already exists
                    if (! str_contains($callbackContent, '$middleware->alias(')) {
                        // Add alias method call
                        $newCallback = $callbackContent."\n".$middlewareAlias;
                    } else {
                        // Insert into existing alias array
                        $aliasPattern = '/(\$middleware->alias\(\[)(.*?)(\]\);)/s';
                        if (preg_match($aliasPattern, $callbackContent, $aliasMatches)) {
                            $existingAliases = $aliasMatches[2];
                            $newAliases = $existingAliases."\n            'biblio.auth' => \\Tpl\\Shared\\Http\\Middleware\\AuthenticateBiblioCommons::class,";
                            $newCallback = preg_replace($aliasPattern, '$1'.$newAliases.'$3', $callbackContent);
                        } else {
                            $newCallback = $callbackContent."\n".$middlewareAlias;
                        }
                    }

                    $newContent = $matches[1].$newCallback.$matches[3];
                    $content = preg_replace($pattern, $newContent, $content);

                    File::put($bootstrapFile, $content);
                    $this->modifiedFiles[] = $bootstrapFile;
                    $this->status['bootstrap_app'] = 'modified';

                    return true;
                }
            }

            // Fallback to stub
            $this->createStubFile('bootstrap.app.stub', 'config/examples/tpl-shared/app.php');

            return false;
        });
    }

    /**
     * Install User model modifications.
     */
    protected function installUserModel(): void
    {
        $userModel = base_path('app/Models/User.php');
        $this->components->task('Configuring app/Models/User.php', function () use ($userModel) {
            if (! File::exists($userModel)) {
                $this->createStubFile('User.model.stub', 'config/examples/tpl-shared/User.php');

                return false;
            }

            $content = File::get($userModel);

            // Check if already configured
            if (str_contains($content, '// TPL Shared - Stateless Authentication')) {
                $this->status['user_model'] = 'skipped';

                return true;
            }

            // Create backup
            $this->createBackup($userModel);

            // Insert stateless properties
            $properties = $this->getUserModelProperties();

            // Find class declaration and insert after it
            $pattern = '/(class\s+User\s+extends\s+\w+\s*\{)/';
            if (preg_match($pattern, $content, $matches)) {
                $newContent = preg_replace(
                    $pattern,
                    $matches[1]."\n".$properties,
                    $content
                );

                File::put($userModel, $newContent);
                $this->modifiedFiles[] = $userModel;
                $this->status['user_model'] = 'modified';

                return true;
            }

            // Fallback to stub
            $this->createStubFile('User.model.stub', 'config/examples/tpl-shared/User.php');

            return false;
        });
    }

    /**
     * Install environment variables in .env file.
     */
    protected function installEnvVariables(): void
    {
        $envFile = base_path('.env');
        $this->components->task('Configuring .env file', function () use ($envFile) {
            if (! File::exists($envFile)) {
                $this->components->warn('.env file not found');

                return false;
            }

            $content = File::get($envFile);

            // Check if already configured
            if (str_contains($content, '# TPL Shared - BiblioCommons Configuration')) {
                $this->status['env_file'] = 'skipped';

                return true;
            }

            // Append BiblioCommons environment variables
            $envBlock = $this->getEnvVariablesBlock();
            File::append($envFile, "\n".$envBlock);

            // Create .env.tpl-shared.example
            $this->createEnvExample();

            $this->modifiedFiles[] = $envFile;
            $this->status['env_file'] = 'modified';

            return true;
        });
    }

    /**
     * Create backup of a file.
     */
    protected function createBackup(string $filePath): void
    {
        // Normalize paths to use forward slashes for cross-platform compatibility
        $normalizedFilePath = str_replace('\\', '/', $filePath);
        $normalizedBasePath = str_replace('\\', '/', base_path());

        // Remove base path to get relative path
        $relativePath = str_replace($normalizedBasePath.'/', '', $normalizedFilePath);

        // Build backup path using forward slashes
        $backupPath = str_replace('\\', '/', $this->backupDir).'/'.$relativePath;

        File::ensureDirectoryExists(dirname($backupPath));
        File::copy($filePath, $backupPath);
    }

    /**
     * Create stub file when automatic modification fails.
     */
    protected function createStubFile(string $stubName, string $destination): void
    {
        $stubContent = $this->getStubContent($stubName);
        $destinationPath = base_path($destination);

        File::ensureDirectoryExists(dirname($destinationPath));
        File::put($destinationPath, $stubContent);

        $this->components->warn("Created example file at: {$destination}");
    }

    /**
     * Save installation status to config file.
     */
    protected function saveInstallationStatus(): void
    {
        $statusFile = base_path('config/tpl-shared-installed.php');

        $content = "<?php\n\nreturn [\n";
        $content .= "    'installed' => true,\n";
        $content .= "    'version' => '0.1.24',\n";
        $content .= "    'timestamp' => '{$this->timestamp}',\n";
        $content .= "    'backup_location' => '{$this->backupDir}',\n";
        $content .= "    'modified_files' => [\n";

        foreach ($this->modifiedFiles as $file) {
            $relativePath = str_replace(base_path().'/', '', $file);
            $content .= "        '{$relativePath}',\n";
        }

        $content .= "    ],\n";
        $content .= "    'status' => [\n";

        foreach ($this->status as $key => $value) {
            $content .= "        '{$key}' => '{$value}',\n";
        }

        $content .= "    ],\n";
        $content .= "];\n";

        File::put($statusFile, $content);
    }

    /**
     * Show current installation status.
     */
    protected function showInstallationStatus(): void
    {
        $statusFile = base_path('config/tpl-shared-installed.php');
        if (! File::exists($statusFile)) {
            return;
        }

        $status = require $statusFile;

        $this->components->info('Installation Details:');
        $this->table(
            ['Component', 'Status'],
            [
                ['config/services.php', $this->formatStatus($status['status']['services_config'] ?? 'unknown')],
                ['config/auth.php', $this->formatStatus($status['status']['auth_config'] ?? 'unknown')],
                ['bootstrap/app.php', $this->formatStatus($status['status']['bootstrap_app'] ?? 'unknown')],
                ['app/Models/User.php', $this->formatStatus($status['status']['user_model'] ?? 'unknown')],
                ['.env', $this->formatStatus($status['status']['env_file'] ?? 'unknown')],
            ]
        );

        if (isset($status['timestamp'])) {
            $this->components->info("Installed: {$status['timestamp']}");
        }
    }

    /**
     * Format status for display.
     */
    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'modified' => '✅ Modified',
            'skipped' => '⏭️ Already installed',
            'failed' => '⚠️ Failed - stub created',
            default => '❓ Unknown',
        };
    }

    /**
     * Show installation summary.
     */
    protected function showInstallationSummary(): void
    {
        $this->components->info('Installation Summary');
        $this->newLine();

        // Show status for each component
        $this->table(
            ['Component', 'Status'],
            [
                ['config/services.php', $this->formatStatus($this->status['services_config'] ?: 'failed')],
                ['config/auth.php', $this->formatStatus($this->status['auth_config'] ?: 'failed')],
                ['bootstrap/app.php', $this->formatStatus($this->status['bootstrap_app'] ?: 'failed')],
                ['app/Models/User.php', $this->formatStatus($this->status['user_model'] ?: 'failed')],
                ['.env', $this->formatStatus($this->status['env_file'] ?: 'failed')],
            ]
        );

        $this->newLine();

        if (count($this->modifiedFiles) > 0) {
            $this->components->info('Backups created at:');
            $this->line("  {$this->backupDir}");
            $this->newLine();
        }

        // Show required .env variables
        $this->components->warn('⚠️ IMPORTANT: Update these .env variables with your actual values:');
        $this->newLine();

        $this->table(
            ['Variable', 'Example Value', 'Description'],
            [
                ['BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com', 'BiblioCommons API base URL'],
                ['BIBLIOCOMMONS_API_KEY', 'your-api-key-here', 'Your BiblioCommons API key'],
                ['BIBLIOCOMMONS_LIBRARY_ID', 'tpl', 'Your library ID (e.g., tpl, nypl)'],
                ['BIBLIOCOMMONS_API_URL', 'https://tpl.bibliocommons.com/api/external-templates', 'Templates API URL'],
                ['BIBLIO_SESSION_COOKIE', 'bc_session', 'BiblioCommons session cookie name'],
            ]
        );

        $this->newLine();
        $this->components->info('Next Steps:');
        $this->line('  1. Update .env variables with your actual values');
        $this->line('  2. Publish package assets: php artisan vendor:publish --provider="Tpl\Shared\SharedServiceProvider"');
        $this->line('  3. Use BiblioCommons layouts: <x-tpl-shared::layout>');
        $this->line('  4. Protect routes with middleware: Route::middleware(\'biblio.auth\')');
        $this->newLine();

        $this->components->info('📚 Documentation:');
        $this->line('  • AUTH_PROVIDER_GUIDE.md - Laravel authentication setup');
        $this->line('  • MIDDLEWARE_GUIDE.md - Middleware usage');
        $this->line('  • BIBLIOCOMMONS.md - Template integration');
        $this->line('  • DOCUMENTATION_INDEX.md - Complete documentation hub');

        $this->newLine();
        $this->components->info('✅ Installation complete!');
    }

    /**
     * Get BiblioCommons config block for services.php.
     */
    protected function getBiblioCommonsConfigBlock(): string
    {
        return <<<'PHP'
    // TPL Shared - BiblioCommons Configuration
    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
        'api_base_url' => env('BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com'),
        'api_key' => env('BIBLIOCOMMONS_API_KEY'),
        'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),
    ],
PHP;
    }

    /**
     * Get BiblioCommons guard config for auth.php.
     */
    protected function getBiblioGuardConfig(): string
    {
        return <<<'PHP'
        // TPL Shared - BiblioCommons Guard
        'biblio' => [
            'driver' => 'biblio',
            'provider' => 'biblio',
            'session_cookie' => env('BIBLIO_SESSION_COOKIE', 'bc_session'),
        ],
PHP;
    }

    /**
     * Get BiblioCommons provider config for auth.php.
     */
    protected function getBiblioProviderConfig(): string
    {
        return <<<'PHP'
        // TPL Shared - BiblioCommons Provider
        'biblio' => [
            'driver' => 'biblio',
            'model' => App\Models\User::class,
        ],
PHP;
    }

    /**
     * Get middleware alias block for bootstrap/app.php.
     */
    protected function getMiddlewareAliasBlock(): string
    {
        return <<<'PHP'
        // TPL Shared - BiblioCommons Middleware
        $middleware->alias([
            'biblio.auth' => \Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons::class,
        ]);
PHP;
    }

    /**
     * Get User model properties block.
     */
    protected function getUserModelProperties(): string
    {
        return <<<'PHP'
    // TPL Shared - Stateless Authentication Properties
    // These properties support BiblioCommons stateless authentication
    // Users are not stored in database - data is fetched from API on each request
    public $id;

    public $name;

    public $email;

    public $password;

    public $email_verified_at;

    // Mark as existing to prevent save attempts
    public $exists = true;

PHP;
    }

    /**
     * Get environment variables block.
     */
    protected function getEnvVariablesBlock(): string
    {
        return <<<'ENV'
# TPL Shared - BiblioCommons Configuration
# Update these values with your actual BiblioCommons credentials and URLs

# BiblioCommons API base URL
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com

# Your BiblioCommons API key (required)
BIBLIOCOMMONS_API_KEY=your-api-key-here

# Your library ID (e.g., tpl, nypl, etc.)
BIBLIOCOMMONS_LIBRARY_ID=tpl

# BiblioCommons external templates API URL (for header/footer)
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates

# BiblioCommons session cookie name (default: bc_session)
BIBLIO_SESSION_COOKIE=bc_session
ENV;
    }

    /**
     * Create .env.tpl-shared.example file.
     */
    protected function createEnvExample(): void
    {
        $exampleContent = <<<'ENV'
# TPL Shared Package - BiblioCommons Configuration Example
# Copy these variables to your .env file and update with your actual values

# BiblioCommons API Configuration
# ================================

# BiblioCommons API base URL
# This is the base URL for all BiblioCommons API requests
BIBLIOCOMMONS_API_BASE_URL=https://api.bibliocommons.com

# BiblioCommons API Key
# Required for authenticating API requests to BiblioCommons
# Contact your BiblioCommons administrator to obtain this key
BIBLIOCOMMONS_API_KEY=your-api-key-here

# Library ID
# Your library's unique identifier in the BiblioCommons system
# Examples: tpl (Toronto Public Library), nypl (New York Public Library)
BIBLIOCOMMONS_LIBRARY_ID=tpl

# BiblioCommons Templates API URL
# Used to fetch header, footer, and navigation templates
# Replace 'tpl' with your library ID
BIBLIOCOMMONS_API_URL=https://tpl.bibliocommons.com/api/external-templates

# BiblioCommons Session Cookie Name
# The name of the cookie used by BiblioCommons for session management
# Default: bc_session (usually no need to change this)
BIBLIO_SESSION_COOKIE=bc_session

# Usage Examples
# ==============
#
# 1. BiblioCommons Templates:
#    Use the layout component: <x-tpl-shared::layout>
#
# 2. Authentication:
#    Protect routes: Route::middleware('biblio.auth')->group(...)
#    Get user: Auth::guard('biblio')->user()
#
# 3. Direct API Access:
#    $biblioSso = app(\Tpl\Shared\Services\BiblioSsoService::class);
#    $profile = $biblioSso->fetchUserProfile($sessionId);
#
# Documentation
# =============
# See AUTH_PROVIDER_GUIDE.md for complete authentication setup
# See MIDDLEWARE_GUIDE.md for middleware usage
# See BIBLIOCOMMONS.md for template integration
# See DOCUMENTATION_INDEX.md for all documentation
ENV;

        File::put(base_path('.env.tpl-shared.example'), $exampleContent);
    }

    /**
     * Insert content into an array in a config file.
     */
    protected function insertIntoArray(string $content, string $arrayKey, string $newContent): string
    {
        // Pattern to find the array key and its content
        $pattern = "/({$arrayKey}\s*=>\s*\[)(.*?)(\],)/s";

        if (preg_match($pattern, $content, $matches)) {
            $arrayContent = $matches[2];
            $newArrayContent = $arrayContent."\n".$newContent."\n";

            return preg_replace($pattern, '$1'.$newArrayContent.'$3', $content);
        }

        return $content;
    }

    /**
     * Get stub content.
     */
    protected function getStubContent(string $stubName): string
    {
        // Return appropriate stub content based on stub name
        return match ($stubName) {
            'services.config.stub' => $this->getServicesConfigStub(),
            'auth.config.stub' => $this->getAuthConfigStub(),
            'bootstrap.app.stub' => $this->getBootstrapAppStub(),
            'User.model.stub' => $this->getUserModelStub(),
            default => "// Stub content for {$stubName}\n// Please configure manually\n",
        };
    }

    /**
     * Get services config stub content.
     */
    protected function getServicesConfigStub(): string
    {
        return <<<'PHP'
<?php

// TPL Shared - BiblioCommons Configuration
// Add this to your config/services.php file in the return array

return [
    // ...existing services

    'bibliocommons' => [
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),
        'api_base_url' => env('BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com'),
        'api_key' => env('BIBLIOCOMMONS_API_KEY'),
        'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),
    ],
];
PHP;
    }

    /**
     * Get auth config stub content.
     */
    protected function getAuthConfigStub(): string
    {
        return <<<'PHP'
<?php

// TPL Shared - BiblioCommons Authentication Configuration
// Add this to your config/auth.php file

return [
    'guards' => [
        // ...existing guards

        // TPL Shared - BiblioCommons Guard
        'biblio' => [
            'driver' => 'biblio',
            'provider' => 'biblio',
            'session_cookie' => env('BIBLIO_SESSION_COOKIE', 'bc_session'),
        ],
    ],

    'providers' => [
        // ...existing providers

        // TPL Shared - BiblioCommons Provider
        'biblio' => [
            'driver' => 'biblio',
            'model' => App\Models\User::class,
        ],
    ],
];
PHP;
    }

    /**
     * Get bootstrap app stub content.
     */
    protected function getBootstrapAppStub(): string
    {
        return <<<'PHP'
<?php

// TPL Shared - BiblioCommons Middleware Configuration
// Add this to your bootstrap/app.php file in the withMiddleware callback

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // TPL Shared - BiblioCommons Middleware
        $middleware->alias([
            'biblio.auth' => \Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
PHP;
    }

    /**
     * Get User model stub content.
     */
    protected function getUserModelStub(): string
    {
        return <<<'PHP'
<?php

// TPL Shared - User Model Configuration
// Add these properties to your app/Models/User.php class

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // TPL Shared - Stateless Authentication Properties
    // These properties support BiblioCommons stateless authentication
    // Users are not stored in database - data is fetched from API on each request
    public $id;
    public $name;
    public $email;
    public $password;
    public $email_verified_at;

    // Mark as existing to prevent save attempts
    public $exists = true;

    // ...rest of your User model
}
PHP;
    }
}
