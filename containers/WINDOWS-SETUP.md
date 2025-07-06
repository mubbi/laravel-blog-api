# Laravel Blog API - Docker Setup Guide for Windows

This guide will help you set up the **fully automated** Docker development environment for the Laravel Blog API project on Windows. **Zero manual configuration required!**

## Prerequisites

1. **Docker Desktop for Windows**
   - Download from: https://www.docker.com/products/docker-desktop
   - Ensure WSL 2 backend is enabled
   - Allocate at least 4GB RAM to Docker
   - Verify installation: `docker --version`

2. **Git for Windows**
   - Download from: https://git-scm.windows.com/
   - Includes Git Bash which is required for shell scripts
   - Verify installation: `git --version`

3. **Make for Windows** (Optional but recommended)
   - Install via Chocolatey: `choco install make`
   - Or install manually from: http://gnuwin32.sourceforge.net/packages/make.htm
   - Alternative: Use Git Bash for all make commands

## 🚀 Quick Start (Fully Automated)

### ⚡ One-Command Complete Setup (Recommended)

Open Git Bash, PowerShell, or Command Prompt in the project root:

```bash
# Complete automated setup - main + testing environments
make docker-setup-complete
```

**🎉 That's it! This single command will:**
- ✅ **Clean up** any existing Docker containers and images
- ✅ **Auto-generate APP_KEY** using OpenSSL (unique keys for dev/test)
- ✅ **Copy and configure** all environment files automatically
- ✅ **Start main environment** (API on http://localhost:8081)
- ✅ **Start testing environment** (isolated test database)
- ✅ **Install dependencies** automatically
- ✅ **Run migrations and seeders** for both environments
- ✅ **Setup queue workers** with smart readiness detection
- ✅ **Display access URLs** and next steps

### Alternative Options

**Development environment only:**
```bash
make docker-setup-local
```

**Testing environment only:**
```bash
make docker-setup-testing
```

## 🔑 Automatic APP_KEY Generation

**No more manual key generation or environment file editing!**

- Uses OpenSSL (included with Git Bash) to generate secure keys
- Creates different unique keys for development and testing
- Automatically replaces empty `APP_KEY=` in all environment files
- Works seamlessly on Windows (Git Bash), WSL, or PowerShell

### Step-by-Step Process (All Automated)

1. **Environment File Creation:**
   - `.env.docker.example` → `.env` (main development environment)
   - `.env.testing.docker.example` → `.env.testing` (testing environment)

2. **APP_KEY Generation:**
   - Development: `APP_KEY=base64:xxxxxxxxxxxx` 
   - Testing: `APP_KEY=base64:yyyyyyyyyyyy` (different key)

3. **Container Orchestration:**
   - MySQL and Redis start first (with health checks)
   - Laravel app waits for database connectivity
## 📊 After Setup - Access Points

**🎯 Your applications are ready at:**

| Service | URL | Description |
|---------|-----|-------------|
| **Main API** | http://localhost:8081 | Laravel application |
| **API Health** | http://localhost:8081/api/health | Health check endpoint |
| **API Docs** | http://localhost:8081/docs/api | API documentation |
| **MySQL Main** | localhost:3306 | Development database |
| **MySQL Test** | localhost:3307 | Testing database |
| **Redis** | localhost:6379 | Cache/sessions |

**🔐 Database Credentials:**
- Username: `laravel_user`
- Password: `laravel_password`
- Main DB: `laravel_blog`
- Test DB: `laravel_blog_test`

## 🔧 Monitoring & Management

### Real-time Status Monitoring

```bash
# Check if everything is running and healthy
make docker-status

# Detailed readiness check  
make docker-check-ready

# View live logs from all containers (Ctrl+C to exit)
make docker-logs

# Check queue worker status specifically
make docker-queue-status
```

## 📋 Available Commands (Windows Compatible)

All commands work perfectly in **Windows Command Prompt**, **PowerShell**, and **Git Bash**:

### 🚀 Setup Commands
```cmd
rem Complete setup - main + testing (RECOMMENDED)
make docker-setup-complete

rem Individual environment setup
make docker-setup-local        rem Development only
make docker-setup-testing      rem Testing only
make docker-setup-env          rem Environment files only
```

### 📊 Monitoring Commands
```cmd
rem Status and health monitoring
make docker-status             rem Container status + URLs
make docker-check-ready        rem Application readiness
make docker-logs               rem Live logs (Ctrl+C to exit)
make docker-queue-status       rem Queue worker status
```

### 🛠️ Management Commands  
```cmd
rem Container management
make docker-up                 rem Start containers only
make docker-down               rem Stop all containers
make docker-cleanup            rem Complete cleanup

rem Container access
make docker-bash               rem Access main container
make docker-test-bash          rem Access test container
```

### 🧪 Testing Commands
```cmd
rem Automated testing
make docker-tests              rem Run tests with fresh DB
make docker-tests-coverage     rem Tests with coverage report
```

## Windows-Specific Considerations

### Character Encoding
- All output has been optimized for Windows terminals
- No emoji or Unicode characters that cause display issues
- Clean, readable status messages in Command Prompt and PowerShell

### Path Handling
- All volume mounts work correctly with Windows paths
- No path conversion issues between Windows and containers
- Proper handling of file permissions

### Performance on Windows
- Optimized volume mounts for Windows Docker Desktop
- Reasonable startup times (2-5 minutes for initial setup)
- Good development performance with file watching

## Troubleshooting Windows Issues

### Common Problems and Solutions

1. **Docker is not running**
   ```bash
   # Error: Cannot connect to the Docker daemon
   # Solution: Start Docker Desktop and wait for it to fully initialize
   ```

2. **Port conflicts**
   ```bash
   # Error: Port already in use
   # Solution: Stop other services or modify ports in docker-compose.yml
   netstat -ano | findstr :8081
   ```

3. **Permission denied errors**
   ```bash
   # Solution: Ensure Docker Desktop has access to your drive
   # Docker Desktop -> Settings -> Resources -> File Sharing
   ```

4. **Make command not found**
   ```bash
   # If you don't have make installed, use Git Bash:
   # Open Git Bash and run make commands from there
   ```

5. **Container startup issues**
   ```bash
   # Solution: Clean reset and retry
   make docker-cleanup
   make docker-setup-local
   ```

### Windows Terminal Configuration

For the best experience, use one of these terminals:

1. **Git Bash** (Recommended)
   - Comes with Git for Windows
   - Full Unix-like environment
   - All commands work perfectly

2. **Windows Terminal** (Modern)
   - Download from Microsoft Store
   - Better display and color support
   - Multiple tab support

3. **PowerShell** (Built-in)
   - Available on all Windows systems
   - Good compatibility with make commands

### File Watching and Development

Windows file watching works well with this setup:
- Code changes are immediately reflected in containers
- No need to restart containers for code changes
- Laravel's file watching works correctly

## Xdebug Setup for Windows IDEs

### PHPStorm Configuration

1. **Configure PHP Interpreter:**
   - File -> Settings -> PHP -> CLI Interpreter
   - Add new Docker Compose interpreter
   - Service: `laravel_blog_api`

2. **Configure Xdebug:**
   - File -> Settings -> PHP -> Debug
   - Port: `9003`
   - Check "Can accept external connections"

3. **Path Mappings:**
   - Local path: `C:\your\project\path`
   - Remote path: `/var/www/html`

### VS Code Configuration

Add to `.vscode/launch.json`:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

## Performance Tips for Windows

### Optimize Docker Desktop

1. **Resource Allocation:**
   - CPU: At least 2 cores
   - Memory: 4GB minimum, 8GB recommended
   - Disk: Enable fast disk access

2. **WSL 2 Backend:**
   - Ensure WSL 2 is enabled (much faster than Hyper-V)
   - Keep project files in WSL 2 file system for best performance

3. **Exclude from Antivirus:**
   - Add Docker Desktop folder to antivirus exclusions
   - Add project folder to exclusions

### Development Workflow

```bash
# Start development session
make docker-setup-local

# During development, containers stay running
# Make code changes - they're reflected immediately
# No need to restart containers

# When finished for the day
make docker-down

# For complete cleanup (weekly)
make docker-cleanup
```

## Getting Help

### Debug Information Commands

```bash
# Get comprehensive status
make docker-check-ready

# View logs for troubleshooting
make docker-logs

# Check specific container
docker logs laravel_blog_api

# Test database connectivity
make docker-bash
# Then inside container:
php artisan tinker
# DB::connection()->getPdo(); // Should not throw error
```

### Resources

- **Main README**: `../README.md` for general project information
- **Docker README**: `README.md` for detailed Docker configuration
- **Health Check**: http://localhost:8081/api/health for live status
- **Database**: Connect to localhost:3306 with your favorite DB tool
- **Redis**: Connect to localhost:6379 for cache inspection

---

**Success!** Once setup is complete, you'll have a fully automated, robust Laravel development environment running on Windows with:
- Automatic startup orchestration
- Health monitoring
- Queue processing
- Database migrations
- Clean Windows terminal output
- Full Xdebug support
