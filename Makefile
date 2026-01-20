# =============================================================================
# Variables
# =============================================================================

DOCKER_COMPOSE_MAIN := containers/docker-compose.yml
DOCKER_COMPOSE_TEST := containers/docker-compose.test.yml
DOCKER_COMPOSE_SONAR := containers/docker-compose.sonarqube.yml
CONTAINER_MAIN := laravel_blog_api
CONTAINER_TEST := laravel_blog_api_test
CONTAINER_DIR := containers

# =============================================================================
# Local Full Setup - Complete Development Environment
# =============================================================================

# Complete local development setup (MAIN COMMAND)
local-setup: docker-cleanup docker-setup-env check-ports install-commit-tools setup-git-hooks
	@echo "🚀 SETUP: Complete local development environment..."
	@echo ""
	@echo "📦 Setting up Docker containers..."
	cd $(CONTAINER_DIR) && docker-compose up -d
	cd $(CONTAINER_DIR) && docker-compose -f docker-compose.test.yml up -d
	@echo ""
	@echo "⏳ Waiting for containers to be ready..."
	@sleep 15
	@echo ""
	@echo "🔧 Installing test dependencies..."
	-docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) composer install --no-interaction --prefer-dist --optimize-autoloader
	-docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan key:generate --env=testing --force
	-docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan migrate:fresh --seed --env=testing --force
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
	@bash $(CONTAINER_DIR)/check-ports.sh
	@echo ""
	@echo "💡 TIP: Use 'make local-setup' to automatically check ports before setup"

# Check port availability before Docker setup
check-ports:
	@echo "🔍 PORTS: Checking port availability for Docker services..."
	@bash $(CONTAINER_DIR)/check-ports.sh || (echo "❌ Port check failed. Please resolve port conflicts before continuing." && exit 1)
	@echo "✅ SUCCESS: All required ports are available!"

# Check SonarQube port availability (standalone command)
check-sonarqube-ports-standalone:
	@echo "🔍 SONARQUBE PORTS: Checking SonarQube port availability..."
	@bash $(CONTAINER_DIR)/check-sonarqube-ports.sh
	@echo ""
	@echo "💡 TIP: Use 'make sonarqube-setup' to automatically check ports before SonarQube setup"

# Check SonarQube port availability
check-sonarqube-ports:
	@echo "🔍 SONARQUBE PORTS: Checking port availability..."
	@bash $(CONTAINER_DIR)/check-sonarqube-ports.sh || (echo "⚠️  SonarQube port check failed. You can continue without SonarQube or resolve port conflicts." && read -p "Continue anyway? (y/N): " confirm && [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ] || exit 1)
	@echo "✅ SUCCESS: SonarQube ports are available!"

# =============================================================================
# Docker Environment Management
# =============================================================================

# Cleanup Docker environment (containers, images, volumes, networks)
docker-cleanup:
	@echo "CLEANUP: Docker environment..."
	@echo "Stopping and removing containers..."
	-cd $(CONTAINER_DIR) && docker-compose down --remove-orphans
	-cd $(CONTAINER_DIR) && docker-compose -f docker-compose.test.yml down --remove-orphans
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
	bash $(CONTAINER_DIR)/setup-env.sh
	@echo "SUCCESS: Environment files setup completed!"

# Verify Docker environment setup
docker-verify-env:
	@echo "VERIFY: Docker environment setup..."
	bash $(CONTAINER_DIR)/verify-env-setup.sh

# =============================================================================
# Docker Development Environment
# =============================================================================

# Start existing development environment (no rebuild)
docker-up:
	@echo "START: Docker development environment..."
	cd $(CONTAINER_DIR) && docker-compose up -d
	@echo "SUCCESS: Development environment started!"

# Stop development environment
docker-down:
	@echo "STOP: Docker development environment..."
	cd $(CONTAINER_DIR) && docker-compose down
	@echo "SUCCESS: Development environment stopped!"

# Restart development environment
docker-restart: docker-down docker-up
	@echo "SUCCESS: Development environment restarted!"

# =============================================================================
# Testing Environment
# =============================================================================

# Internal: Check if test container is running
_test-container-running:
	@docker-compose -f $(DOCKER_COMPOSE_TEST) ps | grep -q '$(CONTAINER_TEST)' && docker-compose -f $(DOCKER_COMPOSE_TEST) ps | grep 'Up' || exit 1

