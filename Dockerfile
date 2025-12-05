# Use official PHP image
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

# Create necessary directories
RUN mkdir -p storage/pdf storage/tmp logs && \
    chmod -R 777 storage logs

# Expose port (Render uses PORT env variable)
EXPOSE ${PORT:-8080}

# Start PHP server
CMD php -S 0.0.0.0:${PORT:-8080} -t public
