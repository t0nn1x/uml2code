# Testing Documentation

## Overview

This document describes the standard procedures for executing and developing tests within the application. The testing framework ensures code quality and system reliability through comprehensive unit and integration testing.

## Test Environment Setup

### 1. Database Configuration

Before running tests, ensure the test database exists. Create it with the following command:

```bash
docker compose exec postgres psql -U uml2code_user postgres -c "CREATE DATABASE uml2code_test_db;"
```

### 2. Test File Generation

To generate a new test file (for example, a controller test), use the Symfony Maker Bundle:

```bash
docker compose exec php php bin/console make:test WebTestCase RegistrationControllerTest
```

This command creates a new test file in the `tests/` directory with the appropriate structure and namespace.

## Test Execution

### Running All Tests

To execute the complete test suite, run:

```bash
docker compose exec php env APP_ENV=test php bin/phpunit
```

This command runs all tests in the `tests/` directory using the test environment configuration.

### Running Specific Tests

For targeted testing, you can run specific test classes or methods:

```bash
# Run a specific test class
docker compose exec php env APP_ENV=test php bin/phpunit tests/Controller/RegistrationControllerTest.php

# Run a specific test method
docker compose exec php env APP_ENV=test php bin/phpunit --filter testRegistrationFlow
```

## Best Practices

1. **Test Isolation**: Ensure each test is independent and does not rely on other tests
2. **Database State**: Use database transactions or fixtures to maintain clean test state
3. **Environment Variables**: Always use the test environment (`APP_ENV=test`) for testing
4. **Coverage**: Aim for comprehensive test coverage of critical application functionality

## Additional Resources

For detailed information on testing methodologies and advanced configuration, refer to the official Symfony and PHPUnit documentation. 
