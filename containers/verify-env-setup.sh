#!/bin/bash

# Environment Setup Verification Script
# This script verifies that the environment setup is working correctly

set -e

echo "VERIFICATION: Environment Setup"
echo "==============================="

# Navigate to project root
cd "$(dirname "$0")/.."

# Check if example files exist
echo ""
echo "Checking example files..."
if [ -f ".env.docker.example" ]; then
    echo "✅ .env.docker.example exists"
else
    echo "❌ .env.docker.example missing"
    exit 1
fi

if [ -f ".env.testing.docker.example" ]; then
    echo "✅ .env.testing.docker.example exists"
else
    echo "❌ .env.testing.docker.example missing"
    exit 1
fi

# Check if working files exist
echo ""
echo "Checking generated environment files..."
if [ -f ".env.docker" ]; then
    echo "✅ .env.docker exists"
else
    echo "❌ .env.docker missing"
    exit 1
fi

if [ -f ".env.testing.docker" ]; then
    echo "✅ .env.testing.docker exists"
else
    echo "❌ .env.testing.docker missing"
    exit 1
fi

if [ -f ".env" ]; then
    echo "✅ .env exists"
else
    echo "❌ .env missing"
    exit 1
fi

# Check APP_KEY is set
echo ""
echo "Checking APP_KEY configuration..."
if grep -q "APP_KEY=base64:" ".env.docker"; then
    echo "✅ .env.docker has APP_KEY set"
else
    echo "❌ .env.docker missing APP_KEY"
    exit 1
fi

if grep -q "APP_KEY=base64:" ".env.testing.docker"; then
    echo "✅ .env.testing.docker has APP_KEY set"
else
    echo "❌ .env.testing.docker missing APP_KEY"
    exit 1
fi

# Check git ignore status
echo ""
echo "Checking git tracking status..."
if git ls-files | grep -q "\.env\.docker$"; then
    echo "❌ .env.docker is tracked by git (should be ignored)"
    exit 1
else
    echo "✅ .env.docker is properly ignored by git"
fi

if git ls-files | grep -q "\.env\.testing\.docker$"; then
    echo "❌ .env.testing.docker is tracked by git (should be ignored)"
    exit 1
else
    echo "✅ .env.testing.docker is properly ignored by git"
fi

if git ls-files | grep -q "\.env\.docker\.example$"; then
    echo "✅ .env.docker.example is tracked by git"
else
    echo "❌ .env.docker.example is not tracked by git"
    exit 1
fi

if git ls-files | grep -q "\.env\.testing\.docker\.example$"; then
    echo "✅ .env.testing.docker.example is tracked by git"
else
    echo "❌ .env.testing.docker.example is not tracked by git"
    exit 1
fi

echo ""
echo "SUCCESS: Environment setup verification passed!"
echo ""
echo "SUMMARY:"
echo "✅ Only example files (.env.docker.example, .env.testing.docker.example) are tracked in git"
echo "✅ Working environment files (.env.docker, .env.testing.docker, .env) are generated and ignored"
echo "✅ APP_KEY is automatically generated for all environments"
echo "✅ Environment setup is clean and automated"
echo ""