# Internal: Setup test container
_test-setup:
	@echo "🧪 TESTING: Setting up test environment..."
	cd $(CONTAINER_DIR) && docker-compose -f docker-compose.test.yml up -d
	@echo ">> Installing dependencies in test container..."
	@sleep 10
	docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) composer install --no-interaction --prefer-dist --optimize-autoloader
	docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan key:generate --env=testing --force
	docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan config:clear
	docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan route:clear
	docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan migrate:fresh --seed --env=testing --force

# Internal: Clear test caches
_test-clear-cache:
	@echo ">> Clearing caches..."
	docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan config:clear
	docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan route:clear

# Run tests (automated testing environment)
# Usage: make test                    - Run all tests
# Usage: make test filter='TestName'  - Run specific test by filter
test:
	@if docker-compose -f $(DOCKER_COMPOSE_TEST) ps | grep -q '$(CONTAINER_TEST)' && docker-compose -f $(DOCKER_COMPOSE_TEST) ps | grep 'Up' >/dev/null 2>&1; then \
		echo "🧪 TESTING: Test container already running. Skipping setup..."; \
		$(MAKE) -s _test-clear-cache; \
		if [ -n "$(filter)" ]; then \
			echo ">> Running filtered test: $(filter)..."; \
			docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan test --filter=$(filter); \
		else \
			echo ">> Running tests..."; \
			docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan test --parallel --recreate-databases --stop-on-failure; \
		fi; \
		echo "✅ SUCCESS: Tests completed!"; \
	else \
		$(MAKE) -s _test-setup; \
		if [ -n "$(filter)" ]; then \
			echo ">> Running filtered test: $(filter)..."; \
			docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan test --filter=$(filter); \
		else \
			echo ">> Running tests..."; \
			docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) php artisan test --parallel --recreate-databases --stop-on-failure; \
		fi; \
		echo "✅ SUCCESS: Tests completed!"; \
	fi

# Run tests with coverage report
test-coverage:
	@if docker-compose -f $(DOCKER_COMPOSE_TEST) ps | grep -q '$(CONTAINER_TEST)' && docker-compose -f $(DOCKER_COMPOSE_TEST) ps | grep 'Up' >/dev/null 2>&1; then \
		echo "🧪 TESTING: Test container already running. Skipping setup..."; \
		$(MAKE) -s _test-clear-cache; \
		echo ">> Running tests with coverage (memory limit: 2GB)..."; \
		docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) bash -c "php -d memory_limit=2G artisan test --coverage --coverage-html reports/coverage --coverage-clover reports/coverage.xml --stop-on-failure --min=70"; \
		echo "✅ SUCCESS: Tests with coverage completed!"; \
	else \
		$(MAKE) -s _test-setup; \
		echo ">> Running tests with coverage (memory limit: 2GB)..."; \
		docker-compose -f $(DOCKER_COMPOSE_TEST) exec -T $(CONTAINER_TEST) bash -c "php -d memory_limit=2G artisan test --coverage --coverage-html reports/coverage --coverage-clover reports/coverage.xml --stop-on-failure --min=70"; \
		echo "✅ SUCCESS: Tests with coverage completed!"; \
	fi

# =============================================================================
# Code Quality Tools
# =============================================================================

# Run Code Linting with Pint
lint:
	@echo "🔍 LINT: Running Pint linter..."
	docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) ./vendor/bin/pint
	@echo "SUCCESS: Linting completed!"

# Run Code Linting (only recent changes)
lint-dirty:
	@echo "🔍 LINT: Running Pint linter on dirty files..."
	docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) ./vendor/bin/pint --dirty
	@echo "SUCCESS: Dirty files linting completed!"

