# Windows Build Scripts

## Quick Start

This project includes Windows-native build management scripts as alternatives to the Unix `Makefile`.

### Available Scripts

- **`build.ps1`** - PowerShell script (recommended)
- **`build.bat`** - Batch script (alternative)

Both provide the same functionality as `make` commands on Unix systems.

## Usage Examples

### PowerShell (Recommended)

```powershell
# Show help
.\build.ps1 help

# Show status
.\build.ps1 status

# Format code
.\build.ps1 format

# Run tests
.\build.ps1 test

# Build assets
.\build.ps1 build

# Create patch version (0.1.0 -> 0.1.1)
.\build.ps1 tag-patch

# Create minor version (0.1.0 -> 0.2.0)
.\build.ps1 tag-minor

# Create major version (0.1.0 -> 1.0.0)
.\build.ps1 tag-major

# Push to GitHub
.\build.ps1 push

# Full release workflow
.\build.ps1 release

# Clean build artifacts
.\build.ps1 clean

# Install dependencies
.\build.ps1 install
```

### Batch Script (Alternative)

```cmd
# Show help
build help

# Show status
build status

# Format code
build format

# Run tests
build test

# Full release workflow
build release
```

## Common Workflows

### Quick Patch Release
```powershell
.\build.ps1 release
```
This will:
1. Format code
2. Build assets
3. Create patch version
4. Push to GitHub

### Manual Release
```powershell
# Format code
.\build.ps1 format

# Run tests
.\build.ps1 test

# Create version tag
.\build.ps1 tag-minor

# Push to GitHub
.\build.ps1 push
```

## Troubleshooting

### PowerShell Execution Policy

If you get "running scripts is disabled" error:

```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Batch Script Issues

If `build.bat` doesn't work, use PowerShell script instead:

```powershell
.\build.ps1 help
```

## Documentation

For complete documentation, see **[WINDOWS_BUILD_GUIDE.md](WINDOWS_BUILD_GUIDE.md)**

## Comparison: Make vs Windows Scripts

| Unix/Linux/Mac | Windows Batch | Windows PowerShell |
|----------------|---------------|-------------------|
| `make help` | `build help` | `.\build.ps1 help` |
| `make status` | `build status` | `.\build.ps1 status` |
| `make format` | `build format` | `.\build.ps1 format` |
| `make test` | `build test` | `.\build.ps1 test` |
| `make release` | `build release` | `.\build.ps1 release` |

---

**See also:**
- [WINDOWS_BUILD_GUIDE.md](WINDOWS_BUILD_GUIDE.md) - Complete Windows guide
- [MAKEFILE_GUIDE.md](MAKEFILE_GUIDE.md) - Unix Makefile guide
- [DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md) - All documentation

