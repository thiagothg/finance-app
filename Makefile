.PHONY: up down restart build test shell shell-root db migrate rollback seed artisan

# Allow passing arguments to certain commands (like `make artisan CMD="route:list"`)
CMD ?=

# Start docker compose in the background
up:
	docker compose up -d

# Stop docker compose
down:
	docker compose down

# Restart the containers
restart: down up

# Build the containers
build:
	docker compose build --no-cache

# Run all tests (executes in the running app container)
test:
	docker compose exec app php artisan test

# Open a shell inside the app container
shell:
	docker compose exec app bash

# Format the code
format:
	docker compose exec app ./vendor/bin/pint

# Run static analysis
static:
	docker compose exec app ./vendor/bin/phpstan analyse

# Open a shell as root
shell-root:
	docker compose exec -u root app bash

# Connect directly to the database alias using standard docker exec
db:
	docker compose exec pgsql psql -U ${DB_USERNAME:-sail} -d ${DB_DATABASE:-testing}

# Run database migrations
migrate:
	docker compose exec app php artisan migrate

# Rollback the last migration
rollback:
	docker compose exec app php artisan migrate:rollback

# Seed the database
seed:
	docker compose exec app php artisan db:seed

refresh-seed:
	docker compose exec app php artisan migrate:fresh --seed

# Run any artisan command, e.g., `make artisan CMD="route:list"`
artisan:
	docker compose exec app php artisan $(CMD)
