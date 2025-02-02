FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents with correct ownership
COPY --chown=www-data:www-data . /var/www

# Set correct permissions for the application directory
RUN find /var/www -type d -exec chmod 755 {} \;
RUN find /var/www -type f -exec chmod 644 {} \;

# Install Node dependencies and build assets
RUN npm install
RUN npm run build

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-scripts

# Change current user to www
USER www-data
