## Laravel Blog

A clean, production-ready latest Laravel and PHP support, latest security improvements, modern folder structure. Perfect for building scalable web apps with Laravel's newest features.

## Git Hooks for Local Automation

-   Setup Hooks using make: `make setup-git-hooks` or follow below steps.
-   copy files to git hooks: `cp -r .githooks/* .git/hooks/`
-   make these files executable: `chmod +x .git/hooks/pre-commit && chmod +x .git/hooks/pre-push && chmod +x .git/hooks/prepare-commit-msg`

### Larastan for Static Code Analysis
-   Run Static Analysis: `make larastan-project` OR `./vendor/bin/phpstan analyse --memory-limit=2G`

## PHPUnit Testing & Coverage

-   Read PEST Docs before writing tests: `https://pestphp.com/docs/expectations`
-   Before running any tests setup testing env by using make command `make setup-testing` or follow these steps `cp .env.testing.example .env.testing`, create db `mg_db_test`, and migrate+seed `php artisan --env=testing migrate --seed`
-   Run all tests: `make php-tests` OR `php artisan test --parallel --recreate-databases`
-   Perform single file test: `php artisan test --filter Events/UserRegistered`
-   Find the slowest running tests: `make php-tests-profile` OR `php artisan test --profile`
-   Get coverage report, Install and Enable XDebug then run this command: `make php-tests-report` OR `php artisan test --parallel --recreate-databases --coverage-html reports/coverage --coverage-clover reports/coverage.xml`

##### Test Reports can be found in `reports/` folder.

-   Reports folder structure:
    ```
    reports/
      coverage/index.html
      coverage.xml
      junit_result.xml
    ```

## Lint/Pint Format Fixing files

-   If you have setup local automation for git hook then lint will be auto performed on changed files
-   Lint All files: `make lint-project` OR `./vendor/bin/pint`
-   Lint Specific Folder: `./vendor/bin/pint app/Models`
-   Lint Specific File: `./vendor/bin/pint app/Models/User.php`
-   Get Detailed Linting: `./vendor/bin/pint -v`
-   Check if files have lint issues: `./vendor/bin/pint --test`
-   Only Lint uncommitted changed files: `make lint-changes` OR `./vendor/bin/pint --dirty`
