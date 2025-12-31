---
applyTo: "**"
---

# ✅ GitHub Copilot Custom Instructions for Laravel 12 Blog API

> **Important:** These instructions work in conjunction with the comprehensive development guides in `.cursor/rules/`. Always refer to:
> - `.cursor/rules/development-guide-ai.mdc` for general Laravel development standards
> - `.cursor/rules/laravel-blog-api-rules.mdc` for project-specific patterns and requirements

These instructions guide Copilot to generate code that aligns with **Laravel 12**, modern **PHP 8.2+** features (targeting 8.4+), **SOLID principles**, and industry best practices to ensure high-quality, maintainable, secure, and scalable applications.

## ✅ Project Overview

This is a modern **Laravel 12 Blog API** built with **PHP 8.2+** (targeting 8.4+ features), featuring a clean architecture, comprehensive testing, Docker-based development environment, and advanced code quality tools. The API serves as a production-ready backend for a blog platform with authentication, role-based permissions, content management, and automated quality assurance.

### Technology Stack
- **Laravel Framework**: 12.0+ (latest features)
- **PHP**: 8.2+ (with strict typing enabled, targeting 8.4+ features)
- **Database**: MySQL 8.0 (development) + MySQL 8.0 (testing - isolated environment)
- **Cache/Session**: Redis (development) + Redis (testing - isolated environment)
- **Authentication**: Laravel Sanctum (API tokens with abilities)
- **Testing**: Pest PHP 3.8+ (BDD-style testing framework)
- **Static Analysis**: Larastan 3.0+ (PHPStan level 10 for Laravel - **MANDATORY**)
- **Code Formatting**: Laravel Pint (PHP-CS-Fixer preset)
- **API Documentation**: Scramble (OpenAPI/Swagger automatic generation)
- **Containerization**: Docker & Docker Compose (multi-service architecture)
- **Quality Analysis**: SonarQube integration (optional)
- **Git Tools**: Husky hooks, semantic commits, automated validation

### Key Development Features
- **Semantic Commit Enforcement**: Automated commit message validation with Conventional Commits
- **Automated Testing Pipeline**: Isolated Docker test environment with 80% coverage requirement
- **Code Quality Gates**: Pre-commit linting, pre-push testing, PHPStan level 10 analysis
- **Multi-Environment Setup**: Separate containers for development, testing, and quality analysis
- **Make-based Workflow**: Standardized commands for all development operations
- **Git Hook Automation**: Automatic code quality checks and commit message formatting

### Key Packages & Tools
- `laravel/sanctum`: API authentication with token abilities
- `dedoc/scramble`: Automatic OpenAPI documentation generation
- `pestphp/pest`: BDD-style testing framework
- `larastan/larastan`: Static analysis (PHPStan for Laravel)
- `laravel/pint`: Code formatting and style enforcement
- `laravel/pail`: Real-time log monitoring
- **Husky**: Git hooks management
- **Commitizen**: Interactive semantic commit interface
- **SonarQube**: Comprehensive code quality analysis (optional)

---

## ✅ General PHP Coding Standards

> **Note:** See `.cursor/rules/development-guide-ai.mdc` for comprehensive PHP and Laravel standards. Key requirements:

- **ALWAYS** use `declare(strict_types=1);` at the top of all PHP files after the `<?php` starting tag.
- Follow **PSR-12** coding standards strictly.
- Use **descriptive, meaningful** names for variables, functions, classes, and files.
- Include **comprehensive PHPDoc** for classes, methods, and complex logic (required for PHPStan level 10).
- Prefer **typed properties**, **typed function parameters**, and **typed return types** (all mandatory).
- Break code into small, single-responsibility functions or classes.
- Avoid magic numbers and hard-coded strings; use **constants**, **config files**, or **Enums**.
- Use strict type declarations throughout.
- **PHPStan level 10 compliance** is mandatory - all properties, parameters, and return types must be explicitly declared.

---

## ✅ PHP 8.2+ Best Practices (Targeting 8.4+)

> **Note:** See `.cursor/rules/laravel-blog-api-rules.mdc` for detailed PHP 8.4 examples and patterns.

- Use **readonly properties** to enforce immutability where appropriate.
- Leverage **Enums** for clear, type-safe constants (all status/type fields use Enums).
- Use **First-class callable syntax** for cleaner callbacks.
- Utilize **Constructor Property Promotion**.
- Use **Union Types**, **Intersection Types**, **true/false return types**, and **Static Return Types**.
- Apply the **Nullsafe Operator (?->)** for safe method/property access.
- Use **Named Arguments** for clarity when calling functions with multiple parameters.
- Prefer **final classes** for utility or domain-specific classes that shouldn't be extended (project standard).
- Adopt **new `Override` attribute** (PHP 8.4) to explicitly mark overridden methods.
- Use **dynamic class constants in Enums** where version-specific behavior is needed.

