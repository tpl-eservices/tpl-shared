.PHONY: help tag-patch tag-minor tag-major push release status test format

# Default target
help:
	@echo "TPL Shared Package - Release Management"
	@echo ""
	@echo "Available commands:"
	@echo "  make status       - Show current version and git status"
	@echo "  make test         - Run tests before releasing"
	@echo "  make format       - Format code with Laravel Pint"
	@echo ""
	@echo "  make tag-patch    - Create a new patch version (0.1.0 -> 0.1.1)"
	@echo "  make tag-minor    - Create a new minor version (0.1.0 -> 0.2.0)"
	@echo "  make tag-major    - Create a new major version (0.1.0 -> 1.0.0)"
	@echo ""
	@echo "  make push         - Push commits and tags to GitHub"
	@echo "  make release      - Full release: test, format, commit, tag-patch, and push"
	@echo ""
	@echo "Current version: $$(git describe --tags --abbrev=0 2>/dev/null || echo 'No tags yet')"

# Show current status
status:
	@echo "=== Current Version ==="
	@git describe --tags --abbrev=0 2>/dev/null || echo "No tags yet"
	@echo ""
	@echo "=== Git Status ==="
	@git status --short
	@echo ""
	@echo "=== Recent Commits ==="
	@git log --oneline -5
	@echo ""
	@echo "=== All Tags ==="
	@git tag -l

# Run tests
test:
	@echo "Running tests..."
	@composer test

# Format code
format:
	@echo "Formatting PHP code with Pint..."
	@composer format

# Create a patch version tag (0.1.0 -> 0.1.1)
tag-patch:
	@echo "Creating new patch version..."
	@$(MAKE) _create-tag TYPE=patch

# Create a minor version tag (0.1.0 -> 0.2.0)
tag-minor:
	@echo "Creating new minor version..."
	@$(MAKE) _create-tag TYPE=minor

# Create a major version tag (0.1.0 -> 1.0.0)
tag-major:
	@echo "Creating new major version..."
	@$(MAKE) _create-tag TYPE=major

# Internal target to create tags
_create-tag:
	@if [ -n "$$(git status --porcelain)" ]; then \
		echo "Error: Working directory is not clean. Commit or stash changes first."; \
		git status --short; \
		exit 1; \
	fi
	@CURRENT=$$(git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0"); \
	echo "Current version: $$CURRENT"; \
	VERSION=$$(echo $$CURRENT | sed 's/^v//'); \
	MAJOR=$$(echo $$VERSION | cut -d. -f1); \
	MINOR=$$(echo $$VERSION | cut -d. -f2); \
	PATCH=$$(echo $$VERSION | cut -d. -f3); \
	if [ "$(TYPE)" = "major" ]; then \
		NEW_VERSION="$$((MAJOR + 1)).0.0"; \
	elif [ "$(TYPE)" = "minor" ]; then \
		NEW_VERSION="$$MAJOR.$$((MINOR + 1)).0"; \
	else \
		NEW_VERSION="$$MAJOR.$$MINOR.$$((PATCH + 1))"; \
	fi; \
	echo "New version: v$$NEW_VERSION"; \
	read -p "Enter release notes (or press Enter for auto-generated): " NOTES; \
	if [ -z "$$NOTES" ]; then \
		NOTES="Release v$$NEW_VERSION"; \
	fi; \
	git tag -a "v$$NEW_VERSION" -m "$$NOTES"; \
	echo "✅ Created tag v$$NEW_VERSION"; \
	echo ""; \
	echo "Run 'make push' to push to GitHub"

# Push commits and tags to GitHub
push:
	@echo "Pushing to GitHub..."
	@if [ -z "$$(git status --porcelain)" ]; then \
		echo "Pushing main branch..."; \
		git push origin main; \
		echo ""; \
		echo "Pushing tags..."; \
		git push origin --tags; \
		echo ""; \
		echo "✅ Successfully pushed to GitHub"; \
		echo ""; \
		echo "Latest tag: $$(git describe --tags --abbrev=0)"; \
	else \
		echo "Error: Uncommitted changes detected. Commit first."; \
		git status --short; \
		exit 1; \
	fi

# Full release workflow
release:
	@echo "=== Starting Release Process ==="
	@echo ""
	@echo "Step 1: Running tests..."
	@$(MAKE) test
	@echo ""
	@echo "Step 2: Formatting code..."
	@$(MAKE) format
	@echo ""
	@if [ -n "$$(git status --porcelain)" ]; then \
		echo "Step 3: Committing formatted changes..."; \
		git add -A; \
		git commit -m "Format code for release"; \
		echo ""; \
	fi
	@echo "Step 4: Creating patch version tag..."
	@$(MAKE) tag-patch
	@echo ""
	@echo "Step 5: Pushing to GitHub..."
	@$(MAKE) push
	@echo ""
	@echo "🎉 Release complete!"
	@echo ""
	@echo "New version: $$(git describe --tags --abbrev=0)"

# Update version in composer.json
update-version:
	@CURRENT=$$(git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0"); \
	VERSION=$$(echo $$CURRENT | sed 's/^v//'); \
	echo "Updating composer.json version to $$VERSION..."; \
	sed -i '' 's/"version": "[^"]*"/"version": "'$$VERSION'"/' composer.json; \
	sed -i '' 's/"version": "[^"]*"/"version": "'$$VERSION'"/' package.json; \
	echo "✅ Updated version files"

# Clean up
clean:
	@echo "Cleaning up..."
	@rm -rf bootstrap/cache/*.php
	@rm -rf vendor
	@rm -rf node_modules
	@echo "✅ Cleaned"

# Install dependencies
install:
	@echo "Installing dependencies..."
	@composer install
	@pnpm install
	@echo "✅ Dependencies installed"

