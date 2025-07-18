# Laravel Blog API

A clean, modern, and production-ready Laravel Blog API built with the latest versions of Laravel and PHP. This project follows a modern folder structure, applies the latest security best practices, and is designed for scalability.

---

## Table of Contents

1. [Quick Setup](#-quick-setup)
2. [API Documentation](#api-documentation)
3. [Development Workflow](#development-workflow)
4. [Testing](#testing)
5. [Code Quality](#code-quality)
6. [SonarQube Analysis (Optional)](#sonarqube-analysis-optional)
7. [Semantic Commits](#semantic-commits)
8. [Help & Troubleshooting](#help--troubleshooting)

---

## 🚀 Quick Setup

### Prerequisites

- Docker and Docker Compose installed
- Git (for hooks)
- Node.js 18+ (for commit tools)
- Make (for Windows users: install via Chocolatey or use Git Bash)

### Complete Local Development Setup

**One command to set up everything:**

```bash
make local-setup
```

This single command will:
- ✅ **Setup Docker containers** for Laravel API and testing
- ✅ **Install Composer dependencies** automatically  
- ✅ **Configure databases** with migrations and seeders
- ✅ **Setup Git hooks** for code quality enforcement
- ✅ **Install semantic commit tools** (Husky, Commitizen)
- ✅ **Configure PHPStan and unit tests** on git push
- ✅ **Provide access URLs** and next steps

### Optional: SonarQube Code Quality Analysis

```bash
make sonarqube-setup
```

### 📋 Access Points

After setup completion:
- **Laravel API**: http://localhost:8081
- **API Health Check**: http://localhost:8081/api/health
- **API Documentation**: http://localhost:8081/docs/api
- **SonarQube Dashboard**: http://localhost:9000 (when started)
- **MySQL**: localhost:3306 (laravel_user/laravel_password)
- **MySQL Test**: localhost:3307 (laravel_user/laravel_password)
- **Redis**: localhost:6379

---

## API Documentation

Access the API documentation at:
```
http://localhost:8081/docs/api
```

---

## Development Workflow

### Daily Development Commands

```bash
# Interactive semantic commit
make commit

# Run tests
make test

# Run tests with coverage
make test-coverage

# Code quality checks
make lint                # Run Pint linter
make analyze             # Run PHPStan static analysis

# Container management
make docker-up           # Start containers
make docker-down         # Stop containers
make status              # Check container status
make logs                # View logs
make shell               # Access main container shell
```

### Artisan Commands

```bash
# Run any artisan command
make artisan ARGS="migrate --seed"
make artisan ARGS="make:controller ApiController"
```

---

## Testing

This project uses **PEST** for testing with **automated Docker-based testing environment**.

### Quick Testing

```bash
# Run all tests with automated setup
make test

# Run tests with coverage report
make test-coverage
```

### Testing Environment Details

The automated testing setup:
- **Isolated test database**: `laravel_blog_test` on port 3307
- **Separate Redis instance**: For testing on port 6380
- **Fresh migrations**: Automatically run with seeders
- **Coverage reports**: Generated at `reports/coverage/index.html`

### Coverage Requirements

- **Minimum 80% code coverage** enforced
- **HTML reports** available at `reports/coverage/index.html`
- **XML reports** for CI/CD at `reports/coverage.xml`

---

## Code Quality

### Automated Code Quality Tools

```bash
# Run linting with Laravel Pint
make lint

# Lint only changed files (faster)
make lint-dirty

# Run static analysis with PHPStan
make analyze
```

### Git Hooks

Automated quality checks on Git operations:
- **pre-commit**: Runs linting on changed files
- **pre-push**: Runs tests with PHPStan analysis  
- **prepare-commit-msg**: Formats commit messages

---

## SonarQube Analysis (Optional)

**Comprehensive code quality analysis** with SonarQube integration.

### Quick Setup

```bash
# Complete SonarQube setup
make sonarqube-setup
```

### Manual Setup Steps

1. **Start SonarQube server:**
   ```bash
   make sonarqube-start
   ```

2. **Generate authentication token:**
   - Visit http://localhost:9000 (admin/admin)
   - Go to Account → Security → Tokens
   - Generate a new token

3. **Configure token:**
   ```bash
   make sonarqube-setup-token
   ```

4. **Run analysis:**
   ```bash
   make sonarqube-analyze
   ```

### SonarQube Features

- **Code Quality**: Bugs, vulnerabilities, code smells
- **Security Analysis**: Security hotspots and vulnerabilities
- **Test Coverage**: PHPUnit coverage integration
- **Static Analysis**: PHPStan results integration
- **Quality Gates**: Automated threshold enforcement

### SonarQube Commands

```bash
make sonarqube-start      # Start SonarQube server
make sonarqube-analyze    # Run complete analysis
make sonarqube-dashboard  # Open dashboard
make sonarqube-stop       # Stop SonarQube server
```

---

## Semantic Commits

This project enforces **semantic commits** following the [Conventional Commits](https://www.conventionalcommits.org/) specification.

### Commit Workflow

**Interactive guided commits (recommended):**
```bash
make commit
```

**Manual commits (auto-validated):**
```bash
git add .
git commit -m "feat(auth): add user authentication endpoint"
```

### Commit Message Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**Valid types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `perf`, `ci`, `build`, `revert`

**Examples:**
```bash
feat(api): add user registration endpoint
fix(auth): resolve token validation issue  
docs: update API documentation
test(api): add integration tests for auth
```

### Automated Release Process

- **Semantic versioning** based on commit types
- **Automated changelog** generation
- **GitHub releases** with proper tagging
- **Version bumping** in package files

### Commit Commands

```bash
make commit              # Interactive semantic commit
make validate-commit     # Validate recent commits
make release             # Create release (maintainers only)
```

---

## Help & Troubleshooting

### Getting Help

```bash
# Show all available commands with descriptions
make help
```

### Most Common Commands

```bash
# Complete setup from scratch
make local-setup

# Daily development workflow
make commit                      # Interactive semantic commit
make test                        # Run tests
make lint                        # Run code linting
make analyze                     # Run static analysis

# Container management
make docker-up                   # Start containers
make docker-down                 # Stop containers
make status                      # Check container status
make logs                        # View logs
make shell                       # Access container shell

# Optional SonarQube
make sonarqube-setup            # Setup SonarQube
make sonarqube-analyze          # Run quality analysis

# Cleanup
make docker-cleanup             # Clean up everything
```

### Container Architecture

| Service | Container Name | Ports | Purpose |
|---------|---------------|-------|---------|
| **Laravel App** | `laravel_blog_api` | 8081:80 | Main application with Nginx + PHP-FPM |
| **MySQL** | `laravel_blog_api_mysql` | 3306:3306 | Development database |
| **Redis** | `laravel_blog_api_redis` | 6379:6379 | Cache and session store |
| **Queue Worker** | `laravel_blog_api_queue` | - | Background job processor |
| **MySQL Test** | `laravel_blog_api_mysql_test` | 3307:3306 | Testing database |
| **Redis Test** | `laravel_blog_api_redis_test` | 6380:6379 | Testing cache store |
| **SonarQube** | `laravel_blog_sonarqube` | 9000:9000 | Code quality analysis (optional) |

### Common Issues

1. **Port conflicts**: Ensure ports 8081, 3306, 3307, 6379 are not in use
2. **Docker not running**: Make sure Docker Desktop is running
3. **Permission issues**: On Linux/macOS, ensure your user is in the docker group
4. **Node.js not found**: Install Node.js 18+ for commit tools

### Support

- **View logs**: `make logs`
- **Check health**: `make health`
- **Container status**: `make status`
- **Full cleanup**: `make docker-cleanup`

---

**Note:** This project is designed to work with Docker containers for consistency across all development environments. All development tools and dependencies are containerized.

---

## 🚀 Quick Reference

### Core Setup Commands
```bash
make local-setup             # Complete local development setup
make sonarqube-setup         # Optional SonarQube setup (after local-setup)
```

### Daily Development
```bash
make commit                  # Interactive semantic commit
make test                    # Run tests
make lint                    # Code linting  
make analyze                 # Static analysis
```

### Container Management
```bash
make docker-up               # Start containers
make docker-down             # Stop containers
make docker-cleanup          # Clean up everything
```

### Access Points
- **API**: http://localhost:8081
- **Health**: http://localhost:8081/api/health
- **SonarQube**: http://localhost:9000 (when started)

---
