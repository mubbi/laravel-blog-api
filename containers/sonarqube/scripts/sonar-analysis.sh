#!/bin/bash

# SonarQube 25.7.0 Analysis Script for Laravel Blog API
# This script prepares the environment and runs SonarQube analysis
# 
# NETWORK CONFIGURATION: 
# - The scanner uses the SonarQube Docker network for direct container communication
# - Uses service name 'sonarqube' (not container name with underscores)
# - This avoids host networking issues and ensures reliable connectivity

set -e

echo "🚀 Starting SonarQube 25.7.0 Analysis for Laravel Blog API..."

# Load environment variables if .env.sonarqube exists
if [ -f .env.sonarqube ]; then
    echo "📋 Loading SonarQube environment variables..."
    source .env.sonarqube
fi

# Check if SonarQube server is running
check_sonarqube_health() {
    echo "⏳ Checking SonarQube server health..."
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s http://localhost:9000/api/system/status > /dev/null 2>&1; then
            echo "✅ SonarQube server is healthy"
            return 0
        fi
        echo "⏳ Attempt $attempt/$max_attempts: Waiting for SonarQube to be ready..."
        sleep 10
        ((attempt++))
    done
    
    echo "❌ SonarQube server is not responding after $max_attempts attempts"
    exit 1
}

# Run PHPStan analysis with JSON output
run_phpstan_analysis() {
    echo "🔍 Running PHPStan analysis..."
    mkdir -p reports
    
    # Check if main app is running
    if ! docker-compose -f containers/docker-compose.yml ps -q laravel_blog_api > /dev/null 2>&1; then
        echo "Starting main application for PHPStan analysis..."
        cd containers && docker-compose -f docker-compose.yml up -d && cd ..
        echo "⏳ Waiting for application to be ready..."
        sleep 30
    fi
    
    # Run PHPStan with JSON format for SonarQube
    docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api \
        ./vendor/bin/phpstan analyse --configuration=phpstan.neon \
        --error-format=json > reports/phpstan.json || true
    
    echo "✅ PHPStan analysis completed"
}

# Run test coverage
run_test_coverage() {
    echo "🧪 Running test coverage..."
    mkdir -p reports
    
    # Check if test environment is already healthy and running
    cd containers
    if docker-compose -f docker-compose.test.yml ps -q laravel_blog_api_test > /dev/null 2>&1; then
        # Check if container is healthy
        if docker-compose -f docker-compose.test.yml ps laravel_blog_api_test | grep -q "healthy\|Up"; then
            echo "✅ Test environment is already healthy and running"
            cd ..
            # Continue with existing healthy environment
        else
            echo "⏳ Test environment exists but not healthy, restarting..."
            docker-compose -f docker-compose.test.yml up -d
            echo "⏳ Waiting for test environment to be ready..."
            sleep 30
            cd ..
        fi
    else
        echo "⏳ Starting test environment..."
        docker-compose -f docker-compose.test.yml up -d
        echo "⏳ Waiting for test environment to be ready..."
        sleep 30
        cd ..
        
        # Setup the test environment (install dependencies, etc.)
        echo "🔧 Setting up test environment..."
        # Install dependencies
        docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test \
            composer install --no-interaction --prefer-dist --optimize-autoloader
        
        # Generate application key
        docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test \
            php artisan key:generate --env=testing --force
        
        # Run migrations
        docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test \
            php artisan migrate --env=testing --force
    fi
    
    # Run tests with coverage
    docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test \
        php artisan test --coverage --coverage-clover ../reports/coverage.xml --stop-on-failure
    
    echo "✅ Test coverage completed"
    echo "ℹ️  Test environment kept running for other purposes"
}

# Run SonarQube Scanner using direct Docker command
run_sonar_scanner() {
    echo "📊 Running SonarQube Scanner..."
    
    # Get the project token
    if [ -z "$SONAR_TOKEN" ]; then
        echo "⚠️  SONAR_TOKEN not set. Please set it as an environment variable."
        echo "   You can generate a token at http://localhost:9000/account/security"
        echo "   Then run: export SONAR_TOKEN=your_token_here"
        exit 1
    fi
    
    # Get the SonarQube network name
    SONARQUBE_NETWORK=$(docker network ls --format "{{.Name}}" | grep "laravel_blog_sonarqube_sonarqube_network" | head -1)
    
    if [ -z "$SONARQUBE_NETWORK" ]; then
        echo "❌ SonarQube network not found. Make sure SonarQube containers are running."
        echo "   Available networks:"
        docker network ls
        exit 1
    fi
    
    echo "🔗 Using SonarQube network: $SONARQUBE_NETWORK"
    
    # Run SonarQube Scanner using SonarQube network and container name
    echo "🔍 Running SonarQube Scanner with SonarQube network..."
    docker run --rm \
        --network="$SONARQUBE_NETWORK" \
        -v "$(pwd):/usr/src" \
        -w /usr/src \
        -e SONAR_TOKEN=$SONAR_TOKEN \
        sonarsource/sonar-scanner-cli:latest \
        sonar-scanner \
        -Dsonar.projectKey=laravel-blog-api \
        -Dsonar.projectName="Laravel Blog API" \
        -Dsonar.projectVersion=1.0.0 \
        -Dsonar.sources=app \
        -Dsonar.tests=tests \
        -Dsonar.host.url=http://sonarqube:9000 \
        -Dsonar.token=$SONAR_TOKEN \
        -Dsonar.php.coverage.reportPaths=reports/coverage.xml \
        -Dsonar.php.phpstan.reportPaths=reports/phpstan.json \
        -Dsonar.newCode.referenceBranch=main \
        -Dsonar.scm.provider=git \
        -Dsonar.qualitygate.wait=false \
        -Dsonar.qualitygate.timeout=600 \
        -Dsonar.projectBaseDir=/usr/src \
        -Dsonar.scanner.analysisCacheEnabled=false \
        -Dsonar.verbose=true
    
    echo "✅ SonarQube analysis completed"
}

# Cleanup function
cleanup() {
    echo "🧹 Cleanup completed (test environment preserved)"
    echo "ℹ️  Test containers are kept running for other purposes"
    # Note: Test containers are intentionally NOT removed here
}

# Main execution
main() {
    # Set up cleanup trap
    trap cleanup EXIT
    
    # Check if SonarQube is running
    check_sonarqube_health
    
    # Run static analysis
    run_phpstan_analysis
    
    # Run test coverage
    run_test_coverage
    
    # Run SonarQube scanner
    run_sonar_scanner
    
    echo "🎉 SonarQube analysis completed successfully!"
    echo "📊 View results at: http://localhost:9000"
    echo "ℹ️  Test environment has been preserved for other purposes"
}

# Execute main function
main "$@"
