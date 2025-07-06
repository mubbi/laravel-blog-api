# Setup Local Automation by Git Hooks
setup-git-hooks:
	cp -r .githooks/ .git/hooks/
	chmod +x .git/hooks/pre-commit && chmod +x .git/hooks/pre-push && chmod +x .git/hooks/prepare-commit-msg

# Setup Local project
setup-localhost:
	cp .env.example .env
	php artisan key:generate
	composer install
	php artisan migrate --seed

# Setup PHP Unit Tests
setup-testing:
	cp .env.testing.example .env.testing
	php artisan --env=testing migrate:fresh --seed

# Run PHP Unit Tests (local MySQL)
php-tests:
	php artisan test --env=testing

# Run PHP Unit Tests & profile
php-tests-profile:
	php artisan test --profile

# Generate PHP Unit Tests Coverage Report
php-tests-report:
	php artisan test --parallel --coverage-html reports/coverage --coverage-clover reports/coverage.xml

# Lint recent changes
lint-changes:
	./vendor/bin/pint --dirty

# Lint full project
lint-project:
	./vendor/bin/pint

# Larastan Analyze Project
larastan-project:
	./vendor/bin/phpstan analyse --memory-limit=2G

# Docker Commands
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

# Cleanup only test containers
docker-cleanup-testing:
	@echo "CLEANUP: Docker test environment..."
	@echo "Stopping and removing test containers..."
	-cd containers && docker-compose -f docker-compose.test.yml down --remove-orphans
	@echo "SUCCESS: Docker test cleanup completed!"

# Cleanup only main containers
docker-cleanup-main:
	@echo "CLEANUP: Docker main environment..."
	@echo "Stopping and removing main containers..."
	-cd containers && docker-compose down --remove-orphans
	@echo "SUCCESS: Docker main cleanup completed!"

# Setup Docker environment files
docker-setup-env:
	bash containers/setup-env.sh

# Verify Docker environment setup
docker-verify-env:
	bash containers/verify-env-setup.sh

# Setup Docker environment for local development (without affecting test containers) - AUTOMATED
docker-setup-local: docker-cleanup-main docker-setup-env
	@echo "SETUP: Automated local Docker environment setup..."
	cd containers && docker-compose up -d
	@echo ">> Waiting for containers to be ready..."
	@echo ">> Main app will automatically:"
	@echo "   - Wait for database connection"
	@echo "   - Set up environment file"
	@echo "   - Install composer dependencies"
	@echo "   - Run migrations and seeders"
	@echo "   - Start web services"
	@echo ""
	@echo ">> Queue worker will automatically:"
	@echo "   - Wait for main app to be completely ready"
	@echo "   - Start processing queued jobs"
	@echo ""
	@echo ">> This may take a few minutes for initial setup..."
	@echo ">> Use 'make docker-status' to check progress"
	@echo ">> Use 'make docker-logs' to view detailed logs"

# Build and start containers only (for debugging)
docker-build-only: docker-cleanup-main docker-setup-env
	cd containers && docker-compose up -d
	@echo "INFO: Containers started in detached mode"

# Setup Docker environment for testing (without affecting main containers) - AUTOMATED
docker-setup-testing: docker-cleanup-testing docker-setup-env
	@echo "SETUP: Automated testing Docker environment setup..."
	cd containers && docker-compose -f docker-compose.test.yml up -d
	@echo ">> Installing dependencies in test container..."
	@sleep 10
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test composer install --no-interaction --prefer-dist --optimize-autoloader
	@echo ">> Setting up test environment file..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test cp .env.testing.docker .env.testing
	@echo ">> Generating test application key..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan key:generate --env=testing --force
	@echo ">> Running test migrations and seeders..."
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan migrate:fresh --seed --env=testing --force
	@echo ""
	@echo "SUCCESS: Testing environment setup complete!"
	@echo ">> Use 'make docker-tests' to run tests"

# Start Docker local environment
docker-up:
	cd containers && docker-compose up -d

# Stop Docker local environment
docker-down:
	cd containers && docker-compose down

# Start Docker test environment
docker-test-up:
	cd containers && docker-compose -f docker-compose.test.yml up -d

