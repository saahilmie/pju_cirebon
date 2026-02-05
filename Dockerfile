FROM php:8.3-cli

# Force rebuild: 2026-02-04T16:10
# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build assets
RUN npm install && npm run build

# Create storage directories with proper permissions
RUN mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

# Create start script - use server.php router for static files
RUN echo '#!/bin/bash\n\
    php artisan config:clear\n\
    php artisan cache:clear\n\
    php artisan view:clear\n\
    php artisan storage:link\n\
    php artisan migrate --force\n\
    php artisan db:seed --force 2>/dev/null || true\n\
    php -S 0.0.0.0:${PORT:-8080} server.php' > /app/start.sh \
    && chmod +x /app/start.sh

# Start command
CMD ["/app/start.sh"]