---

## ✅ Laravel 12 Project Structure & Conventions

> **Note:** See `.cursor/rules/laravel-blog-api-rules.mdc` for the complete project structure and domain model details.

Maintain a clean, modular structure to enhance readability and testability:

```
app/
├── Actions/              # Single-responsibility action classes (create as needed)
├── Console/              # Artisan commands (create as needed)
├── Data/                 # Data Transfer Objects (DTOs) (create as needed)
├── Enums/                # Enums for type-safe constants ✅
├── Events/               # Domain events (create as needed)
├── Exceptions/           # Custom exceptions (create as needed)
├── Http/
│   ├── Controllers/      # Thin controllers ✅
│   ├── Middleware/       # HTTP middleware ✅
│   ├── Requests/         # Form Request validation ✅
│   ├── Resources/        # API Resource responses ✅
├── Jobs/                 # Queued jobs (create as needed)
├── Listeners/            # Event listeners (create as needed)
├── Models/               # Eloquent models ✅
├── Policies/             # Authorization policies ✅
├── Providers/            # Service providers ✅
├── Services/             # Business logic ✅
├── Support/              # Helpers & utility classes (create as needed)
└── Rules/                # Custom validation rules (create as needed)
```

### Domain Models
The application follows a blog-centric domain model with the following entities:

#### Core Entities
- **User**: Blog users with role-based permissions
- **Article**: Blog posts with status management
- **Category**: Hierarchical content organization
- **Tag**: Flexible content labeling
- **Comment**: User interactions on articles

#### Supporting Entities
- **Role**: User access levels (Administrator, Editor, Author, Contributor, Subscriber)
- **Permission**: Granular access control
- **Notification**: System-wide messaging
- **NewsletterSubscriber**: Email subscription management

### Enums (PHP 8.1+ Features)
All status and type fields use PHP enums for type safety:
- `UserRole`: User permission levels
- `ArticleStatus`: Article publication states
- `ArticleAuthorRole`: Multi-author support
- `NotificationType`: System notification types

### Service Layer Architecture
The application uses a service-oriented architecture:
- **AuthService**: Authentication and token management
- **Interfaces**: All services implement contracts for testability
- **Dependency Injection**: Constructor-based service injection

### API Structure
- **Versioned APIs**: `/api/v1/` prefix for version control
- **RESTful Design**: Standard HTTP methods and status codes
- **Resource-based**: JSON API responses with proper HTTP status codes
- **Authentication**: Bearer token authentication via Sanctum

### Database Design
Key relationships:
- **Users ↔ Roles**: Many-to-many with pivot table
- **Articles ↔ Users**: Multiple user relationships (created_by, approved_by, updated_by)
- **Articles ↔ Categories**: Many-to-many relationship
- **Articles ↔ Tags**: Many-to-many relationship
- **Articles ↔ Comments**: One-to-many relationship
- **Notifications**: Polymorphic relationships for flexible messaging

**Controllers must:**

- Remain **thin**; business logic belongs in Services or Actions.
- Use **dependency injection** with readonly properties.
- Use **Form Requests** for validation.
- Return **typed responses**, e.g., `JsonResponse`.
- Use **Resource classes** for API responses.
- Be **final classes** for immutability.
- **Use invokable pattern** with `__invoke()` method (project-specific requirement).
- Follow the project-specific controller template in `.cursor/rules/laravel-blog-api-rules.mdc`.

**Business logic belongs in:**

- Services
- Actions
- Event Listeners or Jobs for async work

---

## ✅ Eloquent ORM & Database

> **Note:** See `.cursor/rules/development-guide-ai.mdc` for comprehensive database standards.

- Use `$fillable` or `$guarded` to control mass assignment.
- Utilize **casts** for dates, booleans, JSON, etc.
- Apply **accessors & mutators** for data transformation.
- Prefer Eloquent or Query Builder over raw SQL.
- Always use migrations for schema changes with proper constraints.
- **Eager load relationships** (`with()`, `load()`) - mandatory to prevent N+1 queries.
- Use `select()` to limit columns.
- Use `chunk()` or `cursor()` for large datasets.
- **Transactions** are mandatory for multi-step writes.

---

## ✅ API Development

