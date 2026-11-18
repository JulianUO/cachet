# Production image for Cachet 3.x (Laravel).
# Pins PHP to a release that satisfies composer.json (^8.3.36 || ^8.4.27 || ^8.5.12).
ARG PHP_VERSION=8.4.27

# -----------------------------------------------------------------------------
# Composer dependencies (build tools stay out of the final image)
# -----------------------------------------------------------------------------
FROM php:${PHP_VERSION}-cli-bookworm AS vendor

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libpq-dev \
        libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --no-progress

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    && composer clear-cache

# -----------------------------------------------------------------------------
# Runtime: Apache + PHP
# -----------------------------------------------------------------------------
FROM php:${PHP_VERSION}-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libpq-dev \
        libsqlite3-dev \
        libldap2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap --with-libdir=lib/$(uname -m)-linux-gnu \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        ldap \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    # Keep only the shared libraries the compiled extensions link against;
    # drop compilers, headers and -dev packages (they were most of the scanner findings).
    && runtime="$(find /usr/local/lib/php/extensions -type f -name '*.so' -exec ldd '{}' ';' \
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) next; gsub("^/(usr/)?", "", so); print so }' \
        | sort -u | xargs -r dpkg-query --search | cut -d: -f1 | sort -u)" \
    && apt-mark manual $runtime >/dev/null \
    && apt-get purge -y --auto-remove \
        $PHPIZE_DEPS \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libpq-dev \
        libsqlite3-dev \
        libldap2-dev \
        libc6-dev \
        linux-libc-dev \
    && apt-get upgrade -y \
    && rm -rf /var/lib/apt/lists/*

# Production php.ini plus hardening:
# - session.upload_progress off: mitigation for CVE-2026-115560 until the next PHP release.
# - expose_php off: don't advertise the PHP version in X-Powered-By.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'expose_php = Off\nsession.upload_progress.enabled = 0\n' > "$PHP_INI_DIR/conf.d/zz-cachet-security.ini"

# Serve Laravel's public/ directory and allow .htaccess rewrites.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && a2enmod rewrite headers

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Publish frontend assets from Composer packages (no Node build required).
# A throwaway APP_KEY is enough for artisan during image build.
RUN cp .env.example .env \
    && php artisan key:generate --force --no-interaction \
    && php artisan package:discover --ansi \
    && php artisan vendor:publish --tag=laravel-assets --ansi --force \
    && php artisan vendor:publish --tag=cachet-assets --ansi --force \
    && php artisan filament:assets --ansi \
    && rm -f .env \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