# Run Static Analysis (PHPStan)
analyze:
	@echo "🔍 ANALYZE: Running PHPStan static analysis..."
	@echo ">> Ensuring .env file exists with APP_KEY..."
	@docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) bash -c "if [ ! -f .env ]; then if [ -f .env.docker.example ]; then cp .env.docker.example .env; else echo 'ERROR: .env file not found and .env.docker.example does not exist'; exit 1; fi; fi; if ! grep -q 'APP_KEY=base64:' .env 2>/dev/null; then php artisan key:generate --force 2>/dev/null || true; fi"
	@echo ">> Clearing all caches..."
	@docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) bash -c "rm -rf bootstrap/cache/*.php storage/framework/cache/* storage/framework/views/* 2>/dev/null || true"
	@echo ">> Running PHPStan..."
	docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) ./vendor/bin/phpstan analyse --memory-limit=2G
	@echo "SUCCESS: Static analysis completed!"

# Run Artisan commands
artisan:
	@echo "ARTISAN: Running custom artisan command..."
	@echo "Usage: make artisan ARGS='migrate --seed'"
	docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) php artisan $(ARGS)

# Dump database schema and prune migrations
schema-dump:
	@echo "SCHEMA DUMP: Dumping database schema and pruning migrations..."
	docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) php artisan schema:dump --prune
	@echo "SUCCESS: Database schema dumped and pruned successfully!"

# =============================================================================
# Container Utilities
# =============================================================================

# Access main container shell
shell:
	@echo "SHELL: Accessing main container..."
	docker-compose -f $(DOCKER_COMPOSE_MAIN) exec $(CONTAINER_MAIN) bash

# Access test container shell
test-shell:
	@echo "SHELL: Accessing test container..."
	docker-compose -f $(DOCKER_COMPOSE_TEST) exec $(CONTAINER_TEST) bash

# View logs from all containers
logs:
	@echo "LOGS: Viewing container logs..."
	cd $(CONTAINER_DIR) && docker-compose logs -f

# Check container status and connection info
status:
	@echo "STATUS: Container information..."
	cd $(CONTAINER_DIR) && docker-compose ps
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
	@if docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) test -f storage/laravel_ready 2>/dev/null; then \
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
	@cd $(CONTAINER_DIR) && docker-compose ps

# =============================================================================
# SonarQube Quality Analysis (Optional)
# =============================================================================

# Internal: Setup SonarQube environment file
_sonarqube-setup-env:
	@if [ ! -f $(CONTAINER_DIR)/.env.sonarqube ]; then \
		echo "📋 Creating SonarQube environment file from example..."; \
		if [ -f .env.sonarqube.example ]; then \
			cp .env.sonarqube.example $(CONTAINER_DIR)/.env.sonarqube; \
			echo "✅ SonarQube environment file created from .env.sonarqube.example"; \
		else \
			echo "❌ .env.sonarqube.example not found. Creating basic environment file..."; \
			echo "# SonarQube Environment Configuration" > $(CONTAINER_DIR)/.env.sonarqube; \
			echo "SONAR_HOST_URL=http://localhost:9000" >> $(CONTAINER_DIR)/.env.sonarqube; \
			echo "# SONAR_TOKEN=your_token_here" >> $(CONTAINER_DIR)/.env.sonarqube; \
			echo "SONAR_PROJECT_KEY=laravel-blog-api" >> $(CONTAINER_DIR)/.env.sonarqube; \
			echo "SONAR_PROJECT_NAME=\"Laravel Blog API\"" >> $(CONTAINER_DIR)/.env.sonarqube; \
			echo "SONAR_PROJECT_VERSION=1.0.0" >> $(CONTAINER_DIR)/.env.sonarqube; \
			echo "SONAR_SOURCES=app" >> $(CONTAINER_DIR)/.env.sonarqube; \
			echo "SONAR_TESTS=tests" >> $(CONTAINER_DIR)/.env.sonarqube; \
		fi; \
	else \
		echo "✅ SonarQube environment file already exists"; \
	fi

# Complete SonarQube setup and analysis
sonarqube-setup: _sonarqube-setup-env check-sonarqube-ports sonarqube-start
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
	cd $(CONTAINER_DIR) && docker-compose -f docker-compose.sonarqube.yml up -d

# Stop SonarQube Server
sonarqube-stop:
	@echo "SONARQUBE: Stopping SonarQube server..."
	cd $(CONTAINER_DIR) && docker-compose -f docker-compose.sonarqube.yml down
	@echo "SUCCESS: SonarQube server stopped!"

# Setup SonarQube environment and token
sonarqube-setup-env: _sonarqube-setup-env
	@echo "SUCCESS: SonarQube environment setup completed!"

