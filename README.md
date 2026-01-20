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
9. [TODO: Missing APIs & Tasks](#-todo-missing-apis--tasks)

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
make test                # Run all tests
make test filter='Auth'  # Run specific test by filter
make test-coverage       # Run tests with coverage report

# Code quality checks
make lint                # Run Pint linter
make lint-dirty          # Lint only changed files (faster)
make analyze             # Run PHPStan static analysis

# Container management
make docker-up           # Start containers
make docker-down         # Stop containers
make docker-restart      # Restart containers
make status              # Check container status
make health              # Check application health
make logs                # View logs
make shell               # Access main container shell
make test-shell          # Access test container shell
```

### Artisan Commands

```bash
# Run any artisan command
make artisan ARGS="migrate --seed"
make artisan ARGS="make:controller ApiController"

# Dump database schema and prune migrations
make schema-dump
```

**Note:** `schema-dump` creates a SQL schema dump file and removes old migration files, which helps keep your migration history clean and improves performance.

---

## Testing

This project uses **PEST** for testing with **automated Docker-based testing environment**.

### Quick Testing

```bash
# Run all tests with automated setup
make test

# Run specific test by filter
make test filter='Auth'

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

- **Minimum 70% code coverage** enforced
- **HTML reports** available at `reports/coverage/index.html`
- **XML reports** for CI/CD at `reports/coverage.xml`

---

## Code Quality

### Automated Code Quality Tools

```bash
# Run linting with Laravel Pint (all files)
make lint

# Lint only changed files (faster, recommended for quick checks)
make lint-dirty

# Run static analysis with PHPStan (level 10)
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
make phpstan-sonar        # Generate PHPStan JSON report only (reports/phpstan.json)
make sonarqube-scan-local # Run scanner only (local network mode)
make sonarqube-scan-ci    # Run scanner only (CI/external SonarQube)
make sonarqube-dashboard  # Open dashboard
make sonarqube-stop       # Stop SonarQube server
make sonarqube-clean      # Clean SonarQube data (reset)
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
make test                        # Run all tests
make test filter='Auth'          # Run specific test
make test-coverage              # Run tests with coverage
make lint                        # Run code linting (all files)
make lint-dirty                  # Lint only changed files
make analyze                     # Run static analysis

# Container management
make docker-up                   # Start containers
make docker-down                 # Stop containers
make docker-restart              # Restart containers
make status                      # Check container status
make health                      # Check application health
make logs                        # View logs
make shell                       # Access main container shell
make test-shell                  # Access test container shell

# Utilities
make artisan ARGS='migrate'     # Run artisan commands
make schema-dump                # Dump database schema and prune migrations
make check-ports                 # Check port availability

# Optional SonarQube
make sonarqube-setup            # Setup SonarQube
make sonarqube-analyze          # Run quality analysis
make sonarqube-clean            # Clean SonarQube data

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
make test                    # Run all tests
make test filter='Auth'      # Run specific test
make test-coverage           # Run tests with coverage
make lint                    # Code linting (all files)
make lint-dirty              # Lint only changed files
make analyze                 # Static analysis
```

### Container Management
```bash
make docker-up               # Start containers
make docker-down             # Stop containers
make docker-restart          # Restart containers
make docker-cleanup          # Clean up everything
make shell                   # Access main container shell
make test-shell              # Access test container shell
```

### Database Utilities
```bash
make schema-dump             # Dump database schema and prune migrations
make artisan ARGS='migrate'  # Run database migrations
```

### Access Points
- **API**: http://localhost:8081
- **Health**: http://localhost:8081/api/health
- **SonarQube**: http://localhost:9000 (when started)

## 🚧 TODO: Missing APIs & Tasks

Based on the codebase review, the following APIs and features are pending implementation:

### ✅ Already Implemented

- **Authentication**: Login, Logout, Refresh Token, Forgot Password, Reset Password
- **User Management (Admin)**: CRUD operations, Ban/Unban, Block/Unblock
- **User Profile**: Get `/me`, Update profile
- **Article Management (Admin)**: List, Show, Create, Approve, Feature, Report, Pin, Unpin, Archive, Restore from archive, Trash, Restore from trash
- **Article Management (Public)**: List, Show by slug, Get comments, Like article, Dislike article
  - Like/Dislike endpoints use `ArticleReactionType` enum for type safety
  - Real IP address detection via `Helper::getRealIpAddress()` for accurate tracking
  - Refactored service methods with reduced code duplication
- **Comment Management (Admin)**: List, Approve, Delete
- **Comment Management (Public)**: Get comments for article, Create comment, Update own comment, Delete own comment, Report comment, Get own comments
- **Newsletter Management (Admin)**: List subscribers, Delete subscriber
- **Newsletter Management (Public)**: Subscribe, Unsubscribe, Verify subscription, Verify unsubscription
- **Notification Management (Admin)**: List, Create
- **Taxonomy (Public)**: Get categories, Get tags
- **Taxonomy Management (Admin/Editor)**: Create category, Update category, Delete category, Create tag, Update tag, Delete tag
- **Media Management**: Upload media, List media library, Get media details, Update media metadata, Delete media
  - Available for both authenticated users and admin
  - Full CRUD operations implemented
- **Social/Community Features**: Follow user, Unfollow user, Get user followers, Get user following, View user profile
  - All social features fully implemented
  - Follow/unfollow with proper authorization checks
  - Public profile viewing with follower/following lists

### ❌ Pending Implementation

- **NextJS Client App**
  - Complete NextJS App to integrate all these APIs (separate REPO once APIs are complete)

- **User Notifications**
  - Get user's notifications endpoint (for authenticated users to view their own notifications)
  - Mark notification as read endpoint
  - Delete notification endpoint (user can delete their own notifications)
  - Mark all notifications as read endpoint
  - Get unread notifications count endpoint
  - Note: Admin notification management is already implemented, but user-facing notification endpoints are missing

- **Analytics & Settings**
  - View analytics dashboard endpoint (user and admin)
  - Get site statistics endpoint
  - Manage site settings endpoints (CRUD)
  
---
