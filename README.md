
# Laravel Blog API

A clean, modern, and production-ready Laravel Blog API built with the latest versions of Laravel and PHP. This project follows a modern folder structure, applies the latest security best practices, and is designed for scalability. It serves as the backend API for a blog platform whose frontend will be built separately using Next.js.

---

## Table of Contents

1. [API Documentation](#api-documentation)
2. [Local Setup](#local-setup)
3. [Git Hooks Automation](#git-hooks-automation)
4. [Running Tests & Coverage](#running-tests--coverage)
5. [Linting & Code Formatting](#linting--code-formatting)
6. [Static Code Analysis with Larastan](#static-code-analysis-with-larastan)

---

## API Documentation

- Access the API documentation at:

  ```
  {APP_URL}/docs/api
  ```

---

## Local Setup

To set up the project on your local machine:

1. Run the following Make command:

   ```bash
   make setup-localhost
   ```

   **Or perform manually:**

   - Copy the example environment file:

     ```bash
     cp .env.example .env
     ```


   - Copy the example environment file:

     ```bash
     php artisan key:generate
     ```

   - Create the database named `laravel_blog`.

   - Run migrations and seed the database:

     ```bash
     php artisan migrate --seed
     ```

---

## Git Hooks Automation

Automate common Git tasks using hooks:

1. Set up using Make:

   ```bash
   make setup-git-hooks
   ```

2. **Or manually:**

   - Copy the Git hooks:

     ```bash
     cp -r .githooks/* .git/hooks/
     ```

   - Make them executable:

     ```bash
     chmod +x .git/hooks/pre-commit
     chmod +x .git/hooks/pre-push
     chmod +x .git/hooks/prepare-commit-msg
     ```

---

## Running Tests & Coverage

- Review PEST documentation before writing tests:

  [PEST PHP Expectations](https://pestphp.com/docs/expectations)

### Setup for Testing Environment

1. Run:

   ```bash
   make setup-testing
   ```

2. **Or manually:**

   - Copy the testing environment file:

     ```bash
     cp .env.testing.example .env.testing
     ```

   - Create the database named `laravel_blog_testing`.

   - Run migrations and seed:

     ```bash
     php artisan --env=testing migrate --seed
     ```

### Running Tests

- Run all tests:

  ```bash
  make php-tests
  # or
  php artisan test --parallel --recreate-databases
  ```

- Run a specific test:

  ```bash
  php artisan test --filter Events/UserRegistered
  ```

- Profile slow running tests:

  ```bash
  make php-tests-profile
  # or
  php artisan test --profile
  ```

- Generate code coverage report (requires XDebug):

  ```bash
  make php-tests-report
  # or
  php artisan test --parallel --recreate-databases --coverage-html reports/coverage --coverage-clover reports/coverage.xml
  ```

#### Code Coverage Reports path:

```
reports/
  coverage/index.html
  coverage.xml
```

---

## Linting & Code Formatting

### Automated Linting with Git Hooks

- If Git hooks are set up, linting will automatically run on changed files.

### Manually Linting Codes

- Lint entire project:

  ```bash
  make lint-project
  # or
  ./vendor/bin/pint
  ```

- Lint specific folder:

  ```bash
  ./vendor/bin/pint app/Models
  ```

- Lint specific file:

  ```bash
  ./vendor/bin/pint app/Models/User.php
  ```

- Detailed linting:

  ```bash
  ./vendor/bin/pint -v
  ```

- Check for lint issues without fixing:

  ```bash
  ./vendor/bin/pint --test
  ```

- Lint only changed files:

  ```bash
  make lint-changes
  # or
  ./vendor/bin/pint --dirty
  ```

---

## Static Code Analysis with Larastan

- Run static analysis with memory limit adjustment:

  ```bash
  make larastan-project
  # or
  ./vendor/bin/phpstan analyse --memory-limit=2G
  ```

---

**Note:**
- For best results, ensure you have all required PHP extensions and dependencies installed.
- The frontend of this project will be built using Next.js and linked here in the future.
