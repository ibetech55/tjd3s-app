# Use the official PHP image as a base image
FROM php:8.1-apache

# Install necessary PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Create the uploads directory and set permissions
RUN mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod -R 777 /var/www/html/uploads

# Copy the current directory contents into the container at /var/www/html
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80
