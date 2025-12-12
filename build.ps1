# TPL Shared Package - Build Management (Windows PowerShell)
# This is the Windows PowerShell equivalent of the Makefile

param(
    [Parameter(Position=0)]
    [string]$Command = "help"
)

$ErrorActionPreference = "Stop"

function Show-Help {
    Write-Host "TPL Shared Package - Build Management" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Available commands:"
    Write-Host "  .\build.ps1 status        - Show current version and git status"
    Write-Host "  .\build.ps1 test          - Run tests before releasing"
    Write-Host "  .\build.ps1 format        - Format code with Laravel Pint"
    Write-Host "  .\build.ps1 build         - Build frontend assets with Vite"
    Write-Host ""
    Write-Host "  .\build.ps1 tag-patch     - Create a new patch version (0.1.0 -> 0.1.1)"
    Write-Host "  .\build.ps1 tag-minor     - Create a new minor version (0.1.0 -> 0.2.0)"
    Write-Host "  .\build.ps1 tag-major     - Create a new major version (0.1.0 -> 1.0.0)"
    Write-Host "                              Note: Version files are auto-updated on tagging"
    Write-Host ""
    Write-Host "  .\build.ps1 push          - Push commits and tags to GitHub"
    Write-Host "  .\build.ps1 release       - Full release: test, format, commit, tag-patch, and push"
    Write-Host ""
    Write-Host "  .\build.ps1 update-version - Manually update composer.json/package.json from latest tag"
    Write-Host "  .\build.ps1 clean         - Clean up cache and dependencies"
    Write-Host "  .\build.ps1 install       - Install dependencies"
    Write-Host ""

    $currentTag = git describe --tags --abbrev=0 2>$null
    if (-not $currentTag) { $currentTag = "No tags yet" }
    Write-Host "Current version: $currentTag" -ForegroundColor Yellow
}

function Show-Status {
    Write-Host "=== Current Version ===" -ForegroundColor Cyan
    $currentTag = git describe --tags --abbrev=0 2>$null
    if (-not $currentTag) { $currentTag = "No tags yet" }
    Write-Host $currentTag
    Write-Host ""

    Write-Host "=== Git Status ===" -ForegroundColor Cyan
    git status --short
    Write-Host ""

    Write-Host "=== Recent Commits ===" -ForegroundColor Cyan
    git log --oneline -5
    Write-Host ""

    Write-Host "=== All Tags ===" -ForegroundColor Cyan
    git tag -l
}

function Run-Tests {
    Write-Host "Running tests..." -ForegroundColor Yellow
    composer test
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Tests failed!" -ForegroundColor Red
        exit $LASTEXITCODE
    }
    Write-Host "Tests passed!" -ForegroundColor Green
}

function Format-Code {
    Write-Host "Formatting PHP code with Pint..." -ForegroundColor Yellow
    composer format
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Formatting failed!" -ForegroundColor Red
        exit $LASTEXITCODE
    }
    Write-Host "Code formatted successfully!" -ForegroundColor Green
}

function Build-Assets {
    Write-Host "Building frontend assets with Vite..." -ForegroundColor Yellow

    $hasPnpm = Get-Command pnpm -ErrorAction SilentlyContinue
    if ($hasPnpm) {
        pnpm build
    } else {
        npm run build
    }

    if ($LASTEXITCODE -ne 0) {
        Write-Host "Build failed!" -ForegroundColor Red
        exit $LASTEXITCODE
    }
    Write-Host "Build complete!" -ForegroundColor Green
}

