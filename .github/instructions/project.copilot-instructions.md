---
applyTo: "**"
---

# ✅ GitHub Copilot Custom Instructions for Laravel 12 & PHP 8.4

These instructions guide Copilot to generate code that aligns with **Laravel 12**, modern **PHP 8.4** features, **SOLID principles**, and industry best practices to ensure high-quality, maintainable, secure, and scalable applications.

## ✅ Project Overview

This is a modern Laravel 12 Blog API built with PHP 8.4, featuring a clean architecture, comprehensive testing, and Docker-based development environment. The API serves as the backend for a blog platform with authentication, role-based permissions, and content management capabilities.

### Technology Stack
- **Laravel Framework**: 12.0+ (latest)
- **PHP**: 8.4+ (with strict typing enabled)
- **Database**: MySQL 8.0
- **Cache/Session**: Redis
- **Authentication**: Laravel Sanctum (API tokens)
- **Testing**: Pest PHP 3.8+ (BDD-style testing framework)
- **Static Analysis**: Larastan 3.0+ (PHPStan for Laravel)
- **Code Formatting**: Laravel Pint (PHP-CS-Fixer preset)
- **API Documentation**: Scramble (OpenAPI/Swagger documentation)
- **Containerization**: Docker & Docker Compose

### Key Packages
- `laravel/sanctum`: API authentication
- `dedoc/scramble`: OpenAPI documentation generation
- `pestphp/pest`: Testing framework
- `larastan/larastan`: Static analysis
- `laravel/pint`: Code formatting
- `laravel/pail`: Real-time log monitoring

---

## ✅ General PHP Coding Standards

- Always use `declare(strict_types=1);` at the top of all PHP files after the `<?php` starting tag.
- Follow **PSR-12** coding standards.
- Prioritize clear, readable, and expressive code.
- Use **descriptive, meaningful** names for variables, functions, classes, and files.
- Include PHPDoc for classes, methods, and complex logic.
- Prefer **typed properties**, **typed function parameters**, and **typed return types**.
- Break code into small, single-responsibility functions or classes.
- Avoid magic numbers and hard-coded strings; use **constants**, **config files**, or **Enums**.
- Use strict type declarations throughout.

---

## ✅ PHP 8.4 Best Practices

- Use **readonly properties** to enforce immutability where appropriate.
- Leverage **Enums** for clear, type-safe constants.
- Use **First-class callable syntax** for cleaner callbacks.
- Utilize **Constructor Property Promotion**.
- Use **Union Types**, **Intersection Types**, **true/false return types**, and **Static Return Types**.
- Apply the **Nullsafe Operator (?->)** for safe method/property access.
- Use **Named Arguments** for clarity when calling functions with multiple parameters.
- Prefer **final classes** for utility or domain-specific classes that shouldn't be extended.
- Adopt **new `Override` attribute** (PHP 8.4) to explicitly mark overridden methods.
- Use **dynamic class constants in Enums** where version-specific behavior is needed.

---

## ✅ Laravel 12 Project Structure & Conventions

Maintain a clean, modular structure to enhance readability and testability:

```
app/
├── Actions/              # Single-responsibility action classes
├── Console/              # Artisan commands
├── Data/                 # Data Transfer Objects (DTOs)
├── Enums/                # Enums for type-safe constants
├── Events/               # Domain events
├── Exceptions/           # Custom exceptions
├── Http/
│   ├── Controllers/      # Thin controllers
│   ├── Middleware/       # HTTP middleware
│   ├── Requests/         # Form Request validation
│   ├── Resources/        # API Resource responses
├── Jobs/                 # Queued jobs
├── Listeners/            # Event listeners
├── Models/               # Eloquent models
├── Policies/             # Authorization policies
├── Providers/            # Service providers
├── Services/             # Business logic
├── Support/              # Helpers & utility classes
└── Rules/                # Custom validation rules
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

- Remain thin; business logic belongs in Services or Actions.
- Use **dependency injection**.
- Use **Form Requests** for validation.
- Return typed responses, e.g., `JsonResponse`.
- Use Resource classes for API responses.

**Business logic belongs in:**

- Services
- Actions
- Event Listeners or Jobs for async work

---

## ✅ Eloquent ORM & Database

- Use `$fillable` or `$guarded` to control mass assignment.
- Utilize **casts** for dates, booleans, JSON, etc.
- Apply **accessors & mutators** for data transformation.
- Prefer Eloquent or Query Builder over raw SQL.
- Always use migrations for schema changes with proper constraints.
- Prefer **UUIDs** or **ULIDs** as primary keys for scalability.

---

## ✅ API Development

- Use API Resource classes for structured JSON responses.
- Use **route model binding**.
- Use Form Requests for input validation.
- Follow correct HTTP status codes (200, 201, 204, 400, 422, 500, etc.).
- Version APIs (e.g., `/api/v1/users`).

### Project-Specific API Guidelines
- **Authentication**: Use Laravel Sanctum with Bearer tokens
- **Authorization**: Implement ability-based token permissions
- **Documentation**: Use Scramble attributes (`#[Group]`) for API organization
- **Validation**: Use dedicated Form Request classes
- **Response Format**: Use API Resource classes for consistent JSON responses
- **Error Handling**: Return proper HTTP status codes with meaningful error messages

