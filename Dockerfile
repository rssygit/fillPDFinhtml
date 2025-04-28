FROM php:8.2-apache

# Copy content from /public folder into the Apache folder
COPY ./public/ /var/www/html/

# Expose port 80
EXPOSE 80