# Setup SonarQube environment and token
sonarqube-setup-token: _sonarqube-setup-env
	@echo "SONARQUBE: Setting up SonarQube token..."
	@echo "🔧 Opening SonarQube token setup helper..."
	./$(CONTAINER_DIR)/sonarqube/scripts/setup-sonar-token.sh
	@echo "SUCCESS: SonarQube token setup completed!"

# Wait for SonarQube to become healthy
sonarqube-wait:
	@echo "⏳ SONARQUBE: Waiting for SonarQube to be ready..."
	@bash -c 'max_attempts=30; attempt=1; while [ $$attempt -le $$max_attempts ]; do if curl -fsS http://localhost:9000/api/system/status >/dev/null 2>&1; then echo "✅ SonarQube is ready"; exit 0; fi; echo "⏳ Attempt $$attempt/$$max_attempts: still starting..."; sleep 10; attempt=$$((attempt+1)); done; echo "❌ SonarQube is not responding after $$max_attempts attempts"; exit 1'

# Run PHPStan and save JSON report for SonarQube
phpstan-sonar: docker-up
	@echo "🔍 SONARQUBE: Running PHPStan (JSON report)..."
	@mkdir -p reports
	@echo ">> Ensuring .env file exists with APP_KEY..."
	@docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) bash -c "if [ ! -f .env ]; then if [ -f .env.docker.example ]; then cp .env.docker.example .env; else echo 'ERROR: .env file not found and .env.docker.example does not exist'; exit 1; fi; fi; if ! grep -q 'APP_KEY=base64:' .env 2>/dev/null; then php artisan key:generate --force 2>/dev/null || true; fi"
	@echo ">> Running PHPStan (errors will NOT fail the build; report is still generated)..."
	-docker-compose -f $(DOCKER_COMPOSE_MAIN) exec -T $(CONTAINER_MAIN) ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > reports/phpstan.json
	@echo "✅ SUCCESS: PHPStan JSON report saved to reports/phpstan.json"

# Run SonarQube scanner in Docker, using SonarQube network (local setup)
sonarqube-scan-local: sonarqube-start sonarqube-wait
	@echo "📊 SONARQUBE: Running SonarQube Scanner (local network mode)..."
	@bash -c 'set -euo pipefail; \
		PROJECT_ROOT="$$(pwd)"; \
		if [ -f "$(CONTAINER_DIR)/.env.sonarqube" ]; then set -a; . "$(CONTAINER_DIR)/.env.sonarqube"; set +a; \
		elif [ -f ".env.sonarqube" ]; then set -a; . ".env.sonarqube"; set +a; fi; \
		if [ -z "$${SONAR_TOKEN:-}" ] || [ "$${SONAR_TOKEN:-}" = "your_token_here" ]; then \
			echo "❌ SONAR_TOKEN is not configured. Please run: make sonarqube-setup-token"; \
			exit 1; \
		fi; \
		SONAR_TOKEN="$$(printf "%s" "$$SONAR_TOKEN" | tr -d "\r")"; \
		mkdir -p reports; \
		NETWORK="$$(docker network ls --format "{{.Name}}" | grep -E "laravel_blog_sonarqube_sonarqube_network$$" | head -1 || true)"; \
		if [ -z "$$NETWORK" ]; then NETWORK="$$(docker network ls --format "{{.Name}}" | grep "sonarqube_network" | head -1 || true)"; fi; \
		if [ -z "$$NETWORK" ]; then \
			echo "❌ SonarQube network not found. Make sure SonarQube is running."; \
			echo "Available networks:"; \
			docker network ls; \
			exit 1; \
		fi; \
		echo "🔗 Using SonarQube network: $$NETWORK"; \
		set +e; \
		docker run --rm \
			--network="$$NETWORK" \
			--mount type=bind,source="$$PROJECT_ROOT",target=/usr/src \
			-e "SONAR_TOKEN=$$SONAR_TOKEN" \
			sonarsource/sonar-scanner-cli:latest \
			sh -c "cd /usr/src && sonar-scanner -Dsonar.host.url=http://sonarqube:9000 -Dsonar.token=$$SONAR_TOKEN -Dsonar.qualitygate.wait=false -Dsonar.qualitygate.timeout=600 -Dsonar.verbose=true" \
			2>&1 | tee "reports/sonar-scanner.log"; \
		rc="$${PIPESTATUS[0]}"; \
		set -e; \
		if [ "$$rc" -ne 0 ]; then \
			echo "❌ SonarQube scanner failed (exit code: $$rc)"; \
			echo "📄 See: reports/sonar-scanner.log"; \
			exit "$$rc"; \
		fi; \
		echo "✅ SonarQube analysis completed (local)";'

