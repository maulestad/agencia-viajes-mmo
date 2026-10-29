# Imagen única para Docker local y Render
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod headers \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY . /var/www/html/

RUN sed -i 's/\r$//' /var/www/html/docker/entrypoint.sh \
    && chmod +x /var/www/html/docker/entrypoint.sh \
    && mkdir -p /var/www/html/storage/backups /var/www/html/storage/logs /var/www/html/storage/sessions \
    && chown -R www-data:www-data /var/www/html/storage

ENV APP_ENV=production \
    APP_DEBUG=0

EXPOSE 80
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
