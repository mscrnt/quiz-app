# Use the official PHP image with FPM
FROM php:7.4-fpm

# Install necessary extensions
RUN docker-php-ext-install mysqli

# Set the working directory
WORKDIR /var/www/html

# Copy all project files to the container
COPY . /var/www/html
