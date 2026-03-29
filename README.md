# Finance App

Financial management application with the architecture split into two parts:

- `backend/`: Laravel REST API
- `frontend/`: React web interface

## Project Structure

```text
finance-app/
├── backend/   # Laravel 12 + REST API + database and supporting services
├── frontend/  # React 19 + TypeScript + Vite
└── 
```

## Current Stack

### Backend

- Laravel 12
- PHP 8.2+
- Laravel Sanctum
- Laravel Horizon
- Laravel Pulse
- Scramble for API documentation
- PostgreSQL
- Redis
- Meilisearch
- Mailpit
- Docker Compose

### Frontend

- React 19
- TypeScript
- Vite
- React Router
- TanStack Query
- Axios
- Zustand
- Tailwind CSS 4

## Running The Project

### 1. Backend (`backend/`)

The backend contains the application's main API.

#### Docker Option

```bash
cd backend
docker compose up --build -d
```

Default exposed services:

- HTTP API: `http://localhost`
- HTTPS API: `https://localhost`
- Backend Vite: `http://localhost:5173`
- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`
- Meilisearch: `localhost:7700`
- Mailpit: `http://localhost:8025`

Useful commands:

```bash
cd backend
make up
make test
make format
make static
make migrate
make seed
make down
```

#### Local Option Without Docker

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
composer run dev
```

### 2. Frontend (`frontend/`)

The frontend contains the React web application that consumes the backend API.

```bash
cd frontend
npm install
npm run dev
```

Useful commands:

```bash
cd frontend
npm run dev
npm run build
npm run lint
npm run preview
```

If needed, configure the API URL in the frontend `.env` file:

```env
VITE_API_URL=http://localhost/api
```

## API Documentation

The backend uses Scramble to generate the documentation automatically.

- Documentation URL: `http://localhost/docs/api`
- Export OpenAPI: `php artisan scramble:export`

## Initial Endpoints

Some routes already available in the API:

- `GET /api/health`
- authentication in `routes/auth.php`
- accounts in `routes/accounts.php`
- categories in `routes/categories.php`
- transactions in `routes/transactions.php`
- households in `routes/households.php`
- currencies in `routes/currencies.php`

## Notes

- The root `README.md` describes the overall monorepo structure.
- Implementation-specific details can live in `backend/README.md` and `frontend/README.md`.
- The frontend is still using an initial template structure and can evolve as screens are implemented.