> **Note:** See `.cursor/rules/laravel-blog-api-rules.mdc` for project-specific API patterns and response formats.

- Use API Resource classes for structured JSON responses.
- Use **route model binding**.
- Use Form Requests for input validation (must implement `withDefaults()` method - project requirement).
- Follow correct HTTP status codes (200, 201, 204, 400, 422, 500, etc.).
- Version APIs (e.g., `/api/v1/users` - all routes must be prefixed with `/api/v1/`).

### Project-Specific API Guidelines
- **Authentication**: Use Laravel Sanctum with Bearer tokens
- **Authorization**: Implement ability-based token permissions
- **Documentation**: Use Scramble attributes (`#[Group]`) for API organization
- **Validation**: Use dedicated Form Request classes with `withDefaults()` method
- **Response Format**: All responses must follow this structure:
  ```json
  {
    "status": true,
    "message": "Success message",
    "data": { /* response data */ }
  }
  ```
- **Error Format**: 
  ```json
  {
    "status": false,
    "message": "Error message",
    "data": null,
    "error": null
  }
  ```
- **Error Handling**: Use try-catch in controllers with proper logging (see project-specific error handling pattern)

---

## ✅ Security Best Practices

> **Note:** See `.cursor/rules/development-guide-ai.mdc` for comprehensive security guidelines.

- Never trust user input; validate and sanitize all inputs.
- Use Eloquent or Query Builder to prevent SQL injection.
- Use CSRF, XSS, and validation protections.
- Store secrets in `.env`, never hard-coded.
- Enforce authorization via Policies or Gates.
- Apply least-privilege principles.
- Always use `$fillable` or `$guarded` for mass assignment protection.
- Never use `$request->all()` without filtering - use validated data only.

### Project-Specific Security Guidelines
- **Authentication**: Use Laravel Sanctum with secure token management
- **Authorization**: Implement role-based access control with permissions
- **Token Management**: Use ability-based tokens for fine-grained access
- **Input Validation**: Use Form Request classes for comprehensive validation
- **Password Security**: Use bcrypt with configurable rounds
- **API Security**: Implement rate limiting and proper CORS configuration

---

## ✅ Testing Standards

> **Note:** See `.cursor/rules/laravel-blog-api-rules.mdc` for project-specific test templates and patterns.

- Prefer **Pest PHP** for concise, readable tests (BDD-style with describe/it blocks).
- Use **factories** for test data.
- Include **feature tests** and **unit tests**.
- Mock external services with `Http::fake()`.
- **Minimum 80% test coverage** is enforced (project requirement).
- Use AAA pattern (Arrange, Act, Assert).

### Project-Specific Testing Guidelines
- **Test Organization**: Feature tests in `/tests/Feature/`, Unit tests in `/tests/Unit/`
- **Database Testing**: Use `RefreshDatabase` trait for clean state
- **BDD Style**: Use describe/it blocks for organization
- **Factory Usage**: Leverage model factories for test data generation
- **API Testing**: Test endpoints with proper authentication and authorization
- **Test Naming**: Use descriptive test names that describe behavior
- **Response Assertions**: Always assert the project-specific response format (`status`, `message`, `data` fields)

---

## ✅ Software Quality & Maintainability

> **Note:** See `.cursor/rules/development-guide-ai.mdc` for comprehensive quality standards and pattern decision matrix.

- Follow **SOLID**, **DRY**, **KISS**, and **YAGNI** principles.
- Document complex logic with **comprehensive PHPDoc** and inline comments.
- Default to **immutability**, **dependency injection**, and **encapsulation**.
- Use the **pattern decision matrix** to determine when to use Actions, Services, Repositories, and DTOs.
- **PHPStan level 10 compliance** is mandatory - no ignored errors.
- **Laravel Pint** for code formatting (mandatory).
- **Final classes** for immutability (project standard).

---

## ✅ Performance & Optimization

> **Note:** See `.cursor/rules/development-guide-ai.mdc` for comprehensive performance guidelines.

- **Eager load relationships** to prevent N+1 queries (mandatory).
- Use caching for frequently accessed data.
- Paginate large datasets with `paginate()`.
- Queue long-running tasks.
- Optimize database indexes.
- Use `select()` to limit columns.
- Use `chunk()` or `cursor()` for large datasets.

### Project-Specific Performance Guidelines
- **Caching Strategy**: Use Redis for sessions and application cache (project uses Redis)
- **Database Optimization**: Implement proper indexing and foreign key constraints
- **Query Optimization**: Use Eloquent relationships and eager loading
- **Background Processing**: Use supervisor-managed queue workers
- **API Response**: Use API Resource classes for optimized JSON responses

