#!/bin/bash

# SonarQube 25.7.0 Analysis Script for CI/CD environments
# This script assumes SonarQube is running externally and delegates to Makefile targets

set -e

echo "🚀 Starting SonarQube 25.7.0 Analysis for Laravel Blog API (CI/CD Mode)..."

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../" && pwd)"

# Check if coverage and phpstan reports exist
if [ ! -f "$PROJECT_ROOT/reports/coverage.xml" ]; then
    echo "⚠️  Coverage report not found at reports/coverage.xml"
    echo "   Please run tests with coverage first"
fi

if [ ! -f "$PROJECT_ROOT/reports/phpstan.json" ]; then
    echo "⚠️  PHPStan report not found at reports/phpstan.json"
    echo "   Please run PHPStan analysis first"
fi

echo "📊 Running SonarQube Scanner (delegating to Makefile)..."
make -C "$PROJECT_ROOT" sonarqube-scan-ci
