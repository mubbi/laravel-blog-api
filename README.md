# Laravel Blog API

A clean, modern, and production-ready Laravel Blog API built with the latest versions of Laravel and PHP. This project follows a modern folder structure, applies the latest security best practices, and is designed for scalability. It serves as the backend API for a blog platform whose frontend will be built separately using Next.js.

---

## Table of Contents

1. [API Documentation](#api-documentation)
2. [Docker Setup](#docker-setup)
3. [Development Workflow](#development-workflow)
4. [Testing](#testing)
5. [Code Quality](#code-quality)
6. [SonarQube Analysis](#sonarqube-analysis)
7. [Git Hooks](#git-hooks)
8. [Help & Troubleshooting](#help--troubleshooting)

---

## API Documentation

Access the API documentation at:
```
http://localhost:8081/docs/api
```

---

## Docker Setup

This project uses **Docker exclusively** for both development and testing environments to ensure consistency across all platforms. The setup is **fully automated** with zero manual intervention required.

### Prerequisites

- Docker and Docker Compose installed
- Git (for hooks)
- Make (for Windows users: install via Chocolatey or use Git Bash)

### 🚀 Quick Start (Recommended)

**One-command setup** for complete development environment:

```bash
# Complete automated setup (development + testing environments)
make docker-dev
```

This single command will:
- ✅ Clean up any existing Docker containers and images
- ✅ **Automatically generate APP_KEY** for all environments using OpenSSL
- ✅ **Copy and configure environment files** from examples
- ✅ Start both development and testing Docker containers
- ✅ Install all Composer dependencies
- ✅ Run database migrations and seeders
- ✅ Set up queue workers with smart readiness detection
- ✅ Provide access URLs and next steps

### Alternative Setup Options

**Development environment only:**
```bash
make docker-dev
```

**Testing environment only:**
```bash
make docker-test
```

**Environment files only:**
```bash
make docker-setup-env
```

### 📋 Access Points

After setup completion:
- **Laravel API**: http://localhost:8081
- **API Health Check**: http://localhost:8081/api/health
- **API Documentation**: http://localhost:8081/docs/api
- **SonarQube Dashboard**: http://localhost:9000 (when started)
- **MySQL Main**: localhost:3306 (laravel_user/laravel_password)
- **MySQL Test**: localhost:3307 (laravel_user/laravel_password)
- **Redis**: localhost:6379

### � Environment File Structure

This project maintains a clean environment file structure:

**Tracked in Git:**
- `.env.docker.example` - Main development environment template
- `.env.testing.docker.example` - Testing environment template

**Generated automatically (ignored by Git):**
- `.env` - Main development environment (Laravel's default)
- `.env.testing` - Testing environment (Laravel's testing default)

All working environment files are automatically generated from templates with proper APP_KEY generation.

### 🔧 Container Management

```bash
# Check container status and access points
make docker-status

# Check application health
make docker-health

# View logs from all containers
make docker-logs

# View specific container logs
make docker-logs-app     # Main app logs
make docker-logs-queue   # Queue worker logs

# Container control
make docker-up           # Start existing containers
make docker-down         # Stop all containers
make docker-restart      # Restart containers

# Cleanup
make docker-cleanup      # Complete cleanup (containers, images, volumes)
```

---

## Development Workflow

### Container Access

```bash
# Access main container shell
make docker-shell

# Access test container shell (if test environment is running)
make docker-test-shell
```

### Running Artisan Commands

```bash
# Run any artisan command in Docker
make docker-artisan ARGS="migrate --seed"
make docker-artisan ARGS="make:controller ApiController"
make docker-artisan ARGS="queue:work"
```

### Queue Management

```bash
# Check queue worker status
make docker-queue-status

# View queue worker logs
make docker-logs-queue
```

### Xdebug Configuration

Xdebug is disabled by default for better performance but can be enabled:

```bash
# Enable Xdebug for debugging
XDEBUG_MODE=debug make docker-dev

# Enable for coverage reports
XDEBUG_MODE=coverage make docker-dev

# Disable Xdebug (default)
XDEBUG_MODE=off make docker-dev
```

---

## Testing

This project uses PEST for testing with **automated Docker-based testing environment**.

### 🧪 Quick Testing

**Run all tests with automated setup:**
```bash
make docker-test
```

**Run tests with coverage report:**
```bash
make docker-test-coverage
```

### Testing Environment Details

The automated testing setup creates:
- **Isolated test database**: `laravel_blog_test` on port 3307
- **Separate Redis instance**: For testing on port 6380
- **Unique APP_KEY**: Generated specifically for test environment
- **Fresh migrations**: Automatically run with seeders

### Coverage Reports

Coverage reports are generated at:
```
reports/
  coverage/index.html    # HTML coverage report
  coverage.xml          # XML coverage report for CI/CD
```

### Coverage Requirements

All tests enforce a **minimum of 80% code coverage**:
- Tests will fail if coverage falls below this threshold
- Reports highlight uncovered code paths
- Critical business logic must be properly tested

### Manual Test Environment Management

```bash
# Start test environment only (for debugging)
make docker-test-up

# Stop test environment
make docker-test-down
```

---

## Code Quality

---

## SonarQube Analysis

**Comprehensive code quality analysis** with SonarQube 25.7.0 Community Edition, integrated with PHPStan static analysis and PHPUnit test coverage.

### 🚀 Quick Start

**Complete automated setup and analysis:**
```bash
make docker-sonarqube-analyze
```

This single command will:
- ✅ Setup SonarQube environment file if missing
- ✅ Start SonarQube server with PostgreSQL database
- ✅ Validate authentication token configuration
- ✅ Run PHPStan static analysis
- ✅ Execute PHPUnit tests with coverage
- ✅ Upload all results to SonarQube
- ✅ Open dashboard for review

### 🔧 First-Time Setup

**1. Environment Setup:**
```bash
make docker-sonarqube-setup-env
```

**2. Start SonarQube Server:**
```bash
make docker-sonarqube-start
```

**3. Configure Authentication Token:**
```bash
make docker-sonarqube-setup-token
```

This interactive helper will:
- Check if SonarQube server is running
- Guide you through token generation at http://localhost:9000
- Automatically save the token to your environment file

### 📊 Analysis Features

- **Code Quality**: Bugs, vulnerabilities, and code smells detection
- **Security Analysis**: Security hotspots and vulnerability scanning
- **Test Coverage**: PHPUnit test coverage integration
- **Static Analysis**: PHPStan results integration
- **Quality Gates**: Automated quality threshold enforcement
- **Technical Debt**: Measure and track technical debt
- **Code Duplication**: Detect duplicate code blocks

### 🎯 Quality Standards

The project enforces these quality standards:
- **Coverage**: ≥ 80% for new code
- **Duplications**: ≤ 3% for new code
- **Security Rating**: A (no vulnerabilities)
- **Maintainability Rating**: A
- **New Issues**: 0 (no new bugs or code smells)

### 📋 Available Commands

| Command | Description |
|---------|-------------|
| `make docker-sonarqube-analyze` | Complete analysis (recommended) |
| `make docker-sonarqube-start` | Start SonarQube server |
| `make docker-sonarqube-stop` | Stop SonarQube server |
| `make docker-sonarqube-setup-env` | Setup environment file |
| `make docker-sonarqube-setup-token` | Interactive token setup |
| `make docker-sonarqube-scan` | Run scanner only |
| `make docker-sonarqube-reports` | Generate reports only |
| `make docker-sonarqube-dashboard` | Open SonarQube dashboard |
| `make docker-sonarqube-clean` | Clean all data |
| `make docker-sonarqube-ci` | CI/CD analysis (external server) |

### 🌐 Access Points

- **SonarQube Dashboard**: http://localhost:9000
- **Default Credentials**: admin/admin (change on first login)
- **Token Management**: http://localhost:9000/account/security

### 📚 Documentation

For detailed setup, configuration, and troubleshooting information, see: [containers/SONARQUBE.md](containers/SONARQUBE.md)

---

### Linting with Pint

**Automated code formatting** using Laravel Pint:

```bash
# Lint entire project
make docker-lint

# Lint only changed files (faster)
make docker-lint-dirty
```

### Static Analysis with Larastan

**Static code analysis** for better code quality:

```bash
# Run static analysis
make docker-analyze
```

### Quality Checks

All code quality tools run within Docker containers:
- **No local PHP installation required**
- **Consistent results across all environments**
- **Integrated with testing workflow**

---

## Git Hooks

Automate code quality checks on Git operations:

### Setup

```bash
# Install Git hooks (no environment requirements)
make setup-git-hooks
```

### What the hooks do:

- **pre-commit**: Runs linting on changed files
- **pre-push**: Runs tests with coverage validation
- **prepare-commit-msg**: Formats commit messages

### Manual Hook Installation

If you prefer manual setup:
```bash
cp -r .githooks/ .git/hooks/
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/pre-push
chmod +x .git/hooks/prepare-commit-msg
```

---

## Help & Troubleshooting

### Getting Help

```bash
# Show all available commands with descriptions
make help
```

### Common Issues

1. **Port conflicts**: Ensure ports 8081, 3306, 3307, 6379 are not in use
2. **Docker not running**: Make sure Docker Desktop is running
3. **Permission issues**: On Linux/macOS, ensure your user is in the docker group

### Container Architecture

| Service | Container Name | Ports | Purpose |
|---------|---------------|-------|---------|
| **Laravel App** | `laravel_blog_api` | 8081:80 | Main application with Nginx + PHP-FPM |
| **MySQL** | `laravel_blog_api_mysql` | 3306:3306 | Development database |
| **Redis** | `laravel_blog_api_redis` | 6379:6379 | Cache and session store |
| **Queue Worker** | `laravel_blog_api_queue` | - | Background job processor |
| **MySQL Test** | `laravel_blog_api_mysql_test` | 3307:3306 | Testing database |
| **Redis Test** | `laravel_blog_api_redis_test` | 6380:6379 | Testing cache store |
| **SonarQube** | `laravel_blog_sonarqube` | 9000:9000 | Code quality analysis (when started) |
| **SonarQube DB** | `laravel_blog_sonarqube_db` | 5432:5432 | SonarQube PostgreSQL database |

### Available Commands

For a complete list of all available commands, run:
```bash
make help
```

Key command categories:
- **Environment Setup**: `docker-setup-*`, `docker-verify-env`
- **Development**: `docker-up`, `docker-down`, `docker-restart`, `docker-shell`
- **Testing**: `docker-test`, `docker-test-coverage`, `docker-test-*`
- **Code Quality**: `docker-lint`, `docker-analyze`, `docker-sonarqube-*`
- **Utilities**: `docker-logs`, `docker-status`, `docker-health`

---

**Note:** This project is designed to work exclusively with Docker. All development, testing, and code quality tools are containerized for consistency and ease of use.
