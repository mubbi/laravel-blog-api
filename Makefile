# =============================================================================
# Local Full Setup - Complete Development Environment
# =============================================================================

# Complete local development setup (MAIN COMMAND)
local-setup: docker-cleanup docker-setup-env check-ports install-commit-tools setup-git-hooks
	@echo "🚀 SETUP: Complete local development environment..."
	@echo ""
	@echo "📦 Setting up Docker containers..."
	cd containers && docker-compose up -d
	cd containers && docker-compose -f docker-compose.test.yml up -d
	@echo ""
	@echo "⏳ Waiting for containers to be ready..."
	@sleep 15
	@echo ""
	@echo "🔧 Installing test dependencies..."
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test composer install --no-interaction --prefer-dist --optimize-autoloader
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan key:generate --env=testing --force
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan migrate:fresh --seed --env=testing --force
	@echo ""
	@echo "✅ SUCCESS: Local development environment setup complete!"
	@echo ""
	@echo "🎯 DEVELOPMENT ENVIRONMENT READY:"
	@echo "  - Laravel API: http://localhost:8081"
	@echo "  - Health Check: http://localhost:8081/api/health"
	@echo "  - MySQL: localhost:3306"
	@echo "  - Redis: localhost:6379"
	@echo ""
	@echo "🧪 TESTING ENVIRONMENT READY:"
	@echo "  - Test MySQL: localhost:3307"
	@echo "  - Test Redis: localhost:6380"
	@echo ""
	@echo "🛠️  GIT TOOLS READY:"
	@echo "  - Semantic commits with Husky hooks"
	@echo "  - PHPStan and unit tests on push"
	@echo "  - make commit - Interactive semantic commits"
	@echo ""
	@echo "📊 OPTIONAL: make sonarqube-setup - Setup SonarQube analysis"

# Install Node.js dependencies for commit tools
install-commit-tools:
	@echo "SETUP: Installing commit tools..."
	npm install
	@echo "SUCCESS: Commit tools installed!"

