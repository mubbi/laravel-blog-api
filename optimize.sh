#!/bin/bash

# Universal Laravel Optimize Script
# This script can be run from anywhere to optimize the Laravel application

# Function to check if we're in a Docker environment
check_docker_env() {
    if [ -f /.dockerenv ]; then
        return 0  # We're in Docker
    else
        return 1  # We're not in Docker
    fi
}

# Function to run optimize:clear in container
run_in_container() {
    echo "Running optimize:clear in Docker container..."

    # Try different methods to run the command
    if command -v docker >/dev/null 2>&1; then
        # Method 1: Direct docker exec
        docker exec laravel_blog_api php artisan optimize:clear 2>/dev/null && return 0

        # Method 2: With explicit environment variables
        MSYS_NO_PATHCONV=1 docker exec laravel_blog_api php artisan optimize:clear 2>/dev/null && return 0

        # Method 3: With bash shell
        docker exec laravel_blog_api bash -c "php artisan optimize:clear" 2>/dev/null && return 0

        echo "Error: Could not run optimize:clear in container"
        echo "Make sure the Laravel container is running: docker-compose up -d"
        return 1
    else
        echo "Error: Docker is not available"
        return 1
    fi
}

# Function to run optimize:clear directly
run_direct() {
    echo "Running optimize:clear directly..."

    # Check if artisan exists
    if [ -f "./artisan" ]; then
        php artisan optimize:clear
    elif [ -f "../artisan" ]; then
        cd .. && php artisan optimize:clear
    else
        echo "Error: Could not find artisan file"
        echo "Make sure you're in the Laravel project directory"
        return 1
    fi
}

# Main execution logic
main() {
    echo "Laravel Optimize Script"
    echo "======================"

    # Check if we're inside Docker container
    if check_docker_env; then
        echo "Running inside Docker container..."
        run_direct
    else
        echo "Running on host system..."

        # Try to run in container first
        if run_in_container; then
            echo "✓ Successfully ran optimize:clear in container"
        else
            echo "Container method failed, trying direct execution..."
            if run_direct; then
                echo "✓ Successfully ran optimize:clear directly"
            else
                echo "✗ Failed to run optimize:clear"
                echo ""
                echo "Troubleshooting:"
                echo "1. Make sure Docker containers are running: docker-compose up -d"
                echo "2. Check container status: docker ps"
                echo "3. Try running manually: docker exec laravel_blog_api php artisan optimize:clear"
                return 1
            fi
        fi
    fi
}

# Run the main function
main "$@"
