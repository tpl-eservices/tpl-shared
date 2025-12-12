# Windows Build Scripts Guide

This guide explains how to use the Windows-native build scripts (`build.bat` and `build.ps1`) as alternatives to the Unix `Makefile`.

## Quick Start

### PowerShell (Recommended)

```powershell
# Show all available commands
.\build.ps1 help

# Run full release workflow
.\build.ps1 release
```

### Batch Script

```cmd
# Show all available commands
build help

# Run full release workflow
build release
```

---

## Available Commands

| Command | Description |
|---------|-------------|
| `help` | Show all available commands and current version |
| `status` | Show git status, recent commits, and tags |
| `test` | Run Pest test suite |
| `format` | Format PHP code with Laravel Pint |
| `build` | Build frontend assets with Vite |
| `tag-patch` | Create patch version (0.1.0 → 0.1.1) |
| `tag-minor` | Create minor version (0.1.0 → 0.2.0) |
| `tag-major` | Create major version (0.1.0 → 1.0.0) |
| `push` | Push commits and tags to GitHub |
| `release` | Full release workflow (format, build, tag, push) |
| `update-version` | Update version files from latest git tag |
| `clean` | Remove build artifacts and dependencies |
| `install` | Install composer and npm/pnpm dependencies |

---

## Usage Examples

### PowerShell

```powershell
# Information commands
.\build.ps1 help
.\build.ps1 status

# Development commands
.\build.ps1 format
.\build.ps1 test
.\build.ps1 build

# Version management
.\build.ps1 tag-patch
.\build.ps1 tag-minor
.\build.ps1 tag-major

# Git operations
.\build.ps1 push

# Full workflow
.\build.ps1 release

# Maintenance
.\build.ps1 clean
.\build.ps1 install
```

### Batch Script

```cmd
# Information commands
build help
build status

# Development commands
build format
build test
build build

# Version management
build tag-patch
build tag-minor
build tag-major

# Git operations
build push

# Full workflow
build release

# Maintenance
build clean
build install
```

---

## Detailed Command Documentation

### `help` - Show Help

**Description:** Displays all available commands with brief descriptions and shows the current version.

**Usage:**
```powershell
.\build.ps1 help
```

**Output:**
```
TPL Shared Package - Build Management

Available commands:
  .\build.ps1 status        - Show current version and git status
  .\build.ps1 test          - Run tests before releasing
  .\build.ps1 format        - Format code with Laravel Pint
  .\build.ps1 build         - Build frontend assets with Vite
  ...
  
Current version: v0.1.24
```

---

### `status` - Show Status

**Description:** Shows current version, git status, recent commits, and all tags.

**Usage:**
```powershell
.\build.ps1 status
```

**Output:**
```
=== Current Version ===
v0.1.24

=== Git Status ===
M  composer.json
M  package.json

=== Recent Commits ===
abc1234 Bump version to 0.1.24
def5678 Format code for release
...

=== All Tags ===
v0.1.0
v0.1.1
...
v0.1.24
```

---

### `test` - Run Tests

**Description:** Runs the Pest test suite.

**Usage:**
```powershell
.\build.ps1 test
```

**What it does:**
- Executes `composer test`
- Shows test results
- Exits with error code if tests fail

---

### `format` - Format Code

**Description:** Formats all PHP code using Laravel Pint according to Laravel coding standards.

**Usage:**
```powershell
.\build.ps1 format
```

**What it does:**
- Executes `composer format` (which runs `vendor/bin/pint`)
- Formats all PHP files
- Shows which files were modified

---

### `build` - Build Assets

**Description:** Builds frontend assets using Vite for production.

**Usage:**
```powershell
.\build.ps1 build
```

**What it does:**
- Uses `pnpm build` if pnpm is installed
- Falls back to `npm run build` if pnpm not found
- Compiles and optimizes JavaScript and CSS
- Creates production-ready bundles in `public/build/`

---

### `tag-patch` - Create Patch Version

**Description:** Creates a new patch version tag (increments the third number: 0.1.0 → 0.1.1).

**Usage:**
```powershell
.\build.ps1 tag-patch
```