# Run SonarQube scanner in Docker (CI mode / external SonarQube)
sonarqube-scan-ci:
	@echo "📊 SONARQUBE: Running SonarQube Scanner (CI mode)..."
	@bash -c 'set -euo pipefail; \
		PROJECT_ROOT="$$(pwd)"; \
		if [ -f "$(CONTAINER_DIR)/.env.sonarqube" ]; then set -a; . "$(CONTAINER_DIR)/.env.sonarqube"; set +a; \
		elif [ -f ".env.sonarqube" ]; then set -a; . ".env.sonarqube"; set +a; fi; \
		if [ -z "$${SONAR_TOKEN:-}" ] || [ "$${SONAR_TOKEN:-}" = "your_token_here" ]; then \
			echo "❌ SONAR_TOKEN not set. Please set it as an environment variable or in containers/.env.sonarqube"; \
			exit 1; \
		fi; \
		if [ -z "$${SONAR_HOST_URL:-}" ]; then \
			echo "⚠️  SONAR_HOST_URL not set. Using default: http://localhost:9000"; \
			SONAR_HOST_URL="http://localhost:9000"; \
		fi; \
		SONAR_TOKEN="$$(printf "%s" "$$SONAR_TOKEN" | tr -d "\r")"; \
		SONAR_HOST_URL="$$(printf "%s" "$$SONAR_HOST_URL" | tr -d "\r")"; \
		mkdir -p reports; \
		docker run --rm \
			-v "$$PROJECT_ROOT:/usr/src" \
			-w /usr/src \
			sonarsource/sonar-scanner-cli:latest \
			sonar-scanner \
			-Dsonar.host.url="$$SONAR_HOST_URL" \
			-Dsonar.token="$$SONAR_TOKEN" \
			-Dsonar.projectBaseDir=/usr/src; \
		echo "✅ SonarQube analysis completed (CI)"; \
		echo "📊 View results at: $$SONAR_HOST_URL";'

# Run complete SonarQube analysis
sonarqube-analyze: _sonarqube-setup-env sonarqube-start
	@echo "SONARQUBE: Running complete quality analysis..."
	@echo "ℹ️  SONAR_TOKEN is required for analysis"
	@echo "   Generate token at: http://localhost:9000/account/security"
	@if [ -n "$$SONAR_TOKEN" ]; then \
		echo "✅ SONAR_TOKEN is set in your environment"; \
	elif [ -f $(CONTAINER_DIR)/.env.sonarqube ] && grep -q "^SONAR_TOKEN=" $(CONTAINER_DIR)/.env.sonarqube && ! grep -q "^SONAR_TOKEN=your_token_here" $(CONTAINER_DIR)/.env.sonarqube; then \
		echo "✅ SONAR_TOKEN is configured in containers/.env.sonarqube"; \
	elif [ -f .env.sonarqube ] && grep -q "^SONAR_TOKEN=" .env.sonarqube && ! grep -q "^SONAR_TOKEN=your_token_here" .env.sonarqube; then \
		echo "✅ SONAR_TOKEN is configured in .env.sonarqube"; \
	else \
		echo "❌ SONAR_TOKEN is not configured. Please run: make sonarqube-setup-token"; \
		echo "   Current token status:"; \
		if [ -f $(CONTAINER_DIR)/.env.sonarqube ]; then grep -n "SONAR_TOKEN" $(CONTAINER_DIR)/.env.sonarqube || true; else echo "   containers/.env.sonarqube not found"; fi; \
		if [ -f .env.sonarqube ]; then grep -n "SONAR_TOKEN" .env.sonarqube || true; else echo "   .env.sonarqube not found"; fi; \
		exit 1; \
	fi
	$(MAKE) -s phpstan-sonar
	$(MAKE) -s test-coverage
	$(MAKE) -s sonarqube-scan-local
	@echo "SUCCESS: SonarQube analysis completed!"

