#!/bin/bash

# Docker Environment Setup Script
# This script helps set up environment files for Docker development

set -e

echo "SETUP: Docker Environment Setup"
echo "=================================="

# Function to copy environment files
setup_env_file() {
    local source_file=$1
    local target_file=$2
    local env_type=$3

    if [ ! -f "$source_file" ]; then
        echo "ERROR: Source file $source_file not found!"
        return 1
    fi

    if [ -f "$target_file" ]; then
        echo "WARNING: $target_file already exists."
        read -p "Do you want to overwrite it? (y/N): " confirm
        if [[ ! $confirm =~ ^[Yy]$ ]]; then
            echo "SKIP: $env_type environment setup."
            return 0
        fi
    fi

    cp "$source_file" "$target_file"
    echo "SUCCESS: Created $target_file"

    # Set default XDEBUG_MODE if not set
    if ! grep -q "XDEBUG_MODE=" "$target_file"; then
        echo "" >> "$target_file"
        echo "# Xdebug configuration" >> "$target_file"
        if [[ $env_type == "testing" ]]; then
            echo "XDEBUG_MODE=coverage" >> "$target_file"
        else
            echo "XDEBUG_MODE=off" >> "$target_file"
        fi
    fi
}

# Navigate to project root
cd "$(dirname "$0")/.."

echo ""
echo "INFO: Setting up environment files..."

# Setup development environment
echo ""
echo "1. Development Environment (.env.docker)"
setup_env_file "containers/.env.docker.example" ".env.docker" "development"

# Setup testing environment
echo ""
echo "2. Testing Environment (.env.testing.docker)"
setup_env_file "containers/.env.testing.docker.example" ".env.testing.docker" "testing"

echo ""
echo "CONFIG: Environment Configuration Options:"
echo ""
echo "For Development (.env.docker):"
echo "  XDEBUG_MODE=off           # Better performance (default)"
echo "  XDEBUG_MODE=debug         # Enable debugging"
echo "  XDEBUG_MODE=coverage      # Enable coverage reports"
echo "  XDEBUG_MODE=debug,coverage # Enable both"
echo ""
echo "For Testing (.env.testing.docker):"
echo "  XDEBUG_MODE=coverage      # Enable coverage reports (default)"
echo "  XDEBUG_MODE=off           # Disable for faster tests"
echo ""

# Check if main .env exists
if [ ! -f ".env" ]; then
    echo "TIP: You might also want to set up your main .env file:"
    echo "   cp .env.example .env"
    echo "   php artisan key:generate"
fi

echo ""
echo "SUCCESS: Environment setup complete!"
echo ""
echo "NEXT STEPS:"
echo "1. Review and adjust the environment files as needed"
echo "2. Run: make docker-setup-local"
echo "3. Start developing!"
