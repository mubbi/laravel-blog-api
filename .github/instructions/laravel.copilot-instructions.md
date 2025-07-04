---
applyTo: "**"
---

# ✅ GitHub Copilot Custom Instructions for Laravel 12 & PHP 8.4

These instructions guide Copilot to generate code that aligns with **Laravel 12**, modern **PHP 8.4** features, **SOLID principles**, and industry best practices to ensure high-quality, maintainable, secure, and scalable applications.

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

---

## ✅ Security Best Practices

- Never trust user input; validate and sanitize all inputs.
- Use Eloquent or Query Builder to prevent SQL injection.
- Use CSRF, XSS, and validation protections.
- Store secrets in `.env`, never hard-coded.
- Enforce authorization via Policies or Gates.
- Apply least-privilege principles.

---

## ✅ Testing Standards

- Prefer **Pest PHP** for concise, readable tests.
- Use **factories** for test data.
- Include **feature tests** and **unit tests**.
- Mock external services with `Http::fake()`.
- Focus on meaningful tests, not 100% coverage obsession.

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

---

## ✅ Modern Laravel Features to Use

- Job batching for complex queue flows.
- Rate Limiting for APIs.

---

## ✅ Copilot Behavior Preferences

- Generate modern, **strictly typed** PHP code.
- Favor readable, maintainable code over cleverness.
- Avoid legacy Laravel patterns (e.g., facade overuse, logic-heavy views).
- Suggest proper class placement.
- Suggest tests alongside features.
