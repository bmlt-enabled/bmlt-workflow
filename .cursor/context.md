# BMLT Workflow Plugin - Development Context & Cursor Rules

## About Cursor Rules
Cursor rules are project-specific guidelines and prompts that help you work more efficiently with your codebase. They provide contextual assistance for common development tasks, debugging, and project-specific workflows.

## Project Overview
- **Plugin Name**: BMLT Workflow
- **Current Version**: 1.1.19
- **Description**: WordPress plugin for BMLT (Basic Meeting List Toolbox) meeting management workflows
- **Author**: @nigel-bmlt
- **License**: GNU General Public License v3

## Key Files and Directories

### Core Plugin Files
- `bmlt-workflow.php` - Main plugin file with WordPress plugin header and core class
- `src/` - PHP source code organized by namespace
  - `BMLT/Integration.php` - BMLT server integration
  - `REST/` - REST API controllers and handlers
  - `BMLTWF_Database.php` - Database operations
  - `BMLTWF_Constants.php` - Plugin constants
  - `BMLTWF_Debug.php` - Debugging utilities

### Configuration Files
- `config.php` - Plugin configuration (debug settings, etc.)
- `composer.json` - PHP dependencies
- `package.json` - Node.js dependencies
- `phpunit.xml` - PHPUnit test configuration

### Version Management
- `CHANGELOG.md` - Release history and changes
- `readme.txt` - WordPress plugin readme with version info
- `support/new-branch.sh` - Script to create new feature branches
- `.github/workflows/` - GitHub Actions workflows for CI/CD and releases

### Testing Infrastructure
- `tests/phpunit/` - PHPUnit tests
- `tests/testcafe/` - End-to-end tests
- `mockoon/` - Mock BMLT server configurations for testing
- `docker/` - Docker testing environment

### Frontend Assets
- `js/` - JavaScript files
- `css/` - Stylesheets
- `public/` - Public-facing PHP templates
- `admin/` - WordPress admin interface files

### Internationalization
- `lang/` - Translation files (.po, .mo, .json)
- `templates/` - Email templates

## Version Management Workflow

### Current Version References
The plugin version is defined in multiple places:
1. `bmlt-workflow.php` line 25: `Version: 1.1.19`
2. `bmlt-workflow.php` line 30: `define('BMLTWF_PLUGIN_VERSION', '1.1.19');`
3. `readme.txt`: `Stable tag: 1.1.19`

### Release Process
1. Create feature branch: `./support/new-branch.sh feature-name`
2. Make changes and commit
3. Update version numbers in all files
4. Update CHANGELOG.md
5. Merge feature branch to main
6. Create git tag: `git tag version-number && git push --tag`
7. GitHub Actions automatically triggers release workflow on tag push
8. Release workflow runs tests, creates zip file, and publishes to WordPress.org

## Development Conventions

### PHP Code Style
- Uses namespaces: `bmltwf\`
- Classes in `src/` directory
- WordPress coding standards
- Uses traits for shared functionality

### JavaScript/CSS
- Files in `js/` and `css/` directories
- Uses WordPress enqueue functions
- Includes third-party libraries (Select2, DataTables)

### Testing
- PHPUnit for unit tests
- TestCafe for E2E tests
- Mockoon for BMLT API mocking
- Docker environment for consistent testing

### Git Workflow
- Feature branches for development
- Main branch for releases
- Tags for version releases
- Commit messages should reference GitHub issues

## Common Development Tasks

### Adding New Features
1. Create feature branch
2. Add code in appropriate `src/` directory
3. Add tests in `tests/` directory
4. Update version numbers
5. Update CHANGELOG.md
6. Create pull request

### Bug Fixes
1. Create branch for issue
2. Implement fix
3. Add/update tests
4. Test thoroughly
5. Update CHANGELOG.md
6. Create pull request

### Release Preparation
1. Update all version references
2. Update CHANGELOG.md
3. Run tests
4. Use release script
5. Create git tag

## Important Notes
- Plugin requires WordPress 5.2+
- Tested up to WordPress 6.6.1
- Supports BMLT 2.x and 3.x
- Has multisite support
- Includes internationalization support
- Uses WordPress REST API
- Integrates with Google Maps for geocoding
- Uses GitHub Actions for automated releases and CI/CD
- Releases are triggered by git tags and automatically published to WordPress.org 