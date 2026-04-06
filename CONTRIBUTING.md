# Contributing to BMLT Workflow

## Testing

All tests are located in the `tests/` directory.

### Unit Tests (PHPUnit)

Unit tests use PHPUnit to test individual components and functions.

**Requirements:**
- Composer

**Setup:**
```bash
composer update
```

**Running tests:**
```bash
./vendor/bin/phpunit
```

### Integration Tests (TestCafe)

Integration tests use TestCafe for end-to-end browser testing with Mockoon providing BMLT API mocking.

**Requirements:**
- Node.js and npm
- TestCafe
- Mockoon (desktop application)

**Setup:**
```bash
npm install -g testcafe
```

Download and install Mockoon from: https://mockoon.com/

**Running tests:**

1. Start Mockoon UI and load the environment from `mockoon/` directory
2. Start the mock BMLT API server in Mockoon
3. Run TestCafe tests from the root directory:
```bash
testcafe
```

**About Mockoon:**
Mockoon provides a mock BMLT Root Server API for testing without requiring a live BMLT instance. The mock API configuration is stored in the `mockoon/` directory and simulates BMLT responses for service bodies, meetings, formats, and authentication.

## Submitting Changes

1. Fork the repository
2. Create a feature branch
3. Write tests for your changes
4. Ensure all tests pass
5. Submit a pull request

## Version bumping and release

The version number is retrieved from two places which need to be manually updated before release:
```
/readme.txt
/bmlt-workflow.php
```

To release you can run the release script from support/

```bash
sh support/release.sh <branch-from> <target-version-number>
```

eg. to release version 1.1.40

```bash
sh support/release.sh 1.1.39-fixes 1.1.40
```


## Issues

Report issues at: https://github.com/bmlt-enabled/bmlt-workflow/issues

For questions, reach out on BMLT Slack: #wordpress-BMLT-workflow