function New-Tag {
    param([string]$Type)

    # Check if working directory is clean
    $status = git status --porcelain 2>$null
    if ($status) {
        Write-Host "Error: Working directory is not clean. Commit or stash changes first." -ForegroundColor Red
        git status --short
        exit 1
    }

    # Get current version
    $current = git describe --tags --abbrev=0 2>$null
    if (-not $current) { $current = "v0.0.0" }
    Write-Host "Current version: $current" -ForegroundColor Cyan

    # Parse version numbers
    $version = $current -replace '^v', ''
    $parts = $version -split '\.'
    $major = [int]$parts[0]
    $minor = [int]$parts[1]
    $patch = [int]$parts[2]

    # Calculate new version
    switch ($Type) {
        "major" { $newVersion = "$($major + 1).0.0" }
        "minor" { $newVersion = "$major.$($minor + 1).0" }
        default { $newVersion = "$major.$minor.$($patch + 1)" }
    }

    Write-Host "New version: v$newVersion" -ForegroundColor Green
    Write-Host ""

    # Get release notes
    $notes = Read-Host "Enter release notes (or press Enter for auto-generated)"
    if (-not $notes) { $notes = "Release v$newVersion" }

    Write-Host ""
    Write-Host "Updating version files..." -ForegroundColor Yellow

    # Update composer.json
    if (Test-Path composer.json) {
        (Get-Content composer.json) -replace '"version": "[^"]*"', "`"version`": `"$newVersion`"" | Set-Content composer.json
        Write-Host "  ✓ Updated composer.json" -ForegroundColor Green
    }

    # Update package.json
    if (Test-Path package.json) {
        (Get-Content package.json) -replace '"version": "[^"]*"', "`"version`": `"$newVersion`"" | Set-Content package.json
        Write-Host "  ✓ Updated package.json" -ForegroundColor Green
    }

    # Check if there are changes to commit
    $status = git status --porcelain 2>$null
    if ($status) {
        Write-Host ""
        Write-Host "Committing version updates..." -ForegroundColor Yellow
        git add composer.json package.json
        git commit -m "Bump version to $newVersion"
        Write-Host "✓ Committed version updates" -ForegroundColor Green
        Write-Host ""
    }

    # Create the tag
    Write-Host "Creating tag v$newVersion..." -ForegroundColor Yellow
    git tag -a "v$newVersion" -m $notes
    Write-Host "✓ Created tag v$newVersion" -ForegroundColor Green
    Write-Host ""

    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "  1. Run '.\build.ps1 push' to push to GitHub"
    Write-Host "  2. Or run '.\build.ps1 release' to do everything automatically"
}

function Push-ToGitHub {
    Write-Host "Pushing to GitHub..." -ForegroundColor Yellow

    # Check if working directory is clean
    $status = git status --porcelain 2>$null
    if ($status) {
        Write-Host "Error: Uncommitted changes detected. Commit first." -ForegroundColor Red
        git status --short
        exit 1
    }

    Write-Host "Pushing main branch..." -ForegroundColor Yellow
    git push origin main
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to push main branch!" -ForegroundColor Red
        exit $LASTEXITCODE
    }

    Write-Host ""
    Write-Host "Pushing tags..." -ForegroundColor Yellow
    git push origin --tags
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to push tags!" -ForegroundColor Red
        exit $LASTEXITCODE
    }

    Write-Host ""
    Write-Host "✓ Successfully pushed to GitHub" -ForegroundColor Green
    Write-Host ""

    $latestTag = git describe --tags --abbrev=0
    Write-Host "Latest tag: $latestTag" -ForegroundColor Cyan
}

