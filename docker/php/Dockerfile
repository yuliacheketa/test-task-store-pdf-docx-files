FROM php:8.2-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath sockets opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
