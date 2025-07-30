FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js and npm for commit tools
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
# Use www-data (which already exists) instead of creating a new user
RUN usermod -u 1000 www-data 2>/dev/null || echo "www-data UID already 1000"
RUN groupmod -g 1000 www-data 2>/dev/null || echo "www-data GID already 1000"
RUN usermod -d /var/www -s /bin/bash www-data

# Create nginx directories
RUN mkdir -p /var/log/nginx
RUN mkdir -p /var/lib/nginx

# Create supervisor directories
RUN mkdir -p /var/log/supervisor

# Copy nginx configuration
COPY nginx/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Copy supervisor configuration
COPY supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy startup scripts
COPY start-services.sh /usr/local/bin/start-services.sh
COPY start-main-app.sh /usr/local/bin/start-main-app.sh
COPY start-queue-worker.sh /usr/local/bin/start-queue-worker.sh
COPY fix-permissions.sh /usr/local/bin/fix-permissions.sh
RUN chmod +x /usr/local/bin/start-services.sh
RUN chmod +x /usr/local/bin/start-main-app.sh
RUN chmod +x /usr/local/bin/start-queue-worker.sh
RUN chmod +x /usr/local/bin/fix-permissions.sh

# Create necessary Laravel directories and set proper permissions
RUN mkdir -p /var/www/html/storage/app/public \
    && mkdir -p /var/www/html/storage/framework/cache/data \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/testing \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache

# Set proper ownership and permissions for Laravel directories
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html

# Expose port 80 and start services
EXPOSE 80
CMD ["/usr/local/bin/start-services.sh"]