function Start-Release {
    Write-Host "=== Starting Release Process ===" -ForegroundColor Cyan
    Write-Host ""

    Write-Host "Step 1: Formatting code..." -ForegroundColor Yellow
    Format-Code
    Write-Host ""

    # Check if there are PHP/code changes to commit (excluding build artifacts)
    $status = git status --porcelain 2>$null | Where-Object { $_ -notmatch 'public/build/' }
    if ($status) {
        Write-Host "Step 2: Committing formatted changes..." -ForegroundColor Yellow
        # Add only non-build files
        git add --all -- ':!public/build/*'
        git commit -m "Format code for release"
        Write-Host ""
    }

    Write-Host "Step 3: Building frontend assets..." -ForegroundColor Yellow
    Build-Assets
    Write-Host ""

    # Check if there are build artifacts to commit
    $status = git status --porcelain 2>$null
    if ($status) {
        Write-Host "Step 4: Committing build artifacts..." -ForegroundColor Yellow
        git add -A
        git commit -m "Build frontend assets for release"
        Write-Host ""
    }

    Write-Host "Step 5: Creating patch version tag..." -ForegroundColor Yellow
    New-Tag -Type "patch"
    Write-Host ""

    Write-Host "Step 6: Pushing to GitHub..." -ForegroundColor Yellow
    Push-ToGitHub
    Write-Host ""

    Write-Host "🎉 Release complete!" -ForegroundColor Green
    Write-Host ""

    $newVersion = git describe --tags --abbrev=0
    Write-Host "New version: $newVersion" -ForegroundColor Cyan
}

function Update-Version {
    $current = git describe --tags --abbrev=0 2>$null
    if (-not $current) {
        Write-Host "Error: No tags found. Create a tag first with '.\build.ps1 tag-patch'." -ForegroundColor Red
        exit 1
    }

    $version = $current -replace '^v', ''
    Write-Host "Updating version to $version..." -ForegroundColor Yellow

    if (Test-Path composer.json) {
        (Get-Content composer.json) -replace '"version": "[^"]*"', "`"version`": `"$version`"" | Set-Content composer.json
        Write-Host "  ✓ Updated composer.json" -ForegroundColor Green
    }

    if (Test-Path package.json) {
        (Get-Content package.json) -replace '"version": "[^"]*"', "`"version`": `"$version`"" | Set-Content package.json
        Write-Host "  ✓ Updated package.json" -ForegroundColor Green
    }

    Write-Host ""
    Write-Host "Version files updated to $version" -ForegroundColor Green
    Write-Host "Run 'git add -A && git commit -m `"Bump version to $version`"' to commit changes"
}

function Remove-BuildArtifacts {
    Write-Host "Cleaning up..." -ForegroundColor Yellow

    if (Test-Path bootstrap\cache) {
        Remove-Item bootstrap\cache\*.php -ErrorAction SilentlyContinue
    }
    if (Test-Path vendor) {
        Remove-Item vendor -Recurse -Force
    }
    if (Test-Path node_modules) {
        Remove-Item node_modules -Recurse -Force
    }

    Write-Host "✓ Cleaned" -ForegroundColor Green
}

function Install-Dependencies {
    Write-Host "Installing dependencies..." -ForegroundColor Yellow

    composer install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Composer install failed!" -ForegroundColor Red
        exit $LASTEXITCODE
    }

    $hasPnpm = Get-Command pnpm -ErrorAction SilentlyContinue
    if ($hasPnpm) {
        pnpm install
    } else {
        npm install
    }

    if ($LASTEXITCODE -ne 0) {
        Write-Host "Package manager install failed!" -ForegroundColor Red
        exit $LASTEXITCODE
    }

    Write-Host "✓ Dependencies installed" -ForegroundColor Green
}

# Route to appropriate command
switch ($Command.ToLower()) {
    "help" { Show-Help }
    "status" { Show-Status }
    "test" { Run-Tests }
    "format" { Format-Code }
    "build" { Build-Assets }
    "tag-patch" { New-Tag -Type "patch" }
    "tag-minor" { New-Tag -Type "minor" }
    "tag-major" { New-Tag -Type "major" }
    "push" { Push-ToGitHub }
    "release" { Start-Release }
    "update-version" { Update-Version }
    "clean" { Remove-BuildArtifacts }
    "install" { Install-Dependencies }
    default {
        Write-Host "Unknown command: $Command" -ForegroundColor Red
        Write-Host ""
        Show-Help
        exit 1
    }
}

