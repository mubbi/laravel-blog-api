# SonarQube 25.7.0 Code Quality Analysis for Laravel Blog API

## 🎯 Overview

This guide provides complete setup and usage instructions for **SonarQube 25.7.0 Community Edition** integrated with the Laravel Blog API project. The setup provides comprehensive code quality analysis, security scanning, and test coverage monitoring.

## 🚀 Features

- **Code Quality Analysis**: Detect bugs, vulnerabilities, and code smells
- **Security Analysis**: Identify security hotspots and vulnerabilities  
- **Test Coverage**: Monitor PHPUnit test coverage metrics
- **Static Analysis**: Integrate PHPStan analysis results
- **Technical Debt**: Measure and track technical debt
- **Code Duplication**: Detect duplicate code blocks
- **Quality Gates**: Automated quality threshold enforcement

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                 SonarQube 25.7.0 Setup                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐    ┌─────────────────┐                │
│  │  SonarQube      │    │  PostgreSQL 16  │                │
│  │  25.7.0         │◄───┤  Database       │                │
│  │  (Port 9000)    │    │  (Port 5432)    │                │
│  └─────────────────┘    └─────────────────┘                │
│           │                                                 │
│           ▼                                                 │
│  ┌─────────────────┐    ┌─────────────────┐                │
│  │  Scanner CLI    │    │  Laravel App    │                │
│  │  v7.1.0         │◄───┤  PHPStan +      │                │
│  │  (latest)       │    │  PHPUnit        │                │
│  └─────────────────┘    └─────────────────┘                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Network Configuration

- **SonarQube Network**: `laravel_blog_sonarqube_sonarqube_network`
- **Scanner Communication**: Direct container-to-container using service name `sonarqube`
- **Host Access**: SonarQube web interface accessible at `http://localhost:9000`

## 🔧 Installation & Setup

### Prerequisites

- Docker and Docker Compose installed
- At least 8GB RAM available for optimal performance
- 10GB free disk space
- Laravel Blog API project setup complete

### Step 1: Start SonarQube Server

```bash
# Start SonarQube 25.7.0 with PostgreSQL 16
make docker-sonarqube-start

# Wait for services to be ready (3-5 minutes)
# Monitor startup with: docker logs laravel_blog_sonarqube -f
```

The server will be available at: **http://localhost:9000**

### Step 2: Setup Environment

```bash
# Automated environment setup
make docker-sonarqube-setup-env
```

This will:
- Create `containers/.env.sonarqube` from example if missing
- Set up basic SonarQube configuration
- Prepare the environment for token configuration

### Step 3: Generate and Configure Token

```bash
# Interactive token setup (recommended)
make docker-sonarqube-setup-token
```

This helper will:
- Check if SonarQube server is running
- Open instructions for token generation
- Prompt for token input with validation
- Automatically save token to environment file

**Manual token generation:**
1. **Access SonarQube**: http://localhost:9000
2. **Default credentials**: `admin` / `admin`
3. **Change password** on first login (required)
4. **Navigate to**: Account → Security → Tokens
5. **Create new token**: 
   - Name: `laravel-blog-api-analysis`
   - Type: **User Token** (recommended)
   - Expiration: Set as needed
6. **Copy the token** (starts with `squ_`)

### Step 4: Configure Environment

```bash
# Automated environment setup (recommended)
make docker-sonarqube-setup-env

# Interactive token setup with helper
make docker-sonarqube-setup-token

# Manual setup (alternative)
export SONAR_TOKEN=squ_your_generated_token_here
```

## 🚀 Usage Commands

### Quick Start

```bash
# Complete analysis (recommended) - includes all steps
make docker-sonarqube-analyze
```

This command will:
1. Start SonarQube server (if not running)
2. Run PHPStan static analysis
3. Execute PHPUnit tests with coverage
4. Upload all results to SonarQube
5. Display analysis results

### Individual Commands

