#!/bin/bash

# SonarQube 25.7.0 Analysis Script for CI/CD environments
# This script assumes SonarQube is running externally and just runs the analysis

set -e

echo "🚀 Starting SonarQube 25.7.0 Analysis for Laravel Blog API (CI/CD Mode)..."

# Load environment variables if .env.sonarqube exists
if [ -f .env.sonarqube ]; then
    echo "📋 Loading SonarQube environment variables..."
    source .env.sonarqube
fi

# Check required environment variables
if [ -z "$SONAR_TOKEN" ]; then
    echo "❌ SONAR_TOKEN not set. Please set it as an environment variable."
    exit 1
fi

if [ -z "$SONAR_HOST_URL" ]; then
    echo "⚠️  SONAR_HOST_URL not set. Using default: http://localhost:9000"
    SONAR_HOST_URL="http://localhost:9000"
fi

# Check if coverage and phpstan reports exist
if [ ! -f "reports/coverage.xml" ]; then
    echo "⚠️  Coverage report not found at reports/coverage.xml"
    echo "   Please run tests with coverage first"
fi

if [ ! -f "reports/phpstan.json" ]; then
    echo "⚠️  PHPStan report not found at reports/phpstan.json"
    echo "   Please run PHPStan analysis first"
fi

# Run SonarQube Scanner
echo "📊 Running SonarQube Scanner..."

# Create reports directory if it doesn't exist
mkdir -p reports

# Run SonarQube Scanner directly with Docker
docker run --rm \
    -v "$(pwd):/usr/src" \
    -w /usr/src \
    sonarsource/sonar-scanner-cli:latest \
    sonar-scanner \
    -Dsonar.projectKey=laravel-blog-api \
    -Dsonar.projectName="Laravel Blog API" \
    -Dsonar.projectVersion=1.0.0 \
    -Dsonar.sources=app \
    -Dsonar.tests=tests \
    -Dsonar.host.url=$SONAR_HOST_URL \
    -Dsonar.token=$SONAR_TOKEN \
    -Dsonar.php.coverage.reportPaths=reports/coverage.xml \
    -Dsonar.php.phpstan.reportPaths=reports/phpstan.json \
    -Dsonar.newCode.referenceBranch=main \
    -Dsonar.scm.provider=git \
    -Dsonar.qualitygate.wait=true \
    -Dsonar.qualitygate.timeout=600 \
    -Dsonar.projectBaseDir=/usr/src

echo "✅ SonarQube analysis completed"
echo "📊 View results at: $SONAR_HOST_URL"