---

## ✅ Security Best Practices

- Never trust user input; validate and sanitize all inputs.
- Use Eloquent or Query Builder to prevent SQL injection.
- Use CSRF, XSS, and validation protections.
- Store secrets in `.env`, never hard-coded.
- Enforce authorization via Policies or Gates.
- Apply least-privilege principles.

### Project-Specific Security Guidelines
- **Authentication**: Use Laravel Sanctum with secure token management
- **Authorization**: Implement role-based access control with permissions
- **Token Management**: Use ability-based tokens for fine-grained access
- **Input Validation**: Use Form Request classes for comprehensive validation
- **Password Security**: Use bcrypt with configurable rounds
- **API Security**: Implement rate limiting and proper CORS configuration

---

## ✅ Testing Standards

- Prefer **Pest PHP** for concise, readable tests.
- Use **factories** for test data.
- Include **feature tests** and **unit tests**.
- Mock external services with `Http::fake()`.
- Focus on meaningful tests, not 100% coverage obsession.

### Project-Specific Testing Guidelines
- **Test Organization**: Feature tests in `/tests/Feature/`, Unit tests in `/tests/Unit/`
- **Database Testing**: Use `RefreshDatabase` trait for clean state
- **BDD Style**: Use describe/it blocks for organization
- **Factory Usage**: Leverage model factories for test data generation
- **API Testing**: Test endpoints with proper authentication and authorization
- **Test Naming**: Use descriptive test names that describe behavior

---

## ✅ Software Quality & Maintainability

- Follow **SOLID**, **DRY**, **KISS**, and **YAGNI** principles.
- Document complex logic with PHPDoc and inline comments.
- Default to **immutability**, **dependency injection**, and **encapsulation**.

---

## ✅ Performance & Optimization

- Eager load relationships to prevent N+1 queries.
- Use caching for frequently accessed data.
- Paginate large datasets with `paginate()`.
- Queue long-running tasks.
- Optimize database indexes.

### Project-Specific Performance Guidelines
- **Caching Strategy**: Use Redis for sessions and application cache
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

- Generate modern, **strictly typed** PHP code.
- Favor readable, maintainable code over cleverness.
- Avoid legacy Laravel patterns (e.g., facade overuse, logic-heavy views).
- Suggest proper class placement.
- Suggest tests alongside features.

### Project-Specific Preferences
- **Code Quality**: Always include `declare(strict_types=1);` in all PHP files
- **Documentation**: Use Scramble attributes for API documentation
- **Architecture**: Implement service layer pattern with interfaces
- **Error Handling**: Use specific exception types with proper HTTP responses
- **Database**: Use proper Eloquent relationships with type hints
- **Development**: Consider Docker environment when suggesting commands or configurations
- **Testing**: Write Pest PHP tests using BDD-style describe/it blocks
- **Static Analysis**: Ensure code passes Larastan level 10 analysis

### Common Development Commands
```bash
# Docker environment
make docker-setup-all     # Complete setup
make docker-dev          # Start development
make docker-test         # Run all tests
make docker-lint         # Run code formatting
make docker-analyze      # Run static analysis

# Database operations
make docker-artisan ARGS="migrate"
make docker-artisan ARGS="migrate:fresh --seed"
```

### Environment Configuration
- **Development**: Docker-based with MySQL 8.0, Redis, and PHP 8.4
- **API Base URL**: http://localhost:8081
- **API Documentation**: http://localhost:8081/docs/api
- **Testing**: Separate Docker environment with clean database state
