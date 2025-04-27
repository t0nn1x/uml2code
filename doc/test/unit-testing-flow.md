# Testing Flow

This document describes the standard flow for running and writing tests in this project.

## 1. Create the Test Database

Before running tests, ensure the test database exists. You can create it with the following command:

```sh
docker compose exec postgres psql -U uml2code_user postgres -c "CREATE DATABASE uml2code_test_db;"
```

## 2. Generate a Test File

To generate a new test file (for example, a controller test), use the Symfony Maker Bundle:

```sh
docker compose exec php php bin/console make:test WebTestCase RegistrationControllerTest
```

This will create a new test file in the `tests/` directory.

## 3. Run the Tests

To execute the test suite, run:

```sh
docker compose exec php env APP_ENV=test php bin/phpunit
```

This will run all tests in the `tests/` directory using the test environment.

---

For more details, see the Symfony and PHPUnit documentation. 