# Setup Git Hooks
setup-git-hooks:
	@echo "SETUP: Installing Git hooks..."
	cp -r .githooks/* .git/hooks/
	chmod +x .git/hooks/pre-commit && chmod +x .git/hooks/pre-push && chmod +x .git/hooks/prepare-commit-msg && chmod +x .git/hooks/commit-msg
	@echo "SUCCESS: Git hooks installed!"

# Interactive semantic commit
commit:
	@echo "🚀 Starting interactive semantic commit..."
	npm run commit

# Validate commit message format
validate-commit:
	@echo "VALIDATE: Checking commit message format..."
	npm run lint:commit

# Create a release (for maintainers)
release:
	@echo "RELEASE: Creating release with release-please..."
	npm run release

# =============================================================================
# Port Management
# =============================================================================

# Check port availability (standalone command)
check-ports-standalone:
	@echo "🔍 PORTS: Checking port availability for Docker services..."
	@bash containers/check-ports.sh
	@echo ""
	@echo "💡 TIP: Use 'make local-setup' to automatically check ports before setup"

# Check SonarQube port availability (standalone command)
check-sonarqube-ports-standalone:
	@echo "🔍 SONARQUBE PORTS: Checking SonarQube port availability..."
	@bash containers/check-sonarqube-ports.sh
	@echo ""
	@echo "💡 TIP: Use 'make sonarqube-setup' to automatically check ports before SonarQube setup"

# =============================================================================
# Docker Environment Management
# =============================================================================

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

# Check port availability before Docker setup
check-ports:
	@echo "🔍 PORTS: Checking port availability for Docker services..."
	@bash containers/check-ports.sh || (echo "❌ Port check failed. Please resolve port conflicts before continuing." && exit 1)
	@echo "✅ SUCCESS: All required ports are available!"

# =============================================================================
# Docker Development Environment
# =============================================================================

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

# =============================================================================
# Testing Environment
# =============================================================================


# Run tests (automated testing environment)
test:
	@if docker-compose -f containers/docker-compose.test.yml ps | grep -q 'laravel_blog_api_test' && docker-compose -f containers/docker-compose.test.yml ps | grep 'Up'; then \
		echo "🧪 TESTING: Test container already running. Skipping setup..."; \
		echo ">> Running tests..."; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan test --parallel --recreate-databases --stop-on-failure; \
		echo "✅ SUCCESS: Tests completed!"; \
	else \
		echo "🧪 TESTING: Running complete test suite..."; \
		cd containers && docker-compose -f docker-compose.test.yml up -d; \
		echo ">> Installing dependencies in test container..."; \
		sleep 10; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test composer install --no-interaction --prefer-dist --optimize-autoloader; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan key:generate --env=testing --force; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan migrate:fresh --seed --env=testing --force; \
		echo ">> Running tests..."; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan test --parallel --recreate-databases --stop-on-failure; \
		echo "✅ SUCCESS: Tests completed!"; \
	fi


# Run tests with coverage report
test-coverage:
	@if docker-compose -f containers/docker-compose.test.yml ps | grep -q 'laravel_blog_api_test' && docker-compose -f containers/docker-compose.test.yml ps | grep 'Up'; then \
		echo "🧪 TESTING: Test container already running. Skipping setup..."; \
		echo ">> Running tests with coverage..."; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan test --coverage --coverage-html reports/coverage --coverage-clover reports/coverage.xml --stop-on-failure --min=70; \
		echo "✅ SUCCESS: Tests with coverage completed!"; \
	else \
		echo "🧪 TESTING: Running tests with coverage..."; \
		cd containers && docker-compose -f docker-compose.test.yml up -d; \
		sleep 10; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test composer install --no-interaction --prefer-dist --optimize-autoloader; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan key:generate --env=testing --force; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan migrate:fresh --seed --env=testing --force; \
		echo ">> Running tests with coverage..."; \
		docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan test --coverage --coverage-html reports/coverage --coverage-clover reports/coverage.xml --stop-on-failure --min=70; \
		echo "✅ SUCCESS: Tests with coverage completed!"; \
	fi

# =============================================================================
# Code Quality Tools
# =============================================================================

# Run Code Linting with Pint
lint:
	@echo "🔍 LINT: Running Pint linter..."
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api ./vendor/bin/pint
	@echo "SUCCESS: Linting completed!"

# Run Code Linting (only recent changes)
lint-dirty:
	@echo "🔍 LINT: Running Pint linter on dirty files..."
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api ./vendor/bin/pint --dirty
	@echo "SUCCESS: Dirty files linting completed!"

# Run Static Analysis (PHPStan)
analyze:
	@echo "🔍 ANALYZE: Running PHPStan static analysis..."
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api ./vendor/bin/phpstan analyse --memory-limit=2G
	@echo "SUCCESS: Static analysis completed!"

# Run Artisan commands
artisan:
	@echo "ARTISAN: Running custom artisan command..."
	@echo "Usage: make artisan ARGS='migrate --seed'"
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api php artisan $(ARGS)

# =============================================================================
# Container Utilities
# =============================================================================

# Access main container shell
shell:
	@echo "SHELL: Accessing main container..."
	docker-compose -f containers/docker-compose.yml exec laravel_blog_api bash

# Access test container shell
test-shell:
	@echo "SHELL: Accessing test container..."
	docker-compose -f containers/docker-compose.test.yml exec laravel_blog_api_test bash

# View logs from all containers
logs:
	@echo "LOGS: Viewing container logs..."
	cd containers && docker-compose logs -f

# Check container status and connection info
status:
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
	@echo "  - Application Shell: make shell"
	@echo "  - Logs: make logs"

# Check application readiness and health
health:
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
	@echo "INFO: Container Health Status:"
	@cd containers && docker-compose ps

# =============================================================================
# SonarQube Quality Analysis (Optional)
# =============================================================================

# Complete SonarQube setup and analysis
sonarqube-setup: sonarqube-setup-env check-sonarqube-ports sonarqube-start
	@echo "🔍 SONARQUBE: Complete setup and analysis..."
	@echo "⏳ SonarQube is starting up... This may take a few minutes."
	@echo "📊 SonarQube will be available at: http://localhost:9000"
	@echo "   Default credentials: admin/admin"
	@echo ""
	@echo "🔧 NEXT STEPS:"
	@echo "1. Visit http://localhost:9000 and login (admin/admin)"
	@echo "2. Generate token at: Account → Security → Tokens"
	@echo "3. Run: make sonarqube-setup-token"
	@echo "4. Run: make sonarqube-analyze"

# Start SonarQube Server
sonarqube-start:
	@echo "SONARQUBE: Starting SonarQube server..."
	cd containers && docker-compose -f docker-compose.sonarqube.yml up -d

# Stop SonarQube Server
sonarqube-stop:
	@echo "SONARQUBE: Stopping SonarQube server..."
	cd containers && docker-compose -f docker-compose.sonarqube.yml down
	@echo "SUCCESS: SonarQube server stopped!"

# Check SonarQube port availability
check-sonarqube-ports:
	@echo "🔍 SONARQUBE PORTS: Checking port availability..."
	@bash containers/check-sonarqube-ports.sh || (echo "⚠️  SonarQube port check failed. You can continue without SonarQube or resolve port conflicts." && read -p "Continue anyway? (y/N): " confirm && [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ] || exit 1)
	@echo "✅ SUCCESS: SonarQube ports are available!"

# Setup SonarQube environment and token
sonarqube-setup-token:
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
sonarqube-setup-env:
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
sonarqube-analyze: sonarqube-setup-env sonarqube-start
	@echo "SONARQUBE: Running complete quality analysis..."
	@echo "⚠️  Make sure to set SONAR_TOKEN environment variable first!"
	@echo "   Generate token at: http://localhost:9000/account/security"
	@if grep -q "^SONAR_TOKEN=" containers/.env.sonarqube && ! grep -q "^SONAR_TOKEN=your_token_here" containers/.env.sonarqube; then \
		echo "✅ SONAR_TOKEN is configured in .env.sonarqube"; \
	else \
		echo "❌ SONAR_TOKEN is not configured. Please run: make sonarqube-setup-token"; \
		echo "   Current token status:"; \
		grep -n "SONAR_TOKEN" containers/.env.sonarqube || echo "   No SONAR_TOKEN found"; \
		exit 1; \
	fi
	./containers/sonarqube/scripts/sonar-analysis.sh
	@echo "SUCCESS: SonarQube analysis completed!"

# View SonarQube dashboard
sonarqube-dashboard:
	@echo "📊 Opening SonarQube dashboard..."
	open http://localhost:9000 || echo "Please open http://localhost:9000 in your browser"

# Clean SonarQube data (reset everything)
sonarqube-clean:
	@echo "SONARQUBE: Cleaning SonarQube data..."
	cd containers && docker-compose -f docker-compose.sonarqube.yml down -v
	@echo "SUCCESS: SonarQube data cleaned!"

# =============================================================================
# Help and Usage
# =============================================================================

# Show available commands and usage
help:
	@echo "Laravel Blog API - Local Development Environment"
	@echo "==============================================="
	@echo ""
	@echo "🚀 MAIN SETUP COMMAND:"
	@echo "  make local-setup         - Complete local development setup"
	@echo "                            (Docker containers + Testing + Git tools)"
	@echo ""
	@echo "📊 OPTIONAL SETUP:"
	@echo "  make sonarqube-setup     - Setup SonarQube code quality analysis"
	@echo ""
	@echo "🔧 DEVELOPMENT WORKFLOW:"
	@echo "  make commit              - Interactive semantic commit"
	@echo "  make test                - Run all tests"
	@echo "  make test-coverage       - Run tests with coverage report"
	@echo "  make lint                - Run code linting (Pint)"
	@echo "  make analyze             - Run static analysis (PHPStan)"
	@echo ""
	@echo "🐳 CONTAINER MANAGEMENT:"
	@echo "  make docker-up           - Start containers"
	@echo "  make docker-down         - Stop containers"
	@echo "  make status              - Check container status"
	@echo "  make health              - Check application health"
	@echo "  make logs                - View container logs"
	@echo "  make shell               - Access main container shell"
	@echo ""
	@echo "🔍 SONARQUBE (OPTIONAL):"
	@echo "  make sonarqube-start     - Start SonarQube server"
	@echo "  make sonarqube-analyze   - Run code quality analysis"
	@echo "  make sonarqube-dashboard - Open SonarQube dashboard"
	@echo "  make sonarqube-stop      - Stop SonarQube server"
	@echo ""
	@echo "🧹 CLEANUP:"
	@echo "  make docker-cleanup      - Clean up all containers and resources"
	@echo ""
	@echo "📋 ACCESS POINTS:"
	@echo "  - Laravel API: http://localhost:8081"
	@echo "  - Health Check: http://localhost:8081/api/health"
	@echo "  - SonarQube: http://localhost:9000 (when started)"
	@echo "  - MySQL: localhost:3306"
	@echo "  - Redis: localhost:6379"

# Default target
.PHONY: help
.DEFAULT_GOAL := help
