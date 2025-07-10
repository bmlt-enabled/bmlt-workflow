# Cursor Rules for BMLT Workflow Plugin

## What are Cursor Rules?

Cursor rules are project-specific guidelines and prompts that help you work more efficiently with your codebase. They provide contextual assistance for common development tasks, debugging, and project-specific workflows.

## How to Use Cursor Rules

1. **Open Cursor** in your project
2. **Access rules** through Cursor's AI assistant
3. **Reference specific rules** when asking questions about the project
4. **Get contextual help** based on the BMLT Workflow plugin structure

## Project Structure

This `.cursor` directory contains:

- **`rules.json`** - Main rules configuration with 11 specialized rule sets
- **`context.md`** - Project context and structure information
- **`commands.md`** - Complete command reference for development tasks
- **`github-templates.md`** - GitHub issue templates and common responses
- **`quick-reference.md`** - Quick reference guide for common tasks

## Available Rule Sets

### 1. BMLT Workflow Development
Core development tasks for the BMLT Workflow WordPress plugin

### 2. Version Management & GitHub Releases
Managing plugin versions, changelog updates, and GitHub Actions release process

### 3. GitHub Issues & Bug Fixes
Working with GitHub tickets, bug reports, and feature requests

### 4. Testing & Quality Assurance
PHPUnit tests, TestCafe E2E tests, and quality assurance processes

### 5. GitHub Actions & CI/CD
GitHub Actions workflows, continuous integration, and deployment processes

### 6. WordPress Plugin Development
WordPress-specific development tasks and best practices

### 7. Internationalization & Localization
Working with translations, language files, and i18n/l10n

### 8. BMLT Integration & API
Working with BMLT server integration and API endpoints

### 9. Frontend Development
JavaScript, CSS, and frontend user interface development

### 10. Database & Data Management
Database operations, migrations, and data handling

### 11. Documentation & User Support
Creating documentation, user guides, and support materials

## Example Usage

When working on a bug fix, you can reference the rules:

- "According to the GitHub Issues & Bug Fixes rules, how should I create a branch for issue #123?"
- "Based on the Version Management rules, what files need version updates?"
- "Following the Testing rules, what tests should I run before committing?"

## Benefits

- **Contextual Knowledge**: Rules understand your project structure and conventions
- **Consistent Workflows**: Ensures everyone follows the same processes
- **Faster Development**: Quick access to project-specific guidance
- **Reduced Errors**: Built-in best practices and common pitfalls

## Current Project Info

- **Plugin Version**: 1.1.19
- **WordPress Compatibility**: 5.2+ (tested up to 6.6.1)
- **BMLT Support**: 2.x and 3.x
- **Release Process**: GitHub Actions automated workflow
- **Testing**: PHPUnit + TestCafe E2E tests

## Getting Help

If you need help with Cursor rules or the BMLT Workflow plugin:

1. Check the relevant rule set in `rules.json`
2. Reference the context and commands files
3. Use the quick reference for common tasks
4. Follow the GitHub templates for issue management 