| Command | Description |
|---------|-------------|
| `make docker-sonarqube-start` | Start SonarQube 25.7.0 server |
| `make docker-sonarqube-stop` | Stop SonarQube server |
| `make docker-sonarqube-setup-env` | Setup SonarQube environment file |
| `make docker-sonarqube-setup-token` | Interactive token setup helper |
| `make docker-sonarqube-analyze` | Complete analysis (recommended) |
| `make docker-sonarqube-scan` | Run scanner only (server must be running) |
| `make docker-sonarqube-reports` | Generate reports only |
| `make docker-sonarqube-dashboard` | Open SonarQube dashboard |
| `make docker-sonarqube-clean` | Clean all SonarQube data |
| `make docker-sonarqube-ci` | Run analysis for CI/CD (external server) |

### Server Management

```bash
# Start server
make docker-sonarqube-start

# Check server status
curl -s http://localhost:9000/api/system/status

# View server logs
docker logs laravel_blog_sonarqube -f

# Stop server
make docker-sonarqube-stop

# Clean all data (reset everything)
make docker-sonarqube-clean
```

## 📊 Analysis Results

### Accessing Results

1. **Web Interface**: http://localhost:9000
2. **Project Dashboard**: Navigate to `laravel-blog-api` project
3. **Quality Gate**: View pass/fail status and metrics

### Key Metrics

- **Coverage**: PHPUnit test coverage percentage
- **Duplications**: Code duplication percentage
- **Issues**: Bugs, vulnerabilities, and code smells
- **Security**: Security hotspots and vulnerabilities
- **Maintainability**: Technical debt and maintainability rating

### Quality Gate

The project uses a custom quality gate with these conditions:
- **Coverage**: > 70%
- **Duplicated Lines**: < 3%
- **New Issues**: = 0
- **Security Rating**: A (no vulnerabilities)
- **Maintainability Rating**: A

## 🔍 Analysis Components

### PHPStan Integration

- **Configuration**: `phpstan.neon`
- **Output Format**: JSON for SonarQube consumption
- **Execution**: Runs in main Laravel container
- **Report Location**: `reports/phpstan.json`

### PHPUnit Coverage

- **Configuration**: `phpunit.xml`
- **Output Format**: Clover XML
- **Execution**: Runs in test container
- **Report Location**: `reports/coverage.xml`

### Scanner Configuration

- **Configuration**: `sonar-project.properties`
- **Network**: SonarQube Docker network
- **Scanner Version**: v7.1.0 (latest)
- **Communication**: Container-to-container via service name

## 🛠️ Configuration Files

### Docker Compose

**File**: `containers/docker-compose.sonarqube.yml`

```yaml
services:
  sonarqube:
    image: sonarqube:25.7.0.110598-community
    container_name: laravel_blog_sonarqube
    depends_on:
      sonarqube_db:
        condition: service_healthy
    environment:
      SONAR_JDBC_URL: jdbc:postgresql://sonarqube_db:5432/sonar
      SONAR_JDBC_USERNAME: sonar
      SONAR_JDBC_PASSWORD: sonar_password
    ports:
      - "9000:9000"
    networks:
      - sonarqube_network

  sonarqube_db:
    image: postgres:16
    container_name: laravel_blog_sonarqube_db
    environment:
      POSTGRES_USER: sonar
      POSTGRES_PASSWORD: sonar_password
      POSTGRES_DB: sonar
```

### Analysis Scripts

**Local Development**: `containers/sonarqube/scripts/sonar-analysis.sh`
- Complete analysis including PHPStan and PHPUnit
- Automatic SonarQube server health checking
- Docker network-based communication

**CI/CD**: `containers/sonarqube/scripts/sonar-analysis-ci.sh`
- Lightweight scanner for external SonarQube servers
- Environment variable based configuration

### Quality Configuration

**Quality Gate**: `containers/sonarqube/config/quality-gate.json`
- Coverage: ≥ 80% for new code
- Duplications: ≤ 3% for new code
- Security and reliability ratings: A required

