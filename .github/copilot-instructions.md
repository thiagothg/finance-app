# Copilot Instructions

You are an expert in Laravel, PHP, and related web development technologies.

  Core Principles
  - Write concise, technical responses with accurate PHP/Laravel examples.
  - Prioritize SOLID principles for object-oriented programming and clean architecture.
  - Follow PHP and Laravel best practices, ensuring consistency and readability.
  - Design for scalability and maintainability, ensuring the system can grow with ease.
  - Prefer iteration and modularization over duplication to promote code reuse.
  - Use consistent and descriptive names for variables, methods, and classes to improve readability.

  Dependencies
  - Composer for dependency management
  - PHP 8.4+
  - Laravel 12.0+

  PHP and Laravel Standards
  - Leverage PHP 8.4+ features when appropriate (e.g., typed properties, match expressions).
  - Adhere to PSR-12 coding standards for consistent code style.
  - Always use strict typing: declare(strict_types=1);
  - Utilize Laravel's built-in features and helpers to maximize efficiency.
  - Follow Laravel's directory structure and file naming conventions.
  - Implement robust error handling and logging:
    > Use Laravel's exception handling and logging features.
    > Create custom exceptions when necessary.
    > Employ try-catch blocks for expected exceptions.
  - Use Laravel's validation features for form and request data.
  - Implement middleware for request filtering and modification.
  - Utilize Laravel's Eloquent ORM for database interactions.
  - Use Laravel's query builder for complex database operations.
  - Create and maintain proper database migrations and seeders.
  - Prefer readable and explicit code.
  - Avoid large controllers.


  Laravel Best Practices
  - Use Eloquent ORM and Query Builder over raw SQL queries when possible
  - Implement Repository and Service patterns for better code organization and reusability
  - Utilize Laravel's built-in authentication and authorization features (Sanctum, Policies)
  - Leverage Laravel's caching mechanisms (Redis, Memcached) for improved performance
  - Use job queues and Laravel Horizon for handling long-running tasks and background processing
  - Implement comprehensive testing using Pest and Laravel Dusk for unit, feature, and browser tests
  - Use API resources and versioning for building robust and maintainable APIs
  - Implement proper error handling and logging using Laravel's exception handler and logging facade
  - Utilize Laravel's validation features, including Form Requests, for data integrity
  - Implement database indexing and use Laravel's query optimization features for better performance
  - Use Laravel Telescope for debugging and performance monitoring in development
  - Leverage Laravel Nova or Filament for rapid admin panel development
  - Implement proper security measures, including CSRF protection, XSS prevention, and input sanitization

  Code Architecture
    * Naming Conventions:
      - Use consistent naming conventions for folders, classes, and files.
      - Follow Laravel's conventions: singular for models, plural for controllers (e.g., User.php, UsersController.php).
      - Use PascalCase for class names, camelCase for method names, and snake_case for database columns.
    * Controller Design:
      - Controllers should be final classes to prevent inheritance.
      - Make controllers read-only (i.e., no property mutations).
      - Avoid injecting dependencies directly into controllers. Instead, use method injection or service classes.
      - Controllers should remain thin.
      - Use Method Injection for `FormRequest` validation.
      - Always return `JsonResource` (or collections of them) for API responses.
    * Model Design:
      - Models should be final classes to ensure data integrity and prevent unexpected behavior from inheritance.
    * Services (The "Business Brain"):
      - Create a `Services` folder within the `app` directory.
      - Service classes should be final and read-only.
      - Responsibility: COORDINATION and BUSINESS LOGIC.
      - Use services for enforcing permissions, calculating totals, triggering events, and multi-step processes.
      - Services should call Repositories or Eloquent directly.
    * Repositories (The "Data Drawer"):
      - Responsibility: DATA ACCESS only.
      - ONLY use Repositories when:
        * Queries are extremely complex or long (clutters the Service).
        * There is a need to abstract multiple data sources (e.g., Cache + DB).
      - Avoid "Boilerplate Repositories" that just wrap simple Eloquent methods. Prefer using Eloquent directly in the Service for 80% of cases.
    * Routing:
      - Maintain consistent and organized routes.
      - Create separate route files for each major model or feature area.
      - Group related routes together (e.g., all user-related routes in routes/user.php).
      - Group routes by middleware and use descriptive name prefixes.
      - Follow the convention: `->name('[resource].[action]')`.
    * Type Declarations:
      - Always use explicit return type declarations for methods and functions.
      - Use appropriate PHP type hints for method parameters.
      - Leverage PHP 8.3+ features like union types and nullable types when necessary.
    * Data Type Consistency:
      - Be consistent and explicit with data type declarations throughout the codebase.
      - Use type hints for properties, method parameters, and return types.
      - Leverage PHP's strict typing to catch type-related errors early.
    * Error Handling:
      - Use Laravel's exception handling and logging features to handle exceptions.
      - Create custom exceptions when necessary.
      - Use try-catch blocks for expected exceptions.
      - Handle exceptions gracefully and return appropriate responses.
    * Enums
        - Attributes like status, types, or roles MUST use Enums from `App\Enums`.
        - Always include name `Enum` in the name of the enum class.
    * Testing
      - Use Pest for unit testing.
      - Use Laravel Pint for code formatting.
      - Phpstan must be used for static analysis.

  Key points
  - Follow Laravel’s MVC architecture for clear separation of business logic, data, and presentation layers.
  - Implement request validation using Form Requests to ensure secure and validated data inputs.
  - Use Laravel’s built-in authentication system, including Laravel Sanctum for API token management.
  - Ensure the REST API follows Laravel standards, using API Resources for structured and consistent responses.
  - Leverage task scheduling and event listeners to automate recurring tasks and decouple logic.
  - Implement database transactions using Laravel's database facade to ensure data consistency.
  - Use Eloquent ORM for database interactions, enforcing relationships and optimizing queries.
  - Implement API versioning for maintainability and backward compatibility.
  - Optimize performance with caching mechanisms like Redis and Memcached.
  - Ensure robust error handling and logging using Laravel’s exception handler and logging features.
  - Create folder when necessary to separate feature or things that are related.
  - **Always** use comment the function to explain what it does.


### MCPs

- **Always** use the Context7 MCP to search for documentation and websites
- **Always** use the Serena MCP for semantic code retrieval and editing tools

### Example Implementation

```php
// Controller Example
public function store(StoreTransactionRequest $request, TransactionService $service): TransactionResource
{
    $transaction = $service->create($request->validated());
    return new TransactionResource($transaction);
}
```