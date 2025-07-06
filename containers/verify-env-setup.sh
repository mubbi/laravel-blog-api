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
if [ -f ".env" ]; then
    echo "✅ .env exists"
else
    echo "❌ .env missing"
    exit 1
fi

if [ -f ".env.testing" ]; then
    echo "✅ .env.testing exists"
else
    echo "❌ .env.testing missing"
    exit 1
fi

# Check APP_KEY is set
echo ""
echo "Checking APP_KEY configuration..."
if grep -q "APP_KEY=base64:" ".env"; then
    echo "✅ .env has APP_KEY set"
else
    echo "❌ .env missing APP_KEY"
    exit 1
fi

if grep -q "APP_KEY=base64:" ".env.testing"; then
    echo "✅ .env.testing has APP_KEY set"
else
    echo "❌ .env.testing missing APP_KEY"
    exit 1
fi

# Check git ignore status
echo ""
echo "Checking git tracking status..."
if git ls-files | grep -q "\.env$"; then
    echo "❌ .env is tracked by git (should be ignored)"
    exit 1
else
    echo "✅ .env is properly ignored by git"
fi

if git ls-files | grep -q "\.env\.testing$"; then
    echo "❌ .env.testing is tracked by git (should be ignored)"
    exit 1
else
    echo "✅ .env.testing is properly ignored by git"
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

# Check database configurations
echo ""
echo "Checking database configurations..."

# Check main environment database config
if grep -q "DB_HOST=mysql$" ".env" && \
   grep -q "DB_DATABASE=laravel_blog$" ".env" && \
   grep -q "DB_USERNAME=laravel_user$" ".env" && \
   grep -q "DB_PASSWORD=laravel_password$" ".env"; then
    echo "✅ .env has correct main database configuration"
else
    echo "❌ .env has incorrect database configuration"
    echo "Expected: DB_HOST=mysql, DB_DATABASE=laravel_blog, DB_USERNAME=laravel_user, DB_PASSWORD=laravel_password"
    exit 1
fi

# Check test environment database config
if grep -q "DB_HOST=mysql_test$" ".env.testing" && \
   grep -q "DB_DATABASE=laravel_blog_test$" ".env.testing" && \
   grep -q "DB_USERNAME=laravel_user$" ".env.testing" && \
   grep -q "DB_PASSWORD=laravel_password$" ".env.testing"; then
    echo "✅ .env.testing has correct test database configuration"
else
    echo "❌ .env.testing has incorrect database configuration"
    echo "Expected: DB_HOST=mysql_test, DB_DATABASE=laravel_blog_test, DB_USERNAME=laravel_user, DB_PASSWORD=laravel_password"
    exit 1
fi

echo ""
echo "SUCCESS: Environment setup verification passed!"
echo ""
echo "SUMMARY:"
echo "✅ Only example files (.env.docker.example, .env.testing.docker.example) are tracked in git"
echo "✅ Working environment files (.env, .env.testing) are generated and ignored"
echo "✅ APP_KEY is automatically generated for all environments"
echo "✅ Database configurations are correct:"
echo "   - Main: mysql/laravel_blog (for production containers)"
echo "   - Test: mysql_test/laravel_blog_test (for test containers)"
echo "✅ Environment setup is clean and automated"
echo ""