**Interactive Process:**
```
Current version: v0.1.24
New version: v0.1.25

Enter release notes (or press Enter for auto-generated): Bug fixes and improvements

Updating version files...
  ✓ Updated composer.json
  ✓ Updated package.json

Committing version updates...
✓ Committed version updates

Creating tag v0.1.25...
✓ Created tag v0.1.25

Next steps:
  1. Run '.\build.ps1 push' to push to GitHub
  2. Or run '.\build.ps1 release' to do everything automatically
```

**When to use:**
- Bug fixes
- Small improvements
- Documentation updates
- Patch releases

---

### `tag-minor` - Create Minor Version

**Description:** Creates a new minor version tag (increments the second number: 0.1.0 → 0.2.0).

**Usage:**
```powershell
.\build.ps1 tag-minor
```

**When to use:**
- New features
- Backward-compatible changes
- Significant updates

---

### `tag-major` - Create Major Version

**Description:** Creates a new major version tag (increments the first number: 0.1.0 → 1.0.0).

**Usage:**
```powershell
.\build.ps1 tag-major
```

**When to use:**
- Breaking changes
- Major architecture changes
- Public API changes
- Version 1.0 release

---

### `push` - Push to GitHub

**Description:** Pushes all commits and tags to GitHub.

**Usage:**
```powershell
.\build.ps1 push
```

**What it does:**
1. Checks that working directory is clean
2. Pushes main branch to origin
3. Pushes all tags to origin
4. Shows the latest tag

**Output:**
```
Pushing to GitHub...
Pushing main branch...
To github.com:tpl-eservices/tpl-shared.git
   abc1234..def5678  main -> main

Pushing tags...
To github.com:tpl-eservices/tpl-shared.git
 * [new tag]         v0.1.25 -> v0.1.25

✓ Successfully pushed to GitHub

Latest tag: v0.1.25
```

---

### `release` - Full Release Workflow

**Description:** Complete release workflow - the recommended way to release.

**Usage:**
```powershell
.\build.ps1 release
```

**What it does:**
1. **Formats code** with Laravel Pint
2. **Commits formatting changes** (if any)
3. **Builds frontend assets** with Vite
4. **Creates patch version tag** with release notes
5. **Pushes to GitHub** (commits and tags)

**Output:**
```
=== Starting Release Process ===

Step 1: Formatting code...
Code formatted successfully!

Step 2: Committing formatted changes...
[main abc1234] Format code for release
 2 files changed, 10 insertions(+), 5 deletions(-)

Step 3: Building frontend assets...
Build complete!

Step 4: Creating patch version tag...
Current version: v0.1.24
New version: v0.1.25
...

Step 5: Pushing to GitHub...
✓ Successfully pushed to GitHub

🎉 Release complete!

New version: v0.1.25
```

---

### `update-version` - Update Version Files

**Description:** Manually updates `composer.json` and `package.json` with the version from the latest git tag.

**Usage:**
```powershell
.\build.ps1 update-version
```

**When to use:**
- When version files are out of sync with git tags
- After manually creating a tag
- To fix version mismatches

---

### `clean` - Clean Build Artifacts

**Description:** Removes all build artifacts and dependencies.

**Usage:**
```powershell
.\build.ps1 clean
```

**What it removes:**
- `bootstrap/cache/*.php` - Laravel cache files
- `vendor/` - Composer dependencies
- `node_modules/` - NPM/PNPM dependencies

**When to use:**
- Before fresh installation
- To free up disk space
- To fix dependency issues
- Clean slate for troubleshooting

---

### `install` - Install Dependencies

**Description:** Installs all project dependencies.

**Usage:**
```powershell
.\build.ps1 install
```

**What it does:**
- Runs `composer install` for PHP dependencies
- Runs `pnpm install` or `npm install` for Node dependencies
- Auto-detects whether to use pnpm or npm

**When to use:**
- After cloning repository
- After running `clean`
- Setting up development environment

---

## Recommended Workflows

### Quick Patch Release

For quick bug fixes and minor updates:

```powershell
# One command does everything
.\build.ps1 release
```

This is the **recommended** and **fastest** way to release.

---

### Manual Minor Version Release

For more control over the process:

```powershell
# Step 1: Format code
.\build.ps1 format

# Step 2: Run tests
.\build.ps1 test

# Step 3: Build assets
.\build.ps1 build

# Step 4: Create minor version
.\build.ps1 tag-minor

# Step 5: Push to GitHub
.\build.ps1 push
```

