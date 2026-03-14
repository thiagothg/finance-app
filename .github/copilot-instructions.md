# Copilot Instructions

This repository is a personal finance management system.

Follow these coding principles when generating code.

## Backend

Language: PHP
Framework: Laravel

Controllers should remain thin.

Business logic must be implemented inside service classes.

Repositories should be used for data access.

## Code Style

- Prefer readable and explicit code.
- Avoid large controllers.
- Use dependency injection whenever possible.
- Use Pint for code formatting.
- Phpstan must be used for static analysis.

## Domain

Main entities:

Account
Transaction
Person
Category
Asset

Transactions represent financial movements.

Transaction types:

income
expense
transfer

Transfers are modeled as two transactions.

## Testing

Whenever generating business logic, also suggest unit tests.

Important areas to test:

balance calculation
transaction creation
dashboard aggregation


## MCPs

- **Always** use the Context7 MCP to search for documentation and websites
- **Always** use the Serena MCP for semantic code retrieval and editing tools

## Architecture Standards

### Models
- Use typed properties for all methods.
- Define relationships with explicit return types (`BelongsTo`, `HasMany`, etc.).
- Attributes like status, types, or roles MUST use Enums from `App\Enums`.
- Use `protected function casts(): array` for all attribute casting.
- Always include the `HasFactory` trait.

### Services
- Business logic MUST reside in `App\Services`.
- Use constructor injection for all dependencies.
- Methods should be descriptive and return either Models, DTOs, or Collections—avoid raw arrays.

### Controllers
- Use Method Injection for `FormRequest` validation.
- Keep controllers thin: delegate business logic to Services.
- Always return `JsonResource` (or collections of them) for API responses.
- Class names must follow the `[Name]Controller` convention.

### Routes
- Use plural nouns for resource-based endpoints (e.g., `/transactions`).
- Group routes by middleware and use descriptive name prefixes.
- Follow the convention: `->name('[resource].[action]')`.

### Example Implementation

```php
// Controller Example
public function store(StoreTransactionRequest $request, TransactionService $service): TransactionResource
{
    $transaction = $service->create($request->validated());
    return new TransactionResource($transaction);
}
```