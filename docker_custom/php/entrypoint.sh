#!/bin/sh
set -e

echo "Starting Laravel application entrypoint..."

# Initialize public directory in shared volume for nginx
# Volume mount overrides image files, so we need to populate it
if [ ! -f /var/www/html/public/index.php ] && [ -d /tmp/app_backup/public ]; then
    echo "Initializing public directory in shared volume for nginx..."
    cp -a /tmp/app_backup/public/. /var/www/html/public/ 2>/dev/null || true
    chown -R www-data:www-data /var/www/html/public 2>/dev/null || true
    echo "✅ Public directory initialized"
fi

# Wait for database to be ready
echo "Waiting for database connection..."
echo "DB_HOST: ${DB_HOST:-db}"
echo "DB_PORT: ${DB_PORT:-3306}"
echo "DB_USERNAME: ${DB_USERNAME:-not set}"
echo "DB_DATABASE: ${DB_DATABASE:-not set}"

MAX_RETRIES=30
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if php -r "
    try {
        \$pdo = new PDO('mysql:host=${DB_HOST:-db};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}');
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        exit(0);
    } catch (PDOException \$e) {
        echo 'Connection error: ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
    "; then
        echo "✅ Database is up!"
        break
    else
        RETRY_COUNT=$((RETRY_COUNT + 1))
        echo "Database is unavailable - sleeping (attempt $RETRY_COUNT/$MAX_RETRIES)"
        sleep 2
    fi
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "❌ ERROR: Could not connect to database after $MAX_RETRIES attempts"
    echo "Please check:"
    echo "  - DB_HOST is correct (should be 'db' for Docker service name)"
    echo "  - DB_USERNAME and DB_PASSWORD are correct"
    echo "  - Database container is running and healthy"
    exit 1
fi

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

# Fix .env file permissions (if mounted from host)
if [ -f /var/www/html/.env ]; then
    chown www-data:www-data /var/www/html/.env || true
    chmod 664 /var/www/html/.env || true
    echo "✅ Fixed .env file permissions"
fi

# Start Supervisor in background for queue workers
mkdir -p /var/log/supervisor /var/run
chown -R www-data:www-data /var/log/supervisor

# Fix supervisor config if it has wrong user (for backward compatibility)
if [ -f /etc/supervisor/conf.d/laravel-worker.conf ]; then
    sed -i 's/user=appuser/user=www-data/g' /etc/supervisor/conf.d/laravel-worker.conf 2>/dev/null || true
fi

echo "Starting Supervisor for queue workers..."
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf

# Start PHP-FPM in foreground
echo "Starting PHP-FPM..."
exec php-fpm

