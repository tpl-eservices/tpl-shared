# Version Management Guide

Complete guide for managing versions, releases, and tags for the TPL Shared package.

## Overview

The package uses semantic versioning and provides automated tools for version management across all platforms:

- **Unix/Linux/Mac:** `make` commands
- **Windows PowerShell:** `.\build.ps1` commands
- **Windows Batch:** `build` commands

---

## Quick Start

### Complete Release Workflow (Recommended)

```bash
# Unix/Linux/Mac
make release

# Windows PowerShell
.\build.ps1 release

# Windows Batch
build release
```

This automatically:

1. ✅ Formats code with Laravel Pint
2. ✅ Commits formatting changes
3. ✅ Builds frontend assets
4. ✅ Creates patch version tag
5. ✅ Pushes to GitHub

### Check Current Status

```bash
# Unix/Linux/Mac
make status

# Windows
.\build.ps1 status
```

Output:

```
=== Current Version ===
v0.1.24

=== Git Status ===
M  composer.json
M  package.json

=== Recent Commits ===
abc1234 Bump version to 0.1.24
def5678 Format code for release

=== All Tags ===
v0.1.0
v0.1.1
...
v0.1.24
```

---

## Version Management Commands

### Semantic Versioning

The package follows [Semantic Versioning](https://semver.org/spec/v2.0.0/):

**Format:** `MAJOR.MINOR.PATCH`

- **Patch (0.1.0 → 0.1.1):** Bug fixes, documentation updates, small improvements
- **Minor (0.1.0 → 0.2.0):** New features, backward-compatible changes
- **Major (0.1.0 → 1.0.0):** Breaking changes, API changes, major refactoring

### Create Specific Versions

#### Patch Version

```bash
# Unix/Linux/Mac
make tag-patch

# Windows
.\build.ps1 tag-patch
```

**When to use:**

- Bug fixes
- Small improvements
- Documentation updates
- No API changes

#### Minor Version

```bash
# Unix/Linux/Mac
make tag-minor

# Windows
.\build.ps1 tag-minor
```

**When to use:**

- New features
- Additional functionality
- Backward-compatible changes
- New components

#### Major Version

```bash
# Unix/Linux/Mac
make tag-major

# Windows
.\build.ps1 tag-major
```

**When to use:**

- Breaking changes
- API modifications
- Major architecture changes
- Version 1.0 release

### Push to GitHub

```bash
# Unix/Linux/Mac
make push

# Windows
.\build.ps1 push
```

**What it does:**

- Checks for uncommitted changes
- Pushes main branch to origin
- Pushes all tags to origin
- Shows latest tag

---

## Version Files

The package maintains version consistency across multiple files:

### Version Files

- `composer.json` - PHP package version
- `package.json` - Node.js package version
- Git tags - Release versions

### Update Version Files Manually

```bash
# Unix/Linux/Mac
make update-version

# Windows
.\build.ps1 update-version
```

**When to use:**

- Version files are out of sync with git tags
- After manually creating a tag
- To fix version mismatches

### Version File Examples

#### composer.json

```json
{
    "name": "tpl/shared",
    "version": "0.1.24",
    "description": "A comprehensive shared Laravel package for TPL projects",
    "type": "laravel-package"
}
```

#### package.json

```json
{
    "name": "tpl-shared",
    "version": "0.1.24",
    "description": "TPL Shared Package Frontend",
    "private": true
}
```

---

## Release Workflows

### 1. Quick Patch Release (Most Common)

```bash
# Make changes
# ... edit files ...

# Commit changes
git add -A
git commit -m "Fix: Small bug fix"

# One command release
make release
```

**Result:** Creates patch version (e.g., 0.1.24 → 0.1.25)

### 2. Manual Minor Release

```bash
# Commit changes
git add -A
git commit -m "Add: New BiblioCommons feature"

# Format and test
make format
make test

# Create minor version
make tag-minor
# Prompts: "Enter release notes:"
# You enter: "Add advanced BiblioCommons caching"

# Push to GitHub
make push
```

**Result:** Creates minor version (e.g., 0.1.24 → 0.2.0)

### 3. Major Breaking Release

```bash
# Commit breaking changes
git add -A
git commit -m "Breaking: Change component API structure"

# Create major version
make tag-major
# Enter notes: "Breaking: New component API structure"

# Push
make push
```

**Result:** Creates major version (e.g., 0.1.24 → 1.0.0)

### 4. Check Before Release

```bash
# Check current state
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

---

## Release Notes

### Automatic Release Notes

When creating a version tag, you can:

1. **Auto-generate** (press Enter):

    ```
    Release v0.1.25
    ```

2. **Custom message:**
    ```
    Enter release notes: Fix BiblioCommons caching bug
    ```

### Update CHANGELOG.md

Always update `CHANGELOG.md` with release notes:

```markdown
## [0.1.25] - 2025-01-15

### Fixed

- Fixed BiblioCommons caching bug on Windows
- Resolved cookie reading issues

### Changed

- Improved error handling in BiblioSsoService
```

---

## Git Tag Management

### List Tags

```bash
# Show all tags
git tag -l

# Show current version
git describe --tags --abbrev=0

# Using build scripts
make status
```

### Delete Tags (Local)

```bash
# Delete local tag
git tag -d v0.1.25
```

### Delete Tags (Remote)

```bash
# Delete remote tag
git push origin :refs/tags/v0.1.25

# Or using modern syntax
git push origin --delete v0.1.25
```

### Revert a Release

If you need to undo a release:

```bash
# Delete local tag
git tag -d v0.1.25

# Delete remote tag
git push origin :refs/tags/v0.1.25

# Note: If already installed by others, create a new patch instead
```

---

## Version Validation

### Check Version Consistency

```bash
# Check all version files
make status

# Should show consistent versions across:
# - composer.json
# - package.json
# - Latest git tag
```

### Fix Version Mismatch

```bash
# Update version files to match git tags
make update-version

# Or manually edit and commit:
# Edit composer.json and package.json
# Commit changes
# Create new tag
```

### Version Number Examples

| Current | Patch  | Minor | Major |
| ------- | ------ | ----- | ----- |
| 0.1.24  | 0.1.25 | 0.2.0 | 1.0.0 |
| 1.2.3   | 1.2.4  | 1.3.0 | 2.0.0 |
| 2.0.0   | 2.0.1  | 2.1.0 | 3.0.0 |

---

## Host Application Updates

### After Publishing New Version

Host applications can install the new version:

```bash
# Install specific version
composer require tpl/shared:^0.2.0

# Or update to latest
composer update tpl/shared

# Check installed version
composer show tpl/shared
```

### Force Update

```bash
# Clear Composer cache
composer clear-cache

# Update specific package
composer update tpl/shared --with-all-dependencies

# Reinstall package
composer remove tpl/shared
composer require tpl/shared:^0.2.0
```

---

## Error Handling

### "Working directory is not clean"

```bash
Error: Working directory is not clean. Commit or stash changes first.
```

**Solution:**

```bash
# Commit changes
git add -A
git commit -m "Your message"

# Or stash changes
git stash

# Then try again
make tag-patch
```

### "Uncommitted changes detected"

```bash
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

```bash
Error: Tag v0.1.25 already exists
```

**Solution:**

```bash
# Delete the local tag
git tag -d v0.1.25

# Delete the remote tag (if pushed)
git push origin :refs/tags/v0.1.25

# Try again
make tag-patch
```

### Version Files Out of Sync

```bash
Warning: Version mismatch between composer.json and git tags
```

**Solution:**

```bash
# Update version files
make update-version

# Or manually fix and commit
# Edit composer.json and package.json
git add -A
git commit -m "Fix version numbers"
```

---

## Best Practices

### Before Releasing

- [ ] All tests pass: `make test`
- [ ] Code is formatted: `make format`
- [ ] Assets built: `make build`
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Working directory is clean

### Version Number Rules

1. **Patch** for backwards-compatible bug fixes
2. **Minor** for new functionality in a backwards-compatible manner
3. **Major** for any backwards-incompatible changes

### Release Communication

1. Update CHANGELOG.md with detailed notes
2. Create GitHub release with description
3. Notify development team of breaking changes
4. Update external documentation if needed

### Version Strategy

- **Frequent patches** for bug fixes (weekly if needed)
- **Regular minors** for new features (monthly)
- **Careful majors** for breaking changes (rare)

---

## Integration with CI/CD

### GitHub Actions Example

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
            - name: Setup Node.js
              uses: actions/setup-node@v3
              with:
                  node-version: '20'
            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.4'
            - name: Install dependencies
              run: |
                  composer install
                  npm install
            - name: Run tests
              run: make test
            - name: Build assets
              run: make build
```

### Automated Version Detection

```bash
# Extract version from tag
VERSION=$(git describe --tags --abbrev=0)

# Update version in files
sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" package.json
sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" composer.json
```

---

## Monitoring and Analytics

### Track Version Usage

- Monitor GitHub clone counts
- Track Composer download statistics
- Survey host application versions
- Monitor breaking change adoption

### Release Metrics

- Time between releases
- Number of downloads per version
- Issue reports by version
- Adoption rate of new features

---

## Commands Reference

### Quick Reference

| Command               | Platform        | Purpose               |
| --------------------- | --------------- | --------------------- |
| `make help`           | Unix            | Show all commands     |
| `make status`         | Unix            | Check current state   |
| `make test`           | Unix            | Run tests             |
| `make format`         | Unix            | Format code           |
| `make build`          | Unix            | Build assets          |
| `make tag-patch`      | Unix            | Create patch version  |
| `make tag-minor`      | Unix            | Create minor version  |
| `make tag-major`      | Unix            | Create major version  |
| `make push`           | Unix            | Push to GitHub        |
| `make release`        | Unix            | Full release workflow |
| `make clean`          | Unix            | Clean artifacts       |
| `make install`        | Unix            | Install dependencies  |
| `.\build.ps1 help`    | Windows (PS)    | Show all commands     |
| `.\build.ps1 status`  | Windows (PS)    | Check current state   |
| `.\build.ps1 release` | Windows (PS)    | Full release workflow |
| `build help`          | Windows (Batch) | Show all commands     |
| `build release`       | Windows (Batch) | Full release workflow |

### Command Equivalents

| Unix           | Windows PS            | Windows Batch   |
| -------------- | --------------------- | --------------- |
| `make help`    | `.\build.ps1 help`    | `build help`    |
| `make status`  | `.\build.ps1 status`  | `build status`  |
| `make test`    | `.\build.ps1 test`    | `build test`    |
| `make format`  | `.\build.ps1 format`  | `build format`  |
| `make release` | `.\build.ps1 release` | `build release` |

---

## Need Help?

### Documentation

- [Development Guide](README.md) - Complete development workflow
- [Installation Guide](../installation/README.md) - Installation instructions
- [Troubleshooting](../troubleshooting/README.md) - Common issues

### Support

- **GitHub Issues:** https://github.com/tpl-eservices/tpl-shared/issues
- **Team:** Contact TPL development team
- **Documentation:** Check guides in this repository

---

**Happy releasing! 🚀**

Built with ❤️ for Toronto Public Library
