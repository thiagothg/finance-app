# Database Generation Instructions

Generate Laravel migrations and Eloquent models for a family finance management system.

Follow Laravel best practices and code style.

Requirements:

- Laravel 12 compatible
- Use Laravel Pint coding style
- Use typed properties when possible
- Use proper foreign key constraints
- Use snake_case table names
- Use plural table naming
- Add fillable attributes in models
- Define relationships in models
- Use Enum for columns with define values options

Database: PostgreSQL

## Tables

### users

Default Laravel users table.

Fields:

- id
- name
- email
- password
- timestamps

---

### households

Represents a financial household.

Fields:

- id
- name
- owner_id (foreign key → users.id)
- timestamps

Relationships:

Household belongs to User (owner)

---

### household_members

Represents membership of users in a household.

Fields:

- id
- household_id (FK → households.id)
- user_id (FK → users.id)
- role (owner | member | viewer)
- timestamps

Relationships:

HouseholdMember belongs to Household
HouseholdMember belongs to User

---

### accounts

Represents financial accounts.

Fields:

- id
- user_id (FK → users.id)
- name
- type (checking | savings | cash)
- initial_balance decimal(12,2)
- currency default 'BRL'
- timestamps

Relationships:

Account belongs to User
Account has many Transactions

---

### categories

Represents financial categories.

Fields:

- id
- household_id (FK → households.id)
- name
- type (income | expense)
- icon (nullable)
- color (nullable)
- timestamps

Relationships:

Category belongs to Household
Category has many Transactions

---

### transactions

Represents financial movements.

Fields:

- id
- account_id (FK → accounts.id)
- category_id (FK → categories.id)
- spender_user_id (FK → users.id)
- amount decimal(12,2)
- type (income | expense | transfer)
- description (nullable)
- transaction_at (datetime)
- to_account_id (FK → accounts.id)
- timestamps

Relationships:

Transaction belongs to Account (account_id)
Transaction belongs to Category (category_id)
Transaction belongs to User (spender_user_id)
Transaction belongs to Account (to_account_id)

---

## Model Relationships

Define Eloquent relationships for all foreign keys.

Examples:

- Household hasMany Accounts
- Account hasMany Transactions
- Category hasMany Transactions
- User hasMany Accounts
- User hasMany Transactions
- User hasMany HouseholdMembers
- Household hasMany HouseholdMembers
- HouseholdMember belongs to Household
- HouseholdMember belongs to User

---

## Output Format

Generate:

1. Laravel migration files
2. Eloquent model classes
3. Relationship methods
4. Fillable attributes


# Factories

- Generate factories for all models
- Generate seeders for all models

# Tests

- Generate tests for all models
