# P1-06: kunci 8.2 sesuai composer.json (php ^8.2) + progress.md. Jangan bump tanpa uji.
FROM php:8.2-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    bash \
    git \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libpq-dev \
    nginx \
    supervisor \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_pgsql pgsql \
        intl zip pcntl bcmath gd exif opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/90-evote.ini
COPY docker/supervisor/worker.conf /etc/supervisor/conf.d/worker.conf

COPY . /var/www/html
WORKDIR /var/www/html

RUN chown -R ${UID}:${GID} /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

USER ${UID}:${GID}

EXPOSE 9000

CMD ["php-fpm"]