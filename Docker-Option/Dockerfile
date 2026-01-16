FROM php:8.4-apache

# Install extensions required by the app (PDO, MySQL)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (required for your .htaccess rules)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# (Optional) Update permissions so Apache can write to directories if needed
RUN chown -R www-data:www-data /var/www/html
