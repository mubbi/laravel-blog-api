# Setup Git Hooks (no environment requirements)
setup-git-hooks:
	@echo "SETUP: Installing Git hooks..."
	cp -r .githooks/ .git/hooks/
	chmod +x .git/hooks/pre-commit && chmod +x .git/hooks/pre-push && chmod +x .git/hooks/prepare-commit-msg
	@echo "SUCCESS: Git hooks installed!"

# Run Code Linting in Docker
docker-lint:
	@echo "LINT: Running Pint linter in Docker..."
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api ./vendor/bin/pint
	@echo "SUCCESS: Linting completed!"

# Run Code Linting (only recent changes) in Docker
docker-lint-dirty:
	@echo "LINT: Running Pint linter on dirty files in Docker..."
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api ./vendor/bin/pint --dirty
	@echo "SUCCESS: Dirty files linting completed!"

# Run Static Analysis (Larastan) in Docker
docker-analyze:
	@echo "ANALYZE: Running Larastan static analysis in Docker..."
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api ./vendor/bin/phpstan analyse --memory-limit=2G
	@echo "SUCCESS: Static analysis completed!"

# Run Artisan commands in Docker
docker-artisan:
	@echo "ARTISAN: Running custom artisan command in Docker..."
	@echo "Usage: make docker-artisan ARGS='migrate --seed'"
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api php artisan $(ARGS)

# Docker Environment Management

# Cleanup Docker environment (containers, images, volumes, networks)
docker-cleanup:
	@echo "CLEANUP: Docker environment..."
	@echo "Stopping and removing containers..."
	-cd containers && docker-compose down --remove-orphans
	-cd containers && docker-compose -f docker-compose.test.yml down --remove-orphans
	@echo "Removing project-specific images..."
	-docker rmi $$(docker images --filter "reference=containers_*" -q) 2>/dev/null || true
	@echo "Removing dangling images..."
	-docker image prune -f
	@echo "Removing unused volumes..."
	-docker volume prune -f
	@echo "Removing unused networks..."
	-docker network prune -f
	@echo "SUCCESS: Docker cleanup completed!"

# Setup Docker environment files
docker-setup-env:
	@echo "SETUP: Docker environment files..."
	bash containers/setup-env.sh
	@echo "SUCCESS: Environment files setup completed!"

# Verify Docker environment setup
docker-verify-env:
	@echo "VERIFY: Docker environment setup..."
	bash containers/verify-env-setup.sh

# Docker Development Environment

# Setup and start main development environment (full automated setup)
docker-dev: docker-cleanup docker-setup-env
	@echo "SETUP: Starting main development environment..."
	cd containers && docker-compose up -d
	@echo ">> Waiting for containers to be ready..."
	@echo ">> Main app will automatically:"
	@echo "   - Wait for database connection"
	@echo "   - Set up environment file"
	@echo "   - Install composer dependencies"
	@echo "   - Run migrations and seeders"
	@echo "   - Start web services"
	@echo ">> Queue worker will automatically start processing jobs"
	@echo ">> This may take a few minutes for initial setup..."
	@echo ">> Use 'make docker-status' to check progress"
	@echo ">> Use 'make docker-logs' to view detailed logs"
	@echo "SUCCESS: Development environment started!"

# Start existing development environment (no rebuild)
docker-up:
	@echo "START: Docker development environment..."
	cd containers && docker-compose up -d
	@echo "SUCCESS: Development environment started!"

# Stop development environment
docker-down:
	@echo "STOP: Docker development environment..."
	cd containers && docker-compose down
	@echo "SUCCESS: Development environment stopped!"

# Restart development environment
docker-restart: docker-down docker-up
	@echo "SUCCESS: Development environment restarted!"

# Rebuild and start development environment (force rebuild images)
docker-rebuild: docker-cleanup docker-setup-env
	@echo "REBUILD: Docker development environment..."
	cd containers && docker-compose build --no-cache
	cd containers && docker-compose up -d
	@echo "SUCCESS: Development environment rebuilt and started!"

# Docker Testing Environment

