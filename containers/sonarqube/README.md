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
└── docker-compose.sonarqube.yml # Docker Compose for SonarQube server
```

## 🚀 Scripts

### `scripts/sonar-analysis.sh`
- **Purpose**: Complete analysis for local development
- **Usage**: Called by `make docker-sonarqube-analyze`
- **Features**: 
  - Starts SonarQube server if needed
  - Runs PHPStan analysis
  - Executes PHPUnit tests with coverage
  - Uploads results to SonarQube
  - Uses SonarQube Docker network for communication

### `scripts/sonar-analysis-ci.sh`
- **Purpose**: Analysis for CI/CD environments
- **Usage**: Called by `make docker-sonarqube-ci` or GitHub Actions
- **Features**:
  - Assumes external SonarQube server
  - Uses SONAR_HOST_URL environment variable
  - Lightweight scanner-only execution

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
# Start SonarQube and run complete analysis
make docker-sonarqube-analyze

# Run scanner only (server must be running)
make docker-sonarqube-scan
```

### CI/CD
```bash
# External SonarQube server
export SONAR_HOST_URL=https://sonarqube.example.com
export SONAR_TOKEN=squ_your_token
make docker-sonarqube-ci
```

### Direct Script Execution
```bash
# Local analysis
export SONAR_TOKEN=squ_your_token
./containers/sonarqube/scripts/sonar-analysis.sh

# CI analysis
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
- **Makefile**: Commands reference scripts in this directory
- **GitHub Actions**: CI workflow uses `sonar-analysis-ci.sh`
- **Docker Compose**: SonarQube server configuration
- **Project Root**: `sonar-project.properties` references these reports

## 📚 Documentation

For complete setup and usage instructions, see: [../SONARQUBE.md](../SONARQUBE.md)
