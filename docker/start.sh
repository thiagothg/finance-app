#!/bin/sh
set -e

echo "🚀 Starting Finance App with FrankenPHP..."

if [ "${APP_ENV}" != "local" ]; then
    # Cache configuration for performance
    echo "⚡ Caching configuration..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Start FrankenPHP via Octane
echo "✅ Starting FrankenPHP server..."

OCTANE_CMD="php artisan octane:frankenphp"
OCTANE_CMD="${OCTANE_CMD} --host=${OCTANE_HOST:-0.0.0.0}"
OCTANE_CMD="${OCTANE_CMD} --port=${OCTANE_PORT:-80}"
OCTANE_CMD="${OCTANE_CMD} --admin-port=${OCTANE_ADMIN_PORT:-2019}"

if [ "${APP_ENV}" = "local" ]; then
    echo "👀 Watch mode enabled (local environment)"
    OCTANE_CMD="${OCTANE_CMD} --watch"
    OCTANE_CMD="${OCTANE_CMD} --log-level=${OCTANE_LOG_LEVEL:-info}"
fi

exec ${OCTANE_CMD}
