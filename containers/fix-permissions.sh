#!/bin/bash

echo "🔧 Quick permission fix for Laravel storage directories..."

# This script can be run inside the container to fix permissions
# Usage: docker exec -it laravel_blog_api /usr/local/bin/fix-permissions.sh

# Fix ownership
echo "📁 Fixing ownership..."
chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true

# Fix permissions
echo "🔐 Fixing permissions..."
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage/logs 2>/dev/null || true
chmod -R 775 /var/www/html/storage/framework 2>/dev/null || true
chmod -R 775 /var/www/html/storage/app 2>/dev/null || true

# Make artisan executable
chmod +x /var/www/html/artisan 2>/dev/null || true

echo "✅ Permissions fixed!"
echo "📋 Current storage permissions:"
ls -la /var/www/html/storage/logs/ 2>/dev/null || echo "Logs directory not accessible"
