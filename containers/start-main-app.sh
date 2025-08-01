#!/bin/bash

echo "[MAIN] Starting Laravel main application..."

# Function to handle shutdown signals
shutdown() {
    echo "Shutting down services..."
    # Remove ready marker on shutdown
    rm -f /var/www/html/storage/laravel_ready
    pkill nginx
    pkill php-fpm
    exit 0
}

# Trap shutdown signals
trap shutdown SIGTERM SIGINT

# Remove any existing ready marker
rm -f /var/www/html/storage/laravel_ready

# Fix permissions at startup
echo "[MAIN] SETUP: Fixing Laravel directory permissions..."
/usr/local/bin/fix-permissions.sh

# Wait for database to be ready
echo "[MAIN] WAITING: Database connection..."
until php -r "
try {
    \$host = getenv('DB_HOST') ?: 'mysql';
    \$port = getenv('DB_PORT') ?: '3306';
    \$database = getenv('DB_DATABASE') ?: 'laravel_blog';
    \$username = getenv('DB_USERNAME') ?: 'laravel_user';
    \$password = getenv('DB_PASSWORD') ?: 'laravel_password';

    \$pdo = new PDO(\"mysql:host=\$host;port=\$port;dbname=\$database\", \$username, \$password);
    echo 'OK';
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" >/dev/null 2>&1; do
    echo "Database not ready, waiting..."
    sleep 5
done

echo "[MAIN] SUCCESS: Database connection established!"

# Check if this is the first run or if we need to set up
if [ ! -f .env ]; then
    echo "[MAIN] SETUP: Environment file..."
    if [ -f .env.docker.example ]; then
        cp .env.docker.example .env
        echo "[MAIN] WARNING: Using .env.docker.example as template. Run setup-env.sh to generate proper configuration."
    else
        echo "[MAIN] ERROR: No environment file found. Please run setup-env.sh first."
        exit 1
    fi
fi

# Install dependencies if needed
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "[MAIN] SETUP: Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate application key if needed
if ! grep -q "APP_KEY=base64:" .env; then
    echo "[MAIN] SETUP: Generating application key..."
    php artisan key:generate
fi

# Run migrations
echo "[MAIN] SETUP: Running database migrations..."
php artisan migrate --force

# Run seeders (only if not already seeded)
echo "[MAIN] SETUP: Running database seeders..."
php artisan db:seed --force

# Clear and cache config
echo "[MAIN] SETUP: Optimizing application..."
php artisan config:clear
php artisan config:cache
php artisan route:cache

# Fix permissions after optimization
echo "[MAIN] SETUP: Fixing permissions after optimization..."
/usr/local/bin/fix-permissions.sh

# Create ready marker to signal that the app is fully set up
echo "[MAIN] SUCCESS: Application setup complete! Creating ready marker..."
touch /var/www/html/storage/laravel_ready

echo "[MAIN] STARTING: Web services..."

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
exec nginx -g "daemon off;"
