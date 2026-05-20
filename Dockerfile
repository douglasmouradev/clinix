FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p storage/logs storage/cache storage/uploads \
    && chown -R www-data:www-data storage public/uploads

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY docker/apache.conf /etc/apache2/conf-available/clinix.conf
RUN a2enconf clinix

EXPOSE 80
