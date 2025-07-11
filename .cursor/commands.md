# BMLT Workflow - Development Commands Reference

## Git Workflow Commands

### Branch Management
```bash
# Create new feature branch
./support/new-branch.sh feature-name

# Switch to existing branch
git switch branch-name

# Create and switch to new branch
git switch -c feature-name

# Push new branch to remote
git push --set-upstream origin branch-name
```

### Release Management
```bash
# Create git tag to trigger GitHub Actions release
git tag version-number
git push --tag

# Example: Release version 1.1.20
git tag 1.1.20
git push --tag

# GitHub Actions will automatically:
# - Run tests
# - Create release zip file
# - Publish to WordPress.org
# - Create GitHub release
```

## Testing Commands

### PHPUnit Tests
```bash
# Run all PHPUnit tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/phpunit/src/BMLT/IntegrationTest.php

# Run tests with coverage
./vendor/bin/phpunit --coverage-html coverage/

# Run tests in Docker
docker-compose -f docker/docker-compose.yml run --rm php ./vendor/bin/phpunit
```

### TestCafe E2E Tests
```bash
# Run all E2E tests
npx testcafe chrome tests/testcafe/

# Run specific test file
npx testcafe chrome tests/testcafe/bmlt3x_e2e_test.js

# Run tests with specific configuration
npx testcafe chrome tests/testcafe/ --config-file .testcaferc.js
```

### Docker Testing Environment
```bash
# Start Docker testing environment
docker-compose -f docker/docker-compose.yml up -d

# Run tests in Docker
docker-compose -f docker/docker-compose.yml run --rm php ./vendor/bin/phpunit

# Stop Docker environment
docker-compose -f docker/docker-compose.yml down
```

## Development Commands

### Composer (PHP Dependencies)
```bash
# Install PHP dependencies
composer install

# Update dependencies
composer update

# Add new dependency
composer require package-name

# Run composer scripts
composer test
```

### NPM (Node.js Dependencies)
```bash
# Install Node.js dependencies
npm install

# Update dependencies
npm update

# Add new dependency
npm install package-name

# Run npm scripts
npm test
```

### WordPress Development
```bash
# Generate translation files
wp i18n make-json lang --no-purge

# Make .pot file
wp i18n make-pot . lang/bmlt-workflow.pot

# Update .mo files
wp i18n update-po lang/bmlt-workflow.pot
```

## Version Management Commands

### Update Version Numbers
```bash
# Update version in main plugin file
sed -i 's/Version: 1.1.19/Version: 1.1.20/' bmlt-workflow.php
sed -i "s/define('BMLTWF_PLUGIN_VERSION', '1.1.19');/define('BMLTWF_PLUGIN_VERSION', '1.1.20');/" bmlt-workflow.php

# Update version in readme.txt
sed -i 's/Stable tag: 1.1.19/Stable tag: 1.1.20/' readme.txt

# Update version in package.json (if applicable)
npm version 1.1.20
```

### Check Version Consistency
```bash
# Check all version references
grep -r "1.1.19" bmlt-workflow.php readme.txt
grep -r "BMLTWF_PLUGIN_VERSION" bmlt-workflow.php
```

## Code Quality Commands

### PHP Code Quality
```bash
# Run PHP linting
find src/ -name "*.php" -exec php -l {} \;

# Run PHP_CodeSniffer (if configured)
./vendor/bin/phpcs src/

# Run PHPStan (if configured)
./vendor/bin/phpstan analyse src/
```

### JavaScript Code Quality
```bash
# Run ESLint
npx eslint js/

# Fix ESLint issues
npx eslint js/ --fix

# Run Prettier
npx prettier --write js/
```

## Database Commands

### WordPress Database
```bash
# Run WordPress database updates
wp db upgrade

# Export database
wp db export backup.sql

# Import database
wp db import backup.sql
```

## Deployment Commands

### WordPress Plugin Deployment
```bash
# Create plugin zip file
zip -r bmlt-workflow-1.1.20.zip . -x "*.git*" "node_modules/*" "vendor/*" "tests/*" "docker/*" "mockoon/*"

# Upload to WordPress.org (if you have access)
svn add bmlt-workflow-1.1.20.zip
svn commit -m "Release version 1.1.20"
```

## GitHub Actions Commands

### Trigger Release Workflow
```bash
# Create and push a tag to trigger release workflow
git tag 1.1.20
git push --tag

# This will automatically:
# - Run PHPUnit tests
# - Create release zip file
# - Publish to WordPress.org
# - Create GitHub release
```

### Check Workflow Status
```bash
# View workflow runs (via GitHub web interface)
# Go to: https://github.com/bmlt-enabled/bmlt-workflow/actions

# Check specific workflow
# Go to: https://github.com/bmlt-enabled/bmlt-workflow/actions/workflows/release.yml
```

### Debug Workflow Issues
```bash
# View workflow logs (via GitHub web interface)
# Go to Actions tab → Click on failed workflow → View logs

# Re-run failed workflow
# Go to Actions tab → Click on workflow → Re-run jobs
```

## Debug Commands

### Enable Debug Mode
```bash
# Enable debug in config.php
sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/" config.php

# Disable debug for release
sed -i "s/define('BMLTWF_DEBUG', true);/define('BMLTWF_DEBUG', false);/" config.php
```

### Log Analysis
```bash
# View WordPress debug log
tail -f wp-content/debug.log

# View plugin-specific logs
tail -f wp-content/plugins/bmlt-workflow/debug.log
```

## Common Workflows

### Complete Feature Development
```bash
# 1. Create feature branch
./support/new-branch.sh feature-name

# 2. Make changes and commit
git add .
git commit -m "Add feature: description (#issue-number)"

# 3. Push to remote
git push

# 4. Create pull request (via GitHub web interface)

# 5. After review, merge to main and create release
git switch main
git merge feature-name
git tag 1.1.20
git push --tag
```

### Bug Fix Workflow
```bash
# 1. Create bug fix branch
./support/new-branch.sh fix-issue-123

# 2. Implement fix and test
# ... make changes ...
./vendor/bin/phpunit
npx testcafe chrome tests/testcafe/

# 3. Commit fix
git add .
git commit -m "Fix: description of fix (#123)"

# 4. Push and create PR
git push
```

### Release Preparation
```bash
# 1. Update version numbers
# (use sed commands above)

# 2. Update CHANGELOG.md
# (manually add entry)

# 3. Run all tests
./vendor/bin/phpunit
npx testcafe chrome tests/testcafe/

# 4. Merge to main and create release
git switch main
git merge feature-branch
git tag 1.1.20
git push --tag

# 5. GitHub Actions will handle the rest automatically
``` 