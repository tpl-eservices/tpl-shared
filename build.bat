@echo off
REM TPL Shared Package - Build Management (Windows)
REM This is the Windows batch equivalent of the Makefile

setlocal enabledelayedexpansion

REM Get command argument
set COMMAND=%1

REM If no command provided, show help
if "%COMMAND%"=="" (
    call :help
    exit /b 0
)

REM Route to appropriate command
if "%COMMAND%"=="help" call :help
if "%COMMAND%"=="status" call :status
if "%COMMAND%"=="test" call :test
if "%COMMAND%"=="format" call :format
if "%COMMAND%"=="build" call :build-assets
if "%COMMAND%"=="tag-patch" call :tag-patch
if "%COMMAND%"=="tag-minor" call :tag-minor
if "%COMMAND%"=="tag-major" call :tag-major
if "%COMMAND%"=="push" call :push
if "%COMMAND%"=="release" call :release
if "%COMMAND%"=="update-version" call :update-version
if "%COMMAND%"=="clean" call :clean
if "%COMMAND%"=="install" call :install

exit /b 0

REM ============================================================================
REM Help Command
REM ============================================================================
:help
echo TPL Shared Package - Build Management
echo.
echo Available commands:
echo   build status        - Show current version and git status
echo   build test          - Run tests before releasing
echo   build format        - Format code with Laravel Pint
echo   build build         - Build frontend assets with Vite
echo.
echo   build tag-patch     - Create a new patch version (0.1.0 -^> 0.1.1)
echo   build tag-minor     - Create a new minor version (0.1.0 -^> 0.2.0)
echo   build tag-major     - Create a new major version (0.1.0 -^> 1.0.0)
echo                         Note: Version files are auto-updated on tagging
echo.
echo   build push          - Push commits and tags to GitHub
echo   build release       - Full release: test, format, commit, tag-patch, and push
echo.
echo   build update-version - Manually update composer.json/package.json from latest tag
echo   build clean         - Clean up cache and dependencies
echo   build install       - Install dependencies
echo.
for /f "delims=" %%i in ('git describe --tags --abbrev=0 2^>nul') do set CURRENT_TAG=%%i
if not defined CURRENT_TAG set CURRENT_TAG=No tags yet
echo Current version: !CURRENT_TAG!
exit /b 0

REM ============================================================================
REM Status Command
REM ============================================================================
:status
echo === Current Version ===
for /f "delims=" %%i in ('git describe --tags --abbrev=0 2^>nul') do set CURRENT_TAG=%%i
if not defined CURRENT_TAG set CURRENT_TAG=No tags yet
echo !CURRENT_TAG!
echo.
echo === Git Status ===
git status --short
echo.
echo === Recent Commits ===
git log --oneline -5
echo.
echo === All Tags ===
git tag -l
exit /b 0

REM ============================================================================
REM Test Command
REM ============================================================================
:test
echo Running tests...
call composer test
if %errorlevel% neq 0 (
    echo Tests failed!
    exit /b %errorlevel%
)
echo Tests passed!
exit /b 0

REM ============================================================================
REM Format Command
REM ============================================================================
:format
echo Formatting PHP code with Pint...
call composer format
if %errorlevel% neq 0 (
    echo Formatting failed!
    exit /b %errorlevel%
)
echo Code formatted successfully!
exit /b 0

REM ============================================================================
REM Build Command
REM ============================================================================
:build-assets
echo Building frontend assets with Vite...
where pnpm >nul 2>&1
if %errorlevel% equ 0 (
    call pnpm build
) else (
    call npm run build
)
if %errorlevel% neq 0 (
    echo Build failed!
    exit /b %errorlevel%
)
echo Build complete!
exit /b 0

REM ============================================================================
REM Tag Commands
REM ============================================================================
:tag-patch
echo Creating new patch version...
call :create-tag patch
exit /b 0

:tag-minor
echo Creating new minor version...
call :create-tag minor
exit /b 0

:tag-major
echo Creating new major version...
call :create-tag major
exit /b 0

REM ============================================================================
REM Internal Create Tag Function
REM ============================================================================
:create-tag
set TAG_TYPE=%1

REM Check if working directory is clean
for /f %%i in ('git status --porcelain 2^>nul ^| find /c /v ""') do set DIRTY_COUNT=%%i
if !DIRTY_COUNT! gtr 0 (
    echo Error: Working directory is not clean. Commit or stash changes first.
    git status --short
    exit /b 1
)

REM Get current version
for /f "delims=" %%i in ('git describe --tags --abbrev=0 2^>nul') do set CURRENT=%%i
if not defined CURRENT set CURRENT=v0.0.0
echo Current version: !CURRENT!

REM Parse version numbers
set VERSION=!CURRENT:v=!
for /f "tokens=1,2,3 delims=." %%a in ("!VERSION!") do (
    set MAJOR=%%a
    set MINOR=%%b
    set PATCH=%%c
)

REM Calculate new version
if "!TAG_TYPE!"=="major" (
    set /a NEW_MAJOR=!MAJOR!+1
    set NEW_VERSION=!NEW_MAJOR!.0.0
) else if "!TAG_TYPE!"=="minor" (
    set /a NEW_MINOR=!MINOR!+1
    set NEW_VERSION=!MAJOR!.!NEW_MINOR!.0
) else (
    set /a NEW_PATCH=!PATCH!+1
    set NEW_VERSION=!MAJOR!.!MINOR!.!NEW_PATCH!
)

echo New version: v!NEW_VERSION!
echo.

REM Get release notes
set /p NOTES="Enter release notes (or press Enter for auto-generated): "
if "!NOTES!"=="" set NOTES=Release v!NEW_VERSION!

echo.
echo Updating version files...

