FROM php:8.2-apache

# Copy all files into the Apache public folder
COPY ./ /var/www/html/

# Expose port 80
EXPOSE 80
