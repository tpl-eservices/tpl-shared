# Troubleshooting: Composer Can't Pull New Versions

## Problem Description

After creating and pushing a new tag (e.g., v0.1.2), Composer in host applications still shows the old version (e.g., 0.1.0).

```bash
composer update tpl/shared
# Nothing to install, update or remove

composer show tpl/shared
# versions : * 0.1.0  ← Still old version!
```

## Root Cause

The git tag was pointing to a commit where `composer.json` still had the old version number. This happened because the Makefile was creating tags BEFORE updating and committing the version files.

**The sequence was wrong:**
1. ❌ Create tag v0.1.2
2. ❌ Update composer.json to "version": "0.1.2"
3. ❌ Commit version update

**Result:** Tag v0.1.2 → Commit with "version": "0.1.0" in composer.json

Composer reads the version from composer.json at the tagged commit, so it saw 0.1.0 instead of 0.1.2.

## Solution Applied

### 1. Fixed the Makefile Order

**New correct sequence:**
1. ✅ Update composer.json and package.json
2. ✅ Commit the version updates
3. ✅ Create tag on that commit

Now tags always point to commits with matching version numbers.

### 2. Fixed Existing Broken Tag (v0.1.2)

The v0.1.2 tag was already pushed with the wrong commit. We fixed it:

```bash
# Delete old tag locally
git tag -d v0.1.2

# Delete old tag on GitHub
git push origin :refs/tags/v0.1.2

# Create new tag on the correct commit (with version update)
git tag -a v0.1.2 -m "Release v0.1.2" b24c7ec

# Push new tag
git push origin v0.1.2
```

### 3. Clear Composer Cache

Host applications needed to clear their cache:

```bash
composer clear-cache
composer update tpl/shared
```

## How to Fix This Issue

### If You Encounter This Problem

**Step 1: Verify the Issue**

Check what version is in composer.json at the tag:

```bash
cd /path/to/package
git show v0.1.2:composer.json | grep '"version"'
```

If it shows the wrong version (e.g., "0.1.0" when tag is v0.1.2), you need to fix it.

**Step 2: Fix the Tag**

In the package repository:

```bash
# Find the commit that has the correct version
git log --oneline --all | grep "Bump version"

# Example output:
# b24c7ec Bump version to 0.1.2
# abc1234 Some other commit
# def5678 Bump version to 0.1.1

# Delete the wrong tag locally
git tag -d v0.1.2

# Delete the wrong tag on GitHub
git push origin :refs/tags/v0.1.2

# Create new tag on the correct commit
git tag -a v0.1.2 -m "Release v0.1.2" b24c7ec

# Push the corrected tag
git push origin v0.1.2
```

**Step 3: Update Host Application**

In the host Laravel app:

```bash
# Clear Composer cache
composer clear-cache

# Update the package
composer update tpl/shared

# Verify
composer show tpl/shared
# Should now show: versions : * 0.1.2
```

## Prevention

The Makefile is now fixed to prevent this issue. When you use:

```bash
make tag-patch  # or tag-minor, tag-major
```

It automatically:
1. ✅ Updates composer.json and package.json with new version
2. ✅ Commits those updates
3. ✅ Creates tag pointing to that commit
4. ✅ Ready to push

**Always use the Makefile commands** instead of creating tags manually with `git tag`.

## Verification After Fix

### Check Tag Points to Correct Commit

```bash
# In package repository
git show v0.1.2:composer.json | grep '"version"'
# Should output: "version": "0.1.2"

# Check the commit
git log --oneline v0.1.2 -1
# Should show: "Bump version to 0.1.2" commit
```

### Check Composer Sees New Version

```bash
# In host application
composer clear-cache
composer show tpl/shared --all | grep versions
# Should include: 0.1.2
```

### Successful Update

```bash
composer update tpl/shared
# Should output:
# - Upgrading tpl/shared (0.1.0 => 0.1.2)
```

## Common Mistakes to Avoid

### ❌ Don't: Create Tags Manually

```bash
# DON'T DO THIS
git tag -a v0.1.3 -m "Release"
git push origin v0.1.3
# Version files won't be updated!
```

### ✅ Do: Use Makefile

```bash
# DO THIS
make tag-patch
make push
# Everything is handled correctly
```

### ❌ Don't: Update Version After Tagging

```bash
# DON'T DO THIS
git tag -a v0.1.3 -m "Release"
# Then edit composer.json
# Tag already points to old version!
```

### ✅ Do: Let Makefile Handle Order

```bash
# DO THIS
make tag-patch
# Automatically updates versions FIRST, then creates tag
```

## Debugging Commands

### Check Version in Tagged Commit

```bash
git show TAG_NAME:composer.json | grep '"version"'
```

**Example:**
```bash
git show v0.1.2:composer.json | grep '"version"'
# Output: "version": "0.1.2"  ← Correct!
```

### List All Tags with Commit Hashes

```bash
git log --oneline --tags
```

### Check What Composer Sees

```bash
# In host app
composer show tpl/shared --all | grep -E "versions|name"
```

### Compare Local vs Remote Tags

```bash
# List remote tags
git ls-remote --tags origin

# List local tags
git tag -l
```

## The Fix in Detail

### Before Fix (Broken)

```makefile
# OLD (WRONG) ORDER
_create-tag:
    # 1. Create tag FIRST
    git tag -a "v$$NEW_VERSION" -m "$$NOTES"
    
    # 2. Update files AFTER
    sed -i '' 's/"version": "[^"]*"/"version": "'$$NEW_VERSION'"/' composer.json
    
    # 3. Commit updates
    git commit -m "Bump version to $$NEW_VERSION"
```

**Problem:** Tag points to old commit without version update.

### After Fix (Correct)

```makefile
# NEW (CORRECT) ORDER
_create-tag:
    # 1. Update files FIRST
    sed -i '' 's/"version": "[^"]*"/"version": "'$$NEW_VERSION'"/' composer.json
    
    # 2. Commit updates
    git commit -m "Bump version to $$NEW_VERSION"
    
    # 3. Create tag LAST (points to commit with updates)
    git tag -a "v$$NEW_VERSION" -m "$$NOTES"
```

**Solution:** Tag points to commit WITH version update.

## Summary

**The Issue:**
- Tags were created before version files were updated
- Composer read old version numbers from tagged commits

**The Fix:**
- Updated Makefile to update versions BEFORE creating tags
- Re-created affected tags to point to correct commits
- Host apps cleared cache and updated successfully

**Prevention:**
- Always use `make tag-patch/minor/major` commands
- Never create tags manually with `git tag`
- The Makefile ensures correct order

**Verification:**
```bash
# In package
git show v0.1.2:composer.json | grep version
# Should match tag version

# In host app
composer show tpl/shared
# Should show latest version
```

This issue is now permanently fixed! ✅