# Stop Docker test environment
docker-test-down:
	cd containers && docker-compose -f docker-compose.test.yml down

# Run tests in Docker
docker-tests:
	cd containers && docker-compose -f docker-compose.test.yml up -d
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan test --parallel --recreate-databases
	cd containers && docker-compose -f docker-compose.test.yml down

# Run tests with coverage in Docker
docker-tests-coverage:
	cd containers && docker-compose -f docker-compose.test.yml up -d
	docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan test --parallel --recreate-databases --coverage-html reports/coverage
	cd containers && docker-compose -f docker-compose.test.yml down

# Bash into main container
docker-bash:
	docker-compose -f containers/docker-compose.yml exec laravel_blog_api bash

# Bash into test container
docker-test-bash:
	docker-compose -f containers/docker-compose.test.yml exec laravel_blog_api_test bash

# View logs
docker-logs:
	cd containers && docker-compose logs -f

# Check container status
docker-status:
	cd containers && docker-compose ps
	@echo ""
	@echo ">> Access your application:"
	@echo "  - Laravel API: http://localhost:8081"
	@echo "  - MySQL: localhost:3306 (user: laravel_user, password: laravel_password)"
	@echo "  - Redis: localhost:6379"

# Check queue worker status
docker-queue-status:
	@echo "QUEUE STATUS: Worker Status:"
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api_queue ps aux | grep "queue:work" || echo "Queue worker not running"
	@echo ""
	@echo "QUEUE STATUS: Job Status:"
	docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api php artisan queue:work --stop-when-empty --max-jobs=0 2>/dev/null || echo "Cannot check queue status - application may not be ready"

# Check application readiness
docker-check-ready:
	@echo "CHECK: Application readiness..."
	@echo ""
	@echo "INFO: Main App Ready Marker:"
	@if docker-compose -f containers/docker-compose.yml exec -T laravel_blog_api test -f /tmp/laravel_ready; then \
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

# Open application in browser
docker-open:
	@echo "BROWSER: Opening Laravel API at http://localhost:8081/api/health"

# Rebuild Docker images
docker-rebuild:
	cd containers && docker-compose down
	cd containers && docker-compose build --no-cache
	cd containers && docker-compose up -d

# Comprehensive Docker Setup - Sets up both main and testing environments
docker-setup-complete: docker-cleanup docker-setup-env
	@echo "SETUP: Complete Docker environment (main + testing)..."
	@echo ""
	@echo "STEP 1: Setting up main environment..."
	cd containers && docker-compose up -d
	@echo ""
	@echo "STEP 2: Waiting for main environment to be ready..."
	@echo ">> This may take a few minutes for initial setup..."
	@echo ">> Use 'make docker-status' to check progress"
	@echo ">> Use 'make docker-logs' to view detailed logs"
	@echo ""
	@echo "STEP 3: Setting up testing environment..."
	cd containers && docker-compose -f docker-compose.test.yml up -d
	@echo ">> Installing dependencies in test container..."
	@sleep 10
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test composer install --no-interaction --prefer-dist --optimize-autoloader
	@echo ">> Setting up test environment file..."
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test cp .env.testing.docker .env.testing
	@echo ">> Generating test application key..."
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan key:generate --env=testing --force
	@echo ">> Running test migrations and seeders..."
	-docker-compose -f containers/docker-compose.test.yml exec -T laravel_blog_api_test php artisan migrate:fresh --seed --env=testing --force
	@echo ""
	@echo "SUCCESS: Complete Docker environment setup finished!"
	@echo ""
	@echo "SUMMARY:"
	@echo ">> Main environment: http://localhost:8081"
	@echo ">> Test environment: Ready for testing"
	@echo ">> MySQL Main: localhost:3306"
	@echo ">> MySQL Test: localhost:3307"
	@echo ">> Redis: localhost:6379"
	@echo ""
	@echo "NEXT STEPS:"
	@echo ">> Run 'make docker-status' to check all containers"
	@echo ">> Run 'make docker-tests' to run tests"
	@echo ">> Access API at http://localhost:8081/api/health"