# Setup and run tests (automated testing environment)
docker-test: docker-setup-env
	@echo "TEST: Setting up and running tests..."
	cd containers && docker-compose -f docker-compose.test.yml up -d
	@echo ">> Installing dependencies in test container..."
	@sleep 10
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test composer install --no-interaction --prefer-dist --optimize-autoloader
	@echo ">> Verifying test environment file exists..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test test -f .env.testing || { echo "ERROR: .env.testing not found. Run 'make docker-setup-env' first."; exit 1; }
	@echo ">> Generating test application key..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan key:generate --env=testing --force
	@echo ">> Running test migrations and seeders..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan migrate:fresh --seed --env=testing --force
	@echo ">> Running tests..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan test --parallel --recreate-databases --stop-on-failure
	cd containers && docker-compose -f docker-compose.test.yml down
	@echo "SUCCESS: Tests completed!"

# Run tests with coverage report
docker-test-coverage: docker-setup-env
	@echo "TEST: Running tests with coverage..."
	cd containers && docker-compose -f docker-compose.test.yml up -d
	@echo ">> Installing dependencies in test container..."
	@sleep 10
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test composer install --no-interaction --prefer-dist --optimize-autoloader
	@echo ">> Verifying test environment file exists..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test test -f .env.testing || { echo "ERROR: .env.testing not found. Run 'make docker-setup-env' first."; exit 1; }
	@echo ">> Generating test application key..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan key:generate --env=testing --force
	@echo ">> Running test migrations and seeders..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan migrate:fresh --seed --env=testing --force
	@echo ">> Running tests with coverage..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan test --coverage --coverage-html reports/coverage --coverage-clover reports/coverage.xml --stop-on-failure --min=80
	cd containers && docker-compose -f docker-compose.test.yml down
	@echo "SUCCESS: Tests with coverage completed!"

# Start test environment only (for debugging)
docker-test-up:
	@echo "START: Docker test environment..."
	cd containers && docker-compose -f docker-compose.test.yml up -d
	@echo "SUCCESS: Test environment started!"

# Stop test environment
docker-test-down:
	@echo "STOP: Docker test environment..."
	cd containers && docker-compose -f docker-compose.test.yml down
	@echo "SUCCESS: Test environment stopped!"

# Docker Utilities

# Access main container shell
docker-shell:
	@echo "SHELL: Accessing main container..."
	docker-compose -f containers/docker-compose.yml exec laravel_blog_api bash

# Access test container shell
docker-test-shell:
	@echo "SHELL: Accessing test container..."
	docker-compose -f containers/docker-compose.test.yml exec laravel_blog_api_test bash

# View logs from all containers
docker-logs:
	@echo "LOGS: Viewing container logs..."
	cd containers && docker-compose logs -f

# View logs from main app only
docker-logs-app:
	@echo "LOGS: Viewing main app logs..."
	cd containers && docker-compose logs -f laravel_blog_api

# View logs from queue worker only
docker-logs-queue:
	@echo "LOGS: Viewing queue worker logs..."
	cd containers && docker-compose logs -f laravel_blog_api_queue

# Check container status and connection info
docker-status:
	@echo "STATUS: Container information..."
	cd containers && docker-compose ps
	@echo ""
	@echo ">> Application Access Points:"
	@echo "  - Laravel API: http://localhost:8081"
	@echo "  - Health Check: http://localhost:8081/api/health"
	@echo "  - MySQL: localhost:3306 (user: laravel_user, password: laravel_password)"
	@echo "  - Redis: localhost:6379"
	@echo ""
	@echo ">> Development Tools:"
	@echo "  - XDebug: Port 9003 (when enabled)"
	@echo "  - Application Shell: make docker-shell"
	@echo "  - Logs: make docker-logs"

# Docker Health & Monitoring

# Check application readiness and health
docker-health:
	@echo "HEALTH: Checking application status..."
	@echo ""
	@echo "INFO: Main App Ready Marker:"
	@if docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api test -f storage/laravel_ready 2>/dev/null; then \
		echo "SUCCESS: Ready marker exists - main app setup complete"; \
	else \
		echo "WAITING: Ready marker missing - main app still setting up"; \
	fi
	@echo ""
	@echo "INFO: HTTP Health Check:"
	@if curl -f http://localhost:8081/api/health -H "Accept: application/json" >/dev/null 2>&1; then \
		echo "SUCCESS: HTTP endpoint accessible"; \
	else \
		echo "ERROR: HTTP endpoint not accessible"; \
	fi
	@echo ""
	@echo "INFO: Queue Worker Status:"
	@if docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api_queue pgrep -f "queue:work" >/dev/null 2>&1; then \
		echo "SUCCESS: Queue worker is running"; \
	else \
		echo "WAITING: Queue worker not running"; \
	fi
	@echo ""
	@echo "INFO: Container Health Status:"
	@cd containers && docker-compose ps

