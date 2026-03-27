FROM php:8.2-apache

# Install dependencies for Laravel and MongoDB
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    libssl-dev \
    pkg-config

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install MongoDB extension (Versión 1.21 requerida por el composer.lock)
RUN pecl install mongodb-1.21.4 && docker-php-ext-enable mongodb

# Install mongodump from MongoDB Database Tools (necesario para backups)
RUN curl -O https://fastdl.mongodb.org/tools/db/mongodb-database-tools-debian11-x86_64-100.9.4.tgz && \
    tar -zxvf mongodb-database-tools-debian11-x86_64-100.9.4.tgz && \
    cp mongodb-database-tools-debian11-x86_64-100.9.4/bin/mongodump /usr/local/bin/ && \
    rm -rf mongodb-database-tools-*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy the application code
COPY . /var/www/html/

# Configurar Apache DocumentRoot
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Install project dependencies (without running post-scripts that miss the Render ENV)
RUN COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-scripts

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy start script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]