---

### Check Project Status

Before starting work:

```powershell
# See current version and git status
.\build.ps1 status
```

---

### Fresh Development Setup

After cloning the repository:

```powershell
# Install all dependencies
.\build.ps1 install

# Check everything is working
.\build.ps1 test
```

---

### Clean and Reinstall

When dependencies are causing issues:

```powershell
# Remove everything
.\build.ps1 clean

# Reinstall fresh
.\build.ps1 install
```

---

## Comparison: Makefile vs Windows Scripts

| Makefile (Unix) | build.bat (Windows) | build.ps1 (Windows) |
|-----------------|---------------------|---------------------|
| `make help` | `build help` | `.\build.ps1 help` |
| `make status` | `build status` | `.\build.ps1 status` |
| `make test` | `build test` | `.\build.ps1 test` |
| `make format` | `build format` | `.\build.ps1 format` |
| `make build` | `build build` | `.\build.ps1 build` |
| `make tag-patch` | `build tag-patch` | `.\build.ps1 tag-patch` |
| `make tag-minor` | `build tag-minor` | `.\build.ps1 tag-minor` |
| `make tag-major` | `build tag-major` | `.\build.ps1 tag-major` |
| `make push` | `build push` | `.\build.ps1 push` |
| `make release` | `build release` | `.\build.ps1 release` |
| `make clean` | `build clean` | `.\build.ps1 clean` |
| `make install` | `build install` | `.\build.ps1 install` |

**Result:** Complete feature parity across all platforms!

---

## Troubleshooting

### PowerShell Execution Policy Error

**Error:**
```
File cannot be loaded because running scripts is disabled on this system
```

**Solution:**
```powershell
# Check current policy
Get-ExecutionPolicy

# Set policy for current user (recommended)
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser

# Or run once with bypass
powershell -ExecutionPolicy Bypass -File .\build.ps1 help
```

---

### Working Directory Not Clean

**Error:**
```
Error: Working directory is not clean. Commit or stash changes first.
```

**Solution:**
```powershell
# Check what's uncommitted
git status

# Commit changes
git add -A
git commit -m "Your commit message"

# Or stash changes
git stash
```

---

### No Tags Yet

**Error:**
```
Current version: No tags yet
```

**Solution:**
```powershell
# Create initial tag
.\build.ps1 tag-patch
```

---

### Tests Failed

**Error:**
```
Tests failed!
```

**Solution:**
1. Review test output to see what failed
2. Fix the failing tests
3. Run tests again: `.\build.ps1 test`
4. Commit fixes and try release again

---

### Build Failed

**Error:**
```
Build failed!
```

**Solution:**
1. Check if `node_modules` is installed: `.\build.ps1 install`
2. Check for TypeScript errors in frontend code
3. Try cleaning and rebuilding: `.\build.ps1 clean` then `.\build.ps1 install`

---

## Tips and Tricks

### Add to PATH (Optional)

To use `build` from anywhere:

1. Open System Properties → Environment Variables
2. Edit the `Path` variable for your user
3. Add the full path to your project directory
4. Now you can run `build help` from anywhere

---

### Create PowerShell Alias

Add to your PowerShell profile (`$PROFILE`):

```powershell
# Create alias for build script
function build { & "C:\Users\YourName\Projects\tpl-shared\build.ps1" @args }
```

Then use:
```powershell
build status
build release
```

---

### Pre-commit Hook

Create `.git/hooks/pre-commit` to auto-format before commits:

```bash
#!/bin/sh
# Format code before commit
./build.ps1 format
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

---

## See Also

- **[MAKEFILE_GUIDE.md](MAKEFILE_GUIDE.md)** - Complete Makefile documentation (Unix/Linux/Mac)
- **[VERSION_MANAGEMENT.md](VERSION_MANAGEMENT.md)** - Version management workflow
- **[WINDOWS_SCRIPTS_README.md](WINDOWS_SCRIPTS_README.md)** - Quick reference
- **[README.md](README.md)** - Project overview

---

**Platform:** Windows  
**Scripts:** `build.bat`, `build.ps1`  
**Equivalent:** `Makefile` (Unix)  
**Status:** Production Ready

