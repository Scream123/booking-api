FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    autoconf \
    gcc \
    make \
    libzip-dev \
    zip \
    unzip \
    git && \
    docker-php-ext-install pdo_mysql bcmath zip pcntl sockets && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN curl -sS https://getcomposer.org | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./_docker/app/php.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /var/www

COPY --chown=www-data:www-data . /var/www

CMD ["php-fpm"]