# View SonarQube dashboard
sonarqube-dashboard:
	@echo "📊 Opening SonarQube dashboard..."
	@bash -c 'if command -v cmd.exe >/dev/null 2>&1; then cmd.exe /c start http://localhost:9000 >/dev/null 2>&1 || true; elif command -v xdg-open >/dev/null 2>&1; then xdg-open http://localhost:9000 >/dev/null 2>&1 || true; elif command -v open >/dev/null 2>&1; then open http://localhost:9000 >/dev/null 2>&1 || true; else echo "Please open http://localhost:9000 in your browser"; fi'

# Clean SonarQube data (reset everything)
sonarqube-clean:
	@echo "SONARQUBE: Cleaning SonarQube data..."
	cd $(CONTAINER_DIR) && docker-compose -f docker-compose.sonarqube.yml down -v
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
	@echo "  make test filter='...'   - Run specific test by filter (e.g., filter='Auth')"
	@echo "  make test-coverage       - Run tests with coverage report"
	@echo "  make lint                - Run code linting (Pint)"
	@echo "  make lint-dirty          - Lint only changed files"
	@echo "  make analyze             - Run static analysis (PHPStan)"
	@echo ""
	@echo "🐳 CONTAINER MANAGEMENT:"
	@echo "  make docker-up           - Start containers"
	@echo "  make docker-down         - Stop containers"
	@echo "  make docker-restart      - Restart containers"
	@echo "  make docker-cleanup      - Clean up all containers and resources"
	@echo "  make status              - Check container status"
	@echo "  make health              - Check application health"
	@echo "  make logs                - View container logs"
	@echo "  make shell               - Access main container shell"
	@echo "  make test-shell          - Access test container shell"
	@echo ""
	@echo "🛠️  UTILITIES:"
	@echo "  make artisan ARGS='...' - Run artisan command (e.g., ARGS='migrate --seed')"
	@echo "  make schema-dump        - Dump database schema and prune migrations"
	@echo "  make check-ports         - Check port availability"
	@echo "  make check-ports-standalone - Check ports (standalone, non-blocking)"
	@echo ""
	@echo "🔍 SONARQUBE (OPTIONAL):"
	@echo "  make sonarqube-start     - Start SonarQube server"
	@echo "  make sonarqube-setup-token - Help configure SONAR_TOKEN"
	@echo "  make sonarqube-analyze   - Run code quality analysis"
	@echo "  make phpstan-sonar       - Generate PHPStan JSON report (reports/phpstan.json)"
	@echo "  make sonarqube-scan-local - Run SonarQube scanner only (local network mode)"
	@echo "  make sonarqube-scan-ci   - Run SonarQube scanner only (CI/external SonarQube)"
	@echo "  make sonarqube-dashboard - Open SonarQube dashboard"
	@echo "  make sonarqube-stop      - Stop SonarQube server"
	@echo "  make sonarqube-clean     - Clean SonarQube data"
	@echo ""
	@echo "📋 ACCESS POINTS:"
	@echo "  - Laravel API: http://localhost:8081"
	@echo "  - Health Check: http://localhost:8081/api/health"
	@echo "  - SonarQube: http://localhost:9000 (when started)"
	@echo "  - MySQL: localhost:3306"
	@echo "  - Redis: localhost:6379"

# Default target
.PHONY: help local-setup install-commit-tools setup-git-hooks commit validate-commit release
.PHONY: check-ports check-ports-standalone check-sonarqube-ports check-sonarqube-ports-standalone
.PHONY: docker-cleanup docker-setup-env docker-verify-env docker-up docker-down docker-restart
.PHONY: test test-coverage _test-container-running _test-setup _test-clear-cache
.PHONY: lint lint-dirty analyze artisan schema-dump shell test-shell logs status health
.PHONY: sonarqube-setup sonarqube-start sonarqube-stop sonarqube-setup-env sonarqube-setup-token
.PHONY: sonarqube-wait phpstan-sonar sonarqube-scan-local sonarqube-scan-ci
.PHONY: sonarqube-analyze sonarqube-dashboard sonarqube-clean _sonarqube-setup-env
.DEFAULT_GOAL := help
