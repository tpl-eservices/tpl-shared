# Version Management Guide

## How Version Numbers Work

The package uses **Semantic Versioning** (semver): `MAJOR.MINOR.PATCH`

- **PATCH** (0.1.0 → 0.1.1) - Bug fixes, documentation
- **MINOR** (0.1.0 → 0.2.0) - New features, backward compatible
- **MAJOR** (0.1.0 → 1.0.0) - Breaking changes

## Automatic Version Updates

When you create a tag using the Makefile, version numbers are **automatically updated** in:
- ✅ `composer.json`
- ✅ `package.json`

### How It Works

```bash
# Current version: v0.1.0
make tag-patch

# What happens:
# 1. Creates git tag v0.1.1
# 2. Updates composer.json: "version": "0.1.1"
# 3. Updates package.json: "version": "0.1.1"
# 4. Commits the version file updates
# 5. Ready to push!
```

## Release Workflow

### Option 1: Automatic (Recommended)

Use `make release` for everything:

```bash
# Make your code changes
git add -A
git commit -m "Fix BiblioCommons caching"

# Run full release (auto-updates versions)
make release
```

**This does:**
1. ✅ Runs tests
2. ✅ Formats code
3. ✅ Commits formatted changes
4. ✅ Creates patch tag (e.g., v0.1.1)
5. ✅ Updates composer.json and package.json
6. ✅ Commits version updates
7. ✅ Pushes everything to GitHub

### Option 2: Manual Control

For more control over the version type:

```bash
# Make changes and commit
git add -A
git commit -m "Add new SearchBar component"

# Test and format
make test
make format

# Commit formatted code if needed
git add -A
git commit -m "Format code"

# Create minor version (new feature)
make tag-minor
# Prompts for release notes
# Automatically updates composer.json and package.json
# Commits the version updates

# Push to GitHub
make push
```

## Commands That Update Versions

### `make tag-patch`
```bash
make tag-patch
```
- Creates patch version (0.1.0 → 0.1.1)
- **Auto-updates** version files
- **Auto-commits** the updates

### `make tag-minor`
```bash
make tag-minor
```
- Creates minor version (0.1.0 → 0.2.0)
- **Auto-updates** version files
- **Auto-commits** the updates

### `make tag-major`
```bash
make tag-major
```
- Creates major version (0.1.0 → 1.0.0)
- **Auto-updates** version files
- **Auto-commits** the updates

### `make release`
```bash
make release
```
- Runs full workflow with patch version
- **Auto-updates** version files
- **Auto-commits** the updates

## Manual Version Update

If you need to manually sync version files with the latest tag:

```bash
make update-version
```

This updates `composer.json` and `package.json` to match the latest git tag.

**When to use:**
- After creating a tag manually with `git tag`
- If version files are out of sync
- After switching branches with different tags

## Checking Current Version

### In the Package

```bash
# Show current version
make status

# Or directly with git
git describe --tags --abbrev=0
```

### In composer.json

```bash
# View version
cat composer.json | grep version

# Output:
# "version": "0.1.0"
```

### In package.json

```bash
# View version
cat package.json | grep version

# Output:
# "version": "0.1.0"
```

## Version Flow Example

### Starting Point
```
composer.json: "version": "0.1.0"
package.json:  "version": "0.1.0"
git tag:       v0.1.0
```

### After `make tag-patch`
```
composer.json: "version": "0.1.1"  ← Auto-updated
package.json:  "version": "0.1.1"  ← Auto-updated
git tag:       v0.1.1              ← Created
```

### After `make push`
```
GitHub: v0.1.1 tag available
Composer: Can install tpl/shared:^0.1.1
```

## Best Practices

### 1. Always Commit Changes First

```bash
# Bad: Working directory not clean
make tag-patch  # ❌ Error

# Good: Commit first
git add -A
git commit -m "Your changes"
make tag-patch  # ✅ Works
```

### 2. Use Semantic Versioning

| Change Type | Command | Example |
|-------------|---------|---------|
| Bug fix | `make tag-patch` | 0.1.0 → 0.1.1 |
| New feature | `make tag-minor` | 0.1.0 → 0.2.0 |
| Breaking change | `make tag-major` | 0.1.0 → 1.0.0 |

### 3. Test Before Releasing

```bash
# Always test first
make test

# Then release
make release
```

### 4. Let Make Handle Versions

Don't manually edit version numbers in `composer.json` or `package.json`. Let the Makefile handle it:

```bash
# ❌ Don't do this
vim composer.json  # Manually change version
git commit -m "Update version"

# ✅ Do this instead
make tag-patch  # Automatically updates versions
```

## Troubleshooting

### Version Files Out of Sync

**Problem:** `composer.json` shows 0.1.0 but latest tag is v0.1.2

**Solution:**
```bash
make update-version
git add composer.json package.json
git commit -m "Sync version files with latest tag"
```

### Wrong Version Created

**Problem:** Created v0.2.0 but meant to create v0.1.1

**Solution:**
```bash
# Delete the wrong tag
git tag -d v0.2.0

# Revert the version commit
git reset --hard HEAD~1

# Create correct version
make tag-patch
```

### Forgot to Push

**Problem:** Created tag but forgot to push

**Solution:**
```bash
# Push everything
make push
```

## Integration with CI/CD

The version numbers in `composer.json` can be used in CI/CD:

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
      
      - name: Get version from composer.json
        id: version
        run: echo "version=$(jq -r .version composer.json)" >> $GITHUB_OUTPUT
      
      - name: Create GitHub Release
        uses: actions/create-release@v1
        with:
          tag_name: ${{ github.ref }}
          release_name: v${{ steps.version.outputs.version }}
```

## FAQ

### Q: Do I need to manually update composer.json?
**A:** No! The Makefile updates it automatically when you create tags.

### Q: What if I forget to update versions?
**A:** Run `make update-version` to sync with the latest tag.

### Q: Can I create tags without the Makefile?
**A:** Yes, but you'll need to manually update version files:
```bash
git tag -a v0.1.1 -m "Release"
make update-version
git add composer.json package.json
git commit -m "Update versions"
git push origin main
git push origin v0.1.1
```

### Q: Does the version affect Composer installation?
**A:** Yes! Host apps can install specific versions:
```bash
composer require tpl/shared:^0.1.0  # Any 0.1.x
composer require tpl/shared:^0.2.0  # Any 0.2.x
composer require tpl/shared:~0.1.1  # 0.1.1 to 0.1.x
```

### Q: What version should I start with?
**A:** 
- `0.1.0` - Initial development
- `1.0.0` - First stable release
- Follow semver from there

## Summary

✅ **Version updates are automatic** when using Makefile commands  
✅ **Both composer.json and package.json** are updated  
✅ **Version commits are automatic** after tagging  
✅ **No manual version editing needed**  

**Most common workflow:**
```bash
git add -A
git commit -m "Your changes"
make release  # Everything automatic!
```

**Version files stay in sync with git tags automatically!** 🎉