REM Update composer.json
if exist composer.json (
    powershell -Command "(Get-Content composer.json) -replace '\"version\": \"[^\"]*\"', '\"version\": \"!NEW_VERSION!\"' | Set-Content composer.json"
    echo   [32m✓[0m Updated composer.json
)

REM Update package.json
if exist package.json (
    powershell -Command "(Get-Content package.json) -replace '\"version\": \"[^\"]*\"', '\"version\": \"!NEW_VERSION!\"' | Set-Content package.json"
    echo   [32m✓[0m Updated package.json
)

REM Check if there are changes to commit
for /f %%i in ('git status --porcelain 2^>nul ^| find /c /v ""') do set CHANGES_COUNT=%%i
if !CHANGES_COUNT! gtr 0 (
    echo.
    echo Committing version updates...
    git add composer.json package.json
    git commit -m "Bump version to !NEW_VERSION!"
    echo [32m✓[0m Committed version updates
    echo.
)

REM Create the tag
echo Creating tag v!NEW_VERSION!...
git tag -a "v!NEW_VERSION!" -m "!NOTES!"
echo [32m✓[0m Created tag v!NEW_VERSION!
echo.

echo Next steps:
echo   1. Run 'build push' to push to GitHub
echo   2. Or run 'build release' to do everything automatically

exit /b 0

REM ============================================================================
REM Push Command
REM ============================================================================
:push
echo Pushing to GitHub...

REM Check if working directory is clean
for /f %%i in ('git status --porcelain 2^>nul ^| find /c /v ""') do set DIRTY_COUNT=%%i
if !DIRTY_COUNT! gtr 0 (
    echo Error: Uncommitted changes detected. Commit first.
    git status --short
    exit /b 1
)

echo Pushing main branch...
git push origin main
if %errorlevel% neq 0 (
    echo Failed to push main branch!
    exit /b %errorlevel%
)

echo.
echo Pushing tags...
git push origin --tags
if %errorlevel% neq 0 (
    echo Failed to push tags!
    exit /b %errorlevel%
)

echo.
echo [32m✓[0m Successfully pushed to GitHub
echo.

for /f "delims=" %%i in ('git describe --tags --abbrev=0') do echo Latest tag: %%i

exit /b 0

REM ============================================================================
REM Release Command (Full Workflow)
REM ============================================================================
:release
echo === Starting Release Process ===
echo.

echo Step 1: Formatting code...
call :format
if %errorlevel% neq 0 exit /b %errorlevel%
echo.

REM Check if there are PHP/code changes to commit (excluding build artifacts)
for /f %%i in ('git status --porcelain 2^>nul ^| findstr /v "public/build/" ^| find /c /v ""') do set CODE_CHANGES=%%i
if !CODE_CHANGES! gtr 0 (
    echo Step 2: Committing formatted changes...
    git add --all -- :!public/build/*
    git commit -m "Format code for release"
    echo.
)

echo Step 3: Building frontend assets...
call :build-assets
if %errorlevel% neq 0 exit /b %errorlevel%
echo.

REM Check if there are build artifacts to commit
for /f %%i in ('git status --porcelain 2^>nul ^| find /c /v ""') do set BUILD_CHANGES=%%i
if !BUILD_CHANGES! gtr 0 (
    echo Step 4: Committing build artifacts...
    git add -A
    git commit -m "Build frontend assets for release"
    echo.
)

echo Step 5: Creating patch version tag...
call :create-tag patch
if %errorlevel% neq 0 exit /b %errorlevel%
echo.

echo Step 6: Pushing to GitHub...
call :push
if %errorlevel% neq 0 exit /b %errorlevel%
echo.

echo [32m🎉 Release complete![0m
echo.

for /f "delims=" %%i in ('git describe --tags --abbrev=0') do echo New version: %%i

exit /b 0

REM ============================================================================
REM Update Version Command
REM ============================================================================
:update-version
for /f "delims=" %%i in ('git describe --tags --abbrev=0 2^>nul') do set CURRENT=%%i
if not defined CURRENT (
    echo Error: No tags found. Create a tag first with 'build tag-patch'.
    exit /b 1
)

set VERSION=!CURRENT:v=!
echo Updating version to !VERSION!...

if exist composer.json (
    powershell -Command "(Get-Content composer.json) -replace '\"version\": \"[^\"]*\"', '\"version\": \"!VERSION!\"' | Set-Content composer.json"
    echo   [32m✓[0m Updated composer.json
)

if exist package.json (
    powershell -Command "(Get-Content package.json) -replace '\"version\": \"[^\"]*\"', '\"version\": \"!VERSION!\"' | Set-Content package.json"
    echo   [32m✓[0m Updated package.json
)

echo.
echo Version files updated to !VERSION!
echo Run 'git add -A ^&^& git commit -m "Bump version to !VERSION!"' to commit changes

exit /b 0

REM ============================================================================
REM Clean Command
REM ============================================================================
:clean
echo Cleaning up...
if exist bootstrap\cache (
    del /q bootstrap\cache\*.php 2>nul
)
if exist vendor (
    rmdir /s /q vendor
)
if exist node_modules (
    rmdir /s /q node_modules
)
echo [32m✓[0m Cleaned
exit /b 0

REM ============================================================================
REM Install Command
REM ============================================================================
:install
echo Installing dependencies...
call composer install
if %errorlevel% neq 0 (
    echo Composer install failed!
    exit /b %errorlevel%
)

where pnpm >nul 2>&1
if %errorlevel% equ 0 (
    call pnpm install
) else (
    call npm install
)

if %errorlevel% neq 0 (
    echo Package manager install failed!
    exit /b %errorlevel%
)

echo [32m✓[0m Dependencies installed
exit /b 0

