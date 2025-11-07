#!/bin/sh
set -e

echo "Starting Laravel application entrypoint..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php -r "
    try {
        \$pdo = new PDO('mysql:host=${DB_HOST:-db};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}');
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        exit(0);
    } catch (PDOException \$e) {
        exit(1);
    }
" 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
done
echo "Database is up!"

# Run migrations if requested
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Create storage link if it doesn't exist
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link || true
fi

# Clear and cache configuration
if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "staging" ]; then
    echo "Optimizing application for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Set permissions
echo "Setting permissions..."
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Start Supervisor in background for queue workers
echo "Starting Supervisor for queue workers..."
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf

# Start PHP-FPM in foreground
echo "Starting PHP-FPM..."
exec php-fpm

