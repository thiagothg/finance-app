# Contributing to Family Finance App

First off, thank you for considering contributing to the Family Finance App! We welcome contributions to help improve this personal finance management system.

The following is a set of guidelines for contributing to the project. These are mostly guidelines, not rules. Use your best judgment, and feel free to propose changes to this document in a pull request.

---

## 1. Getting Started

This project uses FrankenPHP running inside Docker. To get your local environment set up:

```bash
# Build and start all services
docker compose up --build -d

# View logs
docker compose logs app -f

# Run tests
docker compose exec -t app php artisan test
```

Please refer to the `README.md` for a full list of accessible ports and services.

---

## 2. Development Workflow

### Branching
- Always create a new branch for your feature or bug fix.
- Branch names should be descriptive (e.g., `feature/add-accounts-api`, `fix/transaction-calculation`).

### Commits
- We strictly follow [Conventional Commits](https://www.conventionalcommits.org/).
  - Examples: `feat: add start workout session endpoint`, `fix: workout plan validation`, `docs: update architecture rules`.
- **Never** make a commit without explicit permission from the project owner. Always wait for approval.

---

## 3. Code Architecture & Conventions

This project leverages Laravel Boost defaults (PHP 8.4, Laravel 12, Octane, Pest, Pint), extended with strict project-specific rules documented in `ARCHITECTURE.md`.

### Core Rules
- **Strict Typing:** Always use `declare(strict_types=1);` at the top of all PHP files.
- **Feature Folders:** Group your files by feature. For example, if you create a new feature, place its Services, Controllers, Requests, and Resources in a dedicated feature folder.
- **Comments:** Comment functions and complex logic to clearly explain their purpose.

### Controllers
- Must be `final` classes and read-only (no property mutations).
- Keep them **thin**. Avoid injecting dependencies via the constructor.
- Use **Method Injection** (e.g., for `FormRequest` validation).
- Always return a `JsonResource` or an `AnonymousResourceCollection` for API responses.

### Models & Enums
- **Models:** Should be `final` classes to prevent unexpected behavior from inheritance.
- **Enums:** Attributes like status, types, or roles MUST use Enums under `App\Enums`. Enum class names must include `Enum` (e.g., `RoleEnum`).

### Services (The Business Brain)
- Must be `final` and read-only.
- Coordinate business logic, permissions, and multi-step processes.
- Services should interact with Eloquent directly for the majority of cases or call Repositories for advanced queries.

### Repositories (The Data Drawer)
- Handle data access *only*.
- **Only** use Repositories when queries are extremely complex or multiple data sources (Cache + DB) must be abstracted.
- Do not create boilerplate repositories that just wrap simple Eloquent methods.

---

## 4. Database & Routing

### Migrations
- Always use **Foreign Keys**, **Indexes**, **Soft Deletes**, and **Timestamps**.
- **NEVER** use cascading deletes on foreign keys.

### Seeders
- Create a new seeder for each feature and register it in `DatabaseSeeder`.
- Run seeders via: `docker compose exec -t app php artisan db:seed --class=[ClassName]`.

### Routing
- Group related routes together by feature/model (e.g., `routes/accounts.php`).
- Names follow the convention: `->name('[resource].[action]')`.

---

## 5. Testing & Quality Assurance

- **Tests:** Always create tests for new features within the `tests` directory. Run them securely inside the container:
  ```bash
  docker compose exec -t app php artisan test
  ```
- **Static Analysis:** All implementations must pass PHPStan analysis.
- **Formatting:** Code formatting is handled by Laravel Pint. Ensure code complies before submitting.

---

## 6. Tooling (For AI Assistants & Contributors)

- **Context7 MCP:** Always use this to search for documentation and external package websites.
- **Serena MCP:** Use for semantic code retrieval and editing tools.

---

## 7. Submission Guidelines

1. Make sure your code adheres to all architectural rules and is cleanly formatted.
2. Ensure you have added necessary tests and that the test suite passes.
3. Push your branch and open a Pull Request. Provide a comprehensive summary of your changes.
4. Address any review comments from the maintainers.
