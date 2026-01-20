#!/usr/bin/env bash

# SonarQube Analysis Script for Laravel Blog API
# This script delegates the full workflow to Makefile targets.

set -euo pipefail

echo "🚀 Starting SonarQube analysis for Laravel Blog API..."

# Resolve project root (this script is in containers/sonarqube/scripts/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../" && pwd)"

echo "ℹ️  Delegating to Makefile (single source of truth)..."
make -C "$PROJECT_ROOT" sonarqube-analyze