**Quality Profile**: `containers/sonarqube/config/quality-profile.json`
- PHP-specific rules and Laravel best practices
- Naming conventions and complexity limits

### Project Configuration

**File**: `sonar-project.properties`

```properties
sonar.projectKey=laravel-blog-api
sonar.projectName=Laravel Blog API
sonar.projectVersion=1.0.0

# Source and test directories
sonar.sources=app
sonar.tests=tests

# Coverage and analysis reports
sonar.php.coverage.reportPaths=reports/coverage.xml
sonar.php.phpstan.reportPaths=reports/phpstan.json

# SCM settings
sonar.scm.provider=git
sonar.newCode.referenceBranch=main
```

### Environment Configuration

**File**: `containers/.env.sonarqube`

```bash
# SonarQube Configuration (automatically generated)
SONAR_HOST_URL=http://localhost:9000
SONAR_TOKEN=squ_your_generated_token_here
SONAR_PROJECT_KEY=laravel-blog-api
SONAR_PROJECT_NAME="Laravel Blog API"
SONAR_PROJECT_VERSION=1.0.0
SONAR_SOURCES=app
SONAR_TESTS=tests
```

**Setup Commands**:
```bash
# Setup environment file
make docker-sonarqube-setup-env

# Interactive token configuration
make docker-sonarqube-setup-token
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Token Authentication Error
**Problem**: `Authentication failed` or `401 Unauthorized`
**Solution**: 
```bash
# Use interactive token setup helper
make docker-sonarqube-setup-token

# Or regenerate token manually at http://localhost:9000/account/security
export SONAR_TOKEN=squ_your_new_token
```

#### 2. Network Connection Issues
**Problem**: `Failed to connect to sonarqube`
**Solution**: 
```bash
# Check SonarQube server status
curl -s http://localhost:9000/api/system/status

# Restart SonarQube server
make docker-sonarqube-stop
make docker-sonarqube-start
```

#### 3. Memory Issues
**Problem**: SonarQube server out of memory
**Solution**: 
```bash
# Check available memory
docker stats laravel_blog_sonarqube

# Increase Docker memory allocation (minimum 8GB recommended)
```

#### 4. Analysis Cache Issues
**Problem**: `Failed to download analysis cache`
**Solution**: Analysis cache is disabled (`analysisCacheEnabled=false`) to avoid these issues.

### Debug Commands

```bash
# Check container status
docker-compose -f containers/docker-compose.sonarqube.yml ps

# View SonarQube logs
docker logs laravel_blog_sonarqube --tail=50

# Check SonarQube server status
curl -s http://localhost:9000/api/system/status

# Check network connectivity
docker run --rm --network=laravel_blog_sonarqube_sonarqube_network \
  curlimages/curl:latest curl -s http://sonarqube:9000/api/server/version

# Test scanner connectivity
docker run --rm --network=laravel_blog_sonarqube_sonarqube_network \
  -e SONAR_TOKEN=$SONAR_TOKEN sonarsource/sonar-scanner-cli:latest \
  sonar-scanner -Dsonar.host.url=http://sonarqube:9000 -Dsonar.sources=. \
  -Dsonar.projectKey=test -X

# Check environment file
cat containers/.env.sonarqube

# Validate token in environment
make docker-sonarqube-setup-token
```

## 🔒 Security Considerations

### Token Management
- Use **User Tokens** (starts with `squ_`) for project-specific access
- Set appropriate expiration dates
- Store tokens securely (environment variables, not in code)
- Rotate tokens regularly

### Network Security
- SonarQube server accessible only on localhost (development)
- Database not exposed externally
- Container-to-container communication within Docker network

### Data Privacy
- Analysis data stored locally in Docker volumes
- No external data transmission
- Database credentials in environment variables only

## 🚀 CI/CD Integration

### GitHub Actions

For CI/CD environments, use external SonarQube server:

```yaml
- name: SonarQube Analysis
  run: |
    make docker-sonarqube-ci
  env:
    SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
    SONAR_HOST_URL: ${{ secrets.SONAR_HOST_URL }}