# Check queue worker status
docker-queue-status:
	@echo "QUEUE: Worker and job status..."
	@echo ""
	@echo "Queue Worker Process:"
	@docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api_queue ps aux | grep "queue:work" || echo "Queue worker not running"
	@echo ""
	@echo "Queue Job Status:"
	@docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api php artisan queue:work --stop-when-empty --max-jobs=0 2>/dev/null || echo "Cannot check queue status - application may not be ready"

# Open application health endpoint in browser (Windows)
docker-open:
	@echo "BROWSER: Opening health check endpoint..."
	@echo "Attempting to open: http://localhost:8081/api/health"
	@start http://localhost:8081/api/health || echo "Could not open browser automatically. Please visit: http://localhost:8081/api/health"

# Complete Docker Setup

# Comprehensive setup - Sets up both development and testing environments
docker-setup-all: docker-cleanup docker-setup-env
	@echo "SETUP: Complete Docker environment (development + testing)..."
	@echo ""
	@echo "STEP 1: Setting up development environment..."
	cd containers && docker-compose up -d
	@echo ""
	@echo "STEP 2: Waiting for development environment to be ready..."
	@echo ">> This may take a few minutes for initial setup..."
	@echo ">> Use 'make docker-status' to check progress"
	@echo ""
	@echo "STEP 3: Setting up testing environment..."
	cd containers && docker-compose -f docker-compose.test.yml up -d
	@echo ">> Installing dependencies in test container..."
	@sleep 10
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test composer install --no-interaction --prefer-dist --optimize-autoloader
	@echo ">> Verifying test environment file exists..."
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test test -f .env.testing || echo "WARNING: .env.testing not found, tests may fail"
	@echo ">> Generating test application key..."
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan key:generate --env=testing --force
	@echo ">> Running test migrations and seeders..."
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan migrate:fresh --seed --env=testing --force
	@echo ""
	@echo "SUCCESS: Complete Docker environment setup finished!"
	@echo ""
	@echo "SUMMARY:"
	@echo ">> Development API: http://localhost:8081"
	@echo ">> Test environment: Ready for testing"
	@echo ">> MySQL Main: localhost:3306"
	@echo ">> MySQL Test: localhost:3307"
	@echo ">> Redis: localhost:6379"
	@echo ""
	@echo "NEXT STEPS:"
	@echo ">> Run 'make docker-status' to check all containers"
	@echo ">> Run 'make docker-test' to run tests"
	@echo ">> Access API at http://localhost:8081/api/health"

# =============================================================================
# SonarQube Quality Analysis
# =============================================================================

# Start SonarQube Server
docker-sonarqube-start:
	@echo "SONARQUBE: Starting SonarQube server..."
	cd containers && docker-compose -f docker-compose.sonarqube.yml up -d
	@echo "⏳ SonarQube is starting up... This may take a few minutes."
	@echo "📊 SonarQube will be available at: http://localhost:9000"
	@echo "   Default credentials: admin/admin"

# Stop SonarQube Server
docker-sonarqube-stop:
	@echo "SONARQUBE: Stopping SonarQube server..."
	cd containers && docker-compose -f docker-compose.sonarqube.yml down
	@echo "SUCCESS: SonarQube server stopped!"

# Setup SonarQube environment and token
docker-sonarqube-setup-token:
	@echo "SONARQUBE: Setting up SonarQube environment and token..."
	@echo "📋 Checking SonarQube environment configuration..."
	@if [ ! -f containers/.env.sonarqube ]; then \
		echo "❌ SonarQube environment file not found. Creating it from example..."; \
		if [ -f .env.sonarqube.example ]; then \
			cp .env.sonarqube.example containers/.env.sonarqube; \
			echo "✅ SonarQube environment file created from .env.sonarqube.example"; \
		else \
			echo "❌ .env.sonarqube.example not found. Creating basic environment file..."; \
			echo "SONAR_HOST_URL=http://localhost:9000" > containers/.env.sonarqube; \
			echo "# SONAR_TOKEN=your_token_here" >> containers/.env.sonarqube; \
		fi; \
	fi
	@echo "🔧 Opening SonarQube token setup helper..."
	./containers/sonarqube/scripts/setup-sonar-token.sh
	@echo "SUCCESS: SonarQube environment setup completed!"

