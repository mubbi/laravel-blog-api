# Docker Setup for Laravel Blog API

This directory contains all Docker configuration files for local development and testing of the Laravel Blog API project.

## � Quick Setup

### Prerequisites
- Docker and Docker Compose installed
- Git (for hooks)
- Node.js 18+ (for commit tools)
- Make

### Complete Local Development Setup

**Main command (run from project root):**
```bash
make local-setup
```

This automated setup will:
- 🧹 Clean up any existing containers and images
- 🔑 **Auto-generate unique APP_KEY** for all environments
- 📝 **Copy and configure** all environment files automatically
- 🐳 Start both **main AND testing** environments
- 📦 Install Composer dependencies
- 🗄️ Run database migrations and seeders
- ⚡ Set up queue workers with readiness detection
- 🛠️ Install Git hooks and semantic commit tools
- ✅ Provide complete development and testing setup

### Optional: SonarQube Code Quality Analysis

```bash
make sonarqube-setup
```

## 🌟 Environment Files

This project uses a clean environment file structure:

### Tracked Files (in Git)
- `.env.docker.example` - Main development environment template
- `.env.testing.docker.example` - Testing environment template

### Generated Files (Ignored by Git)
- `.env` - Main development environment (Laravel's default, auto-generated)
- `.env.testing` - Testing environment (Laravel's testing default, auto-generated)

The automation scripts automatically create the working environment files from the example templates with proper APP_KEY generation.

## Architecture

### Main Services

| Service | Container Name | Ports | Description |
|---------|---------------|-------|-------------|
| **Laravel App** | `laravel_blog_api` | 8081:80 | Main application with Nginx + PHP-FPM |
| **MySQL** | `laravel_blog_api_mysql` | 3306:3306 | MySQL 8.0 database |
| **Redis** | `laravel_blog_api_redis` | 6379:6379 | Redis cache/session store |
| **Queue Worker** | `laravel_blog_api_queue` | - | Laravel queue processor with smart readiness detection |

### Test Services

| Service | Container Name | Ports | Description |
|---------|---------------|-------|-------------|
| **Laravel Test** | `laravel_blog_api_test` | - | Testing environment |
| **MySQL Test** | `laravel_blog_api_mysql_test` | 3307:3306 | Isolated test database |
| **Redis Test** | `laravel_blog_api_redis_test` | 6380:6379 | Test cache store |

### Optional: SonarQube Services

| Service | Container Name | Ports | Description |
|---------|---------------|-------|-------------|
| **SonarQube** | `laravel_blog_sonarqube` | 9000:9000 | Code quality analysis |
| **SonarQube DB** | `laravel_blog_sonarqube_db` | 5432:5432 | PostgreSQL database for SonarQube |

## 🛠️ Available Commands (from project root)

### 🚀 Main Setup Commands
```bash
make local-setup             # Complete local development setup (MAIN COMMAND)
make sonarqube-setup         # Optional SonarQube setup
```

### � Container Management
```bash
make docker-up               # Start containers only (no setup)
make docker-down             # Stop all containers
make status                  # Container status and access URLs
make health                  # Check application health
make logs                    # View all container logs
make shell                   # Access main container shell
```

### 🧪 Testing Operations
```bash
make test                    # Run tests with fresh database
make test-coverage           # Run tests with HTML coverage report
```

### � Code Quality
```bash
make lint                    # Run Pint linter
make analyze                 # Run PHPStan static analysis
```

### 🧹 Cleanup
```bash
make docker-cleanup          # Complete cleanup (containers, images, volumes)
```

## Configuration

### Environment Files

The setup automatically creates and manages:

- **`.env`** - Production-like local development environment (Laravel's default)
- **`.env.testing`** - Testing environment configuration (Laravel's testing default)

Environment files are created automatically during `make local-setup`.

### PHP Configuration

- **PHP 8.4+** with all necessary extensions
- **Xdebug** configurable (off by default for performance)
- **Composer 2.x** (latest stable version)
- **Health checks** for application readiness

### Xdebug Control

```bash
# Disable Xdebug for better performance (default)
XDEBUG_MODE=off make local-setup

# Enable Xdebug for debugging
XDEBUG_MODE=debug make local-setup

# Enable for coverage reports
XDEBUG_MODE=coverage make local-setup
```

### Database Access

Connect to databases using external tools like DBeaver:

**Local Development:**
- Host: `localhost`
- Port: `3306`
- Database: `laravel_blog`
- Username: `laravel_user`
- Password: `laravel_password`

**Testing:**
- Host: `localhost`
- Port: `3307`
- Database: `laravel_blog_test`
- Username: `laravel_user`
- Password: `laravel_password`

### Redis Access

**Local Development:**
- Host: `localhost`
- Port: `6379`

**Testing:**
- Host: `localhost`
- Port: `6380`

## Health Checks and Monitoring

### Built-in Health Monitoring

All services include comprehensive health checks:

- **Laravel App**: HTTP endpoint `/api/health` + ready marker file
- **MySQL**: Database connectivity test  
- **Redis**: Redis ping test
- **Queue Worker**: Process monitoring and queue readiness

### Checking Application Status

```bash
# Quick status overview
make status

# Detailed health check
make health

# View health check endpoint
curl http://localhost:8081/api/health
```

### Startup Timing

- **Initial Setup**: 2-5 minutes (includes composer, migrations, seeders)
- **Subsequent Starts**: 30-60 seconds
- **Health Check Grace Period**: 5 minutes for full initialization

## Common Issues and Solutions

1. **Port conflicts:** If ports are already in use, modify the port mappings in `docker-compose.yml`
2. **Permission issues:** Ensure Docker has access to your project directory
3. **Database connection errors:** Use `make health` to verify database is ready
4. **Containers not starting:** Run `make docker-cleanup` then `make local-setup`

## File Structure

```
containers/
├── docker-compose.yml          # Main development environment
├── docker-compose.test.yml     # Testing environment
├── docker-compose.sonarqube.yml # SonarQube environment
├── setup-env.sh               # Environment setup script
├── verify-env-setup.sh         # Environment verification script
├── start-main-app.sh           # Application startup script
├── start-queue-worker.sh       # Queue worker startup script
├── start-services.sh           # Service orchestration script
├── php/
│   ├── Dockerfile             # PHP-FPM + Nginx image
│   ├── php.ini               # Custom PHP configuration  
│   └── xdebug.ini            # Xdebug configuration
├── nginx/
│   └── default.conf          # Nginx server configuration
├── mysql/
│   └── my.cnf               # MySQL configuration
├── redis/
│   └── redis.conf           # Redis configuration
├── supervisor/
│   └── supervisord.conf     # Process manager configuration
└── sonarqube/              # SonarQube configuration and scripts
```

## Quick Reference

### Most Common Commands (from project root)
```bash
# Complete setup from scratch
make local-setup

# Check if everything is working  
make status
make health

# Development workflow
make logs                    # View logs
make shell                   # Access container
make docker-down             # Stop when done

# Reset everything
make docker-cleanup
```

### URLs and Connections
- **Application**: http://localhost:8081
- **Health Check**: http://localhost:8081/api/health  
- **MySQL**: localhost:3306 (user: laravel_user, password: laravel_password)
- **Redis**: localhost:6379
- **SonarQube**: http://localhost:9000 (when started)

---

**Need help?** 
- Check the main project README for general setup
- Use `make health` to diagnose issues
- View logs with `make logs`
- Create an issue in the repository for bugs
