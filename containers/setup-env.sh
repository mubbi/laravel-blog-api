#!/bin/bash

# Docker Environment Setup Script
# This script automatically sets up environment files for Docker development

set -e

echo "SETUP: Docker Environment Setup"
echo "=================================="

# Function to generate a Laravel APP_KEY
generate_app_key() {
    # Generate a base64 encoded 32-byte random key
    echo "base64:$(openssl rand -base64 32)"
}

# Function to copy and configure environment files
setup_env_file() {
    local source_file=$1
    local target_file=$2
    local env_type=$3

    if [ ! -f "$source_file" ]; then
        echo "ERROR: Source file $source_file not found!"
        return 1
    fi

    # Always overwrite existing files for automated setup
    if [ -f "$target_file" ]; then
        echo "INFO: Overwriting existing $target_file"
    fi

    cp "$source_file" "$target_file"
    echo "SUCCESS: Created $target_file"

    # Generate and set APP_KEY if it's empty
    if grep -q "APP_KEY=$" "$target_file" || grep -q "APP_KEY=\"\"" "$target_file"; then
        local app_key=$(generate_app_key)
        # Use a different delimiter to avoid issues with base64 characters
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS sed
            sed -i '' "s|APP_KEY=.*|APP_KEY=$app_key|" "$target_file"
        else
            # Linux sed
            sed -i "s|APP_KEY=.*|APP_KEY=$app_key|" "$target_file"
        fi
        echo "SUCCESS: Generated APP_KEY for $env_type environment"
    fi

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
echo "INFO: Setting up environment files automatically..."

# Setup development environment
echo ""
echo "1. Development Environment (.env.docker)"
setup_env_file ".env.docker.example" ".env.docker" "development"

# Setup testing environment
echo ""
echo "2. Testing Environment (.env.testing.docker)"
setup_env_file ".env.testing.docker.example" ".env.testing.docker" "testing"

# Setup main .env file if it doesn't exist or if it needs to be updated
echo ""
echo "3. Main Environment File (.env)"
if [ -f ".env.docker" ]; then
    cp ".env.docker" ".env"
    echo "SUCCESS: Updated main .env file from .env.docker"
else
    echo "WARNING: .env.docker not found, cannot update main .env file"
fi

echo ""
echo "SUCCESS: Environment setup complete!"
echo ""
echo "SUMMARY:"
echo "✅ Development environment configured (.env.docker)"
echo "✅ Testing environment configured (.env.testing.docker)"
echo "✅ Main environment file updated (.env)"
echo "✅ APP_KEY automatically generated for all environments"
echo "✅ Database and Redis configurations set"
echo ""
