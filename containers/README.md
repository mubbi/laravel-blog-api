# Docker Setup for Laravel Blog API

This directory contains all Docker configuration files for local development and testing of the Laravel Blog API project.

## 📁 Documentation

- **[SONARQUBE.md](./SONARQUBE.md)** - Complete SonarQube 25.7.0 setup and usage guide

## 🔍 SonarQube Quick Reference

### Essential Commands
```bash
# Start SonarQube server
make docker-sonarqube-start

# Complete analysis (recommended)
make docker-sonarqube-analyze

# Stop SonarQube server  
make docker-sonarqube-stop
```

### Setup Steps
1. `make docker-sonarqube-start` - Start server
2. Visit http://localhost:9000 (admin/admin)
3. Generate token at: Account → Security → Tokens
4. `export SONAR_TOKEN=squ_your_token`
5. `make docker-sonarqube-analyze` - Run analysis

**Full documentation**: [SONARQUBE.md](./SONARQUBE.md)

## 🌟 Environment Files

This project uses a clean environment file structure:

### Tracked Files (in Git)
- `.env.docker.example` - Main development environment template
- `.env.testing.docker.example` - Testing environment template

### Generated Files (Ignored by Git)
- `.env` - Main development environment (Laravel's default, auto-generated)
- `.env.testing` - Testing environment (Laravel's testing default, auto-generated)

The automation scripts automatically create the working environment files from the example templates with proper APP_KEY generation.

## �🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed
- Git (for hooks)
- Make (for Windows users: install via Chocolatey or use Git Bash)

### ⚡ Fully Automated Setup

The Docker environment is **completely automated** with intelligent startup orchestration, automatic APP_KEY generation, and zero manual configuration required.

#### **Option 1: Complete Setup (Recommended)**
```bash
make docker-setup-complete
```
**What this does:**
- 🧹 Cleans up any existing containers and images
- 🔑 **Auto-generates unique APP_KEY** for all environments
- 📝 **Copies and configures** all environment files automatically
- 🐳 Starts both **main AND testing** environments
- 📦 Installs Composer dependencies
- 🗄️ Runs database migrations and seeders
- ⚡ Sets up queue workers with readiness detection
- ✅ Provides complete development and testing setup

#### **Option 2: Development Only**
```bash
make docker-setup-local
```

#### **Option 3: Testing Only**
```bash
make docker-setup-testing
```

### 🔑 Automatic APP_KEY Generation

**No more manual key generation!**
- Uses OpenSSL to generate secure base64-encoded keys
- Different unique keys for development and testing
- Automatically replaces empty APP_KEY in environment files
- Cross-platform compatible (Windows, macOS, Linux)

### 📁 Environment File Automation

**Intelligent environment file management:**
- `.env.docker.example` → `.env` (main development environment)
- `.env.testing.docker.example` → `.env.testing` (testing environment)
- Overwrites existing files for consistent setup
- Preserves custom configurations where appropriate

## Architecture

### Services

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
| **MySQL Test** | `laravel_blog_api_mysql_test` | 3307:3306 | Temporary test database |
| **Redis Test** | `laravel_blog_api_redis_test` | 6380:6379 | Test cache store |

### Startup Orchestration

The system includes robust startup orchestration:

- **Health Checks**: All services have proper health checks
- **Dependency Management**: Containers start in correct order with `depends_on` and health conditions
- **Race Condition Prevention**: Queue worker waits for main app readiness before starting
- **Database Readiness**: Main app waits for MySQL connection before running migrations
- **Ready Marker**: Main app creates `/tmp/laravel_ready` marker when fully initialized

## 🛠️ Available Commands

### 🚀 Setup Commands (Fully Automated)
```bash
# Complete setup - main + testing environments (RECOMMENDED)
make docker-setup-complete

# Individual environment setup
make docker-setup-local      # Development environment only  
make docker-setup-testing    # Testing environment only
make docker-setup-env        # Environment files only (no containers)
```

### 📊 Monitoring & Status
```bash
make docker-status           # Container status and access URLs
make docker-logs             # View all container logs (Ctrl+C to exit)
make docker-check-ready      # Check application readiness
make docker-queue-status     # Check queue worker status
```

### 🔧 Container Management
```bash
make docker-up               # Start containers only (no setup)
make docker-down             # Stop all containers
make docker-cleanup          # Complete cleanup (containers, images, volumes)
make docker-cleanup-main     # Cleanup main environment only
make docker-cleanup-testing  # Cleanup testing environment only
```

### 🧪 Testing Operations
```bash
make docker-tests            # Run tests with fresh database
make docker-tests-coverage   # Run tests with HTML coverage report
make docker-test-up          # Start test containers only
make docker-test-down        # Stop test containers only
```

### 🐚 Container Access
```bash
make docker-bash             # Access main container shell
make docker-test-bash        # Access test container shell
```

### 🔨 Advanced/Development
```bash
make docker-build-only       # Build containers without full setup
make docker-rebuild          # Rebuild images from scratch
make docker-rebuild        # Rebuild Docker images from scratch

# Environment management
make docker-setup-env      # Setup environment files only
```

## Configuration

### Environment Files

The setup automatically creates and manages:

- **`.env`** - Production-like local development environment (Laravel's default)
- **`.env.testing`** - Testing environment configuration (Laravel's testing default)

Environment files are created via `make docker-setup-env` or automatically during `make docker-setup-local`.

### PHP Configuration

- **PHP 8.4+** with all necessary extensions
- **Xdebug** configurable (off by default for performance)
- **Composer 2.x** (latest stable version)
- **Health checks** for application readiness

### Xdebug Control

```bash
# Disable Xdebug for better performance (default)
XDEBUG_MODE=off make docker-setup-local

# Enable Xdebug for debugging
XDEBUG_MODE=debug make docker-setup-local

# Enable for coverage reports
XDEBUG_MODE=coverage make docker-setup-local
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
make docker-status

# Detailed readiness check
make docker-check-ready

# Queue worker specific status
make docker-queue-status

# View health check endpoint
curl http://localhost:8081/api/health
```

### Startup Timing

- **Initial Setup**: 2-5 minutes (includes composer, migrations, seeders)
- **Subsequent Starts**: 30-60 seconds
- **Health Check Grace Period**: 5 minutes for full initialization

## Testing and CI/CD

### Automated Testing on Git Push

The project includes a pre-push git hook that automatically:

1. Starts test containers
2. Runs the full test suite with coverage
3. Runs PHPStan analysis
4. Stops test containers
5. Blocks push if any tests fail

### Manual Testing

```bash
# Run all tests with automated setup
make docker-tests

# Run tests with coverage report (available in reports/coverage)
make docker-tests-coverage

# Setup testing environment only
make docker-setup-testing
```

## Debugging and Development

### Xdebug Configuration

Xdebug is pre-configured and available on port 9003. Configure your IDE:

- **Host:** `localhost`
- **Port:** `9003`
- **IDE Key:** `docker`
- **Path Mappings:** `/var/www/html` → `{your-project-path}`

To enable Xdebug:
```bash
XDEBUG_MODE=debug make docker-setup-local
```

### Container Access

```bash
# Access main application container
make docker-bash

# Access test container
make docker-test-bash

# View real-time logs
make docker-logs
```

### Common Issues and Solutions

1. **Port conflicts:** If ports are already in use, modify the port mappings in `docker-compose.yml`
2. **Permission issues:** Ensure Docker has access to your project directory
3. **Database connection errors:** Use `make docker-check-ready` to verify database is ready
4. **Containers not starting:** Run `make docker-cleanup` then `make docker-setup-local`
5. **Queue worker not processing:** Check with `make docker-queue-status`

## File Structure

```
containers/
├── docker-compose.yml          # Main development environment
├── docker-compose.test.yml     # Testing environment
├── setup-env.sh               # Environment setup script
├── php/
│   ├── Dockerfile             # PHP-FPM + Nginx image
│   ├── php.ini               # Custom PHP configuration  
│   └── xdebug.ini            # Xdebug configuration
├── nginx/
│   └── default.conf          # Nginx server configuration
├── mysql/
│   └── my.cnf               # MySQL configuration
├── redis/
│   └── redis.conf           # Redis configuration (if needed)
├── supervisor/
│   └── supervisord.conf     # Process manager configuration
├── start-main-app.sh         # Main application startup script
├── start-queue-worker.sh     # Queue worker startup script
├── start-services.sh         # Service orchestration script
└── README.md                 # This documentation
```

## Security Notes

- MySQL and Redis are configured for development use
- Default passwords should be changed for production
- Xdebug should be disabled in production environments (`XDEBUG_MODE=off`)
- All services are isolated within Docker networks
- Container startup is fully automated with proper security practices

## Performance Optimization

### Volume Mounts
- Code is mounted for instant file changes during development
- Database and Redis use named volumes for persistence
- Test environment uses optimized settings for faster execution

### Resource Allocation
- MySQL: 256MB buffer pool
- Redis: 256MB max memory  
- PHP: 512MB memory limit
- Xdebug: Disabled by default for better performance

### Startup Performance
- Smart dependency management prevents unnecessary waits
- Health checks optimize container readiness detection
- Queue worker starts only after main app is fully ready

---

**Need help?** 
- Check the main project README for general setup
- Use `make docker-check-ready` to diagnose issues
- View logs with `make docker-logs`
- Create an issue in the repository for bugs

## Quick Reference

### Most Common Commands
```bash
# Complete setup from scratch
make docker-setup-local

# Check if everything is working  
make docker-status
make docker-check-ready

# Development workflow
make docker-logs        # View logs
make docker-bash        # Access container
make docker-down        # Stop when done

# Reset everything
make docker-cleanup
```

### URLs and Connections
- **Application**: http://localhost:8081
- **Health Check**: http://localhost:8081/api/health  
- **MySQL**: localhost:3306 (user: laravel_user, password: laravel_password)
- **Redis**: localhost:6379

### File Overview
- `docker-compose.yml` - Main development environment
- `docker-compose.test.yml` - Testing environment  
- `setup-env.sh` - Environment file creation
- `start-main-app.sh` - Application startup orchestration
- `start-queue-worker.sh` - Queue worker with readiness detection
- `start-services.sh` - Container service management

---