```

### Local Development Workflow

```bash
# 1. Start development environment
make docker-up

# 2. Setup and start SonarQube
make docker-sonarqube-setup-env
make docker-sonarqube-start

# 3. Configure token (interactive helper)
make docker-sonarqube-setup-token

# 4. Make code changes
# ... your development work ...

# 5. Run analysis before commit
make docker-sonarqube-analyze

# 6. Review results at http://localhost:9000
# 7. Fix any issues found
# 8. Commit and push
```

### Complete Automated Workflow

```bash
# One-command setup and analysis
make docker-sonarqube-analyze

# This will:
# - Setup environment files if missing
# - Start SonarQube server if not running
# - Validate token configuration
# - Run PHPStan analysis
# - Execute PHPUnit tests with coverage
# - Upload results to SonarQube
# - Open dashboard for review
```

## 📈 Performance Optimization

### System Requirements

**Minimum**:
- 4GB RAM
- 2 CPU cores
- 5GB disk space

**Recommended**:
- 8GB RAM
- 4 CPU cores
- 10GB disk space

### Docker Configuration

```bash
# Check Docker resource allocation
docker system info | grep -E "CPUs|Total Memory"

# Adjust Docker Desktop settings if needed:
# - Memory: 8GB minimum
# - CPUs: 4 cores recommended
# - Disk: 10GB minimum
```

### SonarQube Optimization

The current setup includes optimized settings:
- **JVM Heap**: 3GB for web and compute engine
- **Elasticsearch**: 1GB heap
- **PostgreSQL**: Optimized for SonarQube workload
- **Connection Pooling**: Optimized for Docker networking

## 📚 Additional Resources

### SonarQube Documentation
- [SonarQube Documentation](https://docs.sonarsource.com/sonarqube/latest/)
- [PHP Analysis](https://docs.sonarsource.com/sonarqube/latest/analyzing-source-code/languages/php/)
- [Quality Gates](https://docs.sonarsource.com/sonarqube/latest/user-guide/quality-gates/)

### Laravel-Specific
- [PHPStan Laravel Extensions](https://github.com/nunomaduro/larastan)
- [Laravel Testing](https://laravel.com/docs/testing)
- [PHPUnit Coverage](https://phpunit.de/documentation.html)

### Docker Resources
- [SonarQube Docker Image](https://hub.docker.com/_/sonarqube)
- [PostgreSQL Docker Image](https://hub.docker.com/_/postgres)

---

## 💡 Quick Reference

### Essential Commands
```bash
# Complete automated workflow
make docker-sonarqube-setup-env          # Setup environment
make docker-sonarqube-start               # Start server
make docker-sonarqube-setup-token         # Configure token
make docker-sonarqube-analyze             # Run analysis

# Or one-command analysis (recommended)
make docker-sonarqube-analyze             # Does everything above
# View results at http://localhost:9000

# Maintenance
make docker-sonarqube-stop                # Stop server
make docker-sonarqube-clean               # Clean data
make docker-sonarqube-dashboard           # Open dashboard
```

### Environment Files
- **Example**: `.env.sonarqube.example` (tracked in git)
- **Working**: `containers/.env.sonarqube` (auto-generated)

### Token Setup
- **Interactive**: `make docker-sonarqube-setup-token`
- **Manual**: Set `SONAR_TOKEN` in `containers/.env.sonarqube`

### Key URLs
- **SonarQube Web**: http://localhost:9000
- **Token Management**: http://localhost:9000/account/security
- **Token Management**: http://localhost:9000/account/security
- **Project Dashboard**: http://localhost:9000/dashboard?id=laravel-blog-api
- **System Status**: http://localhost:9000/api/system/status

### Environment Variables
```bash
SONAR_TOKEN=squ_your_token_here      # Required for analysis
SONAR_HOST_URL=http://localhost:9000 # SonarQube server URL
```

This setup provides production-ready code quality analysis for your Laravel Blog API project! 🎉
