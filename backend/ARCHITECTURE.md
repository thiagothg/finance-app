# Project Architecture & Conventions

This project leverages Laravel Boost for core framework conventions (PHP 8.4, Laravel 12, Octane, Pest, Pint).

The following rules apply to this specific project and *extend* the Boost defaults:

## 1. Code Architecture & Organization

- **Feature Folders**: Always create a folder to organize files by feature. If you are creating a new feature, create a folder for it and put all related files (Services, Controllers, Requests, Resources, etc.) in that folder.
- **Strict Typing**: Always use `declare(strict_types=1);` in all PHP files.
- **Static Analysis**: Always ensure code passes PHPStan static analysis.
- **Error Handling**: Implement robust error handling and logging:
    - Use Laravel's exception handling and logging features.
    - Create custom exceptions when necessary.
    - Employ try-catch blocks for expected exceptions.
- **Validation**: Use Laravel's validation features for form and request data.
- **Middleware**: Implement middleware for request filtering and modification.
- **Eloquent ORM**: Utilize Laravel's Eloquent ORM for database interactions.
- **Query Builder**: Use Laravel's query builder for complex database operations.
- **Database Migrations**: Create and maintain proper database migrations and seeders.
- **Code Style**: Prefer readable and explicit code.
- **Controllers**: Avoid large controllers.
- **Feature Folders**: **Always** create folder for organize files, like the feature folder. For example, if you are creating a new feature, create a folder for it and put all the files related to that feature in that folder. Services, Controllers, Requests, Resources, etc.

## 2. Controllers

- **Final Classes**: Controllers should be `final` classes to prevent inheritance.
- **Read-Only**: Controllers must be read-only (no property mutations).
- **Thin Controllers**: Keep controllers thin. Avoid injecting dependencies directly into controllers. Instead, use method injection or service classes.
- **Method Injection**: Use Method Injection for `FormRequest` validation.
- **Return Types**: Always return `JsonResource` (or collections of them) for API responses.

## 3. Models

- **Final Classes**: Models should be `final` classes to ensure data integrity and prevent unexpected behavior from inheritance.

## 4. Services (The "Business Brain")

- **Location**: Create a `Services` folder within the `app` directory (or within the respective Feature folder).
- **Class Design**: Service classes should be `final` and read-only.
- **Responsibility**: Coordination and Business Logic.
- **Usage**: Use services for enforcing permissions, calculating totals, triggering events, and multi-step processes.
- **Data Access**: Services should call Repositories or Eloquent directly.

## 5. Repositories (The "Data Drawer")

- **Responsibility**: Data Access *only*.
- **When to Use**: ONLY use Repositories when:
  - Queries are extremely complex or long (cluttering the Service).
  - There is a need to abstract multiple data sources (e.g., Cache + DB).
- **Avoid Boilerplate**: Avoid "Boilerplate Repositories" that just wrap simple Eloquent methods. Prefer using Eloquent directly in the Service for 80% of cases.

## 6. Enums

- **Usage**: Attributes like status, types, or roles MUST use Enums from `App\Enums`.
- **Naming**: Always include `Enum` in the name of the enum class.

## 7. Routing

- **Group Routes**: Group related routes together by feature/model (e.g., all user-related routes in `routes/user.php`).
- **Naming Convention**: Follow the convention: `->name('[resource].[action]')`.

## 8. Database Migrations

- **Foreign Keys**: Always use foreign keys.
- **Cascading**: NEVER use cascade on foreign keys.
- **Indexes**: Always use indexes on migrations.
- **Soft Deletes**: Always use soft deletes on migrations.
- **Timestamps**: Always use timestamps on migrations.

## 8.1. Database Seeders

- **Always** use the `docker compose exec -t app php artisan db:seed --class=[ClassName]` command to run seeders.
- **Always** create a new seeder for each new feature and populate with data related to that feature and instance on DatabaseSeeder.

## 9. Testing

- **Always** use PHPStan for static analysis on all implementations.
- **Always** use the `docker compose exec -t app php artisan test` command to run tests.
- **Always** create a new test for each new feature and instance on tests folder.

## 10. Documentation
- **Comments**: Always comment functions to explain what they do.
- **API Documentation**: The project uses Scramble to automatically generate OpenAPI documentation. It infers endpoints from routes, `FormRequest` rules, and `JsonResource` responses. Ensure endpoints are properly typed so the documentation remains accurate.

## 11. Git

- **Always** use [Conventional Commits](https://www.conventionalcommits.org/) for commit messages. Example: `feat: add start workout session endpoint`, `fix: workout plan validation`, `docs: update architecture rules`.
- **Never** make commit without explicit permission from the user. Always wait for the user to ask for a commit.

## 11. MCPs

- **Always** use the Context7 MCP to search for documentation and websites
- **Always** use the Serena MCP for semantic code retrieval and editing tools

## 12. Commands

- **Always** use the `docker compose exec -t app php artisan [command]` to run commands on container

## 13. Dates and Timezones

- **Backend (PHP/Laravel)**: Always use `UTC` for dates and times.
  - Ensure `docker/php.ini` has `date.timezone = UTC`.
  - Database records must store timestamps in UTC.
  - The API MUST always return and expect dates in UTC (ISO-8601 format).
- **Frontend (Flutter)**:
  - Parse received UTC strings from the API.
  - ONLY convert to the user's Local Timezone when displaying dates on the UI.
- **Tokens (Sanctum)**: Let Laravel handle token expiration natively via config values (`SANCTUM_ACCESS_TOKEN_TTL_MINUTES` and `SANCTUM_REFRESH_TOKEN_TTL_DAYS`). Do not manually verify tokens on the PHP side. If looking to prevent unnecessary network requests, the frontend can locally check expiration by comparing the token's UTC expiration date against the device's current UTC time.