# Family Finance App

Family Finance is a personal finance management system designed to help families track expenses, accounts and investments.

## Architecture

Mobile application communicates with a REST API.

Flutter Mobile App
↓
Laravel REST API
↓
PostgreSQL Database

## Main Features

- accounts management
- transactions tracking
- spending by person
- family financial dashboard
- investments tracking
- CSV bank import

## API Documentation

This project uses [Scramble](https://scramble.dedoc.co/) for API documentation. 
The documentation is automatically generated on the fly. You do not need to manually update it when routes change.
- **View Docs:** Visit `/docs/api` in your browser.
- **Export Specs:** To manually export the OpenAPI JSON file, run `php artisan scramble:export`.

## Tech Stack

Backend
- Laravel 12
- FrankenPHP (application server)
- PostgreSQL
- Docker

Mobile
- Flutter
- Riverpod
- Dio

## Docker / FrankenPHP

This project uses [FrankenPHP](https://frankenphp.dev) as the application server, running inside Docker with a multi-stage build.

### Quick Start

```bash
# Build and start all services
docker compose up --build -d

# View logs
docker compose logs app -f

# Run tests
docker compose exec app php artisan test

# Stop services
docker compose down
```

### Ports

| Service     | Port  | Description    |
|-------------|-------|----------------|
| App (HTTP)  | 80    | FrankenPHP     |
| App (HTTPS) | 443   | FrankenPHP     |
| PostgreSQL  | 5432  | Database       |
| Redis       | 6379  | Cache          |
| Meilisearch | 7700  | Search engine  |
| Mailpit     | 8025  | Mail dashboard |

### Docker Structure

```
Dockerfile            # Multi-stage build (deps → app)
docker/
├── Caddyfile         # FrankenPHP server config
├── php.ini           # PHP settings (OPcache, timezone, limits)
└── start.sh          # Entrypoint (cache, migrate, start)
compose.yaml          # All services (app, pgsql, redis, etc.)
```


<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
