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

This project uses Docker for both development and testing environments to ensure consistency. The setup is **fully automated** with zero manual intervention required.

### � Environment File Structure

This project maintains a clean environment file structure:

**Tracked in Git:**
- `.env.docker.example` - Main development environment template
- `.env.testing.docker.example` - Testing environment template

**Generated automatically (ignored by Git):**
- `.env` - Main development environment (Laravel's default)
- `.env.testing` - Testing environment (Laravel's testing default)

All working environment files are automatically generated from templates with proper APP_KEY generation.

### �🚀 Quick Start (Recommended)

**One-command setup** for complete development environment:

```bash
# Complete automated setup (main + testing environments)
make docker-setup-complete
```

This single command will:
- ✅ Clean up any existing Docker containers and images
- ✅ **Automatically generate APP_KEY** for all environments
- ✅ **Copy and configure environment files** from examples
- ✅ Start both main and testing Docker containers
- ✅ Install all Composer dependencies
- ✅ Run database migrations and seeders
- ✅ Set up queue workers with smart readiness detection
- ✅ Provide access URLs and next steps

### Alternative Setup Options

**Development environment only:**
```bash
make docker-setup-local
```

**Testing environment only:**
```bash
make docker-setup-testing
```

**Environment files only:**
```bash
make docker-setup-env
```

### 📋 Access Points

After setup completion:
- **API**: http://localhost:8081
- **API Health Check**: http://localhost:8081/api/health
- **API Documentation**: http://localhost:8081/docs/api
- **MySQL Main**: localhost:3306 (laravel_user/laravel_password)
- **MySQL Test**: localhost:3307 (laravel_user/laravel_password)
- **Redis**: localhost:6379

### Environment Files (Auto-Generated)

The automated setup handles all environment files:

- **`.env.docker.example`** → **`.env`** (main development environment)
- **`.env.testing.docker.example`** → **`.env.testing`** (testing environment)

**🔑 APP_KEY Generation:**
- Unique keys automatically generated using OpenSSL
- Different keys for development and testing environments
- No manual `php artisan key:generate` needed

### 🔧 Monitoring & Management

```bash
# Check container status
make docker-status

# View logs
make docker-logs

# Check application readiness
make docker-check-ready

# Stop containers
make docker-down

# Complete cleanup
make docker-cleanup
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

- Review PEST documentation before writing tests: [PEST PHP Expectations](https://pestphp.com/docs/expectations)

### 🧪 Automated Testing Setup

**Testing environment is automatically configured with:**
- Isolated test database (MySQL on port 3307)
- Separate Redis instance for testing
- Unique APP_KEY for test environment
- Automatic migrations and seeders

### Quick Testing Commands

**Setup testing environment** (if not using `docker-setup-complete`):
```bash
make docker-setup-testing
```

**Run tests in Docker** (recommended):
```bash
# Run all tests with fresh database
make docker-tests

# Run tests with coverage report
make docker-tests-coverage
```

**Run tests locally** (requires local setup):
```bash
# Run all tests with 80% minimum coverage requirement
make php-tests

# Run specific test with coverage validation
php artisan test --filter Events/UserRegistered --stop-on-failure --coverage --min=80

# Profile slow tests with coverage requirements
make php-tests-profile

# Generate coverage report with 80% minimum requirement
make php-tests-report
```

### Testing Environment Details

The automated setup creates:
- **Test Database**: `laravel_blog_test` on `mysql_test:3306` (external port 3307)
- **Test Redis**: Isolated instance on port 6380
- **Environment File**: `.env.testing` (generated from `.env.testing.docker.example`)
- **Dependencies**: Composer packages installed automatically
- **APP_KEY**: Unique key generated for test environment

  ```bash
  make php-tests-report
  # or
  php artisan test --parallel --recreate-databases --coverage-html reports/coverage --coverage-clover reports/coverage.xml --stop-on-failure --min=80
  ```

#### Code Coverage Reports path:

```
reports/
  coverage/index.html
  coverage.xml
```

#### Code Coverage Requirements

All test commands now enforce a **minimum of 80% code coverage**. Tests will fail if coverage falls below this threshold:

- `--min=80`: Enforces 80% minimum coverage requirement
- `--stop-on-failure`: Stops execution on first test failure for faster feedback
- `--coverage`: Enables coverage analysis

**Coverage enforcement is active in:**
- Local test commands (`make php-tests`, `make php-tests-profile`, `make php-tests-report`)
- Docker test commands (`make docker-tests`, `make docker-tests-coverage`)
- Git hooks (pre-push hook)
- Composer test script (`composer test`)

**If tests fail due to insufficient coverage:**
1. Review the coverage report at `reports/coverage/index.html`
2. Add tests for uncovered code paths
3. Ensure critical business logic is properly tested

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

### 🔄 Migration from Manual Setup

**If you've been using the old manual setup process:**

1. **Clean up existing setup:**
   ```bash
   make docker-cleanup
   ```

2. **Use new automated setup:**
   ```bash
   make docker-setup-complete
   ```

3. **Verify everything works:**
   ```bash
   make docker-status
   ```

**What's changed:**
- ✅ **APP_KEY now auto-generated** - no more manual `php artisan key:generate`
- ✅ **Environment files auto-created** - no more copying and editing files
- ✅ **Testing environment included** - both dev and test setups in one command
- ✅ **Zero manual intervention** - everything happens automatically
- ✅ **Better error handling** - clear messages if something goes wrong

---

**Note:**
- For best results, ensure you have all required PHP extensions and dependencies installed.
- The frontend of this project will be built using Next.js and linked here in the future.
