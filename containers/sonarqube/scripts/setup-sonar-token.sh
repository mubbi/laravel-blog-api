#!/bin/bash

# SonarQube Token Setup Helper Script
# This script helps you set up the SONAR_TOKEN for the Laravel Blog API project

set -e

echo "🔧 SonarQube Token Setup Helper"
echo "================================"
echo ""

# Check if SonarQube is running
check_sonarqube_running() {
    if ! curl -f -s http://localhost:9000/api/system/status > /dev/null 2>&1; then
        echo "❌ SonarQube server is not running or not accessible at http://localhost:9000"
        echo ""
        echo "💡 To start SonarQube, run:"
        echo "   cd containers && docker-compose -f docker-compose.sonarqube.yml up -d"
        echo ""
        exit 1
    fi
    echo "✅ SonarQube server is running"
}

# Function to set token in .env.sonarqube file
set_token_in_env() {
    local token=$1
    local env_file=".env.sonarqube"

    if [ -f "$env_file" ]; then
        # Remove existing SONAR_TOKEN line if it exists
        sed -i '/^SONAR_TOKEN=/d' "$env_file"
    fi

    # Add the new token
    echo "SONAR_TOKEN=$token" >> "$env_file"
    echo "✅ Token saved to $env_file"
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
    echo "   Option 2: Save it to .env.sonarqube file (persistent):"
    read -p "   Enter your SonarQube token (or press Enter to skip): " token

    if [ -n "$token" ]; then
        set_token_in_env "$token"
        echo ""
        echo "✅ Token configured successfully!"
        echo "   You can now run: make docker-sonarqube-scan"
    else
        echo ""
        echo "⚠️  No token entered. You can set it later using:"
        echo "   echo 'SONAR_TOKEN=your_token_here' >> .env.sonarqube"
    fi

    echo ""
    echo "🚀 Ready to run SonarQube analysis!"
}

# Execute main function
main "$@"