# Setup SonarQube environment (create .env.sonarqube if missing)
docker-sonarqube-setup-env:
	@echo "SONARQUBE: Setting up SonarQube environment..."
	@if [ ! -f containers/.env.sonarqube ]; then \
		echo "📋 Creating SonarQube environment file from example..."; \
		if [ -f .env.sonarqube.example ]; then \
			cp .env.sonarqube.example containers/.env.sonarqube; \
			echo "✅ SonarQube environment file created from .env.sonarqube.example"; \
		else \
			echo "❌ .env.sonarqube.example not found. Creating basic environment file..."; \
			echo "# SonarQube Environment Configuration" > containers/.env.sonarqube; \
			echo "SONAR_HOST_URL=http://localhost:9000" >> containers/.env.sonarqube; \
			echo "# SONAR_TOKEN=your_token_here" >> containers/.env.sonarqube; \
			echo "SONAR_PROJECT_KEY=laravel-blog-api" >> containers/.env.sonarqube; \
			echo "SONAR_PROJECT_NAME=\"Laravel Blog API\"" >> containers/.env.sonarqube; \
			echo "SONAR_PROJECT_VERSION=1.0.0" >> containers/.env.sonarqube; \
			echo "SONAR_SOURCES=app" >> containers/.env.sonarqube; \
			echo "SONAR_TESTS=tests" >> containers/.env.sonarqube; \
		fi; \
	else \
		echo "✅ SonarQube environment file already exists"; \
	fi
	@echo "SUCCESS: SonarQube environment setup completed!"

# Run complete SonarQube analysis
docker-sonarqube-analyze: docker-sonarqube-setup-env docker-sonarqube-start
	@echo "SONARQUBE: Running complete quality analysis..."
	@echo "⚠️  Make sure to set SONAR_TOKEN environment variable first!"
	@echo "   Generate token at: http://localhost:9000/account/security"
	@if grep -q "^SONAR_TOKEN=" containers/.env.sonarqube && ! grep -q "^SONAR_TOKEN=your_token_here" containers/.env.sonarqube; then \
		echo "✅ SONAR_TOKEN is configured in .env.sonarqube"; \
	else \
		echo "❌ SONAR_TOKEN is not configured. Please run: make docker-sonarqube-setup-token"; \
		echo "   Current token status:"; \
		grep -n "SONAR_TOKEN" containers/.env.sonarqube || echo "   No SONAR_TOKEN found"; \
		exit 1; \
	fi
	./containers/sonarqube/scripts/sonar-analysis.sh
	@echo "SUCCESS: SonarQube analysis completed!"

# Run SonarQube analysis (assumes server is already running)
docker-sonarqube-scan: docker-sonarqube-setup-env
	@echo "SONARQUBE: Running SonarQube scanner..."
	@echo "⚠️  Make sure SONAR_TOKEN is set and SonarQube server is running!"
	@if grep -q "^SONAR_TOKEN=" containers/.env.sonarqube && ! grep -q "^SONAR_TOKEN=your_token_here" containers/.env.sonarqube; then \
		echo "✅ SONAR_TOKEN is configured in .env.sonarqube"; \
	else \
		echo "❌ SONAR_TOKEN is not configured. Please run: make docker-sonarqube-setup-token"; \
		echo "   Current token status:"; \
		grep -n "SONAR_TOKEN" containers/.env.sonarqube || echo "   No SONAR_TOKEN found"; \
		exit 1; \
	fi
	./containers/sonarqube/scripts/sonar-analysis.sh
	@echo "SUCCESS: SonarQube scan completed!"

# Run SonarQube analysis for CI/CD (external SonarQube server)
docker-sonarqube-ci:
	@echo "SONARQUBE: Running SonarQube analysis for CI/CD..."
	@echo "⚠️  Make sure SONAR_TOKEN and SONAR_HOST_URL are set!"
	./containers/sonarqube/scripts/sonar-analysis-ci.sh
	@echo "SUCCESS: SonarQube CI analysis completed!"

