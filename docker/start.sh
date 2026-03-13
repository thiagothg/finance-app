#!/bin/sh
set -e

echo "🚀 Starting Finance App with FrankenPHP..."

# Cache configuration for performance
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations only if explicitly enabled
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "🗄️ Running migrations..."
    php artisan migrate --force
fi

# Start FrankenPHP
echo "✅ Starting FrankenPHP server..."
exec frankenphp run --config /etc/caddy/Caddyfile