---

## ✅ Modern Laravel Features to Use

- Job batching for complex queue flows.
- Rate Limiting for APIs.

### Project-Specific Modern Features
- **Laravel Sanctum**: For API authentication with token abilities
- **Scramble**: For automatic OpenAPI documentation generation
- **Laravel Pail**: For real-time log monitoring
- **Pest PHP**: For BDD-style testing with describe/it syntax
- **Larastan**: For static analysis with maximum strictness (level 10)

---

## ✅ Copilot Behavior Preferences

### Code Generation Standards
- Generate modern, **strictly typed** PHP code with `declare(strict_types=1);`
- Favor readable, maintainable code over cleverness
- Avoid legacy Laravel patterns (e.g., facade overuse, logic-heavy views)
- Suggest proper class placement within the established project structure
- Suggest comprehensive tests alongside features using Pest PHP

### Command Execution Preferences
- **Always use Makefile commands** instead of direct Docker or Composer commands
- **Prefer standardized workflows**: Use `make local-setup` for initial setup
- **Use proper development commands**: `make test`, `make lint`, `make analyze` for quality checks
- **Leverage semantic commits**: Suggest `make commit` for guided commit process
- **Consider Docker environment**: All commands should work within the containerized setup

### Development Workflow Integration
- **Understand the Make-based workflow** and suggest appropriate `make` commands
- **Recommend quality gates**: Always mention running tests and linting before commits
- **Consider the automated pipeline**: Suggest commands that work with Git hooks
- **Respect environment separation**: Understand development vs testing container differences
- **Support troubleshooting**: Use `make help`, `make status`, `make health` for guidance

### Project-Specific Command Suggestions
When suggesting development tasks, always use the established Makefile commands:

#### For Setup & Environment
```bash
make local-setup          # Complete development environment setup
make sonarqube-setup     # Optional quality analysis setup
make docker-up           # Start development containers
make status              # Check environment status
```

#### For Development Workflow
```bash
make commit              # Interactive semantic commits
make test                # Run test suite
make lint                # Code formatting
make analyze             # Static analysis
make artisan ARGS="..."  # Laravel commands
```

#### For Troubleshooting
```bash
make help                # Show all available commands
make health              # Check application health
make logs                # View container logs
make shell               # Access container shell
```

### Project-Specific Development Guidelines

#### Code Quality Standards
- **Always include** `declare(strict_types=1);` in all PHP files
- **Use Scramble attributes** for API documentation (`#[Group]`, `#[ResponseField]`, etc.)
- **Implement service layer pattern** with interfaces for testability
- **Use specific exception types** with proper HTTP response codes (see project-specific error handling pattern)
- **Write comprehensive tests** using Pest PHP with BDD-style describe/it blocks
- **Ensure PHPStan level 10** compliance for maximum static analysis strictness (MANDATORY)
- **Follow project-specific templates** from `.cursor/rules/laravel-blog-api-rules.mdc` for Controllers, Requests, Services, Models, and Resources
- **Use invokable controllers** with `__invoke()` method (project requirement)
- **Implement `withDefaults()` method** in all Form Request classes (project requirement)

#### Development Environment Integration
- **Always use Docker commands** through Makefile for consistency
- **Prefer `make` commands** over direct Docker Compose for standardized workflow
- **Use automated testing environment** with `make test` for isolated, repeatable tests
- **Leverage Git hooks** for automated quality checks on commits and pushes
- **Use semantic commits** with `make commit` for standardized commit messages

#### Testing & Quality Assurance
- **Write Pest PHP tests** using describe/it blocks for better organization
- **Maintain 80%+ test coverage** as enforced by the test pipeline
- **Use separate test environment** with isolated database and Redis instances
- **Run comprehensive quality checks** with SonarQube integration when available
- **Validate code with multiple tools**: Pint (formatting), PHPStan (static analysis), Pest (testing)

#### API Development Standards
- **Follow RESTful conventions** with proper HTTP methods and status codes
- **Use Laravel Sanctum** for API authentication with ability-based tokens
- **Implement proper authorization** using Laravel Policies and Gates
- **Document APIs with Scramble** for automatic OpenAPI/Swagger generation
- **Use API Resource classes** for consistent JSON response formatting
- **Version APIs properly** with `/api/v1/` prefix structure

