-- MySQL initialization script to grant necessary permissions for parallel testing

-- Grant full database privileges to laravel_user for parallel testing
-- This allows the user to create/drop databases needed for parallel test execution
GRANT ALL PRIVILEGES ON `laravel_blog%`.* TO 'laravel_user'@'%';
GRANT CREATE, DROP ON *.* TO 'laravel_user'@'%';

-- Flush privileges to ensure changes take effect
FLUSH PRIVILEGES;

-- Create test database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `laravel_blog_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
