FROM php:8.2-apache

# Install the mysqli PHP extension so the app can connect to MySQL
RUN docker-php-ext-install mysqli

# Copy all your project files into Apache's web root inside the container
COPY . /var/www/html/

# Set correct file permissions for Apache
RUN chown -R www-data:www-data /var/www/html