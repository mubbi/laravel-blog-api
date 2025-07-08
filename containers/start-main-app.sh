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

# Universal permission fix for all systems
echo "[MAIN] SETUP: Fixing Laravel directory permissions..."
# Ensure www-data user owns all files (www-data is standard across systems)
chown -R www-data:www-data /var/www/html 2>/dev/null || true
# Set proper permissions for storage and cache directories
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true
# Ensure specific subdirectories have correct permissions
chmod -R 775 /var/www/html/storage/framework/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage/framework/sessions 2>/dev/null || true
chmod -R 775 /var/www/html/storage/framework/testing 2>/dev/null || true
chmod -R 775 /var/www/html/storage/framework/views 2>/dev/null || true
chmod -R 775 /var/www/html/storage/logs 2>/dev/null || true
chmod -R 775 /var/www/html/storage/app 2>/dev/null || true
# Make artisan executable
chmod +x /var/www/html/artisan 2>/dev/null || true

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

# Universal permission fix after optimization
echo "[MAIN] SETUP: Fixing permissions after optimization..."
chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

# Create ready marker to signal that the app is fully set up
echo "[MAIN] SUCCESS: Application setup complete! Creating ready marker..."
touch /var/www/html/storage/laravel_ready

echo "[MAIN] STARTING: Web services..."

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
exec nginx -g "daemon off;"
