# Architecture

This project follows a modular architecture separating concerns between mobile, backend API and database.

## System Overview

Flutter Mobile App
↓
REST API (Laravel)
↓
PostgreSQL

## Backend Architecture

Laravel follows a service oriented architecture.

Layers:

Controllers
Services
Repositories
Models

Controllers remain thin and delegate business logic to services.

## Domain Model

Core entities:

User
Account
Person
Category
Transaction

Transactions are the central financial entity.

## Financial Model

Account represents where money is stored.

Transaction represents financial movement.

Transaction types:

income
expense
transfer

Transfers are represented as two mirrored transactions.

## Investment Model

Assets represent financial instruments.

InvestmentPosition represents ownership of assets.

Portfolio value is calculated dynamically.

## Infrastructure

Application server: FrankenPHP (Caddy-based PHP server)

Docker multi-stage build:

Stage 1 (deps): composer install with optimized autoloader
Stage 2 (app): FrankenPHP image with PHP extensions and app code

Service topology:

FrankenPHP App → PostgreSQL (database)
FrankenPHP App → Redis (cache/sessions)
FrankenPHP App → Meilisearch (search)
FrankenPHP App → Mailpit (dev email)

PHP configuration managed via docker/php.ini (OPcache, timezone, limits).

Entrypoint (docker/start.sh) runs config/route/view caching and migrations on container start.

## Git

- **Always** use [Conventional Commits](https://www.conventionalcommits.org/) for commit messages. Example: `feat: add start workout session endpoint`, `fix: workout plan validation`, `docs: update architecture rules`.
- **Never** make commit without explicit permission from the user. Always wait for the user to ask for a commit.
