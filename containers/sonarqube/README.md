# SonarQube Integration Files

This directory contains all SonarQube-related files for the Laravel Blog API project.

## 📁 Directory Structure

```
containers/sonarqube/
├── README.md                    # This file
├── scripts/                     # Analysis scripts
│   ├── sonar-analysis.sh       # Main analysis script (local development)
│   └── sonar-analysis-ci.sh    # CI/CD analysis script
├── config/                      # SonarQube configuration
│   ├── quality-gate.json       # Custom quality gate definition
│   └── quality-profile.json    # Custom quality profile for PHP
```

> Note: SonarQube server is defined in `containers/docker-compose.sonarqube.yml` (project root `containers/` directory).

## 🚀 Scripts

### `scripts/sonar-analysis.sh`
- **Purpose**: Wrapper for local analysis
- **Usage**: Delegates to `make sonarqube-analyze`
- **Features**: 
  - Keeps the Makefile as the single source of truth for the workflow
  - Useful if you want to run the script directly, but **prefer using `make` targets**

### `scripts/sonar-analysis-ci.sh`
- **Purpose**: Wrapper for CI/external SonarQube scanning
- **Usage**: Delegates to `make sonarqube-scan-ci`
- **Features**:
  - Assumes external SonarQube server
  - Uses `SONAR_HOST_URL` + `SONAR_TOKEN`
  - Scanner-only execution (reports must already exist if you want them included)

## ⚙️ Configuration Files

### `config/quality-gate.json`
Custom quality gate with these conditions:
- **Coverage**: ≥ 70% for new code
- **Duplications**: ≤ 3% for new code
- **Maintainability**: A rating required
- **Reliability**: A rating required
- **Security**: A rating required
- **Security Hotspots**: 100% reviewed

### `config/quality-profile.json`
Custom PHP quality profile with rules for:
- **Naming Conventions**: Classes, methods, constants
- **Code Complexity**: Maximum lines per function
- **Code Smells**: String literals, switch statements
- **Best Practices**: Laravel-specific recommendations

## 🔧 Usage

### Local Development
```bash
# Complete workflow (recommended)
make sonarqube-analyze

# Generate PHPStan JSON report only (reports/phpstan.json)
make phpstan-sonar

# Run scanner only (local network mode; will start SonarQube if needed)
make sonarqube-scan-local
```

### CI/CD
```bash
# External SonarQube server
export SONAR_HOST_URL=https://sonarqube.example.com
export SONAR_TOKEN=squ_your_token
make sonarqube-scan-ci
```

### Direct Script Execution
```bash
# Local analysis (delegates to Makefile)
export SONAR_TOKEN=squ_your_token # or set it in containers/.env.sonarqube
./containers/sonarqube/scripts/sonar-analysis.sh

# CI analysis (delegates to Makefile)
export SONAR_HOST_URL=https://sonarqube.example.com
export SONAR_TOKEN=squ_your_token
./containers/sonarqube/scripts/sonar-analysis-ci.sh
```

## 🌐 Network Configuration

The analysis scripts use Docker networking for communication:

- **Local Development**: Uses `laravel_blog_sonarqube_sonarqube_network`
- **Service Name**: `sonarqube` (not container name with underscores)
- **Host Access**: SonarQube web interface at `http://localhost:9000`

## 📊 Reports

Generated reports are stored in the project root:
- `reports/coverage.xml` - PHPUnit coverage in Clover format
- `reports/phpstan.json` - PHPStan analysis in JSON format

## 🔗 Integration

These files are integrated with:
- **Makefile**: Owns the SonarQube workflow via `sonarqube-*` targets
- **GitHub Actions**: CI workflow can call `make sonarqube-scan-ci`
- **Docker Compose**: SonarQube server configuration
- **Project Root**: `sonar-project.properties` references these reports

## 📚 Documentation

For complete setup and usage instructions, see: [../SONARQUBE.md](../SONARQUBE.md)
