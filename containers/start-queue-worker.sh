#!/bin/bash

echo "[QUEUE] Waiting for main Laravel application to be ready..."

# Function to check if main application is ready
check_main_app_ready() {
    # Check for the ready marker file created by the main app
    if [ -f /var/www/html/storage/laravel_ready ]; then
        return 0
    fi

    # Fallback: Check if database and migrations are ready
    php artisan migrate:status >/dev/null 2>&1 && \
    php artisan tinker --execute="
        try {
            \$exists = \Illuminate\Support\Facades\Schema::hasTable('permissions');
            echo \$exists ? 'READY' : 'NOT_READY';
        } catch (\Exception \$e) {
            echo 'NOT_READY';
        }
    " 2>/dev/null | grep -q "READY"
}

# Wait for the main application to be ready (max 15 minutes)
WAIT_TIME=0
MAX_WAIT=900  # 15 minutes

while [ $WAIT_TIME -lt $MAX_WAIT ]; do
    if check_main_app_ready; then
        echo "[QUEUE] SUCCESS: Main application is ready! Starting queue worker..."
        break
    fi

    echo "[QUEUE] WAITING: Main application not ready yet, waiting... (${WAIT_TIME}s/${MAX_WAIT}s)"

    # Check if ready marker exists
    if [ -f /var/www/html/storage/laravel_ready ]; then
        echo "[QUEUE] INFO: Ready marker found!"
    else
        echo "[QUEUE] INFO: Ready marker not found, checking database..."
    fi

    sleep 15
    WAIT_TIME=$((WAIT_TIME + 15))
done

if [ $WAIT_TIME -ge $MAX_WAIT ]; then
    echo "[QUEUE] WARNING: Timeout waiting for main application. Starting anyway..."
fi

# Verify the application is actually ready before starting
echo "[QUEUE] INFO: Final verification before starting queue worker..."
if ! php artisan migrate:status >/dev/null 2>&1; then
    echo "[QUEUE] WARNING: Database migrations not accessible, but starting queue worker anyway"
fi

# Start the queue worker
echo "[QUEUE] STARTING: Laravel queue worker..."
exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
