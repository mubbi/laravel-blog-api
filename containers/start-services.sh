#!/bin/bash

# Function to handle shutdown signals
shutdown() {
    echo "Shutting down services..."
    pkill nginx
    pkill php-fpm
    exit 0
}

# Trap shutdown signals
trap shutdown SIGTERM SIGINT

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground but with proper signal handling
exec nginx -g "daemon off;"
