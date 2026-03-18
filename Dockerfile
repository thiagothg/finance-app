# ==============================================================================
# Stage 1: Install PHP dependencies
# ==============================================================================
FROM composer:latest AS deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .

RUN composer dump-autoload --optimize

# ==============================================================================
# Stage 2: Build the FrankenPHP application image
# ==============================================================================
FROM dunglas/frankenphp:php8.4-bookworm AS app

ARG APP_ENV=production

# Install PHP extensions required by Laravel + project dependencies
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    pcntl \
    zip \
    intl \
    opcache \
    gd \
    bcmath \
    exif

# Conditionally install Xdebug if environment is local
RUN if [ "$APP_ENV" = "local" ]; then \
        install-php-extensions xdebug; \
    fi

# Set working directory (FrankenPHP expects /app)
WORKDIR /app

# Copy custom PHP configuration
COPY docker/php.ini $PHP_INI_DIR/conf.d/99-app.ini

# Copy application code from deps stage
COPY --from=deps /app /app

# Copy start script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set storage & cache permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Expose HTTP and HTTPS ports
EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/start.sh"]
