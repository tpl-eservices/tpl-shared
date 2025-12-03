# Makefile Release Management Guide

This package includes a Makefile to simplify versioning, tagging, and publishing.

## Quick Start

```bash
# Show available commands
make help

# Check current status
make status

# Create a new patch release (auto: test, format, tag, push)
make release

# Create a specific version
make tag-minor    # 0.1.0 -> 0.2.0
make push
```

## Available Commands

### Information Commands

#### `make help`
Show all available commands and current version.

```bash
make help
```

#### `make status`
Display current version, git status, recent commits, and all tags.

```bash
make status
```

Output:
```
=== Current Version ===
v0.1.0

=== Git Status ===
M  src/SharedServiceProvider.php

=== Recent Commits ===
abc1234 Add comprehensive documentation
def5678 Fix vendor:publish errors

=== All Tags ===
v0.1.0
```

### Development Commands

#### `make test`
Run the Pest test suite.

```bash
make test
```

Equivalent to: `composer test`

#### `make format`
Format PHP code using Laravel Pint.

```bash
make format
```

Equivalent to: `composer format`

### Versioning Commands

#### `make tag-patch`
Create a new **patch** version (0.1.0 → 0.1.1)

```bash
make tag-patch
```

- Checks for clean working directory
- Increments patch version
- Prompts for release notes
- Creates annotated git tag

**When to use:** Bug fixes, small improvements, documentation updates

#### `make tag-minor`
Create a new **minor** version (0.1.0 → 0.2.0)

```bash
make tag-minor
```

**When to use:** New features, non-breaking changes

#### `make tag-major`
Create a new **major** version (0.1.0 → 1.0.0)

```bash
make tag-major
```

**When to use:** Breaking changes, major rewrites

### Publishing Commands

#### `make push`
Push commits and tags to GitHub.

```bash
make push
```

- Checks for uncommitted changes
- Pushes main branch
- Pushes all tags
- Shows latest tag

#### `make release`
**Complete release workflow** - recommended for most releases.

```bash
make release
```

This runs automatically:
1. ✅ `make test` - Run tests
2. ✅ `make format` - Format code
3. ✅ Auto-commit formatted changes
4. ✅ `make tag-patch` - Create patch version
5. ✅ `make push` - Push to GitHub

**Perfect for:** Quick releases with standard versioning

### Utility Commands

#### `make update-version`
Update version numbers in `composer.json` and `package.json` to match the latest git tag.

```bash
make update-version
```

#### `make install`
Install all dependencies (Composer + pnpm).

```bash
make install
```

#### `make clean`
Clean up cache and dependencies.

```bash
make clean
```

## Usage Examples

### Example 1: Quick Patch Release

You've fixed a bug and want to release:

```bash
# Check everything is ready
make status

# Run the full release workflow
make release
```

This will:
- Run tests
- Format code
- Commit any changes
- Tag as v0.1.1 (patch)
- Push to GitHub

### Example 2: Minor Feature Release

You've added a new feature:

```bash
# Commit your changes first
git add -A
git commit -m "Add new BiblioCommons feature"

# Run tests
make test

# Format code
make format

# Commit formatted code
git add -A
git commit -m "Format code"

# Create minor version tag
make tag-minor
# Prompts: "Enter release notes:"
# You enter: "Add advanced BiblioCommons caching"

# Push to GitHub
make push
```

### Example 3: Major Breaking Change

You've refactored the API:

```bash
# Commit your changes
git add -A
git commit -m "Refactor: Change component API (breaking)"

# Test and format
make test
make format

# Create major version
make tag-major
# Enter notes: "Breaking: New component API structure"

# Push
make push
```

### Example 4: Check Before Release

Before releasing, check everything:

```bash
# See current state
make status

# Should show:
# - Current version
# - Clean working directory
# - Recent commits

# If there are uncommitted changes, commit them first
git add -A
git commit -m "Prepare for release"

# Then proceed with release
make release
```

## Workflow Best Practices

### Standard Release Workflow

