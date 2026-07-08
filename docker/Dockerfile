FROM php:8.4-fpm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install zip pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/nginx/vhost.conf /etc/nginx/sites-available/default
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/public_html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --optimize-autoloader --no-scripts

COPY public/ ./public/
COPY routes/ ./routes/
COPY templates/ ./templates/
COPY src/helpers.php ./

RUN mkdir -p /var/log/supervisor /var/lib/nginx /var/log/nginx /var/run /var/lib/php/sessions \
    && chown -R www-data:www-data /var/log/supervisor /var/lib/nginx /var/log/nginx /var/run /var/lib/php/sessions /var/www/public_html /tmp

RUN rm -f /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
RUN chown www-data:www-data /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /tmp/client_body /tmp/fastcgi_temp /tmp/proxy_temp \
    && chown -R www-data:www-data /tmp/client_body /tmp/fastcgi_temp /tmp/proxy_temp
USER www-data
EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]