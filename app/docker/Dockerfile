FROM php:apache

# Installeer benodigde PHP extensies en werk bij
RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
       libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set document root to public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|<Directory /var/www/>|<Directory /var/www/html/public/>|' /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
EXPOSE 81
EXPOSE 3306