FROM php:8.2-apache

# Enable mysqli extension
RUN docker-php-ext-install mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set permissions for uploads folder
RUN mkdir -p /var/www/html/uploads && \
    chmod 777 /var/www/html/uploads

EXPOSE 80