# Generate reports for SonarQube (without running scanner)
docker-sonarqube-reports:
	@echo "SONARQUBE: Generating analysis reports..."
	@echo ">> Running PHPStan analysis with JSON output..."
	mkdir -p reports
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > reports/phpstan.json || true
	@echo ">> Running test coverage..."
	$(MAKE) docker-test-coverage
	@echo "SUCCESS: Reports generated in reports/ directory!"
	$(MAKE) docker-test-coverage
	@echo "SUCCESS: Reports generated in reports/ directory!"

# View SonarQube dashboard
docker-sonarqube-dashboard:
	@echo "📊 Opening SonarQube dashboard..."
	open http://localhost:9000 || echo "Please open http://localhost:9000 in your browser"

# Clean SonarQube data (reset everything)
docker-sonarqube-clean:
	@echo "SONARQUBE: Cleaning SonarQube data..."
	cd containers && docker-compose -f docker-compose.sonarqube.yml down -v
	@echo "SUCCESS: SonarQube data cleaned!"

# Show available commands and usage
help:
	@echo "Laravel Blog API - Docker-based Development Environment"
	@echo "======================================================"
	@echo ""
	@echo "Quick Start:"
	@echo "  make docker-dev          - Setup and start development environment"
	@echo "  make docker-test         - Run tests (includes setup)"
	@echo "  make docker-status       - Check container status and access points"
	@echo "  make docker-health       - Check application health"
	@echo ""
	@echo "Environment Management:"
	@echo "  make docker-setup-env    - Setup environment files"
	@echo "  make docker-verify-env   - Verify environment setup"
	@echo "  make docker-setup-all    - Setup both dev and test environments"
	@echo "  make docker-cleanup      - Clean up all containers and resources"
	@echo ""
	@echo "Development Environment:"
	@echo "  make docker-up           - Start existing development environment"
	@echo "  make docker-down         - Stop development environment"
	@echo "  make docker-restart      - Restart development environment"
	@echo "  make docker-rebuild      - Rebuild and start environment"
	@echo ""
	@echo "Testing:"
	@echo "  make docker-test         - Run all tests (automated setup)"
	@echo "  make docker-test-coverage - Run tests with coverage report"
	@echo "  make docker-test-up      - Start test environment only"
	@echo "  make docker-test-down    - Stop test environment"
	@echo ""
	@echo "Code Quality:"
	@echo "  make docker-lint         - Run code linting (Pint)"
	@echo "  make docker-lint-dirty   - Lint only changed files"
	@echo "  make docker-analyze      - Run static analysis (Larastan)"
	@echo ""
	@echo "SonarQube Quality Analysis:"
	@echo "  make docker-sonarqube-start    - Start SonarQube server"
	@echo "  make docker-sonarqube-stop     - Stop SonarQube server"
	@echo "  make docker-sonarqube-setup-env - Setup SonarQube environment file"
	@echo "  make docker-sonarqube-setup-token - Setup SonarQube authentication token"
	@echo "  make docker-sonarqube-analyze  - Run complete SonarQube analysis"
	@echo "  make docker-sonarqube-scan     - Run SonarQube scanner only"
	@echo "  make docker-sonarqube-reports  - Generate reports for SonarQube"
	@echo "  make docker-sonarqube-dashboard - Open SonarQube dashboard"
	@echo "  make docker-sonarqube-clean    - Clean SonarQube data"
	@echo ""
	@echo "Utilities:"
	@echo "  make docker-shell        - Access main container shell"
	@echo "  make docker-test-shell   - Access test container shell"
	@echo "  make docker-logs         - View all container logs"
	@echo "  make docker-logs-app     - View main app logs"
	@echo "  make docker-logs-queue   - View queue worker logs"
	@echo "  make docker-artisan ARGS='...' - Run artisan commands"
	@echo "  make docker-queue-status - Check queue worker status"
	@echo "  make docker-open         - Open health endpoint in browser"
	@echo ""
	@echo "Git Tools:"
	@echo "  make setup-git-hooks     - Install Git hooks (no env requirements)"
	@echo ""
	@echo "Access Points:"
	@echo "  - Laravel API: http://localhost:8081"
	@echo "  - Health Check: http://localhost:8081/api/health"
	@echo "  - SonarQube: http://localhost:9000"
	@echo "  - MySQL: localhost:3306"
	@echo "  - Redis: localhost:6379"

# Default target
.PHONY: help
.DEFAULT_GOAL := help
