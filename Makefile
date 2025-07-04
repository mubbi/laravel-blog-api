# Setup Local Automation by Git Hooks
setup-git-hooks:
	cp -r .githooks/* .git/hooks/
	chmod +x .git/hooks/pre-commit && chmod +x .git/hooks/pre-push && chmod +x .git/hooks/prepare-commit-msg

# Setup Local project
setup-localhost:
	cp .env.example .env
	composer install
	pnpm i
	php artisan migrate:fresh --seed

# Setup PHP Unit Tests
setup-testing:
	cp .env.testing.example .env.testing
	php artisan --env=testing migrate:fresh --seed

# Run PHP Unit Tests
php-tests:
	php artisan test --parallel --recreate-databases

# Run PHP Unit Tests & profile
php-tests-profile:
	php artisan test --profile

# Generate PHP Unit Tests Coverage Report
php-tests-report:
	php artisan test --parallel --recreate-databases --coverage-html reports/coverage --coverage-clover reports/coverage.xml

# Lint recent changes
lint-changes:
	./vendor/bin/pint --dirty

# Lint full project
lint-project:
	./vendor/bin/pint
