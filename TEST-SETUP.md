# Running BuddyPress Unit Tests

## Prerequisites

1. **Install Composer dependencies**:
   ```bash
   cd /path/to/buddypress
   composer install
   ```

2. **Set up WordPress Test Suite**:
   - You need the WordPress test suite installed
   - Set the `WP_TESTS_DIR` environment variable to point to your WordPress test directory

3. **Configure Test Database**:
   - Create a separate MySQL database for tests (e.g., `buddypress_test`)
   - Configure database credentials in your test environment

## Running Tests

### Run all tests:
```bash
composer test
```
or
```bash
vendor/bin/phpunit
```

### Run tests for specific components:
```bash
# Activity component tests
vendor/bin/phpunit tests/phpunit/testcases/activity/

# Groups component tests  
vendor/bin/phpunit tests/phpunit/testcases/groups/

# Messages component tests
vendor/bin/phpunit tests/phpunit/testcases/messages/

# Friends component tests
vendor/bin/phpunit tests/phpunit/testcases/friends/

# XProfile component tests
vendor/bin/phpunit tests/phpunit/testcases/xprofile/
```

### Run tests for specific classes we modified:
```bash
# Test BP_Activity_Activity class
vendor/bin/phpunit tests/phpunit/testcases/activity/class.BP_Activity_Activity.php

# Test Groups
vendor/bin/phpunit tests/phpunit/testcases/groups/class.BP_Groups_Group.php
vendor/bin/phpunit tests/phpunit/testcases/groups/class.BP_Groups_Member.php

# Test Messages
vendor/bin/phpunit tests/phpunit/testcases/messages/class.BP_Messages_Message.php
```

### Run multisite tests:
```bash
composer test_multi
```

### Watch mode (auto-run tests on file changes):
```bash
composer test:watch
```

## Checking for PHP Errors

### Run PHP Code Sniffer:
```bash
composer phpcs
```

### Check PHP compatibility (PHP 7.0+):
```bash
composer phpcompat
```

### Fix coding standards automatically:
```bash
composer phpcbf
```

## Quick Test Commands for Our Changes

Run these commands to specifically test the components we modified:

```bash
# Test all activity-related functionality
vendor/bin/phpunit --filter Activity

# Test all groups-related functionality  
vendor/bin/phpunit --filter Groups

# Test all messages-related functionality
vendor/bin/phpunit --filter Messages

# Test all xprofile-related functionality
vendor/bin/phpunit --filter XProfile
```

## What to Look For

1. **Fatal Errors**: Any PHP fatal errors will cause tests to fail immediately
2. **Type Errors**: Our type hints might cause type errors if tests pass incorrect types
3. **Failed Assertions**: Tests that expect specific behavior that our changes might have affected

## If Tests Fail

1. Check the error message to identify which test failed
2. Look at our CHANGELOG-MODERNIZATION.md to see if we modified related code
3. Common issues might be:
   - Strict type declarations causing type mismatches
   - Return type declarations not matching actual returns
   - Parent/child class compatibility issues

## Running a Quick Smoke Test

For a quick verification, run tests on the main components we modified:

```bash
vendor/bin/phpunit tests/phpunit/testcases/activity/class.BP_Activity_Activity.php tests/phpunit/testcases/groups/class.BP_Groups_Member.php tests/phpunit/testcases/messages/class.BP_Messages_Message.php tests/phpunit/testcases/xprofile/class.BP_XProfile_Field.php
```