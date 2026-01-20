#!/bin/bash

# SonarQube Token Setup Helper Script
# This script helps you set up the SONAR_TOKEN for the Laravel Blog API project

set -euo pipefail

echo "🔧 SonarQube Token Setup Helper"
echo "================================"
echo ""

# Resolve project root (this script is in containers/sonarqube/scripts/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../" && pwd)"

# Canonical env file for local development (matches Makefile + docs)
ENV_FILE="$PROJECT_ROOT/containers/.env.sonarqube"
ENV_EXAMPLE_FILE="$PROJECT_ROOT/.env.sonarqube.example"

# Ensure env file exists
ensure_env_file_exists() {
    if [ -f "$ENV_FILE" ]; then
        return 0
    fi

    echo "📋 Creating SonarQube env file (delegating to Makefile)..."
    make -C "$PROJECT_ROOT" -s sonarqube-setup-env >/dev/null 2>&1 || make -C "$PROJECT_ROOT" sonarqube-setup-env

    # Fallback: if Makefile did not create it for any reason, create a basic one.
    if [ ! -f "$ENV_FILE" ]; then
        echo "⚠️  Makefile did not create containers/.env.sonarqube. Creating a basic one..."
        mkdir -p "$(dirname "$ENV_FILE")"
        {
            echo "# SonarQube Environment Variables"
            echo "SONAR_HOST_URL=http://localhost:9000"
            echo "SONAR_PROJECT_KEY=laravel-blog-api"
            echo "SONAR_PROJECT_NAME=\"Laravel Blog API\""
            echo "SONAR_PROJECT_VERSION=1.0.0"
            echo "SONAR_SOURCES=app"
            echo "SONAR_TESTS=tests"
            echo "# SONAR_TOKEN=your_token_here"
        } > "$ENV_FILE"
    fi
}

# Check if SonarQube is running
check_sonarqube_running() {
    if ! curl -f -s http://localhost:9000/api/system/status > /dev/null 2>&1; then
        echo "❌ SonarQube server is not running or not accessible at http://localhost:9000"
        echo ""
        echo "💡 To start SonarQube, run:"
        echo "   make sonarqube-start"
        echo ""
        exit 1
    fi
    echo "✅ SonarQube server is running"
}

# Function to set token in containers/.env.sonarqube file
set_token_in_env() {
    local token=$1
    local tmp_file

    # Defensive: strip CRLF artifacts (Windows/Git Bash)
    token="${token//$'\r'/}"

    ensure_env_file_exists

    tmp_file="$(mktemp)"

    if [ -f "$ENV_FILE" ]; then
        while IFS= read -r line || [ -n "$line" ]; do
            case "$line" in
                SONAR_TOKEN=*) continue ;;
            esac
            printf '%s\n' "$line" >> "$tmp_file"
        done < "$ENV_FILE"
    fi

    printf 'SONAR_TOKEN=%s\n' "$token" >> "$tmp_file"
    mv "$tmp_file" "$ENV_FILE"

    echo "✅ Token saved to containers/.env.sonarqube"
}

# Main function
main() {
    check_sonarqube_running

    echo ""
    echo "📋 To generate a SonarQube token:"
    echo "   1. Open http://localhost:9000 in your browser"
    echo "   2. Login with username: admin, password: admin (default)"
    echo "   3. Go to Account → Security → Generate Token"
    echo "   4. Enter a token name (e.g., 'laravel-blog-api')"
    echo "   5. Click 'Generate'"
    echo ""

    # Open SonarQube in browser (Windows)
    if command -v cmd.exe > /dev/null 2>&1; then
        echo "🌐 Opening SonarQube in your default browser..."
        cmd.exe /c start http://localhost:9000/account/security
    fi

    echo ""
    echo "🔑 Once you have your token, you can:"
    echo ""
    echo "   Option 1: Set it as an environment variable (current session only):"
    echo "   export SONAR_TOKEN=your_token_here"
    echo ""
    echo "   Option 2: Save it to containers/.env.sonarqube (persistent, recommended):"
    read -p "   Enter your SonarQube token (or press Enter to skip): " token

    if [ -n "$token" ]; then
        set_token_in_env "$token"
        echo ""
        echo "✅ Token configured successfully!"
        echo "   You can now run: make sonarqube-analyze"
    else
        echo ""
        echo "⚠️  No token entered. You can set it later using:"
        echo "   echo 'SONAR_TOKEN=your_token_here' >> containers/.env.sonarqube"
    fi

    echo ""
    echo "🚀 Ready to run SonarQube analysis!"
}

# Execute main function
main "$@"