#### Database & Performance
- **Use Eloquent relationships** with proper type hints and eager loading
- **Implement proper database constraints** and foreign key relationships
- **Use Redis for caching** and session management in Docker environment
- **Apply database migrations** with proper rollback strategies
- **Use seeders for test data** in both development and testing environments

#### Project Structure Compliance
- **Follow the established directory structure** as defined in the project overview
- **Use Enums for type safety** (UserRole, ArticleStatus, NotificationType, etc.)
- **Implement service layer architecture** with dependency injection
- **Use Form Request classes** for input validation
- **Apply single responsibility principle** in Controllers, Services, and Actions

#### Troubleshooting & Maintenance
- **Use `make help`** to view all available commands with descriptions
- **Check container health** with `make status` and `make health` commands
- **Access logs** via `make logs` for debugging container issues
- **Use shell access** with `make shell` or `make test-shell` for direct container interaction
- **Clean up resources** with `make docker-cleanup` for complete environment reset

### Project Setup & Development Commands

#### Main Setup Commands (One-time setup)
```bash
# Complete local development environment setup
make local-setup          # Sets up Docker containers, dependencies, Git hooks, and semantic commit tools

# Optional: Add SonarQube code quality analysis
make sonarqube-setup      # After local-setup, adds comprehensive code quality analysis
```

#### Daily Development Workflow
```bash
# Semantic commits with validation
make commit               # Interactive semantic commit (guided process)
make validate-commit      # Validate commit message format

# Testing
make test                 # Run complete test suite (automated Docker environment)
make test-coverage        # Run tests with coverage report (minimum 80% required)

# Code quality
make lint                 # Run Laravel Pint code formatting
make lint-dirty           # Lint only changed files (faster)
make analyze              # Run PHPStan static analysis (level 10)

# Development tools
make artisan ARGS="..."   # Run any artisan command (e.g., make artisan ARGS="migrate --seed")
```

#### Container Management
```bash
# Environment control
make docker-up            # Start development containers
make docker-down          # Stop development containers
make docker-restart       # Restart development environment
make docker-cleanup       # Complete cleanup (containers, images, volumes)

# Utilities
make status               # Check container status and access points
make health               # Check application health and readiness
make logs                 # View container logs
make shell                # Access main container shell
make test-shell           # Access test container shell
```

#### SonarQube Quality Analysis (Optional)
```bash
# SonarQube setup and management
make sonarqube-start      # Start SonarQube server
make sonarqube-setup-token # Setup authentication token
make sonarqube-analyze    # Run comprehensive code quality analysis
make sonarqube-dashboard  # Open SonarQube dashboard
make sonarqube-stop       # Stop SonarQube server
```

### Environment Configuration & Access Points

#### Development Environment
- **Main API**: http://localhost:8081
- **Health Check**: http://localhost:8081/api/health
- **API Documentation**: http://localhost:8081/docs/api
- **MySQL Development**: localhost:3306 (laravel_user/laravel_password)
- **Redis**: localhost:6379

#### Testing Environment (Automated)
- **MySQL Test**: localhost:3307 (separate test database)
- **Redis Test**: localhost:6380 (separate test cache)
- **Coverage Reports**: reports/coverage/index.html
- **Coverage XML**: reports/coverage.xml

#### Optional Quality Analysis
- **SonarQube Dashboard**: http://localhost:9000 (admin/admin)

### Container Architecture
The project uses Docker Compose with multiple services:
- **laravel_blog_api**: Main application (Nginx + PHP-FPM) on port 8081
- **laravel_blog_api_mysql**: Development database on port 3306
- **laravel_blog_api_redis**: Cache and session store on port 6379
- **laravel_blog_api_queue**: Background job processor
- **Test Environment**: Separate containers for isolated testing
- **SonarQube**: Optional code quality analysis server

### Development Workflow Best Practices

#### Semantic Commits
The project enforces semantic commit messages following Conventional Commits:
- Use `make commit` for interactive guided commits
- Automated validation with Git hooks
- Supports: feat, fix, docs, style, refactor, test, chore, perf, ci, build, revert
- Examples: `feat(auth): add user registration endpoint`, `fix(api): resolve token validation`

#### Git Hooks & Automation
- **pre-commit**: Runs linting on changed files
- **pre-push**: Runs tests with PHPStan analysis
- **prepare-commit-msg**: Formats commit messages
- **Husky integration**: Node.js-based commit tools

#### Quality Gates
- **Minimum 80% test coverage** enforced
- **PHPStan level 10** static analysis
- **Laravel Pint** code formatting
- **Pest PHP** BDD-style testing
- **SonarQube integration** for comprehensive analysis
