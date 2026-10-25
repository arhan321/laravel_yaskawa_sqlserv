# ============================================================
# RMS YASKAWA - PHP 8.5 + SQL SERVER + POSTGRESQL + NODE.JS 22
# Laravel | PHP-FPM | Docker | WSL Ubuntu | Reverb | Vite
# Original PHP extensions retained. Dependency installation is part of docker build only.
# Node/npm installed once when the Docker image is built.
# ============================================================

# Official Node runtime: use Node 22 (compatible with Vite 8).
# Multi-stage COPY avoids adding the NodeSource apt repository
# or installing Node through a Windows/WSL npm executable.
FROM node:22-bookworm-slim AS node_runtime

FROM php:8.5-fpm-bookworm

ENV COMPOSER_MEMORY_LIMIT=-1 \
    TZ=Asia/Jakarta

# ============================================================
# 1. SYSTEM DEPENDENCIES - ORIGINAL DEPENDENCIES PRESERVED
# ============================================================

RUN apt-get update && apt-get install -y --no-install-recommends \
    libmemcached-dev \
    libreadline-dev \
    libgmp-dev \
    libzip-dev \
    zlib1g-dev \
    libpq-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libfreetype6-dev \
    libssl-dev \
    libmagickwand-dev \
    libicu-dev \
    libxml2-dev \
    git \
    nano \
    curl \
    iputils-ping \
    ca-certificates \
    unzip \
    tzdata \
    && rm -rf /var/lib/apt/lists/*

# ============================================================
# 1B. NODE.JS 22 + NPM + NPX (NEW)
# Installed in php_yaskawa and available inside the container.
# The app uses a bind mount (./src:/var/www/html), therefore
# npm install / npm run build are run AFTER the Docker image build.
# ============================================================

COPY --from=node_runtime /usr/local/bin/node /usr/local/bin/node
COPY --from=node_runtime /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && node --version \
    && npm --version \
    && npx --version

# ============================================================
# 2. PHP EXTENSIONS - EXISTING DRIVERS AND REVERB SOCKETS
# ============================================================

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j2 \
        soap \
        exif \
        pcntl \
        intl \
        gmp \
        zip \
        pdo_pgsql \
        pgsql \
        bcmath \
        gd \
        sockets


# Microsoft ODBC 18 + SQLSRV / PDO_SQLSRV. Debian 12 matches bookworm above.
# Driver 5.13.x supports PHP 8.5; use a known version instead of unpinned PECL.
ARG SQLSRV_VERSION=5.13.3
RUN curl -fsSL https://packages.microsoft.com/config/debian/12/packages-microsoft-prod.deb \
        -o /tmp/packages-microsoft-prod.deb \
    && dpkg -i /tmp/packages-microsoft-prod.deb \
    && rm /tmp/packages-microsoft-prod.deb \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql18 unixodbc-dev libgssapi-krb5-2 \
    && pecl channel-update pecl.php.net \
    && pecl install sqlsrv-${SQLSRV_VERSION} \
    && pecl install pdo_sqlsrv-${SQLSRV_VERSION} \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && php --ri sqlsrv \
    && php --ri pdo_sqlsrv \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

# ============================================================
# 3. OPCACHE - ORIGINAL CONFIGURATION PRESERVED
# ============================================================

RUN php -m | grep -i "Zend OPcache"

# ============================================================
# 4. REDIS - PRESERVED
# ============================================================

RUN pecl install redis \
    && docker-php-ext-enable redis

# ============================================================
# 5. MONGODB - PRESERVED
# ============================================================

RUN yes '' | pecl install mongodb \
    && docker-php-ext-enable mongodb

# ============================================================
# 6. IMAGICK - PRESERVED
# ============================================================

RUN yes '' | pecl install imagick \
    && docker-php-ext-enable imagick

# ============================================================
# 7. XDEBUG - INSTALLED; ENABLE MANUALLY IF NEEDED
# ============================================================

RUN pecl install xdebug

# ============================================================
# 8. MEMCACHED - PRESERVED
# ============================================================

RUN yes '' | pecl install memcached \
    && docker-php-ext-enable memcached

# ============================================================
# 9. COMPOSER 2 - PRESERVED
# ============================================================

COPY --from=composer:2 \
    /usr/bin/composer \
    /usr/local/bin/composer

# ============================================================
# 11. PHP CONFIGURATION - PRESERVED
# ============================================================

COPY ./local.ini /usr/local/etc/php/conf.d/local.ini

# ============================================================
# 12. WORKING DIRECTORY - PRESERVED
# ============================================================

WORKDIR /var/www/html

# Entrypoint bawaan image resmi PHP; tidak memanggil file installer proyek.
ENTRYPOINT ["docker-php-entrypoint"]

# ============================================================
# 14. PHP-FPM - PRESERVED
# ============================================================

EXPOSE 9000

CMD ["php-fpm"]

