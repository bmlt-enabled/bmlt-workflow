# BMLT Workflow - Quick Reference Guide

## Common Development Tasks

### 1. Version Increment Workflow
**Current Version**: 1.1.19

**Files to Update**:
- `bmlt-workflow.php` (lines 25 & 30)
- `readme.txt` (Stable tag)
- `CHANGELOG.md` (add new entry)

**Commands**:
```bash
# Update version in main file
sed -i 's/Version: 1.1.19/Version: 1.1.20/' bmlt-workflow.php
sed -i "s/define('BMLTWF_PLUGIN_VERSION', '1.1.19');/define('BMLTWF_PLUGIN_VERSION', '1.1.20');/" bmlt-workflow.php

# Update readme
sed -i 's/Stable tag: 1.1.19/Stable tag: 1.1.20/' readme.txt

# Add changelog entry
echo "## 1.1.20 ($(date +%b %d, %Y))" >> CHANGELOG.md
echo "- [Description of changes]" >> CHANGELOG.md
echo "" >> CHANGELOG.md
```

### 2. GitHub Issue Workflow
**Branch Creation**:
```bash
./support/new-branch.sh fix-issue-123
```

**Commit Message Format**:
```bash
git commit -m "Fix: description of fix (#123)"
git commit -m "Feature: new feature description (#456)"
git commit -m "Docs: update documentation (#789)"
```

**Pull Request Process**:
1. Push branch: `git push`
2. Create PR on GitHub
3. Use PR template from `.cursor/github-templates.md`
4. Reference issue: `Closes #123` or `Fixes #123`

### 3. Testing Workflow
**Run All Tests**:
```bash
# PHPUnit tests
./vendor/bin/phpunit

# E2E tests
npx testcafe chrome tests/testcafe/

# Docker tests
docker-compose -f docker/docker-compose.yml run --rm php ./vendor/bin/phpunit
```

**Test Specific Files**:
```bash
# Specific PHPUnit test
./vendor/bin/phpunit tests/phpunit/src/BMLT/IntegrationTest.php

# Specific E2E test
npx testcafe chrome tests/testcafe/bmlt3x_e2e_test.js
```

### 4. Release Process
**Using Release Script**:
```bash
./support/release.sh feature-branch 1.1.20
```

**Manual Release Steps**:
1. Update version numbers (see above)
2. Update CHANGELOG.md
3. Run all tests
4. Merge to main: `git switch main && git merge feature-branch`
5. Create tag: `git tag 1.1.20 && git push --tag`

### 5. Common Code Locations

**Main Plugin File**: `bmlt-workflow.php`
- Plugin header and version info
- Main plugin class `bmltwf_plugin`
- WordPress hooks and filters

**REST API**: `src/REST/`
- `Controller.php` - Main REST controller
- `Handlers/` - Individual endpoint handlers

**BMLT Integration**: `src/BMLT/Integration.php`
- BMLT server communication
- Meeting data handling
- Service body management

**Database**: `src/BMLTWF_Database.php`
- Database operations
- Table creation and updates
- Data queries

**Admin Interface**: `admin/`
- `admin_options.php` - Settings page
- `admin_submissions.php` - Submissions management
- `admin_service_bodies.php` - Service body configuration

**Frontend**: `public/meeting_update_form.php`
- Main meeting update form
- JavaScript: `js/meeting_update_form.js`
- CSS: `css/meeting_update_form.css`

### 6. Debugging

**Enable Debug Mode**:
```bash
sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/" config.php
```

**View Logs**:
```bash
# WordPress debug log
tail -f wp-content/debug.log

# Plugin specific logs
tail -f wp-content/plugins/bmlt-workflow/debug.log
```

**Browser Debugging**:
- Open browser dev tools (F12)
- Check Console tab for JavaScript errors
- Check Network tab for API calls

### 7. Translation Workflow

**Generate Translation Files**:
```bash
# Make .pot file
wp i18n make-pot . lang/bmlt-workflow.pot

# Generate JSON files
wp i18n make-json lang --no-purge

# Update .mo files
wp i18n update-po lang/bmlt-workflow.pot
```

**Add Translatable Strings**:
```php
// PHP
__('String to translate', 'bmlt-workflow');
_e('String to echo', 'bmlt-workflow');

// JavaScript
wp.i18n.__('String to translate', 'bmlt-workflow');
```

### 8. Common Issues & Solutions

**Plugin Not Loading**:
- Check WordPress debug log
- Verify PHP version compatibility
- Check for syntax errors in PHP files

**BMLT Connection Issues**:
- Verify BMLT server URL in settings
- Check BMLT server is accessible
- Verify API endpoints are working

**Form Submission Problems**:
- Check browser console for JavaScript errors
- Verify REST API endpoints are registered
- Check WordPress permalink settings

**Translation Issues**:
- Regenerate translation files
- Clear WordPress cache
- Check .mo files are generated

### 9. Development Environment Setup

**Docker Environment**:
```bash
# Start environment
docker-compose -f docker/docker-compose.yml up -d

# Run tests
docker-compose -f docker/docker-compose.yml run --rm php ./vendor/bin/phpunit

# Stop environment
docker-compose -f docker/docker-compose.yml down
```

**Local Development**:
```bash
# Install dependencies
composer install
npm install

# Run tests
./vendor/bin/phpunit
npx testcafe chrome tests/testcafe/
```

### 10. Code Quality

**PHP Linting**:
```bash
find src/ -name "*.php" -exec php -l {} \;
```

**JavaScript Linting**:
```bash
npx eslint js/
npx eslint js/ --fix
```

**WordPress Coding Standards**:
- Follow WordPress PHP coding standards
- Use WordPress hooks and filters
- Properly enqueue scripts and styles
- Sanitize and validate data

### 11. Emergency Procedures

**Revert to Previous Version**:
```bash
git log --oneline
git checkout <commit-hash>
```

**Rollback Release**:
```bash
git tag -d 1.1.20
git push --delete origin 1.1.20
git reset --hard HEAD~1
git push --force
```

**Database Recovery**:
```bash
# Restore from backup
wp db import backup.sql

# Or restore specific tables
wp db import --tables=wp_bmltwf_submissions backup.sql
```

## Quick Commands Reference

| Task | Command |
|------|---------|
| Create branch | `./support/new-branch.sh branch-name` |
| Run tests | `./vendor/bin/phpunit` |
| Run E2E tests | `npx testcafe chrome tests/testcafe/` |
| Create release | `git tag version && git push --tag` |
| Update version | `sed -i 's/1.1.19/1.1.20/' bmlt-workflow.php` |
| Enable debug | `sed -i "s/DEBUG.*false/DEBUG', true/" config.php` |
| Generate translations | `wp i18n make-json lang --no-purge` |
| Start Docker | `docker-compose -f docker/docker-compose.yml up -d` | 