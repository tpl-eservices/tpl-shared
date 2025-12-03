# Makefile Quick Reference

## Most Used Commands

```bash
# Show help
make help

# Check status
make status

# Quick patch release (auto: test, format, tag, push)
make release

# Create specific version tags
make tag-patch    # 0.1.0 → 0.1.1 (bug fixes)
make tag-minor    # 0.1.0 → 0.2.0 (new features)
make tag-major    # 0.1.0 → 1.0.0 (breaking changes)

# Push to GitHub
make push

# Development
make test         # Run tests
make format       # Format code
```

## Typical Workflow

### Quick Release
```bash
git add -A
git commit -m "Your changes"
make release      # Does everything!
```

### Manual Release
```bash
git add -A
git commit -m "Your changes"
make test
make format
make tag-minor    # or tag-patch, tag-major
make push
```

## Version Types

| Command | From | To | Use For |
|---------|------|-----|---------|
| `tag-patch` | 0.1.0 | 0.1.1 | Bug fixes, docs |
| `tag-minor` | 0.1.0 | 0.2.0 | New features |
| `tag-major` | 0.1.0 | 1.0.0 | Breaking changes |

## Error Fixes

```bash
# "Working directory not clean"
git add -A && git commit -m "message"

# "Uncommitted changes"  
git add -A && git commit -m "message"

# Delete wrong tag
git tag -d v0.1.1
git push origin :refs/tags/v0.1.1
```

## After Release

Host apps can install:
```bash
composer update tpl/shared
```

See **MAKEFILE_GUIDE.md** for complete documentation.

