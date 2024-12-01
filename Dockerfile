FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    xml

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user
RUN useradd -G www-data,root -u 1000 -d /home/dev dev
RUN mkdir -p /home/dev/.composer && \
    chown -R dev:dev /home/dev

# Set proper permissions
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R dev:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy existing application directory
COPY --chown=dev:www-data . /var/www

# Switch to dev user
USER dev

# Install dependencies
RUN composer install

# Generate application key
RUN php artisan key:generate

# Change back to root for FPM
USER root

EXPOSE 9000
CMD ["php-fpm"]