```bash
# 1. Make changes
# ... edit files ...

# 2. Commit changes
git add -A
git commit -m "Your commit message"

# 3. Test
make test

# 4. Format
make format

# 5. Check status
make status

# 6. Tag version
make tag-patch   # or tag-minor, tag-major

# 7. Push
make push

# Done! ✅
```

### Quick Release Workflow

For small changes:

```bash
# 1. Commit changes
git add -A
git commit -m "Fix: Small bug"

# 2. Release (does everything)
make release

# Done! ✅
```

## Version Numbering

Following [Semantic Versioning](https://semver.org/):

**Format:** `MAJOR.MINOR.PATCH`

### Patch Version (0.1.0 → 0.1.1)
```bash
make tag-patch
```
**Use for:**
- Bug fixes
- Documentation updates
- Small improvements
- No API changes

### Minor Version (0.1.0 → 0.2.0)
```bash
make tag-minor
```
**Use for:**
- New features
- New functionality
- Backward compatible changes
- Additional components

### Major Version (0.1.0 → 1.0.0)
```bash
make tag-major
```
**Use for:**
- Breaking changes
- API changes
- Major refactoring
- Incompatible updates

## Error Handling

### "Working directory is not clean"

**Error:**
```
Error: Working directory is not clean. Commit or stash changes first.
```

**Solution:**
```bash
# Commit changes
git add -A
git commit -m "Your message"

# Or stash them
git stash

# Then try again
make tag-patch
```

### "Uncommitted changes detected"

**Error when pushing:**
```
Error: Uncommitted changes detected. Commit first.
```

**Solution:**
```bash
# Commit everything
git add -A
git commit -m "Prepare for release"

# Then push
make push
```

### Tag Already Exists

If you try to create a tag that already exists:

**Solution:**
```bash
# Delete the local tag
git tag -d v0.1.1

# Delete the remote tag (if pushed)
git push origin :refs/tags/v0.1.1

# Try again
make tag-patch
```

## Installation in Host Apps

After running `make push`, host applications can install the new version:

```bash
# Install specific version
composer require tpl/shared:^0.2.0

# Or update to latest
composer update tpl/shared
```

## Checking Released Versions

### On GitHub
Visit: https://github.com/tpl-eservices/tpl-shared/tags

### In Your Package
```bash
# Show all tags
git tag -l

# Show current version
git describe --tags --abbrev=0

# Or use Make
make status
```

### In Host Application
```bash
# After installation
composer show tpl/shared
```

## Automated Release Notes

When creating a tag, you can:

1. **Auto-generate** (press Enter):
   ```
   Release v0.1.1
   ```

2. **Custom message**:
   ```
   Enter release notes: Fix BiblioCommons caching bug
   ```

## Integration with CI/CD

The Makefile can be integrated into CI/CD pipelines:

```yaml
# .github/workflows/release.yml
name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run tests
        run: make test
```

## Tips & Tricks

### Dry Run
To see what would happen without making changes:

```bash
# Check current version
git describe --tags --abbrev=0

# Calculate next version manually
# Current: v0.1.0
# Patch: v0.1.1
# Minor: v0.2.0
# Major: v1.0.0
```

### Multiple Tags in One Session

```bash
# Create tag
make tag-patch    # Creates v0.1.1

# Push
make push

# Create another (rare, but possible)
make tag-patch    # Creates v0.1.2
make push
```

### Revert a Release

If you need to undo a release:

```bash
# Delete local tag
git tag -d v0.1.1

# Delete remote tag
git push origin :refs/tags/v0.1.1

# Note: If already installed by others, create a new patch instead
```

## Summary

**Most common commands:**

```bash
make help          # See all commands
make status        # Check current state
make test          # Run tests
make format        # Format code
make release       # Quick release (patch)
make tag-minor     # New feature release
make push          # Push to GitHub
```

**Typical workflow:**

```bash
# 1. Make changes and commit
git add -A && git commit -m "Your changes"

# 2. Release
make release

# Done! ✅
```

That's it! The Makefile handles versioning, testing, formatting, and publishing automatically.

